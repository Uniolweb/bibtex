<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Uniolit\Bibtex\Bibtex2Html\Service\Bibtex2HtmlService;
use Uniolit\Bibtex\Configuration\BibtexSettings;
use Uniolit\Bibtex\Service\FileService;

/**
 * Make some basic checks for the Flexform in bibtex plugins
 */
class checkFlexformCommand extends Command
{
    protected const NAME = 'bibtex:checkFlexform';
    protected const PLUGIN_SIGNATURE = 'bibtex_bibtex';

    protected string $language = 'de';

    protected InputInterface $input;
    protected OutputInterface $output;
    protected SymfonyStyle $io;

    // todo move the flexform functionality into a service
    protected ?FlexFormTools $flexformTools = null;
    protected ?FlexFormService $flexformService = null;
    protected ?FileService $fileService = null;
    protected ?SiteFinder $siteFinder = null;
    protected ?Bibtex2HtmlService $bibtex2HtmlService = null;

    public function __construct()
    {
        parent::__construct(self::NAME);

        $this->flexformTools = GeneralUtility::makeInstance(FlexFormTools::class);
        $this->flexformService = GeneralUtility::makeInstance(FlexFormService::class);
        $this->fileService = GeneralUtility::makeInstance(FileService::class);
        $this->siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $this->bibtex2HtmlService = GeneralUtility::makeInstance(Bibtex2HtmlService::class);
    }

