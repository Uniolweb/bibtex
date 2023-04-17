<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Bibtex2Html\Service;

class FetchContentResult
{
    public const RESULT_CODE_OK = 0;
    // todo: add individual error codes
    public const RESULT_CODE_ERROR_GENERIC = 1;
    public const RESULT_CODE_ERROR_CONTENT_EMPTY = 2;
    public const RESULT_CODE_ERROR_FILE_MISSING = 3;
    public const RESULT_CODE_ERROR_INVALID_CONFIGURATION_WRONG_FILE_TYPE = 4;
    // missing URL or invalid URL
    public const RESULT_CODE_ERROR_INVALID_CONFIGURATION_WRONG_URL = 5;

    // parse errors
    // ------------
    public const RESULT_CODE_PARSE_INVALID_AUTHOR_FORMAT = 1;
    public const RESULT_CODE_PARSE_MISSING_YEAR = 2;

    protected int $timestamp;
    protected int $resultCode;
    protected string $errorMessage;
    protected string $data;
    protected int $totalNumberOfEntries = 0;
    /** @var array<int,array> */
    protected array $parseErrors;

    public function __construct(
        int $resultCode,
        // todo: is not used, remove?
        string $errorMessage = '',
        string $data = '',
        array $parseErrors = [],
        int $timestamp = 0,
        int $totalNumberOfEntries = -1
    ) {
        $this->initialize(
            $resultCode,
            $errorMessage,
            $data,
            $parseErrors,
            $timestamp,
            $totalNumberOfEntries
        );
    }

    protected function initialize(
        int $resultCode,
        string $errorMessage = '',
        string $data = '',
        array $parseErrors = [],
        int $timestamp = 0,
        int $totalNumberOfEntries = -1,
        int $displayedNumberOfEntries = -1
    ) {
        if ($timestamp === 0) {
            $timestamp = time();
        }
        $this->timestamp = $timestamp;
        $this->resultCode = $resultCode;
        $this->errorMessage = $errorMessage;
        $this->data = $data;
        $this->parseErrors = $parseErrors;
        if ($totalNumberOfEntries === -1) {
            $totalNumberOfEntries = 0;
        }
        $this->totalNumberOfEntries = $totalNumberOfEntries;
    }

    public function __unserialize(array $data)
    {
        $this->initialize(
            $data['errorCode'],
            $data['errorMessage'],
            $data['data'],
            $data['parseErrors'],
            (int)$data['timestamp'],
            (int)($data['totalNumberOfEntries'] ?? 0)
        );
    }

    public function __serialize(): array
    {
        return [
            'timestamp' => $this->timestamp,
            'data' => $this->data,
            'parseErrors' => $this->parseErrors,
            'totalNumberOfEntries' => $this->totalNumberOfEntries,
            'errorCode' => $this->resultCode,
            'errorMessage' => $this->errorMessage,
        ];
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
        return $this->data;
    }

    public function emptyData(): void
    {
        $this->data = '';
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
