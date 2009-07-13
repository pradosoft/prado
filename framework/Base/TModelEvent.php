<?php
/**
 * CModelEvent class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2009 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Prado::using('System.Base.TEvent');

/**
 * CModelEvent class.
 *
 * CModelEvent represents the event parameters needed by events raised by a model.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CModelEvent.php 1093 2009-06-05 13:09:17Z qiang.xue $
 * @package system.base
 * @since 1.0
 */
class TModelEvent extends TEvent
{
	/**
	 * @var boolean whether the model is valid. Defaults to true.
	 * If this is set false, {@link CModel::validate()} will return false and quit the current validation process.
	 */
	public $isValid=true;
}
