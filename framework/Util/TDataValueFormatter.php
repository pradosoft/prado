<?php
/**
 * TDataValueFormatter class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Util
 */

/**
 * TDataValueFormatter class
 *
 * TDataValueFormatter is a utility class that formats a data value
 * according to a format string.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Util
 * @since 3.0
 */
class TDataValueFormatter
{
	/**
	 * Formats the text value according to a format string.
	 * If the format string is empty, the original value is converted into
	 * a string and returned.
	 * If the format string starts with '#', the string is treated as a PHP expression
	 * within which the token '{0}' is translated with the data value to be formated.
	 * Otherwise, the format string and the data value are passed
	 * as the first and second parameters in {@link sprintf}.
	 * @param string format string
	 * @param mixed the data associated with the cell
	 * @param TComponent the context to evaluate the expression
	 * @return string the formatted result
	 */
	public static function format($formatString,$value,$context=null)
	{
		if($formatString==='')
			return TPropertyValue::ensureString($value);
		else if($formatString[0]==='#')
		{
			$expression=strtr(substr($formatString,1),array('{0}'=>'$value'));
			if($context instanceof TComponent)
				return $context->evaluateExpression($expression);
			else
			{
				try
				{
					if(eval("\$result=$expression;")===false)
						throw new Exception('');
					return $result;
				}
				catch(Exception $e)
				{
					throw new TInvalidOperationException('datavalueformatter_expression_invalid',$expression,$e->getMessage());
				}
			}
		}
		else
			return sprintf($formatString,$value);
	}
}

?>