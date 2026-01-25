<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * API Exception
 *
 * Thrown when Archive.org API calls fail
 */
class ApiException extends LocalizedException
{
    private ?string $url;
    private ?int $httpStatusCode;
    private ?string $responseBody;

    /**
     * @param Phrase $phrase
     * @param \Exception|null $cause
     * @param int $code
     * @param string|null $url
     * @param int|null $httpStatusCode
     * @param string|null $responseBody
     */
    public function __construct(
        Phrase $phrase,
        ?\Exception $cause = null,
        int $code = 0,
        ?string $url = null,
        ?int $httpStatusCode = null,
        ?string $responseBody = null
    ) {
        parent::__construct($phrase, $cause, $code);
        $this->url = $url;
        $this->httpStatusCode = $httpStatusCode;
        $this->responseBody = $responseBody;
    }

    /**
     * Create exception for connection failure
     *
     * @param string $url
     * @param string $error
     * @return self
     */
    public static function connectionFailed(string $url, string $error): self
    {
        return new self(
            __('Failed to connect to %1: %2', $url, $error),
            null,
            0,
            $url
        );
    }

    /**
     * Create exception for HTTP error
     *
     * @param string $url
     * @param int $statusCode
     * @param string|null $body
     * @return self
     */
    public static function httpError(string $url, int $statusCode, ?string $body = null): self
    {
        return new self(
            __('HTTP error %1 from %2', $statusCode, $url),
            null,
            $statusCode,
            $url,
            $statusCode,
            $body
        );
    }

    /**
     * Create exception for invalid response
     *
     * @param string $url
     * @param string $reason
     * @return self
     */
    public static function invalidResponse(string $url, string $reason): self
    {
        return new self(
            __('Invalid response from %1: %2', $url, $reason),
            null,
            0,
            $url
        );
    }

    /**
     * Create exception for timeout
     *
     * @param string $url
     * @param int $timeout
     * @return self
     */
    public static function timeout(string $url, int $timeout): self
    {
        return new self(
            __('Request to %1 timed out after %2 seconds', $url, $timeout),
            null,
            0,
            $url
        );
    }

    /**
     * Get the URL that caused the exception
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Get the HTTP status code
     *
     * @return int|null
     */
    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    /**
     * Get the response body
     *
     * @return string|null
     */
    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    /**
     * Get exception details as array
     *
     * @return array
     */
    public function getDetails(): array
    {
        return [
            'url' => $this->url,
            'http_status' => $this->httpStatusCode,
            'message' => $this->getMessage()
        ];
    }
}
