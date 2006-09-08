#!/usr/bin/php
<?php

/**
 * Prado command line developer tools. 
 */

if(!isset($_SERVER['argv'])) 
	die('Must be run from the command line');

//command line options.
$options['create'] = array('c:', 'create=', '<directory>', 'create a new project <directory>');
$options['tests'] = array('t', 'create-tests', '', 'create unit and function test fixtures');

$console = new ConsoleOptions($options, $_SERVER['argv']);

if($console->hasOptions())
{
	if($dir = $console->getOption('create'))
		create_new_prado_project($dir);
	if($console->getOption('tests'))
		create_test_fixtures($dir);
}
else
{
	$details = $console->renderOptions();
echo <<<EOD
Usage: php prado-cli.php [-c] <directory>
Options:
$details
Example: php prado-cli.php -c ./example1

EOD;
}
	
/**
 * Functions to create new prado project.
 */

function create_new_prado_project($dir)
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
	
	$tests = $basePath.'/tests';
	$unit_tests = $tests.'/unit';
	$functional_tests = $tests.'/functional';
	
	create_directory($basePath, 0755);
	create_directory($assetPath,0777);
	create_directory($protectedPath,0755);
	create_directory($runtimePath,0777);
	create_directory($pagesPath,0755);
	
	create_file($indexFile, render_index_file());
	create_file($htaccessFile, render_htaccess_file());
	create_file($defaultPageFile, render_default_page());
}

function create_test_fixtures($dir)
{
	if(strlen(trim($dir)) == 0)
		return;
		
	$rootPath = realpath(dirname(trim($dir)));
	$basePath = $rootPath.'/'.basename($dir);
		
	$tests = $basePath.'/tests';
	$unit_tests = $tests.'/unit';
	$functional_tests = $tests.'/functional';
	
	create_directory($tests,0755);
	create_directory($unit_tests,0755);
	create_directory($functional_tests,0755);
	
	$unit_test_index = $tests.'/unit.php';
	$functional_test_index = $tests.'/functional.php';
	
	create_file($unit_test_index, render_unit_test_fixture());
	create_file($functional_test_index, render_functional_test_fixture());
}

function render_unit_test_fixture()
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

function render_functional_test_fixture()
{
	$tester = realpath(dirname(__FILE__).'/../tests/test_tools/functional_tests.php');
return '<?php

include_once \''.$tester.'\';

$test_cases = dirname(__FILE__)."/functional";

$tester=new PradoFunctionalTester($test_cases);
$tester->run(new SimpleReporter());

?>';
}

function create_directory($dir, $mask)
{
	if(!is_dir($dir))
	{
		mkdir($dir);
		echo "creating $dir\n";
	}	
	if(is_dir($dir))
		chmod($dir, $mask);
}	

function create_file($filename, $content)
{
	if(!is_file($filename))
	{
		file_put_contents($filename, $content);
		echo "creating $filename\n";
	}
}

