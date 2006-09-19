#!/usr/bin/env php
<?php

/**
 * Prado command line developer tools.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 */

if(!isset($_SERVER['argv']) || php_sapi_name()!=='cli')
	die('Must be run from the command line');

require_once(dirname(__FILE__).'/prado.php');

//stub application class
class PradoShellApplication extends TApplication
{
	public function run()
	{
		$this->initApplication();
	}
}

restore_exception_handler();

//register action classes
PradoCommandLineInterpreter::getInstance()->addActionClass('PradoCommandLineCreateProject');
PradoCommandLineInterpreter::getInstance()->addActionClass('PradoCommandLineCreateTests');
PradoCommandLineInterpreter::getInstance()->addActionClass('PradoCommandLinePhpShell');
PradoCommandLineInterpreter::getInstance()->addActionClass('PradoCommandLineUnitTest');

//run it;
PradoCommandLineInterpreter::getInstance()->run($_SERVER['argv']);

//run PHP shell
if(count($_SERVER['argv']) > 1 && strtolower($_SERVER['argv'][1])==='shell')
{
	function __shell_print_var($shell,$var)
	{
		if(!$shell->has_semicolon) echo Prado::varDump($var);
	}
	include_once(dirname(__FILE__).'/3rdParty/PhpShell/php-shell-cmd.php');
}


/**************** END CONFIGURATION **********************/

/**
 * PradoCommandLineInterpreter Class
 *
 * Command line interface, configures the action classes and dispatches the command actions.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @since 3.0.5
 */
class PradoCommandLineInterpreter
{
	/**
	 * @var array command action classes
	 */
	protected $_actions=array();

	/**
	 * @param string action class name
	 */
	public function addActionClass($class)
	{
		$this->_actions[$class] = new $class;
	}

	/**
	 * @return PradoCommandLineInterpreter static instance
	 */
	public static function getInstance()
	{
		static $instance;
		if(is_null($instance))
			$instance = new self;
		return $instance;
	}

	/**
	 * Dispatch the command line actions.
	 * @param array command line arguments
	 */
	public function run($args)
	{
		echo "Command line tools for Prado ".Prado::getVersion().".\n";

		if(count($args) > 1)
			array_shift($args);
		$valid = false;
		foreach($this->_actions as $class => $action)
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
			$this->printHelp();
	}

	/**
	 * Print command line help, default action.
	 */
	public function printHelp()
	{
		echo "usage: php prado-cli.php action <parameter> [optional]\n";
		echo "example: php prado-cli.php -c mysite\n\n";
		echo "actions:\n";
		foreach($this->_actions as $action)
			echo $action->renderHelp();
	}
}

/**
 * Base class for command line actions.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @since 3.0.5
 */
abstract class PradoCommandLineAction
{
	/**
	 * Execute the action.
	 * @param array command line parameters
	 * @return boolean true if action was handled
	 */
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

	protected function initializePradoApplication($directory)
	{
		$app_dir = realpath($directory.'/protected/');
		if($app_dir !== false)
		{
			$app = new PradoShellApplication($app_dir);
			$app->run();
			$dir = substr(str_replace(realpath('./'),'',$app_dir),1);

			echo '** Loaded Prado appplication in directory "'.$dir."\".\n";
		}
		else
			echo '** Unable to load Prado application in directory "'.$directory."\".\n";
	}

}

/**
 * Create a Prado project skeleton, including directories and files.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @since 3.0.5
 */
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

/**
 * Creates test fixtures for a Prado application.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @since 3.0.5
 */
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

/**
 * Creates and run a Prado application in a PHP Shell.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @since 3.0.5
 */
class PradoCommandLinePhpShell extends PradoCommandLineAction
{
	protected $action = 'shell';
	protected $parameters = array();
	protected $optional = array('directory');
	protected $description = 'Runs a PHP interactive interpreter. Initializes the Prado application in the given [directory].';

	public function performAction($args)
	{
		if(count($args) > 1)
			$this->initializePradoApplication($args[1]);
		return true;
	}
}

/**
 * Runs unit test cases.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @since 3.0.5
 */
class PradoCommandLineUnitTest extends PradoCommandLineAction
{
	protected $action = 'test';
	protected $parameters = array('directory');
	protected $optional = array('testcase ...');
	protected $description = 'Runs all unit test cases in the given <directory>. Use [testcase] option to run specific test cases.';

	protected $matches = array();

	public function performAction($args)
	{
		$dir = realpath($args[1]);
		if($dir !== false)
			$this->runUnitTests($dir,$args);
		else
			echo '** Unable to find directory "'.$args[1]."\".\n";
		return true;
	}

	protected function initializeTestRunner()
	{
		$TEST_TOOLS = realpath(dirname(__FILE__).'/../tests/test_tools/');

		require_once($TEST_TOOLS.'/simpletest/unit_tester.php');
		require_once($TEST_TOOLS.'/simpletest/web_tester.php');
		require_once($TEST_TOOLS.'/simpletest/mock_objects.php');
		require_once($TEST_TOOLS.'/simpletest/reporter.php');
	}

	protected function runUnitTests($dir, $args)
	{
		$app_dir = $this->getAppDir($dir);
		if($app_dir !== false)
			$this->initializePradoApplication($app_dir.'/../');

		$this->initializeTestRunner();
		$test_dir = $this->getTestDir($dir);
		if($test_dir !== false)
		{
			$test =$this->getUnitTestCases($test_dir,$args);
			$running_dir = substr(str_replace(realpath('./'),'',$test_dir),1);
			echo 'Running unit tests in directory "'.$running_dir."\":\n";
			$test->run(new TextReporter());
		}
		else
		{
			$running_dir = substr(str_replace(realpath('./'),'',$dir),1);
			echo '** Unable to find test directory "'.$running_dir.'/unit" or "'.$running_dir.'/tests/unit".'."\n";
		}
	}

	protected function getAppDir($dir)
	{
		$app_dir = realpath($dir.'/protected');
		if($app_dir !== false)
			return $app_dir;
		return realpath($dir.'/../protected');
	}

	protected function getTestDir($dir)
	{
		$test_dir = realpath($dir.'/unit');
		if($test_dir !== false)
			return $test_dir;
		return realpath($dir.'/tests/unit/');
	}

	protected function getUnitTestCases($dir,$args)
	{
		$matches = null;
		if(count($args) > 2)
			$matches = array_slice($args,2);
		$test=new GroupTest(' ');
		$this->addTests($test,$dir,true,$matches);
		$test->setLabel(implode(' ',$this->matches));
		return $test;
	}

	protected function addTests($test,$path,$recursive=true,$match=null)
	{
		$dir=opendir($path);
		while(($entry=readdir($dir))!==false)
		{
			if(is_file($path.'/'.$entry) && (preg_match('/[^\s]*test[^\s]*\.php/', strtolower($entry))))
			{
				if($match==null||($match!=null && $this->hasMatch($match,$entry)))
					$test->addTestFile($path.'/'.$entry);
			}
			if($entry!=='.' && $entry!=='..' && $entry!=='.svn' && is_dir($path.'/'.$entry) && $recursive)
				$this->addTests($test,$path.'/'.$entry,$recursive,$match);
		}
		closedir($dir);
	}

	protected function hasMatch($match,$entry)
	{
		$file = strtolower(substr($entry,0,strrpos($entry,'.')));
		foreach($match as $m)
		{
			if(strtolower($m) === $file)
			{
				$this->matches[] = $m;
				return true;
			}
		}
		return false;
	}
}

?>