<?php

class THttpUtility
{
	private static $entityTable=null;

	public static function htmlEncode($s)
	{
		return htmlspecialchars($s);
	}

	public static function htmlDecode($s)
	{
		if(!self::$entityTable)
			self::buildEntityTable();
		return strtr($s,self::$entityTable);
	}

	private static function buildEntityTable()
	{
		self::$entityTable=array_flip(get_html_translation_table(HTML_ENTITIES,ENT_QUOTES));
	}

	public static function quoteJavaScriptString($js,$forUrl=false)
	{
		if($forUrl)
			return strtr($js,array('%'=>'%25',"\t"=>'\t',"\n"=>'\n',"\r"=>'\r','"'=>'\"','\''=>'\\\'','\\'=>'\\\\'));
		else
			return strtr($js,array("\t"=>'\t',"\n"=>'\n',"\r"=>'\r','"'=>'\"','\''=>'\\\'','\\'=>'\\\\'));
	}
}

?>