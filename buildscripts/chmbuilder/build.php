<?php

$ROOT = dirname(__FILE__);

//page root location
$base = realpath($ROOT.'/../../demos/quickstart/protected/');
$output_dir = realpath($ROOT.'/../../build/docs');
$classData = realpath($ROOT.'/../classtree/classes.data');
$classDocBase = realpath($ROOT.'/classes/');

//-------------- END CONFIG ------------------

if(!isset($isChild))
	$isChild = false;

$toc_file = $base.'/controls/TopicList.tpl';

$pages = include($ROOT.'/../texbuilder/pages.php');

include($ROOT.'/ChmQuickstartBuilder.php');
include($ROOT.'/../../framework/PradoBase.php');
class Prado extends PradoBase
{
	protected static $app;

	public static function setApplication($application)
	{
		self::$app=$application;
	}

	public static function getApplication()
	{
		return self::$app;
	}

	public static function setPathOfAlias($alias,$path)
	{
		$aliases = self::getPathAliases();
		if(!isset($aliases[$alias]))
			parent::setPathOfAlias($alias,$path);
	}
}

include($ROOT.'/../../framework/prado.php');

if($isChild)
{
	$classBuilder = new ClassDocBuilder($classDocBase,$output_dir);
	$classBuilder->buildDoc($argv[1]);
}
else
{
	$pages['Control Reference : Standard Controls'][] = 'Controls/Standard.page';


	$quickstart= new ChmQuickstartBuilder($base,$output_dir);
	$quickstart->buildDoc($pages);

	//move class data to protected data directory for prado app.
	$classFile = $ROOT.'/classes/Data/classes.data';
	if(is_file($classData) && !is_file($classFile))
		copy($classData, $classFile);
	$classes = unserialize(file_get_contents($classFile));

	$classBuilder = new ClassDocBuilder($classDocBase,$output_dir);

	//use child process to build doc, otherwise it consumes too much memory
	$child_builder = realpath($ROOT.'/build_child.php');
	foreach($classes as $class =>$data)
	{
		passthru('php '.$child_builder.' '.$class);
	}

	$classBuilder->parseBasePage();

	$toc = new HTMLHelpTOCBuilder();
	$toc->buildToc($toc_file,$output_dir,array_keys($classes));
}

?>