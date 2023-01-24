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
use Uniolit\Bibtex\Configuration\BibtexSettings;
use Uniolit\Bibtex\Service\FileService;

class DumpFlexformInfoCommand extends Command
{
    protected const NAME = 'bibtex:dumpFlexform';
    protected const PLUGIN_SIGNATURE = 'bibtex_bibtex';
    protected const DEFAULT_OUTPUT_FIELDS = ['url'];

    protected string $language = 'de';

    protected InputInterface $input;
    protected OutputInterface $output;
    protected SymfonyStyle $io;

    // todo move the flexform functionality into a service
    protected ?FlexFormTools $flexformTools = null;
    protected ?FlexFormService $flexformService = null;
    protected ?FileService $fileService = null;
    protected ?SiteFinder $siteFinder = null;

    public function __construct()
    {
        parent::__construct(self::NAME);

        $this->flexformTools = GeneralUtility::makeInstance(FlexFormTools::class);
        $this->flexformService = GeneralUtility::makeInstance(FlexFormService::class);
        $this->fileService = GeneralUtility::makeInstance(FileService::class);
        $this->siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
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
            'fields',
            'f',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'What to output',
            self::DEFAULT_OUTPUT_FIELDS
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

        //$this->fields = $input->getOption('output');
        $uid = $input->getOption('uid');

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')->createQueryBuilder();
        /*$queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        */
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
                if ($output->isVerbose()) {
                    $this->io->warning(sprintf(
                        'Fehler: %s [%d] auf Seite [%d]: enthält kein Flexform',
                        $row['header'],
                        $uid,
                        $pid
                    ));
                }
                continue;
            }

            $xml = $row['pi_flexform'] ?? '';
            $xmlArray = $this->flexformXml2Settings($xml);
            $settings = $xmlArray['settings'] ?? [];
            if (!$settings) {
                if ($output->isVerbose()) {
                    $this->io->warning(sprintf(
                        'Fehler: %s [%d] auf Seite [%d]: enthält kein Flexform mit settings',
                        $row['header'],
                        $uid,
                        $pid
                    ));
                }
                continue;
            }

            $style = 'uniol_' . ($this->language ?? 'de');

            //var_dump($settings);

            $bibtexSettings = BibtexSettings::initializeWithSettings($settings, $style);
            $fileType = $bibtexSettings->getFileType();
            switch ($fileType) {
                case 'url':
                    if ($output->isVerbose()) {
                        $this->io->writeln(sprintf(
                            '%s [%d] auf Seite [%d]: fileType=%s, url=%s',
                            $row['header'],
                            $uid,
                            $pid,
                            $fileType,
                            $bibtexSettings->getUrl()
                        ));
                    } else {
                        $this->io->writeln($bibtexSettings->getUrl());
                    }
                    break;
                case 'file':
                    $fileUrl = $bibtexSettings->getFileUrl();
                    if ($fileUrl) {
                        $site = $this->siteFinder->getSiteByPageId($pid);
                        $baseUrl = $site->getBase();
                        if ($output->isVerbose()) {
                            $this->io->writeln(sprintf(
                                '%s [%d] auf Seite [%d]: fileType=%s, url=%s/%s',
                                $row['header'],
                                $uid,
                                $pid,
                                $fileType,
                                $baseUrl,
                                $bibtexSettings->getFileUrl()
                            ));
                        } else {
                            $this->io->writeln($baseUrl . '/' . $bibtexSettings->getFileUrl());
                        }
                    } else {
                        if ($output->isVerbose()) {
                            $this->io->warning(sprintf(
                                '%s [%d] auf Seite [%d]: fileType=%s, no file URL',
                                $row['header'],
                                $uid,
                                $pid,
                                $fileType
                            ));
                        }
                    }
                    break;

                default:
                    if ($output->isVerbose()) {
                        $this->io->warning(sprintf(
                            '%s [%d] auf Seite [%d]: Invalid fileType=%s',
                            $row['header'],
                            $uid,
                            $pid,
                            $fileType
                        ));
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
