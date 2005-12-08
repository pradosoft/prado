<?php

class THttpUtility
{
	private static $_entityTable=null;

	public static function htmlEncode($s)
	{
		return htmlspecialchars($s);
	}

	public static function htmlDecode($s)
	{
		if(!self::$_entityTable)
			self::buildEntityTable();
		return strtr($s,self::$_entityTable);
	}

	private static function buildEntityTable()
	{
		self::$_entityTable=array_flip(get_html_translation_table(HTML_ENTITIES,ENT_QUOTES));
	}

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
}

?>