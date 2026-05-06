<?php

/**
 * TJavaScriptAsset classes
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Javascripts;

use Prado\Web\THttpUtility;

/**
 * TJavaScriptAsset class.
 *
 * TJavaScriptAsset is a value object that carries the properties of a single
 * `<script src="...">` tag and renders it via {@see __toString()}.
 *
 * Properties:
 * - {@see getUrl() Url} — the URL of the JavaScript file.
 * - {@see getAsync() Async} — whether to add the `async` boolean attribute.
 *
 * A per-request CSP nonce registered via {@see TJavaScript::setScriptNonce()} is
 * automatically included as a `nonce` attribute on every rendered tag.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TJavaScriptAsset
{
	/** @var string URL of the JavaScript file. */
	protected $_url;

	/** @var bool Whether to add the `async` boolean attribute. */
	protected $_async;

	public function __construct($url, $async = false)
	{
		$this->setUrl($url);
		$this->setAsync($async);
	}

	/**
	 * Renders the `<script src="...">` tag.
	 * The `nonce` attribute is included when a CSP nonce has been registered via
	 * {@see TJavaScript::setScriptNonce()}.
	 * @return string The rendered script tag.
	 */
	public function __toString(): string
	{
		$attrs = THttpUtility::buildHtmlAttributes([
			'!src' => THttpUtility::htmlEncode($this->getUrl()),
			'async' => $this->getAsync() ?: null,
			'nonce' => TJavaScript::getScriptNonce(),
		]);
		return '<script' . $attrs . '></script>';
	}

	/**
	 * @return string URL of the JavaScript file
	 */
	public function getUrl()
	{
		return $this->_url;
	}

	/**
	 * @param string $url URL of the JavaScript file
	 */
	public function setUrl($url)
	{
		$this->_url = $url;
	}

	/**
	 * @return bool whether the `async` attribute is set
	 */
	public function getAsync()
	{
		return $this->_async;
	}

	/**
	 * @param bool $async whether to add the `async` boolean attribute
	 */
	public function setAsync($async)
	{
		$this->_async = $async;
	}
}
