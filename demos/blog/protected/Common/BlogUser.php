<?php
/**
 * BlogUser class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2015 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 */

Prado::using('System.Security.TUser');

/**
 * BlogUser class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2015 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 */
class BlogUser extends TUser
{
	private $_id;

	public function getID()
	{
		return $this->_id;
	}

	public function setID($value)
	{
		$this->_id=$value;
	}

	public function getIsAdmin()
	{
		return $this->isInRole('admin');
	}

	public function saveToString()
	{
		$a=array($this->_id,parent::saveToString());
		return serialize($a);
	}

	public function loadFromString($data)
	{
		if(!empty($data))
		{
			list($id,$str)=unserialize($data);
			$this->_id=$id;
			return parent::loadFromString($str);
		}
		else
			return $this;
	}
}

