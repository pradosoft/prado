<?php
/**
 * TDataFieldAccessor class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TDataFieldAccessor.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Util
 */

/**
 * TDataFieldAccessor class
 *
 * TDataFieldAccessor is a utility class that provides access to a field of some data.
 * The accessor attempts to obtain the field value in the following order:
 * - If the data is an array, then the field is treated as an array index
 *   and the corresponding element value is returned;
 * - If the data is a TMap or TList object, then the field is treated as a key
 *   into the map or list, and the corresponding value is returned.
 * - If the data is an object, the field is treated as a property or sub-property
 *   defined with getter methods. For example, if the object has a method called
 *   getMyValue(), then field 'MyValue' will retrieve the result of this method call.
 *   If getMyValue() returns an object which contains a method getMySubValue(),
 *   then field 'MyValue.MySubValue' will return that method call result.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: TDataFieldAccessor.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Util
 * @since 3.0
 */
class TDataFieldAccessor
{
	/**
	 * Evaluates the data value at the specified field.
	 * - If the data is an array, then the field is treated as an array index
	 *   and the corresponding element value is returned; the field name can also include
	 *   dots to access subarrays. For example a field named 'MyField.MySubField' will 
	 *   first try to access $data['MyField.MySubField'], then try $data['MyField']['MySubField'].
	 * - If the data is a TMap or TList object, then the field is treated as a key
	 *   into the map or list, and the corresponding value is returned.
	 * - If the data is an object, the field is treated as a property or sub-property
	 *   defined with getter methods. For example, if the object has a method called
	 *   getMyValue(), then field 'MyValue' will retrieve the result of this method call.
	 *   If getMyValue() returns an object which contains a method getMySubValue(),
	 *   then field 'MyValue.MySubValue' will return that method call result.
	 * @param mixed data containing the field value, can be an array, TMap, TList or object.
	 * @param mixed field value
	 * @return mixed value at the specified field
	 * @throws TInvalidDataValueException if field or data is invalid
	 */
	public static function getDataFieldValue($data,$field)
	{
		try
		{
			if(is_array($data) || ($data instanceof ArrayAccess))
			{
				if(isset($data[$field]))
					return $data[$field];

				$tmp = $data;
				foreach (explode(".", $field) as $f)
				    $tmp = $tmp[$f];
				return $tmp;
			}
			else if(is_object($data))
			{
				if(strpos($field,'.')===false)  // simple field
				{
					if(method_exists($data, 'get'.$field))
						return call_user_func(array($data,'get'.$field));
					else
						return $data->{$field};
				}
				else // field in the format of xxx.yyy.zzz
				{
					$object=$data;
					foreach(explode('.',$field) as $f)
						$object = TDataFieldAccessor::getDataFieldValue($object, $f);
					return $object;
				}
			}
		}
		catch(Exception $e)
		{
			throw new TInvalidDataValueException('datafieldaccessor_datafield_invalid',$field,$e->getMessage());
		}
		throw new TInvalidDataValueException('datafieldaccessor_data_invalid',$field);
	}
}
