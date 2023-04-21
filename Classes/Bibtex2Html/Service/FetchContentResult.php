<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Bibtex2Html\Service;

/**
 * @todo possibly split metadata into separate class FetchContentResultMetadata
 */
class FetchContentResult
{
    public const RESULT_CODE_OK = 0;
    // todo: add individual error codes
    public const RESULT_CODE_ERROR_GENERIC = 1;
    public const RESULT_CODE_ERROR_CONTENT_EMPTY = 2;
    public const RESULT_CODE_ERROR_FILE_MISSING = 3;
    public const RESULT_CODE_ERROR_INVALID_CONFIGURATION_WRONG_FILE_TYPE = 4;
    // missing URL or invalid URL (e.g. GeneralUtility::isValidUrl returns false
    // todo: use 2 separate error codes for these
    public const RESULT_CODE_ERROR_INVALID_CONFIGURATION_WRONG_URL = 5;
    // e.g. 404, could not resolve hostname etc.
    public const RESULT_CODE_ERROR_URL_NOT_AVAILABLE = 6;

    // parse errors
    // ------------
    public const RESULT_CODE_PARSE_INVALID_AUTHOR_FORMAT = 1;
    public const RESULT_CODE_PARSE_MISSING_YEAR = 2;

    protected int $timestamp;
    protected int $resultCode;

    /** @var string This can be an additional exception message. It is not always set. */
    protected string $errorMessage = '';

    /**
     * @var string raw bibtex string
     * @todo rename this to rawBibtexString
     */
    protected string $rawBibtexString;
    protected int $totalNumberOfEntries = 0;
    /** @var array<int,array> */
    protected array $parseErrors;

    protected array $parsedEntries = [];

    public function __construct(
        int $resultCode,
        string $errorMessage = '',
        string $data = '',
        array $parseErrors = [],
        int $timestamp = 0,
        int $totalNumberOfEntries = -1,
        array $parsedEntries = []
    ) {
        $this->initialize(
            $resultCode,
            $errorMessage,
            $data,
            $parseErrors,
            $timestamp,
            $totalNumberOfEntries,
            $parsedEntries,
        );
    }

    protected function initialize(
        int $resultCode,
        string $errorMessage = '',
        string $rawBibtexString = '',
        array $parseErrors = [],
        int $timestamp = 0,
        int $totalNumberOfEntries = -1,
        array $parsedEntries = []
    ) {
        if ($timestamp === 0) {
            $timestamp = time();
        }
        $this->timestamp = $timestamp;
        $this->resultCode = $resultCode;
        $this->errorMessage = $errorMessage;
        $this->rawBibtexString = $rawBibtexString;
        $this->parseErrors = $parseErrors;
        if ($totalNumberOfEntries === -1) {
            $totalNumberOfEntries = 0;
        }
        $this->totalNumberOfEntries = $totalNumberOfEntries;
        $this->parsedEntries = $parsedEntries;
    }

    /**
     * @todo Is currently not used, we do not write FetchContentResult to cache, only the metadata
     */
    public function __unserialize(array $data)
    {
        $this->initialize(
            $data['errorCode'],
            $data['errorMessage'],
            // todo: change key to 'rawBibtexString'
            $data['data'] ?? '',
            $data['parseErrors'],
            (int)$data['timestamp'],
            (int)($data['totalNumberOfEntries'] ?? 0),
            $data['entries'] ?? []
        );
    }

    /**
     * @todo Is currently not used, we do not write FetchContentResult to cache, only the metadata
     */
    public function __serialize(): array
    {
        return [
            'timestamp' => $this->timestamp,
            'data' => $this->rawBibtexString,
            'entries' => $this->parsedEntries,
            'parseErrors' => $this->parseErrors,
            'totalNumberOfEntries' => $this->totalNumberOfEntries,
            'errorCode' => $this->resultCode,
            'errorMessage' => $this->errorMessage,
        ];
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * @param string $errorMessage
     */
    public function setErrorMessage(string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    public function isOk(): bool
    {
        return $this->resultCode === 0;
    }

    /**
     * @return int
     */
    public function getResultCode(): int
    {
        return $this->resultCode;
    }

    public function getData(): string
    {
        return $this->rawBibtexString;
    }

    public function emptyData(): void
    {
        $this->rawBibtexString = '';
        $this->parsedEntries = [];
    }

    /**
     * @return array
     */
    public function getParseErrors(): array
    {
        return $this->parseErrors;
    }

    /**
     * @param array $parseErrors
     */
    public function setParseErrors(array $parseErrors): void
    {
        $this->parseErrors = $parseErrors;
    }

    public function setTotalNumberOfEntries(int $count): void
    {
        $this->totalNumberOfEntries = $count;
    }

    public function getTotalNumberOfEntries(): int
    {
        return $this->totalNumberOfEntries;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @return array
     */
    public function getParsedEntries(): array
    {
        return $this->parsedEntries;
    }

    /**
     * @param array $parsedEntries
     */
    public function setParsedEntries(array $parsedEntries): void
    {
        $this->parsedEntries = $parsedEntries;
    }

    /**
     * Get metadata part of result
     *
     * @return FetchContentResult
     */
    public function getMetaData(): FetchContentResult
    {
        $clone = clone $this;
        $clone->emptyData();
        return $clone;
    }

    public function getLanguageLabelFromResult(): string
    {
        return 'error.message.fetch.' . $this->resultCode;
    }
}
