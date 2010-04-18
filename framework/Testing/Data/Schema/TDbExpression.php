<?php
/**
 * TDbExpression class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2009 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * TDbExpression represents a DB expression that does not need escaping.
 * TDbExpression is mainly used in {@link CActiveRecord} as attribute values.
 * When inserting or updating a {@link CActiveRecord}, attribute values of
 * type TDbExpression will be directly put into the corresponding SQL statement
 * without escaping. A typical usage is that an attribute is set with 'NOW()'
 * expression so that saving the record would fill the corresponding column
 * with the current DB server timestamp.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: TDbExpression.php 2679 2009-06-15 07:49:42Z Christophe.Boulain $
 * @package System.Testing.Data.Schema
 * @since 1.0.2
 */
class TDbExpression extends TComponent
{
	/**
	 * @var string the DB expression
	 */
	public $expression;

	/**
	 * Constructor.
	 * @param string the DB expression
	 */
	public function __construct($expression)
	{
		parent::__construct();
		$this->expression=$expression;
	}

	/**
	 * String magic method
	 * @return string the DB expression
	 */
	public function __toString()
	{
		return $this->expression;
	}
}
