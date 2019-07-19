<?php
/**
 * TActivePageAdapter, TCallbackErrorHandler and TInvalidCallbackException class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @author Gabor Berczi <gabor.berczi@devworx.hu> (lazyload additions & progressive rendering)
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Exception;
use Prado\Exceptions\TErrorHandler;
use Prado\Exceptions\TPhpErrorException;
use Prado\Prado;
use Prado\TApplicationMode;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Util\TVarDumper;

/**
 * TCallbackErrorHandler class.
 *
 * Captures errors and exceptions and send them back during callback response.
 * When the application is in debug mode, the error and exception stack trace
 * are shown. A TJavascriptLogger must be present on the client-side to view
 * the error stack trace.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TCallbackErrorHandler extends TErrorHandler
{
	/**
	 * Displays the exceptions to the client-side TJavascriptLogger.
	 * A HTTP 500 status code is sent and the stack trace is sent as JSON encoded.
	 * @param Exception $exception exception details.
	 */
	protected function displayException($exception)
	{
		if ($this->getApplication()->getMode() === TApplicationMode::Debug) {
			$response = $this->getApplication()->getResponse();
			$trace = $this->getExceptionStackTrace($exception);
			// avoid error on non-utf8 strings
			try {
				$trace = TJavaScript::jsonEncode($trace);
			} catch (Exception $e) {
				// strip everythin not 7bit ascii
				$trace = preg_replace('/[^(\x20-\x7F)]*/', '', serialize($trace));
			}

			// avoid exception loop if headers have already been sent
			try {
				$response->setStatusCode(500, 'Internal Server Error');
			} catch (Exception $e) {
			}

			$content = $response->createHtmlWriter();
			$content->getWriter()->setBoundary(TActivePageAdapter::CALLBACK_ERROR_HEADER);
			$content->write($trace);
		} else {
			error_log("Error happened while processing an existing error:\n" . $exception->__toString());
			header('HTTP/1.0 500 Internal Server Error', true, 500);
		}
		$this->getApplication()->getResponse()->flush();
	}

	/**
	 * @param Exception $exception exception details.
	 * @return array exception stack trace details.
	 */
	private function getExceptionStackTrace($exception)
	{
		$data['code'] = $exception->getCode() > 0 ? $exception->getCode() : 500;
		$data['file'] = $this->hidePrivatePathParts($exception->getFile());
		$data['line'] = $exception->getLine();
		$data['trace'] = $exception->getTrace();
		foreach ($data['trace'] as $k => $v) {
			if (isset($v['file'])) {
				$data['trace'][$k]['file'] = $this->hidePrivatePathParts($v['file']);
			}

			if (isset($v['args'])) {
				$argsout = [];
				foreach ($v['args'] as $kArg => $vArg) {
					$data['trace'][$k]['args'][$kArg] = TVarDumper::dump($vArg, 0, false);
				}
			}
		}
		if ($exception instanceof TPhpErrorException) {
			// if PHP exception, we want to show the 2nd stack level context
			// because the 1st stack level is of little use (it's in error handler)
			if (isset($data['trace'][0]) && isset($data['trace'][0]['file']) && isset($data['trace'][0]['line'])) {
				$data['file'] = $data['trace'][0]['file'];
				$data['line'] = $data['trace'][0]['line'];
			}
		}
		$data['type'] = get_class($exception);
		$data['message'] = $exception->getMessage();
		$data['version'] = $_SERVER['SERVER_SOFTWARE'] . ' ' . Prado::getVersion();
		$data['time'] = @strftime('%Y-%m-%d %H:%M', time());
		return $data;
	}
}
