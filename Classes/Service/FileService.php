<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Service;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class FileService
{
    protected ?FileRepository $fileRepository = null;
    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    public function __construct(FileRepository $fileRepository = null, ResourceFactory $resourceFactory = null)
    {
        if (!$fileRepository) {
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        }
        $this->fileRepository = $fileRepository;
        if (!$resourceFactory) {
            $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        }
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * @param string $table
     * @param string $field
     * @param int $uid
     * @return array
     */
    public function getFileObjectsByRelations(string $table, string $field, int $uid): array
    {
        if ($this->getEnvironmentMode() === 'FE') {
            return $this->fileRepository->findByRelation($table, $field, $uid);
        }
        // workaround
        return $this->findByRelation($table, $field, $uid);
    }

    /**
     * workaround because FileRepository::findByRelation does not work in CLI mode!
     * https://forge.typo3.org/issues/61344
     *
     *
     * @param string $tableName
     * @param string $fieldName
     * @param int $uid
     * @return array
     *
     * @todo Remove this if https://forge.typo3.org/issues/61344 https://forge.typo3.org/issues/99442 is fixed (or
     * FileRepository::findByRelation() works in CLI mode
     */
    public function findByRelation(string $tableName, string $fieldName, int $uid): array
    {
        $referenceUids = [];
        $itemList = [];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');

        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        $res = $queryBuilder
            ->select('uid')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter($tableName, Connection::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter($fieldName, Connection::PARAM_STR)
                )
            )
            ->orderBy('sorting_foreign')
            ->executeQuery();

        while ($row = $res->fetchAssociative()) {
            $referenceUids[] = $row['uid'];
        }

        if (!empty($referenceUids)) {
            foreach ($referenceUids as $referenceUid) {
                try {
                    // Just passing the reference uid, the factory is doing workspace
                    // overlays automatically depending on the current environment
                    $itemList[] = $this->resourceFactory->getFileReferenceObject($referenceUid);
                } catch (ResourceDoesNotExistException) {
                    // No handling, just omit the invalid reference uid
                }
            }
            //$itemList = $this->reapplySorting($itemList);
        }

        return $itemList;
    }

    /**
     * Function to return the current application type based on $GLOBALS['TSFE'].
     * This function can be mocked in unit tests to be able to test frontend behaviour.
     *
     * @return string
     *
     * @see \TYPO3\CMS\Core\Resource\AbstractRepository::getEnvironmentMode()
     *
     * @todo handle this differently ?
     */
    protected function getEnvironmentMode()
    {
        return ($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController ? 'FE' : 'BE';
    }
}
