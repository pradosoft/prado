<?php
/**
 * TJavaScript class file
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.Javascripts
 */

/**
 * TJavaScript class.
 *
 * TJavaScript is a utility class containing commonly used javascript-related
 * functions.
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.Javascripts
 * @since 3.0
 */
class TJavaScript
{
	public static function renderScriptFiles($files)
	{
		$str='';
		foreach($files as $file)
			$str.='<script type="text/javascript" src="'.THttpUtility::htmlEncode($file)."\"></script>\n";
		return $str;
	}

	public static function renderScriptFile($file)
	{
		return '<script type="text/javascript" src="'.THttpUtility::htmlEncode($file)."\"></script>\n";
	}

	public static function renderScriptBlocks($scripts)
	{
		if(count($scripts))
			return "<script type=\"text/javascript\">\n/*<![CDATA[*/\n".implode("\n",$scripts)."\n/*]]>*/\n</script>\n";
		else
			return '';
	}

	public static function renderScriptBlock($script)
	{
		return "<script type=\"text/javascript\">\n/*<![CDATA[*/\n{$script}\n/*]]>*/\n</script>\n";
	}

	public static function renderArrayDeclarations($arrays)
	{
		if(count($arrays))
		{
			$str="<script type=\"text/javascript\">\n/*<![CDATA[*/\n";
			foreach($arrays as $name=>$array)
				$str.="var $name=new Array(".implode(',',$array).");\n";
			$str.="\n/*]]>*/\n</script>\n";
			return $str;
		}
		else
			return '';
	}

	public static function renderArrayDeclaration($array)
	{
		$str="<script type=\"text/javascript\">\n/*<![CDATA[*/\n";
		$str.="var $name=new Array(".implode(',',$array).");\n";
		$str.="\n/*]]>*/\n</script>\n";
		return $str;
	}

	public static function quoteString($js,$forUrl=false)
	{
		if($forUrl)
			return strtr($js,array('%'=>'%25',"\t"=>'\t',"\n"=>'\n',"\r"=>'\r','"'=>'\"','\''=>'\\\'','\\'=>'\\\\'));
		else
			return strtr($js,array("\t"=>'\t',"\n"=>'\n',"\r"=>'\r','"'=>'\"','\''=>'\\\'','\\'=>'\\\\'));
	}

	/**
	 * Encodes a PHP variable into javascript representation.
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
	 */
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
			return "'".self::quoteString($value)."'";
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

	public static function jsonEncode($value)
	{
		Prado::using('System.Web.Javascripts.TJSON');
		return TJSON::encode($value);
	}

	public static function jsonDecode($value)
	{
		Prado::using('System.Web.Javascripts.TJSON');
		return TJSON::decode($value);
	}
}

?>