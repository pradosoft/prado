<?php

/**
 * TJavascript class file. Javascript utilties, converts basic PHP types into
 * appropriate javascript types.
 *
 * Example:
 * <code>
 * $options['onLoading'] = "doit";
 * $options['onComplete'] = "more";
 * $js = new TJavascriptSerializer($options);
 * echo $js->toMap();
 * //expects the following javascript code
 * // {'onLoading':'doit','onComplete':'more'}
 * </code>
 *
 * For higher complexity data structures use TJSON to serialize and unserialize.
 *
 * Namespace: System.Web.UI
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.3 $  $Date: 2005/11/10 23:43:26 $
 * @package System.Web.UI
 */
class TJavascriptSerializer
{
	protected $data;
	
	/**
	 * Serialize php data type into equivalent javascript type.
	 * @param mixed data to seralize
	 */
	public function __construct($data)
	{
		$this->data = $data;	
	}
	
	/**
	 * Converts data to javascript data string
	 * @return string javascript equivalent
	 */
	public function toJavascript($strict=false,$toMap=true)
	{
		$type = 'to_'.gettype($this->data);
		return $this->$type($strict,$toMap);
	}
	
	/**
	 * Coverts PHP arrays (only the array values) into javascript array.
	 * @param boolean if true empty string and empty array will be converted
	 * @return string javascript array as string.
	 */
	public function toList($strict=false)
	{
		return $this->to_array($strict);
	}

	/**
	 * Coverts PHP arrays (both key and value) into javascript objects.
	 * @param boolean if true empty string and empty array will be converted
	 * @return string javascript object as string.
	 */
	public function toMap($strict=false)
	{
		return $this->to_array($strict, true);
	}

	protected function to_array($strict=false,$toMap=false)
	{
		$results = array();
		foreach($this->data as $k => $v)
		{
			if($strict || (!$strict && $v !== '' && $v !== array()))
			{
				$serializer = new TJavascriptSerializer($v);
				$result = $serializer->toJavascript($strict,$toMap);
				$results[] = $toMap ? "'{$k}':$result" : $result;
			}
		}
		$brackets = $toMap ? array('{','}') : array('[',']');
		return $brackets[0].implode(',', $results).$brackets[1];
	}
	
	protected function to_object($strict=false,$toMap=false)
	{
		if($this->data instanceof TComponent)
			return $this->to_component($strict=false,$toMap=false);
	
		$serializer = new TJavascriptSerializer(get_object_vars($this->data));
		return $serializer->toMap($strict,$toMap);
	}
	
	protected function to_component($strict=false,$toMap=false)
	{
		throw new TException("component object too complex to serialize");
	}
	
	protected function to_boolean()
	{
		return $this->data ? 'true' : 'false';
	}

	protected function to_integer()
	{
		return "{$this->data}";
	}

	protected function to_double()
	{
		if($this->data === -INF)
			return 'Number.NEGATIVE_INFINITY';
		if($this->data === INF)
			return 'Number.POSITIVE_INFINITY';	
		return "{$this->data}";
	}

	/**
	 * Escapes string to javascript strings. If data to convert is string
	 * and is bracketed with {} or [], it is assumed that the data
	 * is already a javascript object or array and no further coversion is done.
	 */
	protected function to_string()
	{
		//ignore strings surrounded with {} or [], assume they are list or map
		if(strlen($this->data)>1)
		{
			$first = $this->data[0]; $last = $this->data[strlen($this->data)-1];		
			if($first == '[' && $last == ']' ||
				($first == '{' && $last == '}'))
				return $this->data;
		}
		return "'".preg_replace("/\r\n/", '\n', addslashes($this->data))."'";
	}

	protected function to_null()
	{
		return 'null';
	}
}

?>