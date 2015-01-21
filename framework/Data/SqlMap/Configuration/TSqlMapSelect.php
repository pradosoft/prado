<?php
/**
 * TSqlMapStatement, TSqlMapInsert, TSqlMapUpdate, TSqlMapDelete,
 * TSqlMapSelect and TSqlMapSelectKey classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package Prado\Data\SqlMap\Configuration
 */

namespace Prado\Data\SqlMap\Configuration;

/**
 * TSqlMapSelect class file.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Statements
 * @since 3.1
 */
class TSqlMapSelect extends TSqlMapStatement
{
	private $_generate;

	public function getGenerate(){ return $this->_generate; }
	public function setGenerate($value){ $this->_generate = $value; }
}