<?php
/**
 * TTextHighlighterWriter class file
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Prado;
use Prado\Web\THttpUtility;

/**
 * TTextHighlighterWriter class.
 *
 * TTextHighlighterWriter is an helper class for {@link TTextHighlighter} that provides html encoding and
 * avoids a blank line from being printed at the beginning of the code.
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @package Prado\Web\UI\WebControls
 * @since 4.0
 */

class TTextHighlighterWriter extends \Prado\Web\UI\THtmlWriter
{
	protected $firstLine = true;
	/**
	 * Renders a string.
	 * @param string $str string to be rendered
	 */
	public function write($str)
	{
		if ($this->firstLine) {
			$this->firstLine = false;
			$this->_writer->write(THttpUtility::htmlEncode(ltrim($str)));
		} else {
			$this->_writer->write(THttpUtility::htmlEncode($str));
		}
	}
}
