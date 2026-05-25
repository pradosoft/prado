<?php

/**
 * TErrorHandler class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Exceptions;

use Prado\TApplicationMode;
use Prado\Prado;
use Prado\TComponentReflection;

/**
 * TErrorHandler class
 *
 * TErrorHandler handles all PHP user errors and exceptions generated during
 * servicing user requests. It displays these errors using different templates
 * and if possible, using languages preferred by the user.
 * Note: PHP parsing errors cannot be caught and handled by TErrorHandler.
 *
 * The templates used to format the error output are stored under Prado\Exceptions.
 * You may choose to use your own templates, should you not like the templates
 * provided by Prado. Simply set {@see setErrorTemplatePath()}
 * to the path (in namespace format) storing your own templates.
 *
 * There are two sets of templates, one for errors to be displayed to users
 * (called external errors), one for errors to be displayed to system developers
 * (called internal errors). The template file name for the former is
 * `error[StatusCode][-LanguageCode].html`, and for the latter it is
 * `exception[-LanguageCode].html`, where StatusCode refers to response status
 * code (e.g. 404, 500) specified when {@see \Prado\Exceptions\THttpException} is thrown,
 * and LanguageCode is the user preferred language code (e.g. en, zh, de).
 * The templates `error.html` and `exception.html` are default ones
 * that are used if no other appropriate templates are available.
 * Note: these templates are not Prado control templates. They are simply
 * HTML files with keywords (e.g. %%ErrorMessage%%, %%Version%%)
 * to be replaced with the corresponding information.
 *
 * {@see init()} registers the instance with {@see \Prado\TApplication} as the
 * error handler, accessible via {@see \Prado\TApplication::getErrorHandler()};
 * the application then routes all unhandled errors and exceptions through
 * {@see handleError()}.
 *
 * TErrorHandler may be configured in application.xml as follows:
 * ```xml
 * <modules>
 *   <module id="error" class="Prado\Exceptions\TErrorHandler" ErrorTemplatePath="Prado\Exceptions" />
 * </modules>
 * ```
 *
 * Or equivalently in application.php:
 * ```php
 * return [
 *     'modules' => [
 *         'error' => [
 *             'class' => TErrorHandler::class,
 *             'properties' => ['ErrorTemplatePath' => 'Prado\Exceptions'],
 *         ],
 *     ],
 * ];
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TErrorHandler extends \Prado\TModule
{
	/**
	 * error template file basename
	 */
	public const ERROR_FILE_NAME = 'error';
	/**
	 * exception template file basename
	 */
	public const EXCEPTION_FILE_NAME = 'exception';
	/**
	 * number of lines before and after the error line to be displayed in case of an exception
	 */
	public const SOURCE_LINES = 12;
	/**
	 * number of Prado internal function calls to be dropped from stack traces on fatal errors
	 */
	public const FATAL_ERROR_TRACE_DROP_LINES = 5;

	/**
	 * @var string error template directory
	 */
	private $_templatePath;

	/**
	 * @var ?array cached path replacement map for hidePrivatePathParts()
	 * @since 4.3.3
	 */
	private $_privatePathReplacements;

	/**
	 * @var bool whether an error is currently being handled (prevents infinite loops)
	 * @since 4.3.3
	 */
	private bool $_handling = false;

	/**
	 * Initializes the module and registers it as the application error handler.
	 * @param \Prado\Xml\TXmlElement $config module configuration
	 */
	public function init($config)
	{
		$this->setAppErrorHandler();
		parent::init($config);
	}

	/**
	 * Registers this handler as the application error handler when an application is available.
	 * Called during {@see init()}; may also be called by behaviors or subclasses.
	 * @since 4.3.3
	 */
	protected function setAppErrorHandler()
	{
		$this->getApplication()?->setErrorHandler($this);
	}

	/**
	 * Returns the built-in framework directory containing error and exception template files.
	 * @return string absolute filesystem path to the framework templates directory
	 * @since 4.3.3
	 */
	protected function getDefaultErrorTemplatePath(): string
	{
		return Prado::getFrameworkPath() . '/Exceptions/templates';
	}

	/**
	 * Returns the cached path replacement map without building it.
	 * @return ?array associative array of path => replacement strings, or null if not yet built
	 * @since 4.3.3
	 */
	protected function getPrivatePathReplacementsDirect(): ?array
	{
		return $this->_privatePathReplacements;
	}

	/**
	 * Sets the cached path replacement map directly.
	 * @param ?array $value
	 * @since 4.3.3
	 */
	protected function setPrivatePathReplacementsDirect($value): void
	{
		$this->_privatePathReplacements = $value;
	}

	/**
	 * Returns the map used to replace private filesystem paths in output.
	 * Builds and caches the map on first call.
	 * @return array associative array of path => replacement strings
	 * @since 4.3.3
	 */
	protected function getPrivatePathReplacements(): array
	{
		if ($this->getPrivatePathReplacementsDirect() === null) {
			$aRpl = [];
			$docRoot = $this->serverGlobal('DOCUMENT_ROOT') ?? '';
			if ($docRoot !== '') {
				$aRpl[$docRoot] = '${DocumentRoot}';
				$aRpl[str_replace('/', DIRECTORY_SEPARATOR, $docRoot)] = '${DocumentRoot}';
			}
			$aRpl[PRADO_DIR . DIRECTORY_SEPARATOR] = '${PradoFramework}' . DIRECTORY_SEPARATOR;
			if (isset($aRpl[DIRECTORY_SEPARATOR])) {
				unset($aRpl[DIRECTORY_SEPARATOR]);
			}
			$this->setPrivatePathReplacementsDirect(array_reverse($aRpl, true));
		}
		return $this->getPrivatePathReplacementsDirect();
	}

	/**
	 * Returns the raw template path property value without applying defaults.
	 * @return ?string
	 * @since 4.3.3
	 */
	protected function getErrorTemplatePathDirect(): ?string
	{
		return $this->_templatePath;
	}

	/**
	 * Sets the template path property directly to a resolved filesystem path.
	 * @param ?string $value
	 * @since 4.3.3
	 */
	protected function setErrorTemplatePathDirect(?string $value): void
	{
		$this->_templatePath = $value;
	}

	/**
	 * Returns the directory containing error template files.
	 * Defaults to the built-in framework templates directory.
	 * @return string absolute filesystem path to the template directory
	 */
	public function getErrorTemplatePath()
	{
		$errorTemplatePath = $this->getErrorTemplatePathDirect();
		if ($errorTemplatePath === null) {
			$this->setErrorTemplatePathDirect($this->getDefaultErrorTemplatePath());
			$errorTemplatePath = $this->getErrorTemplatePathDirect();
		}
		return $errorTemplatePath;
	}

	/**
	 * Sets the directory containing error and exception template files.
	 * The value must be a namespace path, e.g. 'Prado\Exceptions'.
	 * @param string $value template path in namespace format
	 * @throws TConfigurationException if the path does not resolve to a valid directory
	 */
	public function setErrorTemplatePath($value)
	{
		if (($templatePath = Prado::getPathOfNamespace($value)) === null || !is_dir($templatePath)) {
			throw new TConfigurationException('errorhandler_errortemplatepath_invalid', $value);
		}
		$this->setErrorTemplatePathDirect($templatePath);
	}

	// ── Re-entrance guard ───────────────────────────────────────────────────────

	/**
	 * Returns whether an error is currently being handled by this instance.
	 * Used by {@see handleError()} to detect recursive error conditions.
	 * @return bool true when error handling is already in progress
	 * @since 4.3.3
	 */
	protected function getIsHandled(): bool
	{
		return $this->_handling;
	}

	/**
	 * Sets the re-entrance flag that tracks whether error handling is in progress.
	 * @param bool $value true to mark handling as active, false to reset
	 * @since 4.3.3
	 */
	protected function setIsHandled(bool $value): void
	{
		$this->_handling = $value;
	}

	// ── error handlers ───────────────────────────────────────────────────────

	/**
	 * Handles PHP user errors and exceptions raised during request servicing.
	 * Responds to the {@see \Prado\TApplication} Error event.
	 * Selects an external or debug template based on the exception type and application mode.
	 * @param mixed $sender event sender
	 * @param \Throwable $param the exception or error to handle
	 */
	public function handleError($sender, $param)
	{
		$handling = $this->getIsHandled();
		// We need to restore error and exception handlers,
		// because within error and exception handlers, new errors and exceptions
		// cannot be handled properly by PHP
		$this->restoreErrorHandler();
		$this->restoreExceptionHandler();
		// ensure that we do not enter infinite loop of error handling
		if ($handling) {
			$this->handleRecursiveError($param);
		} else {
			$this->setIsHandled(true);
			if (($response = $this->getResponse()) !== null) {
				$response->clear();
			}
			if (!$this->headersSent()) {
				$header = 'Content-Type: text/html; charset=UTF-8';
				$response = $this->getResponse();
				if ($response) {
					$response->appendHeader($header);
				} else {
					$this->header($header);
				}
			}
			if ($param instanceof THttpException) {
				$this->handleExternalError($param->getStatusCode(), $param);
			} elseif ($this->getApplication()->getMode() === TApplicationMode::Debug) {
				$this->displayException($param);
			} else {
				$this->handleExternalError(500, $param);
			}
		}
	}

	/**
	 * Strips private filesystem paths from a string to prevent leaking server layout.
	 * Replaces the document root, the framework directory, and any per-frame source
	 * directories found in the exception trace with safe placeholder tokens.
	 * @param string $value
	 * @param ?\Exception $exception
	 * @since 3.1.6
	 */
	protected function hideSecurityRelated($value, $exception = null)
	{
		$aRpl = [];
		if ($exception !== null && $exception instanceof \Exception) {
			if ($exception instanceof TPhpFatalErrorException &&
				function_exists('xdebug_get_function_stack')) {
				$aTrace = array_slice(array_reverse(xdebug_get_function_stack()), static::FATAL_ERROR_TRACE_DROP_LINES, -1);
			} else {
				$aTrace = $exception->getTrace();
			}

			foreach ($aTrace as $item) {
				if (isset($item['file'])) {
					$aRpl[dirname($item['file']) . DIRECTORY_SEPARATOR] = '<hidden>' . DIRECTORY_SEPARATOR;
				}
			}
		}
		$docRoot = $this->serverGlobal('DOCUMENT_ROOT') ?? '';
		if ($docRoot !== '') {
			$aRpl[$docRoot] = '${DocumentRoot}';
			$aRpl[str_replace('/', DIRECTORY_SEPARATOR, $docRoot)] = '${DocumentRoot}';
		}
		$aRpl[PRADO_DIR . DIRECTORY_SEPARATOR] = '${PradoFramework}' . DIRECTORY_SEPARATOR;
		if (isset($aRpl[DIRECTORY_SEPARATOR])) {
			unset($aRpl[DIRECTORY_SEPARATOR]);
		}
		$aRpl = array_reverse($aRpl, true);

		return str_replace(array_keys($aRpl), $aRpl, $value);
	}

	/**
	 * Renders a user-facing error page for the given HTTP status code.
	 * Logs non-HTTP exceptions via {@see errorLog()}.
	 * In non-Debug mode, strips sensitive path information from the error message.
	 * @param int $statusCode
	 * @param \Exception $exception
	 */
	protected function handleExternalError($statusCode, $exception)
	{
		if (!($exception instanceof THttpException)) {
			$this->errorLog($exception->__toString());
		}

		$content = $this->getErrorTemplate($statusCode, $exception);

		$serverAdmin = $this->serverGlobal('SERVER_ADMIN') ?? '';

		$isDebug = $this->getApplication()->getMode() === TApplicationMode::Debug;

		$errorMessage = $exception->getMessage();
		if ($isDebug) {
			$version = ($this->serverGlobal('SERVER_SOFTWARE') ?? '') . ' <a href="https://github.com/pradosoft/prado">PRADO</a>/' . Prado::getVersion();
		} else {
			$version = '';
			$errorMessage = $this->hideSecurityRelated($errorMessage, $exception);
		}
		$tokens = [
			'%%StatusCode%%' => "$statusCode",
			'%%ErrorMessage%%' => htmlspecialchars($errorMessage),
			'%%ErrorCode%%' => $exception->getCode(),
			'%%ServerAdmin%%' => $serverAdmin,
			'%%Version%%' => $version,
			'%%Time%%' => date('Y-m-d H:i'),
		];

		$this->getApplication()->getResponse()->setStatusCode($statusCode, $isDebug ? $exception->getMessage() : null);

		echo strtr($content, $tokens);
	}

	/**
	 * Handles an error that occurs while already handling another error.
	 * In Debug mode, renders a minimal HTML page with the exception details.
	 * In non-Debug mode, logs the error via {@see errorLog()} and sends a
	 * `HTTP/1.0 500 Internal Error` header via {@see header()}, but only when
	 * {@see headersSent()} reports that headers have not yet been sent.
	 * Bypasses templates to avoid triggering further errors.
	 * @param \Exception $exception
	 */
	protected function handleRecursiveError($exception)
	{
		if ($this->getApplication()->getMode() === TApplicationMode::Debug) {
			echo "<html><head><title>Recursive Error</title></head>\n";
			echo "<body><h1>Recursive Error</h1>\n";
			echo "<pre>" . $exception->__toString() . "</pre>\n";
			echo "</body></html>";
		} else {
			$this->errorLog("Error happened while processing an existing error:\n" . $exception->__toString());

			if (!$this->headersSent()) {
				$value = 'HTTP/1.0 500 Internal Error';
				$response = $this->getResponse();
				if ($response) {
					$response->appendHeader($value);
				} else {
					$this->header($value);
				}
			}
		}
	}

	// ── Template and output helpers ──────────────────────────────────────────────

	/**
	 * Replaces private filesystem paths in a string using the instance replacement map.
	 * @param string $value
	 */
	protected function hidePrivatePathParts($value)
	{
		$aRpl = $this->getPrivatePathReplacements();
		return str_replace(array_keys($aRpl), $aRpl, $value);
	}

	/**
	 * Renders full exception details including source context and stack trace.
	 * Used when the application is in Debug mode.
	 * In CLI mode outputs plain text; in web mode renders the exception HTML template.
	 * @param \Exception $exception
	 */
	protected function displayException($exception)
	{
		if ($this->phpSapiName() === 'cli') {
			echo $exception->getMessage() . "\n";
			echo $this->getExactTraceAsString($exception);
			return;
		}

		if ($exception instanceof TTemplateException) {
			$fileName = $exception->getTemplateFile();
			$lines = empty($fileName) ? explode("\n", $exception->getTemplateSource()) : @file($fileName);
			$source = $this->getSourceCode($lines, $exception->getLineNumber());
			if ($fileName === '') {
				$fileName = '---embedded template---';
			}
			$errorLine = $exception->getLineNumber();
		} else {
			if (($trace = $this->getExactTrace($exception)) !== null) {
				$fileName = $trace['file'];
				$errorLine = $trace['line'];
			} else {
				$fileName = $exception->getFile();
				$errorLine = $exception->getLine();
			}
			$source = $this->getSourceCode(@file($fileName), $errorLine);
		}

		if ($this->getApplication()->getMode() === TApplicationMode::Debug) {
			$version = ($this->serverGlobal('SERVER_SOFTWARE') ?? '') . ' <a href="https://github.com/pradosoft/prado">PRADO</a>/' . Prado::getVersion();
		} else {
			$version = '';
		}

		$tokens = [
			'%%ErrorType%%' => $exception::class,
			'%%ErrorMessage%%' => $this->addLink(htmlspecialchars($exception->getMessage())),
			'%%ErrorCode%%' => $exception->getCode(),
			'%%SourceFile%%' => htmlspecialchars($this->hidePrivatePathParts($fileName)) . ' (' . $errorLine . ')',
			'%%SourceCode%%' => $source,
			'%%StackTrace%%' => htmlspecialchars($this->getExactTraceAsString($exception)),
			'%%Version%%' => $version,
			'%%Time%%' => date('Y-m-d H:i'),
		];

		$content = $this->getExceptionTemplate($exception);

		echo strtr($content, $tokens);
	}

	/**
	 * Returns the HTML template used for displaying exceptions in debug mode.
	 * Respects the configured {@see getErrorTemplatePath()}, selecting a
	 * language-specific variant (`exception-{lang}.html`) when available and
	 * falling back to `exception.html`.
	 * @param \Exception $exception
	 */
	protected function getExceptionTemplate($exception)
	{
		$lang = Prado::getPreferredLanguage();
		$templatePath = $this->getErrorTemplatePath();
		$exceptionFile = $templatePath . DIRECTORY_SEPARATOR . static::EXCEPTION_FILE_NAME . '-' . $lang . '.html';
		if (!is_file($exceptionFile)) {
			$exceptionFile = $templatePath . DIRECTORY_SEPARATOR . static::EXCEPTION_FILE_NAME . '.html';
		}
		if (($content = @file_get_contents($exceptionFile)) === false) {
			die("Unable to open exception template file '$exceptionFile'.");
		}
		return $content;
	}

	/**
	 * Returns the HTML template used for displaying a user-facing error page.
	 * Selects the most specific available template in priority order:
	 * error{StatusCode}-{lang}.html, error{StatusCode}.html, error-{lang}.html, error.html.
	 *
	 * Override to supply a custom template for specific status codes.
	 * The returned content supports these replacement tokens:
	 * - %%StatusCode%% — HTTP status code
	 * - %%ErrorMessage%% — HTML-encoded error message
	 * - %%ErrorCode%% — exception error code
	 * - %%ServerAdmin%% — server administrator contact (from web server configuration)
	 * - %%Version%% — web server and PRADO version string (debug mode only)
	 * - %%Time%% — formatted timestamp of the error
	 * @param int $statusCode
	 * @param \Exception $exception
	 */
	protected function getErrorTemplate($statusCode, $exception)
	{
		$base = $this->getErrorTemplatePath() . DIRECTORY_SEPARATOR . static::ERROR_FILE_NAME;
		$lang = Prado::getPreferredLanguage();
		if (is_file("$base$statusCode-$lang.html")) {
			$errorFile = "$base$statusCode-$lang.html";
		} elseif (is_file("$base$statusCode.html")) {
			$errorFile = "$base$statusCode.html";
		} elseif (is_file("$base-$lang.html")) {
			$errorFile = "$base-$lang.html";
		} else {
			$errorFile = "$base.html";
		}
		if (($content = @file_get_contents($errorFile)) === false) {
			die("Unable to open error template file '$errorFile'.");
		}
		return $content;
	}

	/**
	 * Returns the most relevant stack frame from an exception's trace.
	 * For {@see TPhpErrorException}, returns the frame where the PHP error occurred.
	 * For {@see TInvalidOperationException}, returns the frame that called __get or __set.
	 * Returns null when no actionable frame is found or the frame is inside eval'd code
	 * (detected by `": eval()'d code"` in the file path on PHP < 8, or `'function' => 'eval'`
	 * on PHP 8+).
	 * @param \Exception $exception
	 */
	protected function getExactTrace($exception)
	{
		$result = null;
		if ($exception instanceof TPhpFatalErrorException &&
			function_exists('xdebug_get_function_stack')) {
			$trace = array_slice(array_reverse(xdebug_get_function_stack()), static::FATAL_ERROR_TRACE_DROP_LINES, -1);
		} else {
			$trace = $exception->getTrace();
		}

		// if PHP exception, we want to show the 2nd stack level context
		// because the 1st stack level is of little use (it's in error handler)
		if ($exception instanceof TPhpErrorException) {
			if (isset($trace[0]['file'])) {
				$result = $trace[0];
			} elseif (isset($trace[1])) {
				$result = $trace[1];
			}
		} elseif ($exception instanceof TInvalidOperationException) {
			// in case of getter or setter error, find out the exact file and row
			if (($result = $this->getPropertyAccessTrace($trace, '__get')) === null) {
				$result = $this->getPropertyAccessTrace($trace, '__set');
			}
		}
		if ($result !== null && (
			strpos($result['file'], ': eval()\'d code') !== false
			|| (isset($result['function']) && $result['function'] === 'eval')
		)) {
			return null;
		}

		return $result;
	}

	/**
	 * Returns the exception stack trace as a string, with private paths sanitized.
	 * When xdebug is active and the exception is a fatal error, uses the xdebug stack.
	 * @param \Exception $exception
	 */
	protected function getExactTraceAsString($exception)
	{
		if ($exception instanceof TPhpFatalErrorException &&
			function_exists('xdebug_get_function_stack')) {
			$trace = array_slice(array_reverse(xdebug_get_function_stack()), static::FATAL_ERROR_TRACE_DROP_LINES, -1);
			$txt = '';
			$row = 0;

			// try to mimic Exception::getTraceAsString()
			foreach ($trace as $line) {
				if (array_key_exists('function', $line)) {
					$func = $line['function'] . '(' . implode(',', $line['params']) . ')';
				} else {
					$func = 'unknown';
				}

				$txt .= '#' . $row . ' ' . $this->hidePrivatePathParts($line['file']) . '(' . $line['line'] . '): ' . $func . "\n";
				$row++;
			}

			return $txt;
		}

		return $this->hidePrivatePathParts($exception->getTraceAsString());
	}

	/**
	 * Scans a trace array for the outermost consecutive frame matching a magic method name.
	 * @param array $trace
	 * @param string $pattern
	 */
	protected function getPropertyAccessTrace($trace, $pattern)
	{
		$result = null;
		foreach ($trace as $t) {
			if (isset($t['function']) && $t['function'] === $pattern) {
				$result = $t;
			} else {
				break;
			}
		}
		return $result;
	}

	/**
	 * Returns an HTML fragment showing the source lines surrounding an error line.
	 * Displays {@see SOURCE_LINES} lines before and after the error line.
	 * The error line itself is wrapped in a `<div class="error">` element.
	 * @param null|array $lines
	 * @param int $errorLine
	 */
	protected function getSourceCode($lines, $errorLine)
	{
		$numLines = is_countable($lines) ? count($lines) : 0;
		$beginLine = $errorLine - static::SOURCE_LINES >= 0 ? $errorLine - static::SOURCE_LINES : 0;
		$endLine = $errorLine + static::SOURCE_LINES <= $numLines ? $errorLine + static::SOURCE_LINES : $numLines;

		$source = '';
		for ($i = $beginLine; $i < $endLine; ++$i) {
			if ($i === $errorLine - 1) {
				$line = htmlspecialchars(sprintf("%04d: %s", $i + 1, str_replace("\t", '    ', $lines[$i])));
				$source .= "<div class=\"error\">" . $line . "</div>";
			} else {
				$source .= htmlspecialchars(sprintf("%04d: %s", $i + 1, str_replace("\t", '    ', $lines[$i])));
			}
		}
		return $source;
	}

	/**
	 * Wraps the first recognized Prado class name in the message with a documentation hyperlink.
	 * @param string $message
	 */
	protected function addLink($message)
	{
		if (null !== ($class = $this->getErrorClassNameSpace($message))) {
			return str_replace($class['name'], '<a href="' . $class['url'] . '" target="_blank">' . $class['name'] . '</a>', $message);
		}
		return $message;
	}

	/**
	 * Extracts the first Prado class name from a message and returns its documentation URL.
	 * @param string $message
	 * @return ?array{url: string, name: string} associative array with 'url' and 'name' keys, or null
	 */
	protected function getErrorClassNameSpace($message)
	{
		$matches = [];
		preg_match('/\b(T[A-Z]\w+)\b/', $message, $matches);
		if (count($matches) > 0) {
			$class = $matches[0];
			$reflection = TComponentReflection::getReflectionClassForType($class);
			if ($reflection === null) {
				return null;
			}
			$classname = $reflection->getNamespaceName();
			return [
				'url' => 'https://pradosoft.github.io/docs/manual/class-' . str_replace('\\', '.', (string) $classname) . '.' . $class . '.html',
				'name' => $class,
			];
		}
		return null;
	}

	// ── PHP global-function wrappers (self-encapsulation) ──────────────────────

	/**
	 * Writes a message to the PHP error log.
	 * Extracted to allow subclasses to capture or suppress error-log output in
	 * tests or custom logging integrations without modifying PHP ini settings.
	 * @param string $message the message to log
	 * @since 4.3.3
	 */
	protected function errorLog(string $message): void
	{
		error_log($message);
	}

	/**
	 * Returns whether HTTP headers have already been sent.
	 * Extracted to allow subclasses to mock header state in test environments.
	 * @return bool true when headers have already been sent
	 * @since 4.3.3
	 */
	protected function headersSent(): bool
	{
		return headers_sent();
	}

	/**
	 * Wraps PHP's built-in {@see \header()} as a protected seam for unit testing.
	 * @param string $header        Raw header string, e.g. `X-Frame-Options: DENY`.
	 * @param bool   $replace       Replace an existing same-name header. Default: `true`.
	 * @param int    $response_code HTTP response code to force; `0` leaves it unchanged.
	 * @since 4.3.3
	 */
	protected function header(string $header, bool $replace = true, int $response_code = 0): void
	{
		header($header, $replace, $response_code);
	}

	/**
	 * Restores the previously installed PHP error handler.
	 * Extracted to allow subclasses to suppress restoration in test environments.
	 * @since 4.3.3
	 */
	protected function restoreErrorHandler(): void
	{
		restore_error_handler();
	}

	/**
	 * Restores the previously installed PHP exception handler.
	 * Extracted to allow subclasses to suppress restoration in test environments.
	 * @see restoreErrorHandler()
	 * @since 4.3.3
	 */
	protected function restoreExceptionHandler(): void
	{
		restore_exception_handler();
	}

	/**
	 * Returns the PHP SAPI name for the current process.
	 * Extracted to allow subclasses to override the SAPI check in test environments.
	 * @return string the SAPI name (e.g. `'cli'`, `'apache2handler'`, `'fpm-fcgi'`)
	 * @since 4.3.3
	 */
	protected function phpSapiName(): string
	{
		return php_sapi_name();
	}

	/**
	 * Returns a value from the `$_SERVER` superglobal, or null when the key is absent.
	 * Extracted to allow subclasses to mock server variables in test environments
	 * without modifying the real superglobal.
	 * @param string $key the `$_SERVER` key to retrieve (e.g. `'DOCUMENT_ROOT'`)
	 * @return mixed the value, or null if the key is not set
	 * @since 4.3.3
	 */
	protected function serverGlobal(string $key): mixed
	{
		return $_SERVER[$key] ?? null;
	}
}
