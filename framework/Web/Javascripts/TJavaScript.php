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
class TJavaScript
{
	public static function quoteJavaScriptString($js,$forUrl=false)
	{
		if($forUrl)
			return strtr($js,array('%'=>'%25',"\t"=>'\t',"\n"=>'\n',"\r"=>'\r','"'=>'\"','\''=>'\\\'','\\'=>'\\\\'));
		else
			return strtr($js,array("\t"=>'\t',"\n"=>'\n',"\r"=>'\r','"'=>'\"','\''=>'\\\'','\\'=>'\\\\'));
	}

	public static function trimJavaScriptString($js)
	{
		if($js!=='' && $js!==null)
		{
			$js=trim($js);
			if(($pos=strpos($js,'javascript:'))===0)
				$js=substr($js,11);
			$js=rtrim($js,';').';';
		}
		return $js;
	}

	public static function encode($value,$toMap=true)
	{
		if(is_string($value))
		{
			if(($n=strlen($value))>2)
			{
				$first=$value[0];
				$last=$value[$n-1];
				if(($first==='[' && $last===']') || ($first==='{' && $last==='}'))
					return $value;
			}
			return "'".self::quoteJavaScriptString($value)."'";
		}
		else if(is_bool($value))
			return $value?'true':'false';
		else if(is_array($value))
		{
			$results=array();
			if($toMap)
			{
				foreach($value as $k=>$v)
					$results[]="'{$k}':".self::encode($v,$toMap);
				return '{'.implode(',',$results).'}';
			}
			else
			{
				foreach($value as $k=>$v)
					$results[]=self::encode($v,$toMap);
				return '['.implode(',',$results).']';
			}
		}
		else if(is_integer($value))
			return "$value";
		else if(is_float($value))
		{
			if($value===-INF)
				return 'Number.NEGATIVE_INFINITY';
			else if($value===INF)
				return 'Number.POSITIVE_INFINITY';
			else
				return "$value";
		}
		else if(is_object($value))
			return self::encode(get_object_vars($this->data),$toMap);
		else if($value===null)
			return 'null';
		else
			return '';
	}

	public static function encodeJSON($value)
	{
		Prado::using('System.Web.Javascripts.TJSON');
		return TJSON::encode($value);
	}

	public static function decodeJSON($value)
	{
		Prado::using('System.Web.Javascripts.TJSON');
		return TJSON::decode($value);
	}
}

?>