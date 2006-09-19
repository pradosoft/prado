#!/usr/bin/env php
<?php

/**
 * Prado command line developer tools.
 */

if(!isset($_SERVER['argv']))
	die('Must be run from the command line');

require_once(dirname(__FILE__).'/prado.php');
class PradoShellApplication extends TApplication
{
	public function run()
	{
		$this->initApplication();
	}
}
class DummyHttpRequest extends THttpRequest
{
	protected function resolveRequest()
	{
	}
}

function __shell_print_var($shell,$var)
{
	if(!$shell->has_semicolon)
		echo Prado::varDump($var);
}

restore_exception_handler();

//run it;
PradoCommandLineInterpreter::run($_SERVER['argv']);
if(count($_SERVER['argv']) > 1 && strtolower($_SERVER['argv'][1])==='shell')
	include_once(dirname(__FILE__).'/3rdParty/PhpShell/php-shell-cmd.php');

class PradoCommandLineInterpreter
{
	protected $_actions=array(
	'PradoCommandLineCreateProject' => null,
	'PradoCommandLineCreateTests' => null,
	'PradoCommandLinePhpShell' => null
	);

	public function __construct()
	{
		foreach(array_keys($this->_actions) as $class)
			$this->_actions[$class] = new $class;
	}

	public static function run($args)
	{
		echo "Command line tools for Prado ".Prado::getVersion().".\n";

		if(count($args) > 1)
			array_shift($args);
		static $int;
		if(is_null($int))
			$int = new self;
		$valid = false;
		foreach($int->_actions as $class => $action)
		{
			if($action->isValidAction($args))
			{
				$valid |= $action->performAction($args);
				break;
			}
			else
			{
				$valid = false;
			}
		}
		if(!$valid)
			$int->printHelp();
	}

	public function printHelp()
	{
		echo "usage: php prado-cli.php action <parameter> [optional]\n";
		echo "example: php prado-cli.php -c mysite\n\n";
		echo "actions:\n";
		foreach($this->_actions as $action)
			echo $action->renderHelp();
	}
}

abstract class PradoCommandLineAction
{
	abstract public function performAction($args);

	protected function createDirectory($dir, $mask)
	{
		if(!is_dir($dir))
		{
			mkdir($dir);
			echo "creating $dir\n";
		}
		if(is_dir($dir))
			chmod($dir, $mask);
	}

	protected function createFile($filename, $content)
	{
		if(!is_file($filename))
		{
			file_put_contents($filename, $content);
			echo "creating $filename\n";
		}
	}

	public function isValidAction($args)
	{
		return strtolower($args[0]) === $this->action &&
				count($args)-1 >= count($this->parameters);
	}

	public function renderHelp()
	{
		$params = array();
		foreach($this->parameters as $v)
			$params[] = '<'.$v.'>';
		$parameters = join($params, ' ');
		$options = array();
		foreach($this->optional as $v)
			$options[] = '['.$v.']';
		$optional = (strlen($parameters) ? ' ' : ''). join($options, ' ');
		$description='';
		foreach(explode("\n", wordwrap($this->description,65)) as $line)
			$description .= '    '.$line."\n";
		return <<<EOD
  {$this->action} {$parameters}{$optional}
{$description}

EOD;
	}
}


class PradoCommandLineCreateProject extends PradoCommandLineAction
{
	protected $action = '-c';
	protected $parameters = array('directory');
	protected $optional = array();
	protected $description = 'Creates a Prado project skeleton for the given <directory>.';

	public function performAction($args)
	{
		$this->createNewPradoProject($args[1]);
		return true;
	}

	/**
	 * Functions to create new prado project.
	 */
	protected function createNewPradoProject($dir)
	{
		if(strlen(trim($dir)) == 0)
			return;

		$rootPath = realpath(dirname(trim($dir)));

		$basePath = $rootPath.'/'.basename($dir);
		$assetPath = $basePath.'/assets';
		$protectedPath  = $basePath.'/protected';
		$runtimePath = $basePath.'/protected/runtime';
		$pagesPath = $protectedPath.'/pages';

		$indexFile = $basePath.'/index.php';
		$htaccessFile = $protectedPath.'/.htaccess';
		$defaultPageFile = $pagesPath.'/Home.page';

		$this->createDirectory($basePath, 0755);
		$this->createDirectory($assetPath,0777);
		$this->createDirectory($protectedPath,0755);
		$this->createDirectory($runtimePath,0777);
		$this->createDirectory($pagesPath,0755);

		$this->createFile($indexFile, $this->renderIndexFile());
		$this->createFile($htaccessFile, $this->renderHtaccessFile());
		$this->createFile($defaultPageFile, $this->renderDefaultPage());
	}

