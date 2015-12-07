<?php
/**
 * ViewUser class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2015 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 */

/**
 * ViewUser class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2015 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 */
class ViewUser extends BlogPage
{
	private $_userRecord=null;

	public function onInit($param)
	{
		parent::onInit($param);
		if(($id=$this->Request['id'])!==null)
			$id=TPropertyValue::ensureInteger($id);
		else
			$id=$this->User->ID;
		if(($this->_userRecord=$this->DataAccess->queryUserByID($id))===null)
			throw new BlogException(500,'profile_id_invalid',$id);
		$this->_userRecord->Email=strtr(strtoupper($this->_userRecord->Email),array('@'=>' at ','.'=>' dot '));
	}

	public function getProfile()
	{
		return $this->_userRecord;
	}
}

