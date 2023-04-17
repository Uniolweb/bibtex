<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Bibtex2Html\Service;

use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Uniolit\Bibtex\Configuration\BibtexSettings;

class FetchContent
{
    /**
     * @var RequestFactory|null
     */
    protected ?RequestFactory $requestFactory = null;

    /**
     * @param RequestFactory|null $requestFactory
     */
    public function __construct(RequestFactory $requestFactory = null)
    {
        $this->requestFactory = $requestFactory ?: GeneralUtility::makeInstance(RequestFactory::class);
    }

    /**
     * @todo return data and metadata in array
     */
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
            $message = 'Bibtex file does not exist or is empty:' . $url;
            return new FetchContentResult(
                FetchContentResult::RESULT_CODE_ERROR_GENERIC,
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
