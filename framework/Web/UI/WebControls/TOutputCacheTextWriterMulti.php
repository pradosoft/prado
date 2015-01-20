<?php
/**
 * TOutputCache class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TOutputCacheTextWriterMulti class
 *
 * TOutputCacheTextWriterMulti is an internal class used by
 * TOutputCache to write simultaneously to multiple writers.
 *
 * @author Gabor Berczi, DevWorx Hungary <gabor.berczi@devworx.hu>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.2
 */
class TOutputCacheTextWriterMulti extends TTextWriter
{
	protected $_writers;

	public function __construct(Array $writers)
	{
		//parent::__construct();
		$this->_writers = $writers;
	}

	public function write($s)
	{
		foreach($this->_writers as $writer)
			$writer->write($s);
	}

	public function flush()
	{
		foreach($this->_writers as $writer)
			$s = $writer->flush();
		return $s;
	}
}