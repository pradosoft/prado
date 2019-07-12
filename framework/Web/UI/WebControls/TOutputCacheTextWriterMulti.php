<?php
/**
 * TOutputCache class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\IO\TTextWriter;

/**
 * TOutputCacheTextWriterMulti class
 *
 * TOutputCacheTextWriterMulti is an internal class used by
 * TOutputCache to write simultaneously to multiple writers.
 *
 * @author Gabor Berczi, DevWorx Hungary <gabor.berczi@devworx.hu>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.2
 */
class TOutputCacheTextWriterMulti extends TTextWriter
{
	protected $_writers;

	public function __construct(array $writers)
	{
		//parent::__construct();
		$this->_writers = $writers;
	}

	public function write($s)
	{
		foreach ($this->_writers as $writer) {
			$writer->write($s);
		}
	}

	public function flush()
	{
		foreach ($this->_writers as $writer) {
			$s = $writer->flush();
		}
		return $s;
	}
}