	protected function renderIndexFile()
	{
		$framework = realpath(dirname(__FILE__)).'/prado.php';
return '<?php
$frameworkPath=\''.$framework.'\';

/** The directory checks may be removed if performance is required **/
$basePath=dirname(__FILE__);
$assetsPath=$basePath."/assets";
$runtimePath=$basePath."/protected/runtime";

if(!is_file($frameworkPath))
	die("Unable to find prado framework path $frameworkPath.");
if(!is_writable($assetsPath))
	die("Please make sure that the directory $assetsPath is writable by Web server process.");
if(!is_writable($runtimePath))
	die("Please make sure that the directory $runtimePath is writable by Web server process.");


require_once($frameworkPath);

$application=new TApplication;
$application->run();

?>';
	}

	protected function renderHtaccessFile()
	{
		return 'deny from all';
	}


	protected function renderDefaultPage()
	{
return <<<EOD
<h1>Welcome to Prado!</h1>
EOD;
	}
}

class PradoCommandLineCreateTests extends PradoCommandLineAction
{
	protected $action = '-t';
	protected $parameters = array('directory');
	protected $optional = array();
	protected $description = 'Create test fixtures in the given <directory>.';

	public function performAction($args)
	{
		$this->createTestFixtures($args[1]);
		return true;
	}

	protected function createTestFixtures($dir)
	{
		if(strlen(trim($dir)) == 0)
			return;

		$rootPath = realpath(dirname(trim($dir)));
		$basePath = $rootPath.'/'.basename($dir);

		$tests = $basePath.'/tests';
		$unit_tests = $tests.'/unit';
		$functional_tests = $tests.'/functional';

		$this->createDirectory($tests,0755);
		$this->createDirectory($unit_tests,0755);
		$this->createDirectory($functional_tests,0755);

		$unit_test_index = $tests.'/unit.php';
		$functional_test_index = $tests.'/functional.php';

		$this->createFile($unit_test_index, $this->renderUnitTestFixture());
		$this->createFile($functional_test_index, $this->renderFunctionalTestFixture());
	}

	protected function renderUnitTestFixture()
	{
		$tester = realpath(dirname(__FILE__).'/../tests/test_tools/unit_tests.php');
return '<?php

include_once \''.$tester.'\';

$app_directory = "../protected";
$test_cases = dirname(__FILE__)."/unit";

$tester = new PradoUnitTester($test_cases, $app_directory);
$tester->run(new HtmlReporter());

?>';
	}

	protected function renderFunctionalTestFixture()
	{
		$tester = realpath(dirname(__FILE__).'/../tests/test_tools/functional_tests.php');
return '<?php

include_once \''.$tester.'\';

$test_cases = dirname(__FILE__)."/functional";

$tester=new PradoFunctionalTester($test_cases);
$tester->run(new SimpleReporter());

?>';
	}

}

class PradoCommandLinePhpShell extends PradoCommandLineAction
{
	protected $action = 'shell';
	protected $parameters = array();
	protected $optional = array('directory');
	protected $description = 'Runs a PHP interactive interpreter. Initializes the Prado application in the given [directory].';

	public function performAction($args)
	{
		if(count($args) > 1)
			$this->createAndRunPradoApp($args);
		return true;
	}

	protected function createAndRunPradoApp($args)
	{
		$app_dir = realpath($args[1].'/protected/');
		if($app_dir !== false)
		{
			$app = new PradoShellApplication($app_dir);
			$app->setRequest(new DummyHttpRequest());
			$app->run();
			$dir = substr(str_replace(realpath('./'),'',$app_dir),1);

			echo '** Loaded Prado appplication in directory "'.$dir."\".\n";
		}
		else
			echo '** Unable to load Prado application in directory "'.$args[1]."\".\n";
	}
}



?>