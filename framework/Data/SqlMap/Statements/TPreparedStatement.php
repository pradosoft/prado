<?php
/**
 * TPreparedStatement class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TPreparedStatement.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Data.SqlMap.Statements
 */

/**
 * TpreparedStatement class.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id: TPreparedStatement.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Data.SqlMap.Statements
 * @since 3.1
 */
class TPreparedStatement extends TComponent
{
	private $_sqlString='';
	private $_parameterNames;
	private $_parameterValues;

	public function __construct()
	{
		$this->_parameterNames=new TList;
		$this->_parameterValues=new TMap;
	}

	public function getPreparedSql(){ return $this->_sqlString; }
	public function setPreparedSql($value){ $this->_sqlString = $value; }

	public function getParameterNames(){ return $this->_parameterNames; }
	public function setParameterNames($value){ $this->_parameterNames = $value; }

	public function getParameterValues(){ return $this->_parameterValues; }
	public function setParameterValues($value){ $this->_parameterValues = $value; }

	public function __wakeup()
	{
		parent::__wakeup();
		if (!$this->_parameterNames) $this->_parameterNames = new TList;
		if (!$this->_parameterValues) $this->_parameterValues = new TMap;
	}
	
	public function __sleep()
	{
		$exprops = array(); $cn = __CLASS__; 
		if (!$this->_parameterNames->getCount()) $exprops[] = "\0$cn\0_parameterNames";
		if (!$this->_parameterValues->getCount()) $exprops[] = "\0$cn\0_parameterValues";
		return array_diff(parent::__sleep(),$exprops);
	}
}

