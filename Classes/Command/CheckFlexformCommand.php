<?php

declare(strict_types=1);
namespace Uniolweb\Bibtex\Command;

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
use Uniolweb\Bibtex\Bibtex2Html\Service\Bibtex2HtmlService;
use Uniolweb\Bibtex\Bibtex2Html\Service\FetchContent;
use Uniolweb\Bibtex\Bibtex2Html\Service\FetchContentResult;
use Uniolweb\Bibtex\Configuration\BibtexSettings;
use Uniolweb\Bibtex\Service\FileService;

/**
 * Make some basic checks for the Flexform in bibtex plugins
 *
 * @todo unify procedure to check with the procedure used in PageLayoutView::setMessage, use common functionality.
 */
class CheckFlexformCommand extends Command
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
    protected FetchContent $fetchContent;

    public function __construct()
    {
        parent::__construct(self::NAME);

        $this->fetchContent = GeneralUtility::makeInstance(FetchContent::class);
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
        $this->addOption(
            'breakonerror',
            'b',
            InputOption::VALUE_NONE,
            'Break on first error for each bibtex file. If this option is on, all errors will be displayed, if not only one error per file.'
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
        $breakOnError = $input->getOption('breakonerror');

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
        $statement = $queryBuilder->executeQuery();

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
                if ($breakOnError) {
                    continue;
                }
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
                if ($breakOnError) {
                    continue;
                }
            }

            $style = 'uniol_' . ($this->language ?? 'de');

            //var_dump($settings);

            // todo: move this to a separate class, collect all errors in an array and return
            $bibtexSettings = BibtexSettings::initializeWithSettings($settings, $style, true);
            $fileType = $bibtexSettings->getFileType();

            $url = '';
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
                        if ($breakOnError) {
                            continue 2;
                        }
                    }
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
                        if ($breakOnError) {
                            continue 2;
                        }
                    }
                    $site = $this->siteFinder->getSiteByPageId($pid);
                    $baseUrl = $site->getBase();
                    $url = $baseUrl . '/' . $fileUrl;
                    break;

                default:
                    $this->io->warning(sprintf(
                        'Invalid fileType: %s [%d] auf Seite [%d], fileType=%s',
                        $row['header'],
                        $uid,
                        $pid,
                        $fileType
                    ));
                    if ($breakOnError) {
                        continue 2;
                    }
            }

            /** @var FetchContentResult $content */
            $content = $this->fetchContent->fetchContent($bibtexSettings);

            if (!$content->isOk()) {
                $this->io->warning(sprintf(
                    'Fehler beim Laden: header="%s" [%d] auf Seite [%d], url=%s, fileType=%s',
                    $row['header'],
                    $uid,
                    $pid,
                    $url,
                    $fileType,
                ));
                if ($breakOnError) {
                    continue;
                }
            }

            // parse bibtex
            try {
                $result = $this->bibtex2HtmlService->bibtex2Html($bibtexSettings, $content->getData());
                $entries = $result['entries'];
                if (!$entries) {
                    $this->io->warning(sprintf(
                        'No entries as result of parsing: header="%s" [%d] auf Seite [%d], url=%s, fileType=%s',
                        $row['header'],
                        $uid,
                        $pid,
                        $url,
                        $fileType,
                    ));
                    if ($breakOnError) {
                        continue;
                    }
                }

                // todo: check if all required fields for type exist
                foreach ($entries as $entry) {
                    // consistency check for author
                    $resultField = $entry['entry'] ?? '';
                    if (preg_match('/^([A-Z]\. ){4,}/', (string)$resultField)) {
                        $this->io->warning(sprintf(
                            'Result with several initials, probably author has wrong format: header="%s" [%d] auf Seite [%d], url=%s, fileType=%s, compiled entry="%s"',
                            $row['header'],
                            $uid,
                            $pid,
                            $url,
                            $fileType,
                            $resultField
                        ));
                        $isError = true;
                        if ($breakOnError) {
                            break;
                        }
                    }
                    if (!($entry['year'] ?? false)) {
                        $this->io->warning(sprintf(
                            'Result missing field year: header="%s" [%d] on page [%d], url=%s, fileType=%s compiled entry="%s"',
                            $row['header'],
                            $uid,
                            $pid,
                            $url,
                            $fileType,
                            $resultField
                        ));
                        $isError = true;
                        if ($breakOnError) {
                            break;
                        }
                    }

                    // todo check if bibtex entry is there
                    $bibtex = $entry['bibtex'];

                    // check URL:
                    $url = $entry['origEntry']['url'] ?? '';
                    if ($url) {
                        // check URL (does not work reliably)
                        /*
                        if (!$this->bibtex2HtmlService->checkByUrl($url)) {
                            $this->io->warning(sprintf(
                                'Result has "url" field with URL which is unavailable: header="%s" [%d] on page [%d], url=%s, fileType=%s url="%s"',
                                $row['header'],
                                $uid,
                                $pid,
                                $url,
                                $fileType,
                                $url
                            ));
                            if ($breakOnError) {
                                break;
                            }
                        }
                        */
                    }
                }
                if ($isError && $breakOnError) {
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
                if ($breakOnError) {
                    continue;
                }
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
