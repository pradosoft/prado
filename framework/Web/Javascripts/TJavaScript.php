<?php
/**
 * TJavaScript class file
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TJavaScript.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.Javascripts
 */

/**
 * TJavaScript class.
 *
 * TJavaScript is a utility class containing commonly-used javascript-related
 * functions.
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Id: TJavaScript.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.Javascripts
 * @since 3.0
 */
class TJavaScript
{
	/**
	 * Renders a list of javascript files
	 * @param array URLs to the javascript files
	 * @return string rendering result
	 */
	public static function renderScriptFiles($files)
	{
		$str='';
		foreach($files as $file)
			$str.= self::renderScriptFile($file);
		return $str;
	}

	/**
	 * Renders a javascript file
	 * @param string URL to the javascript file
	 * @return string rendering result
	 */
	public static function renderScriptFile($file)
	{
		return '<script type="text/javascript" src="'.THttpUtility::htmlEncode($file)."\"></script>\n";
	}

	/**
	 * Renders a list of javascript blocks
	 * @param array javascript blocks
	 * @return string rendering result
	 */
	public static function renderScriptBlocks($scripts)
	{
		if(count($scripts))
			return "<script type=\"text/javascript\">\n/*<![CDATA[*/\n".implode("\n",$scripts)."\n/*]]>*/\n</script>\n";
		else
			return '';
	}

	/**
	 * Renders javascript block
	 * @param string javascript block
	 * @return string rendering result
	 */
	public static function renderScriptBlock($script)
	{
		return "<script type=\"text/javascript\">\n/*<![CDATA[*/\n{$script}\n/*]]>*/\n</script>\n";
	}

	/**
	 * Quotes a javascript string.
	 * After processing, the string is safely enclosed within a pair of
	 * quotation marks and can serve as a javascript string.
	 * @param string string to be quoted
	 * @return string the quoted string
	 */
	public static function quoteString($js)
	{
		return self::jsonEncode($js,JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_TAG);
	}

	/**
	 * @return Marks a string as a javascript function. Once marke, the string is considered as a
	 * raw javascript function that is not supposed to be encoded by {@link encode}
	 */
	public static function quoteJsLiteral($js)
	{
		if($js instanceof TJavaScriptLiteral)
			return $js;
		else
			return new TJavaScriptLiteral($js);
	}

	/**
	 * Deprecated, use {@link quoteJsLiteral} instead
	 */
	public static function quoteFunction($js)
	{
		return self::quoteJsLiteral($js);
	}

	/**
	 * @return boolean true if the parameter is marked as a javascript function, i.e. if it's considered as a
	 * raw javascript function that is not supposed to be encoded by {@link encode}
	 */
	public static function isJsLiteral($js)
	{
		return ($js instanceof TJavaScriptLiteral);
	}

	/**
	 * Deprecated, use {@link isJsLiteral} instead
	 */
	public static function isFunction($js)
	{
		return self::isJsLiteral($js);
	}

	/**
	 * Encodes a PHP variable into javascript representation.
	 *
	 * Example:
	 * <code>
	 * $options['onLoading'] = "doit";
	 * $options['onComplete'] = "more";
	 * echo TJavaScript::encode($options);
	 * //expects the following javascript code
	 * // {'onLoading':'doit','onComplete':'more'}
	 * </code>
	 *
	 * For higher complexity data structures use {@link jsonEncode} and {@link jsonDecode}
	 * to serialize and unserialize.
	 *
	 * @param mixed PHP variable to be encoded
	 * @param boolean whether the output is a map or a list.
	 * @since 3.1.5
	 * @param boolean wether to encode empty strings too. Default to false for BC.
	 * @return string the encoded string
	 */
	public static function encode($value,$toMap=true,$encodeEmptyStrings=false)
	{
		if(is_string($value))
			return self::quoteString($value);
		else if(is_bool($value))
			return $value?'true':'false';
		else if(is_array($value))
		{
			$results='';
			if(($n=count($value))>0 && array_keys($value)!==range(0,$n-1))
			{
				foreach($value as $k=>$v)
				{
					if($v!=='' || $encodeEmptyStrings)
					{
						if($results!=='')
							$results.=',';
						$results.="'$k':".self::encode($v,$toMap,$encodeEmptyStrings);
					}
				}
				return '{'.$results.'}';
			}
			else
			{
				foreach($value as $v)
				{
					if($v!=='' || $encodeEmptyStrings)
					{
						if($results!=='')
							$results.=',';
						$results.=self::encode($v,$toMap, $encodeEmptyStrings);
					}
				}
				return '['.$results.']';
			}
		}
		else if(is_integer($value))
			return "$value";
		else if(is_float($value))
		{
			switch($value)
			{
				case -INF:
					return 'Number.NEGATIVE_INFINITY';
					break;
				case INF:
					return 'Number.POSITIVE_INFINITY';
					break;
				default:
					$locale=localeConv();
					if($locale['decimal_point']=='.')
						return "$value";
					else
						return str_replace($locale['decimal_point'], '.', "$value");
					break;
			}
		}
		else if(is_object($value))
			if ($value instanceof TJavaScriptLiteral)
				return $value->toJavaScriptLiteral();
			else
				return self::encode(get_object_vars($value),$toMap);
		else if($value===null)
			return 'null';
		else
			return '';
	}

	/**
	 * Encodes a PHP variable into javascript string.
	 * This method invokes json_encode to perform the encoding.
	 * @param mixed variable to be encoded
	 * @return string encoded string
	 */
	public static function jsonEncode($value, $options = 0)
	{
		if (is_string($value) &&
			($g=Prado::getApplication()->getGlobalization(false))!==null &&
			strtoupper($enc=$g->getCharset())!='UTF-8')
			$value=iconv($enc, 'UTF-8', $value);
		$s = json_encode($value,$options);
		self::checkJsonError();
		return $s;
	}

	/**
	 * Decodes a javascript string into PHP variable.
	 * This method invokes json_decode to perform the decoding.
	 * @param string string to be decoded
	 * @param bool whether to convert returned objects to associative arrays
	 * @param int recursion depth
	 * @return mixed decoded variable
	 */
	public static function jsonDecode($value, $assoc = false, $depth = 512)
	{
		$s= json_decode($value, $assoc, $depth);
		self::checkJsonError();
		return $s;
	}
	
	private static function checkJsonError()
	{
		switch ($err = json_last_error())
		{
			case JSON_ERROR_NONE:
				return;
				break;
			case JSON_ERROR_DEPTH:
				$msg = 'Maximum stack depth exceeded';
				break;
			case JSON_ERROR_STATE_MISMATCH:
				$msg = 'Underflow or the modes mismatch';
				break;
			case JSON_ERROR_CTRL_CHAR:
				$msg = 'Unexpected control character found';
				break;
			case JSON_ERROR_SYNTAX:
				$msg = 'Syntax error, malformed JSON';
				break;
			case JSON_ERROR_UTF8:
				$msg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
				break;
			default:
				$msg = 'Unknown error';
				break;
		}
		throw new Exception("JSON error ($err): $msg");
	}

	/**
	 * Minimize the size of a javascript script.
	 * This method is based on Douglas Crockford's JSMin.
	 * @param string code that you want to minimzie 
	 * @return minimized version of the code
	 */
	public static function JSMin($code)
	{
		Prado::using('System.Web.Javascripts.JSMin');
		return JSMin::minify($code);
	}
}

