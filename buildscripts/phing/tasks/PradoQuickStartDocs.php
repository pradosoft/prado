<?php
require_once 'phing/Task.php';

require_once(dirname(__FILE__).'/../../chmbuilder/ChmQuickstartBuilder.php');
include(dirname(__FILE__).'/../../../framework/PradoBase.php');
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

include(dirname(__FILE__).'/../../../framework/prado.php');

/**
 * Task to run phpDocumentor for PRADO API docs.
 */
class PradoQuickStartDocs extends Task
{
	private $base_dir;

	private $destdir;

	private $page;

	/**
	 * Set the destination directory for the generated documentation
	 */
	function setOutput(PhingFile $destdir)
	{
		$this->destdir = $destdir;
	}

	function setPages($page)
	{
		$this->page = $page;
	}

	/**
	 * Main entrypoint of the task
	 */
	function main()
	{
		$output = $this->destdir->getAbsolutePath();
		$base = dirname(__FILE__).'/../../../demos/quickstart/protected/';
		error_reporting(0);
		$quickstart= new ChmQuickstartBuilder($base,$output);

		foreach(preg_split('/\s*[, ]+\s*/', $this->page) as $page)
		{
			$file = str_replace(array('/','.page'), array('_','.html'),$page);
			$this->log("Parsing $page");
			file_put_contents($output.'/'.$file, $this->parsePage($quickstart,$page));
			$this->log("Writing $file");
		}
	}

	protected function parsePage($quickstart, $page)
	{
		$_GET['page'] = str_replace(array('/','.page'),array('.',''),$page);
		$_GET['notheme'] = 'true';
		$content = $quickstart->parseHtmlContent($quickstart->getApplicationContent());
		//hide prado specific content
		$content = str_replace('<body>', '<style type="text/css">.prado-specific {display:none;}</style><body>', $content);
		return $content;
	}

}

?>