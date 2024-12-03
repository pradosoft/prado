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
 * TJavaScriptAsset class is a utility class for passing javascript
 * asset files between components of PRADO.  This class contains
 * the URL of the asset and whether or not to load it asynchronously.
 *
 * This renders the html script tag (with or w/o async) via __toString.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TJavaScriptAsset
{
	protected $_url;

	protected $_async;

	public function __construct($url, $async = false)
	{
		$this->_url = $url;
		$this->_async = $async;
	}

	public function __toString()
	{
		$async = $this->getAsync() ? 'async ' : '';
		return '<script ' . $async . 'src="' . THttpUtility::htmlEncode($this->getUrl()) . '"></script>';
	}

	public function getUrl()
	{
		return $this->_url;
	}

	public function setUrl($url)
	{
		$this->_url = $url;
	}

	public function getAsync()
	{
		return $this->_async;
	}

	public function setAsync($async)
	{
		$this->_async = $async;
	}
}
