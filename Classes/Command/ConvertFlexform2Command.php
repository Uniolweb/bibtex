<?php

declare(strict_types=1);
namespace Uniolweb\Bibtex\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Set filterType to 'allow' if filterEntries is not empty. This was not handled correctly in previous
 * ConvertFlexFormCommand.
 */
class ConvertFlexform2Command extends Command
{
    protected const NAME = 'bibtex:convertFlexform2';
    protected const PLUGIN_SIGNATURE = 'bibtex_bibtex';

    /**
     * @var string
     */
    protected const XML_DECLARATION = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>';

    protected bool $dryRun;

    /**
     * @var string
     */
    protected $outputDir;

    protected InputInterface $input;
    protected OutputInterface $output;
    protected SymfonyStyle $io;

    protected ?FlexFormTools $flexformTools = null;
    protected ?FlexFormService $flexformService = null;

    public function __construct()
    {
        parent::__construct(self::NAME);
        $this->outputDir = Environment::getVarPath() . '/log/bibtex/' . self::PLUGIN_SIGNATURE . '_' . str_replace(':', '_', self::NAME);

        if (!file_exists($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }

        $this->flexformTools = GeneralUtility::makeInstance(FlexFormTools::class);
        $this->flexformService = GeneralUtility::makeInstance(FlexFormService::class);
    }

    protected function configure(): void
    {
        $this->setDescription('Set filterType to <allow> if filterEntries is not empty. ');
        $this->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Dry run: do not change');
        $this->addOption(
            'uid',
            'u',
            InputOption::VALUE_REQUIRED,
            'Only this uid'
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

        $this->dryRun = $input->getOption('dry-run');
        $uid = $input->getOption('uid');

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')->createQueryBuilder();
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder->select('uid', 'pid', 'pi_flexform', 'header', 'list_type', 'CType')
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
            $uid = (int)$row['uid'];
            $pid = (int)$row['pid'];
            $strFlexform = $row['pi_flexform'] ?? '';
            if (!$strFlexform) {
                $this->io->writeln(sprintf(
                    'Fehler: %s [%d] auf Seite [%d]: enthält kein Flexform',
                    $row['header'],
                    $uid,
                    $pid
                ));
                continue;
            }

            $xml = $row['pi_flexform'] ?? '';
            $xmlArray = $this->xml2Array($xml);

            // check
            if (!$this->isXmlArrayValid($xmlArray)) {
                $this->io->error(sprintf(
                    'Fehler: %s [%d] auf Seite [%d]: fehlerhaftes Flexform',
                    $row['header'],
                    $uid,
                    $pid
                ));
                continue;
            }
            if (!$this->needsChanges($xmlArray)) {
                $this->io->writeln(sprintf(
                    'Needs no change: %s [%d] auf Seite [%d]',
                    $row['header'],
                    $uid,
                    $pid
                ));
                continue;
            }

            // migrate values
            $xmlArray = $this->migrateXmlArray($xmlArray);
            $newXml = $this->arrayToXml($xmlArray);
            if ($newXml === $xml) {
                $this->io->writeln(sprintf(
                    'Nothing to change, skipping ...: %s [%d] auf Seite [%d]',
                    $row['header'],
                    $uid,
                    $pid
                ));
                continue;
            }

            // save files
            $this->saveXmlToFile($xml, $uid . '-before.xml');
            $this->saveXmlToFile($newXml, $uid . '-after.xml');

            $this->io->section('Before uid=' . $uid . ' pid=' . $pid);
            $this->io->writeln($xml);
            $this->io->section('After uid=' . $uid);
            $this->io->writeln($newXml);

            // save to database

            if (!$this->dryRun) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')->createQueryBuilder();
                $queryBuilder->update('tt_content')
                    ->where(
                        $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
                    )
                    ->set('pi_flexform', $newXml)
                    ->executeStatement();
                $this->io->writeln("Done: Updated uid=$uid");

                $this->writeCleanFlexform($uid);
                $this->io->section('After cleanup uid=' . $uid);
                $this->io->writeln($newXml);
                $this->io->writeln("Done: Cleanup up Flexform for uid=$uid");
            } else {
                $this->io->writeln("Dry run, did not update uid=$uid");
            }

            $count++;
        }
        $this->io->writeln('Number converted=' . $count);
        return 0;
    }

