<?php
/**
 * TCallbackResponseAdapter and TCallbackResponseWriter class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\IO\TTextWriter;

/**
 * TCallbackResponseWriter class.
 *
 * TCallbackResponseWriter class enclosed a chunck of content within a
 * html comment boundary. This allows multiple chuncks of content to return
 * in the callback response and update multiple HTML elements.
 *
 * The {@link setBoundary Boundary} property sets boundary identifier in the
 * HTML comment that forms the boundary. By default, the boundary identifier
 * is generated using microtime.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TCallbackResponseWriter extends TTextWriter
{
	/**
	 * @var string boundary ID
	 */
	private $_boundary;

	/**
	 * Constructor. Generates unique boundary ID using microtime.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->_boundary = sprintf('%x', crc32(uniqid(null, true)));
	}

	/**
	 * @return string boundary identifier.
	 */
	public function getBoundary()
	{
		return $this->_boundary;
	}

	/**
	 * @param string $value boundary identifier.
	 */
	public function setBoundary($value)
	{
		$this->_boundary = $value;
	}

	/**
	 * Returns the text content wrapped within a HTML comment with boundary
	 * identifier as its comment content.
	 * @return string text content chunck.
	 */
	public function flush()
	{
		$content = parent::flush();
		if (empty($content)) {
			return "";
		}
		return '<!--' . $this->getBoundary() . '-->' . $content . '<!--//' . $this->getBoundary() . '-->';
	}
}
