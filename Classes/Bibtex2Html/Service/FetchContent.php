<?php

declare(strict_types=1);
namespace Uniolweb\Bibtex\Bibtex2Html\Service;

use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\File;
use Uniolweb\Bibtex\Cache\BibtexCache;
use Uniolweb\Bibtex\Configuration\BibtexSettings;

class FetchContent
{
    public function __construct(protected RequestFactory $requestFactory, protected BibtexCache $bibtexCache, protected Bibtex2HtmlService $bibtex2HtmlService)
    {
    }

    /**
     * @todo possibly add option $fetchFromCache to make it possible to fetch from cache, but we don't do this
     * right now, we want to always get fresh results.
     */
    public function fetchEntries(BibtexSettings $bibtexSettings, FetchContentResult $contentResult, int $language): FetchContentResult
    {
        if ($contentResult->isOk()) {
            $result = $this->bibtex2HtmlService->bibtex2Html(
                $bibtexSettings,
                $contentResult->getData(),
                $language
            );
            $entries = $result['entries'] ?? [];
            $contentResult->setTotalNumberOfEntries((int)($result['countEntries']['total'] ?? 0));
            $contentResult->setParsedEntries($entries);
            $contentResult->setParseErrors($result['parseErrors'] ?? []);
        }

        return $contentResult;
    }

    public function writeToCache(BibtexSettings $bibtexSettings, FetchContentResult $contentResult)
    {
        // currently caches only metadata
        $this->bibtexCache->set($bibtexSettings->getUnifiedUrl(), $contentResult->getMetaData());
    }

    public function fetchContent(BibtexSettings $bibtexSettings): FetchContentResult
    {
        $fileType = $bibtexSettings->getFileType();

        if (!$fileType) {
            return new FetchContentResult(
                FetchContentResult::RESULT_CODE_ERROR_INVALID_CONFIGURATION_WRONG_FILE_TYPE,
                'Invalid configuration: wrong file type',
                ''
            );
        }
        switch ($fileType) {
            case 'url':
                $url = $bibtexSettings->getUrl();
                if (!$url) {
                    return new FetchContentResult(
                        FetchContentResult::RESULT_CODE_ERROR_INVALID_CONFIGURATION_WRONG_URL,
                        'Invalid configuration: missing URL or invalid URL',
                        ''
                    );
                }
                return $this->fetchContentByUrl($url);

            case 'file':
                $file = $bibtexSettings->getFile();
                if (!$file) {
                    return new FetchContentResult(
                        FetchContentResult::RESULT_CODE_ERROR_FILE_MISSING,
                        'Invalid configuration: Missing file',
                        ''
                    );
                }
                return $this->fetchContentByFile($file);
        }

        return new FetchContentResult(
            FetchContentResult::RESULT_CODE_ERROR_INVALID_CONFIGURATION_WRONG_FILE_TYPE,
            'Invalid configuration: wrong file type',
            ''
        );
    }

    /**
     * @todo: use Guzzle directly, better error handling
     */
    public function fetchContentByUrl(string $url): FetchContentResult
    {
        try {
            $response = $this->requestFactory->request($url);
            $contents = trim((string)$response->getBody()->getContents());
            if ($contents !== '') {
                return new FetchContentResult(0, '', $contents);
            }
            return new FetchContentResult(
                FetchContentResult::RESULT_CODE_ERROR_CONTENT_EMPTY,
                '',
                $contents
            );
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return new FetchContentResult(
                FetchContentResult::RESULT_CODE_ERROR_URL_NOT_AVAILABLE,
                $message
            );
        }
    }

    public function fetchContentByFile(?File $file): FetchContentResult
    {
        if (!$file) {
            return new FetchContentResult(
                FetchContentResult::RESULT_CODE_ERROR_FILE_MISSING,
                'File missing',
                ''
            );
        }
        $contents = trim((string)$file->getStorage()->getFileContents($file));
        if ($contents !== '') {
            return new FetchContentResult(0, '', $contents);
        }
        return new FetchContentResult(
            FetchContentResult::RESULT_CODE_ERROR_CONTENT_EMPTY,
            '',
            $contents
        );
    }
}