    protected function writeCleanFlexform(int $uid): string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')->createQueryBuilder();
        $queryBuilder
            ->getRestrictions()
            ->removeAll();
        $row = $queryBuilder->select('uid', 'pid', 'pi_flexform', 'header', 'list_type', 'CType')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('ctype', $queryBuilder->createNamedParameter('list')),
                $queryBuilder->expr()->eq(
                    'list_type',
                    $queryBuilder->createNamedParameter(self::PLUGIN_SIGNATURE)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        $xml = $row['pi_flexform'];
        $newXml = $this->cleanupXml($row);
        if ($xml !== $newXml) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')->createQueryBuilder();
            $queryBuilder->update('tt_content')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
                )
                ->set('pi_flexform', $newXml)
                ->executeStatement();
            $this->saveXmlToFile($newXml, $uid . '-after+cleaned.xml');
        }
        return $newXml;
    }

    protected function isXmlArrayValid(array $xmlArray): bool
    {
        if (!($xmlArray['data']['sDEF']['lDEF'] ?? false)) {
            return false;
        }

        return true;
    }

    protected function saveXmlToFile($xml, $filename)
    {
        $path = $this->outputDir . '/' . $filename;
        if (!file_exists($path)) {
            file_put_contents($path, $xml);
        }
    }

    protected function needsChanges(array $xmlArray): bool
    {
        $filterType = $xmlArray['data']['sDEF']['lDEF']['settings.filterType']['vDEF'] ?? '';
        $filterEntries = $xmlArray['data']['sDEF']['lDEF']['settings.filterEntries']['vDEF'] ?? '';
        if ($filterEntries && (!$filterType || $filterType === 'none')) {
            // needs changes: filterType should be set
            return true;
        }
        return false;
    }

    protected function migrateXmlArray(array $xmlArray): array
    {
        if (!isset($xmlArray['data']['sDEF']['lDEF'])) {
            return $xmlArray;
        }
        $arrayReference = &$xmlArray['data']['sDEF']['lDEF'];
        $filterType = $arrayReference['settings.filterType']['vDEF'] ?? '';
        $filterEntries = $arrayReference['settings.filterEntries']['vDEF'] ?? '';
        if (!($filterEntries && (!$filterType || $filterType === 'none'))) {
            // needs changes: filterType should be set
            return $xmlArray;
        }
        // set filterType to allow because it is most likely it was allow
        $arrayReference['settings.filterType']['vDEF'] = 'allow';

        return $xmlArray;
    }

    /**
     * !!! WARNING: if cleanFlexFormXML is used, the old fields which are not in the new flexform schema disappear
     * So only do this AFTER migration!
     * @param array $row
     * @return string
     */
    protected function cleanupXml(array $row): string
    {
        $xml = $this->flexformTools->cleanFlexFormXML('tt_content', 'pi_flexform', $row);
        $xmlArray = $this->flexformTools->cleanFlexFormXML;
        return $this->flexformTools->flexArray2Xml($xmlArray, true);
    }

    protected function arrayToXml(array $values): string
    {
        // convert back to XML
        // this does not add (xml declaration)
        // The XML declaration is a processing instruction that identifies the document as being XML. All XML documents should begin with an XML declaration.
        //$xml = $this->flexformTools->flexArray2Xml($xmlArray);

        // this has the problem that "settings.xyz" are converted into "settingsxyz"
        // additionally, type="array" is added
        //$xml = \TYPO3\CMS\Core\Utility\GeneralUtility::array2xml($xmlArray, '', $level = 0, 'T3FlexForms');

        return $this->flexformTools->flexArray2Xml($values, true);
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
