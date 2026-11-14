<?php

/**
 * THttpCronTask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TIOException;
use Prado\IO\HttpClient\THttpClient;
use Prado\TPropertyValue;

/**
 * THttpCronTask class.
 *
 * THttpCronTask sends an HTTP request on each trigger, such as a warm-up of a page, a webhook, or a
 * maintenance URL of another service:
 * ```xml
 * <job name="warm" schedule="0 * * * *" task="Prado\Util\Cron\THttpCronTask"
 *      Url="https://example.com/sitemap.xml" />
 * <job name="hook" schedule="0 6 * * *" task="Prado\Util\Cron\THttpCronTask" Method="POST"
 *      Url="https://hooks.example.com/daily" Headers="Content-Type: application/json|Authorization: Bearer TOKEN"
 *      Body='{"event":"daily"}' />
 * ```
 *
 * The request goes through the {@see getHttpClient HttpClient}, {@see THttpClient::create()} by default.
 * A 2xx status is a success; any other status throws a {@see TIOException}, so the run's result is an
 * error.  The task's {@see getTimeout Timeout}, else the module's TaskTimeout, rounded up to whole
 * seconds, is the client's timeout.  {@see getLogFile LogFile} receives the response body of each run,
 * appended.  {@see getStatusCode StatusCode} is the status of the last run in this process.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class THttpCronTask extends TCronTask
{
	/** @var ?string the URL to request */
	private ?string $_url = null;

	/** @var string the HTTP method */
	private string $_method = 'GET';

	/** @var array<string, string> the request headers, by name */
	private array $_headers = [];

	/** @var ?string the request body, or null for none */
	private ?string $_body = null;

	/** @var ?string the file the response bodies are appended to, or null */
	private ?string $_logFile = null;

	/** @var ?THttpClient the client that sends the request */
	private ?THttpClient $_httpClient = null;

	/** @var ?int the status of the last run */
	private ?int $_statusCode = null;

	/**
	 * Sends the request.
	 * @param TCronModule $cronModule the module running the task
	 * @throws TConfigurationException when the task has no Url
	 * @throws TIOException when the response status is not 2xx
	 * @throws \Prado\IO\HttpClient\THttpClientException when the request fails
	 */
	public function execute($cronModule)
	{
		$url = $this->getUrl();
		if ($url === null) {
			throw new TConfigurationException('httpcrontask_no_url', $this->getName());
		}
		$client = $this->getHttpClient();
		$timeout = $this->getTimeout() ?? (($cronModule instanceof TCronModule) ? $cronModule->getTaskTimeout() : null);
		if ($timeout !== null) {
			$client->setTimeout(max(1, (int) ceil($timeout)));
		}
		$this->_statusCode = null;
		$response = $client->download($this->getMethod(), $url, $this->getHeaders(), $this->getBody());
		$this->_statusCode = $response->getStatusCode();
		if (($log = $this->getLogFile()) !== null) {
			file_put_contents($log, $response->getBody(), FILE_APPEND | LOCK_EX);
		}
		if (!$response->isSuccess()) {
			throw new TIOException('httpcrontask_status', $this->getName(), $this->_statusCode);
		}
	}

	/**
	 * @return ?string the URL to request
	 */
	public function getUrl(): ?string
	{
		return $this->_url;
	}

	/**
	 * @param ?string $value the URL to request
	 * @return static the current object
	 */
	public function setUrl($value): static
	{
		$this->_url = ($value === null || $value === '') ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return string the HTTP method; default 'GET'
	 */
	public function getMethod(): string
	{
		return $this->_method;
	}

	/**
	 * @param string $value the HTTP method, uppercased
	 * @return static the current object
	 */
	public function setMethod($value): static
	{
		$this->_method = strtoupper(trim(TPropertyValue::ensureString($value))) ?: 'GET';
		return $this;
	}

	/**
	 * @return array<string, string> the request headers, by name
	 */
	public function getHeaders(): array
	{
		return $this->_headers;
	}

	/**
	 * @param null|array|string $value the request headers: an array by name, or 'Name: value' lines separated
	 *   by newlines or '|'
	 * @throws TInvalidDataValueException when a line has no name
	 * @return static the current object
	 */
	public function setHeaders($value): static
	{
		$headers = [];
		if (is_array($value)) {
			foreach ($value as $name => $header) {
				$headers[trim((string) $name)] = trim((string) $header);
			}
		} elseif ($value !== null && $value !== '') {
			foreach (preg_split('/\r?\n|\|/', TPropertyValue::ensureString($value)) as $line) {
				if (trim($line) === '') {
					continue;
				}
				[$name, $header] = array_pad(explode(':', $line, 2), 2, null);
				if ($header === null || trim($name) === '') {
					throw new TInvalidDataValueException('httpcrontask_header_invalid', $line);
				}
				$headers[trim($name)] = trim($header);
			}
		}
		$this->_headers = $headers;
		return $this;
	}

	/**
	 * @return ?string the request body, or null for none
	 */
	public function getBody(): ?string
	{
		return $this->_body;
	}

	/**
	 * @param ?string $value the request body, or null for none
	 * @return static the current object
	 */
	public function setBody($value): static
	{
		$this->_body = ($value === null) ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return ?string the file each run appends its response body to, or null
	 */
	public function getLogFile(): ?string
	{
		return $this->_logFile;
	}

	/**
	 * @param ?string $value the file each run appends its response body to, or null
	 * @return static the current object
	 */
	public function setLogFile($value): static
	{
		$this->_logFile = ($value === null || $value === '') ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @throws \Prado\IO\HttpClient\THttpClientException when no HTTP transport is available
	 * @return THttpClient the client that sends the request; defaults to {@see THttpClient::create()}
	 */
	public function getHttpClient(): THttpClient
	{
		return $this->_httpClient ??= THttpClient::create();
	}

	/**
	 * @param ?THttpClient $client the client that sends the request; null restores the default
	 * @return static the current object
	 */
	public function setHttpClient(?THttpClient $client): static
	{
		$this->_httpClient = $client;
		return $this;
	}

	/**
	 * @return ?int the response status of the last run in this process, or null before a run
	 */
	public function getStatusCode(): ?int
	{
		return $this->_statusCode;
	}

	/**
	 * Excludes the client, the last status, and the default-valued properties from serialization.
	 * @param array $exprops by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0Prado\Util\Cron\THttpCronTask\0_httpClient";
		$exprops[] = "\0Prado\Util\Cron\THttpCronTask\0_statusCode";
		if ($this->_method === 'GET') {
			$exprops[] = "\0Prado\Util\Cron\THttpCronTask\0_method";
		}
		if ($this->_headers === []) {
			$exprops[] = "\0Prado\Util\Cron\THttpCronTask\0_headers";
		}
		if ($this->_body === null) {
			$exprops[] = "\0Prado\Util\Cron\THttpCronTask\0_body";
		}
		if ($this->_logFile === null) {
			$exprops[] = "\0Prado\Util\Cron\THttpCronTask\0_logFile";
		}
	}
}