    protected function configure()
    {
        $this->setDescription('Dump information from flexforms for all plugins');
        $this->addOption(
            'uid',
            'u',
            InputOption::VALUE_REQUIRED,
            'Only this uid'
        );
        $this->addOption(
            'showhidden',
            's',
            InputOption::VALUE_NONE,
            'Show hidden content elements'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);

        $uid = $input->getOption('uid');
        $showHidden = $input->getOption('showhidden');

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')->createQueryBuilder();
        if ($showHidden) {
            $queryBuilder
                ->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        }
        // todo: do a join with pages to exclude hidden pages
        $queryBuilder->select('uid', 'pid', 'pi_flexform', 'header')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('ctype', $queryBuilder->createNamedParameter('list')),
                $queryBuilder->expr()->eq(
                    'list_type',
                    $queryBuilder->createNamedParameter(self::PLUGIN_SIGNATURE)
                )
            );
        if ($uid) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            );
        }
        $statement = $queryBuilder->execute();

        $count = 0;
        while ($row = $statement->fetchAssociative()) {
            $isError = false;
            $uid = (int)$row['uid'];
            $pid = (int)$row['pid'];
            if ($output->isVerbose()) {
                $this->io->section(sprintf(
                    '%s [%d] auf Seite [%d]',
                    $row['header'],
                    $uid,
                    $pid
                ));
            }

            $strFlexform = $row['pi_flexform'] ?? '';
            if (!$strFlexform) {
                $this->io->warning(sprintf(
                    'Fehler: %s [%d] auf Seite [%d]: enthält kein Flexform',
                    $row['header'],
                    $uid,
                    $pid
                ));
                continue;
            }

            $xml = $row['pi_flexform'] ?? '';
            $xmlArray = $this->flexformXml2Settings($xml);
            $settings = $xmlArray['settings'] ?? [];
            if (!$settings) {
                $this->io->warning(sprintf(
                    'Fehler: %s [%d] auf Seite [%d]: enthält kein Flexform mit settings',
                    $row['header'],
                    $uid,
                    $pid
                ));

                continue;
            }

            $style = 'uniol_' . ($this->language ?? 'de');

            //var_dump($settings);

            $bibtexSettings = BibtexSettings::initializeWithSettings($settings, $uid, $style);
            $fileType = $bibtexSettings->getFileType();
            $content = '';
            switch ($fileType) {
                case 'url':
                    $url = $bibtexSettings->getUrl();
                    if (!$url) {
                        $this->io->warning(sprintf(
                            '%s [%d] auf Seite [%d]: empty url',
                            $row['header'],
                            $uid,
                            $pid
                        ));
                        continue 2;
                    }
                    $content = $this->bibtex2HtmlService->fetchContentByUrl($url);
                    break;
                case 'file':
                    $fileUrl = $bibtexSettings->getFileUrl();
                    if (!$fileUrl) {
                        $this->io->warning(sprintf(
                            '%s [%d] auf Seite [%d]: fileType=%s, no file URL',
                            $row['header'],
                            $uid,
                            $pid,
                            $fileType
                        ));
                        continue 2;
                    }
                    $site = $this->siteFinder->getSiteByPageId($pid);
                    $baseUrl = $site->getBase();
                    $url = $baseUrl . '/' . $fileUrl;
                    $content = $this->bibtex2HtmlService->fetchContentByFileReference($bibtexSettings->getFileRef());
                    break;

                default:
                    $this->io->warning(sprintf(
                        'Invalid fileType: %s [%d] auf Seite [%d], fileType=%s',
                        $row['header'],
                        $uid,
                        $pid,
                        $fileType
                    ));
                    continue 2;
            }

            if (!$content) {
                $this->io->warning(sprintf(
                    'Kein Inhalt: header="%s" [%d] auf Seite [%d], url=%s, fileType=%s',
                    $row['header'],
                    $uid,
                    $pid,
                    $url,
                    $fileType,
                ));
                continue;
            }

            // parse bibtex
            try {
                $entries = $this->bibtex2HtmlService->bibtex2Html($bibtexSettings, 0);
                if (!$entries) {
                    $this->io->warning(sprintf(
                        'No entries as result of parsing: header="%s" [%d] auf Seite [%d], url=%s, fileType=%s',
                        $row['header'],
                        $uid,
                        $pid,
                        $url,
                        $fileType,
                    ));
                    continue;
                }

                // todo: check if all required fields for type exist
                foreach ($entries as $entry) {
                    // consistency check for author
                    $entryField = $entry['entry'];
                    if (preg_match('/^([A-Z]\. ){4,}/', $entryField)) {
                        $this->io->warning(sprintf(
                            'Result with several initials, probably author has wrong format: header="%s" [%d] auf Seite [%d], url=%s, fileType=%s, result="%s"',
                            $row['header'],
                            $uid,
                            $pid,
                            $url,
                            $fileType,
                            $entryField
                        ));
                        $isError = true;
                        break;
                    }
                }
                if ($isError) {
                    continue;
                }
            } catch (\Exception $e) {
                $this->io->warning(sprintf(
                    'Exception beim Parsen Bibtex Datei <%s>: header="%s" [%d] auf Seite [%d], url=%s, fileType=%s',
                    $e->getMessage(),
                    $row['header'],
                    $uid,
                    $pid,
                    $url,
                    $fileType,
                ));
                continue;
            }

            $count++;
        }
        if ($this->output->isVerbose()) {
            $this->io->writeln('Number of content elements with flexform=' . $count);
        }
        return 0;
    }

    protected function flexformXml2Settings(string $xml): array
    {
        return $this->flexformService->convertFlexFormContentToArray($xml);
    }

    protected function xml2Array(string $xml): array
    {
        $xmlArray = [];
        // cleanup FlexForm
        // !!! WARNING: if cleanFlexFormXML is used, the old fields which are not in the new flexform schema disappear
        //$xml = $this->flexformTools->cleanFlexFormXML('tt_content', 'pi_flexform', $row);
        //$xmlArray = $this->flexformTools->cleanFlexFormXML;

        $result = GeneralUtility::xml2array($xml);
        if ($result && is_array($result)) {
            $xmlArray = $result;
        }

        // !!! important: this will return an array like [ 'settings' => ['sort' ....]]
        // can not be used in combination wth flexArray2Xml or array2xml
        //$xml = $row['pi_flexform'] ?? '';
        //$xmlArray = $this->flexformService->convertFlexFormContentToArray($xml);
        return $xmlArray;
    }
}