function render_index_file()
{
	$framework = realpath(dirname(__FILE__)).'/prado.php';
return '<?php

$basePath=dirname(__FILE__);
$frameworkPath=\''.$framework.'\';
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

function render_htaccess_file()
{
	return 'deny from all';
}


function render_default_page()
{
return <<<EOD
<h1>Welcome to Prado!</h1>
EOD;
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Andrei Zmievski <andrei@php.net>                             |
// +----------------------------------------------------------------------+
//
// $Id$
 
/**
 * Command-line options parsing class.
 *
 * @author Andrei Zmievski <andrei@php.net>
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 */
class ConsoleOptions 
{	
	private $_short_options;
	private $_long_options;
	private $_options;
	private $_args;
	private $_values;
	
	/**
	 * @param array list of options with the following format
	 * <tt>option['name'] = array('short', 'long', 'parameter', 'description')</tt>
	 * see @link setOptions} for details on 'short' and 'long' string details.
	 */
	public function __construct($options, $args)
	{
		$short = '';
		$long = array();
		foreach($options as $option)
		{
			$short .= $option[0];
			$long[] = $option[1];
		}
		$this->setOptions($short,$long);
		$this->_options = $options;
		$this->_args = $args;
	}
		
	/**
    * The second parameter is a string of allowed short options. Each of the
    * option letters can be followed by a colon ':' to specify that the option
    * requires an argument, or a double colon '::' to specify that the option
    * takes an optional argument.
    *
    * The third argument is an optional array of allowed long options. The
    * leading '--' should not be included in the option name. Options that
    * require an argument should be followed by '=', and options that take an
    * option argument should be followed by '=='.
    * 
    * @param string $short_options  specifies the list of allowed short options
    * @param array  $long_options   specifies the list of allowed long options
 	*/
	protected function setOptions($short_options, $long_options=null)
	{
		$this->_short_options = $short_options;
		$this->_long_options = $long_options;
	}
	
	/**
	 * @return string list of options and its descriptions
	 */
	public function renderOptions()
	{
		$options = array();
		$descriptions = array();
		$max = 0;
		foreach($this->_options as $option)
		{
			$short = str_replace(':','',$option[0]);
			$long = str_replace('=','',$option[1]);
			$desc = $option[2];
			$details = " -{$short}, --{$long} {$desc}";
			if(($len = strlen($details)) > $max)
				$max = $len;
			$options[] = $details;
			$descriptions[] = $option[3];
		}
		
		$content = '';
		for($i = 0, $k = count($options); $i < $k; $i++)
			$content .= str_pad($options[$i],$max+3).$descriptions[$i]."\n";
		return $content;
	}
	
	/**
	 * @param string argument name
	 * @return string argument value
	 */
	public function getOption($name)
	{
		if(is_null($this->_values))
			$this->_values = $this->getNamedOptions();
				
		return isset($this->_values[$name]) ? $this->_values[$name] : null;
	}
	
	/**
	 * @return array list of all options given.
	 */
	public function getOptions()
	{
		if(is_null($this->_values))
			$this->_values = $this->getNamedOptions();
		return $this->_values;
	}
	
	/**
	 * @return boolean true if one or more options are given.
	 */
	public function hasOptions()
	{
		if(is_null($this->_values))
			$this->_values = $this->getNamedOptions();
		return count($this->_values) > 0;
	}
	
	/**
	 * Parse the options from args into named arguements.
	 */
	protected function getNamedOptions()
	{
		$options = array();
		$values = $this->parseOptions($this->_args);
		foreach($values[0] as $value)
		{
			foreach($this->_options as $name => $option)
			{
				$short = str_replace(':', '', $option[0]);
				$long = str_replace('=', '', $option[1]);
				if($short == $value[0] || $long == $value[0])
					$options[$name] = is_null($value[1]) ? true : $value[1];
			}
		}
		return $options;
	}
	
    /**
     * Gets the command-line options.
     *
     * The parameter to this function should be the list of command-line
     * arguments without the leading reference to the running program.
     *
     * The return value is an array of two elements: the list of parsed
     * options and the list of non-option command-line arguments. Each entry in
     * the list of parsed options is a pair of elements - the first one
     * specifies the option, and the second one specifies the option argument,
     * if there was one.
     *
     * Long and short options can be mixed.
     *
     * @param array  $args           an array of command-line arguments
     * @return array two-element array containing the list of parsed options and
     * the non-option arguments
     */
    public function parseOptions($args)
    {
        if (empty($args)) 
            return array(array(), array());

		$short_options = $this->_short_options;
		$long_options = $this->_long_options;

        $opts     = array();
        $non_opts = array();

        settype($args, 'array');

        if ($long_options) 
            sort($long_options);

        if (isset($args[0]{0}) && $args[0]{0} != '-') 
			array_shift($args);

        reset($args);
        
		while (list($i, $arg) = each($args)) 
		{

            /* The special element '--' means explicit end of
               options. Treat the rest of the arguments as non-options
               and end the loop. */
            if ($arg == '--') {
                $non_opts = array_merge($non_opts, array_slice($args, $i + 1));
                break;
            }

            if ($arg{0} != '-' || (strlen($arg) > 1 && $arg{1} == '-' && !$long_options)) 
			{
                $non_opts = array_merge($non_opts, array_slice($args, $i));
                break;
            } 
			elseif (strlen($arg) > 1 && $arg{1} == '-') 
                $this->parseLongOption(substr($arg, 2), $long_options, $opts, $args);
			else 
                $this->parseShortOption(substr($arg, 1), $short_options, $opts, $args);
        }

        return array($opts, $non_opts);
    }

    private function parseShortOption($arg, $short_options, &$opts, &$args)
    {
        for ($i = 0; $i < strlen($arg); $i++) 
		{
            $opt = $arg{$i};
            $opt_arg = null;

            /* Try to find the short option in the specifier string. */
            if (($spec = strstr($short_options, $opt)) === false || $arg{$i} == ':')
                throw new Exception("Console_Getopt: unrecognized option -- $opt");

            if (strlen($spec) > 1 && $spec{1} == ':') 
			{
                if (strlen($spec) > 2 && $spec{2} == ':') 
				{
                    if ($i + 1 < strlen($arg)) 
					{
                        /* Option takes an optional argument. Use the remainder of
                           the arg string if there is anything left. */
                        $opts[] = array($opt, substr($arg, $i + 1));
                        break;
                    }
                } 
				else 
				{
                    /* Option requires an argument. Use the remainder of the arg
                       string if there is anything left. */
                    if ($i + 1 < strlen($arg)) 
					{
                        $opts[] = array($opt,  substr($arg, $i + 1));
                        break;
                    } 
					else if (list(, $opt_arg) = each($args))
                        /* Else use the next argument. */;
                    else
                        throw new Exception("Console_Getopt: option requires an argument -- $opt");
                }
            }

            $opts[] = array($opt, $opt_arg);
        }
    }

    private function parseLongOption($arg, $long_options, &$opts, &$args)
    {
        @list($opt, $opt_arg) = explode('=', $arg);
        $opt_len = strlen($opt);

        for ($i = 0; $i < count($long_options); $i++) 
		{
            $long_opt  = $long_options[$i];
            $opt_start = substr($long_opt, 0, $opt_len);

            /* Option doesn't match. Go on to the next one. */
            if ($opt_start != $opt)
                continue;

            $opt_rest  = substr($long_opt, $opt_len);

            /* Check that the options uniquely matches one of the allowed
               options. */
            if ($opt_rest != '' && $opt{0} != '=' &&
                $i + 1 < count($long_options) &&
                $opt == substr($long_options[$i+1], 0, $opt_len)) 
			{
                throw new Exception("Console_Getopt: option --$opt is ambiguous");
            }

            if (substr($long_opt, -1) == '=') 
			{
                if (substr($long_opt, -2) != '==') 
				{
                    /* Long option requires an argument.
                       Take the next argument if one wasn't specified. */;
                    if (!strlen($opt_arg) && !(list(, $opt_arg) = each($args))) 
                        throw new Exception("Console_Getopt: option --$opt requires an argument");
                }
            } 
			else if ($opt_arg) 
                throw new Exception("Console_Getopt: option --$opt doesn't allow an argument");

            $opts[] = array($opt, $opt_arg);
            return;
        }

        throw new Exception("Console_Getopt: unrecognized option --$opt");
    }
}


?>