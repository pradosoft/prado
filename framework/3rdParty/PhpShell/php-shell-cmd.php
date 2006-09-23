<?php
@ob_end_clean();
error_reporting(E_ALL);
set_time_limit(0);

/**
* the wrapper around the PHP_Shell class
*
* - load extensions
* - set default error-handler
* - add exec-hooks for the extensions
*
* To keep the namespace clashing between shell and your program
* as small as possible all public variables and functions from
* the shell are prefixed with __shell:
*
* - $__shell is the object of the shell
*   can be read, this is the shell object itself, don't touch it
* - $__shell_retval is the return value of the eval() before
*   it is printed
*   can't be read, but overwrites existing vars with this name
* - $__shell_exception is the catched Exception on Warnings, Notices, ..
*   can't be read, but overwrites existing vars with this name
*/

//try loading it from PEAR
@require_once('PHP/Shell.php');
if(class_exists('PHP_Shell', false))
{
	require_once "PHP/Shell/Extensions/Autoload.php";
	require_once "PHP/Shell/Extensions/AutoloadDebug.php";
	require_once "PHP/Shell/Extensions/Colour.php";
	require_once "PHP/Shell/Extensions/ExecutionTime.php";
	require_once "PHP/Shell/Extensions/InlineHelp.php";
	require_once "PHP/Shell/Extensions/VerbosePrint.php";
	require_once "PHP/Shell/Extensions/LoadScript.php";
}
else
{
	require_once(dirname(__FILE__)."/PHP/Shell.php");
	require_once(dirname(__FILE__)."/PHP/Shell/Extensions/Autoload.php");
	require_once(dirname(__FILE__)."/PHP/Shell/Extensions/AutoloadDebug.php");
	require_once(dirname(__FILE__)."/PHP/Shell/Extensions/Colour.php");
	require_once(dirname(__FILE__)."/PHP/Shell/Extensions/ExecutionTime.php");
	require_once(dirname(__FILE__)."/PHP/Shell/Extensions/InlineHelp.php");
	require_once(dirname(__FILE__)."/PHP/Shell/Extensions/VerbosePrint.php");
	require_once(dirname(__FILE__)."/PHP/Shell/Extensions/LoadScript.php");
}

/**
* default error-handler
*
* Instead of printing the NOTICE or WARNING from php we wan't the turn non-FATAL
* messages into exceptions and handle them in our own way.
*
* you can set your own error-handler by createing a function named
* __shell_error_handler
*
* @param integer $errno Error-Number
* @param string $errstr Error-Message
* @param string $errfile Filename where the error was raised
* @param interger $errline Line-Number in the File
* @param mixed $errctx ...
*/
function __shell_default_error_handler($errno, $errstr, $errfile, $errline, $errctx) {
    ## ... what is this errno again ?
    if ($errno == 2048) return;

    throw new Exception(sprintf("%s:%d\r\n%s", $errfile, $errline, $errstr));
}

set_error_handler("__shell_default_error_handler");

$__shell = new PHP_Shell();
$__shell_exts = PHP_Shell_Extensions::getInstance();
$__shell_exts->registerExtensions(array(
    "options"        => PHP_Shell_Options::getInstance(), /* the :set command */

    "autoload"       => new PHP_Shell_Extensions_Autoload(),
    "autoload_debug" => new PHP_Shell_Extensions_AutoloadDebug(),
    "colour"         => new PHP_Shell_Extensions_Colour(),
    "exectime"       => new PHP_Shell_Extensions_ExecutionTime(),
    "inlinehelp"     => new PHP_Shell_Extensions_InlineHelp(),
    "verboseprint"   => new PHP_Shell_Extensions_VerbosePrint(),
    "loadscript"     => new PHP_Shell_Extensions_LoadScript()
));

$f = <<<EOF
PHP-Shell - Version %s%s
(c) 2006, Jan Kneschke <jan@kneschke.de>

>> use '?' to open the inline help

EOF;

printf($f,
    $__shell->getVersion(),
    $__shell->hasReadline() ? ', with readline() support' : '');
unset($f);

print $__shell_exts->colour->getColour("default");
while($__shell->input()) {
    if ($__shell_exts->autoload->isAutoloadEnabled() && !function_exists('__autoload')) {
        /**
        * default autoloader
        *
        * If a class doesn't exist try to load it by guessing the filename
        * class PHP_Shell should be located in PHP/Shell.php.
        *
        * you can set your own autoloader by defining __autoload() before including
        * this file
        *
        * @param string $classname name of the class
        */

        function __autoload($classname) {
            global $__shell_exts;

            if ($__shell_exts->autoload_debug->isAutoloadDebug()) {
                print str_repeat(".", $__shell_exts->autoload_debug->incAutoloadDepth())." -> autoloading $classname".PHP_EOL;
            }
            include_once str_replace('_', '/', $classname).'.php';
            if ($__shell_exts->autoload_debug->isAutoloadDebug()) {
                print str_repeat(".", $__shell_exts->autoload_debug->decAutoloadDepth())." <- autoloading $classname".PHP_EOL;
            }
        }
    }

    try {
        $__shell_exts->exectime->startParseTime();
        if ($__shell->parse() == 0) {
            ## we have a full command, execute it

            $__shell_exts->exectime->startExecTime();

            $__shell_retval = eval($__shell->getCode());
            if (isset($__shell_retval)) {
                print $__shell_exts->colour->getColour("value");

                if (function_exists("__shell_print_var")) {
                    __shell_print_var($__shell,$__shell_retval, $__shell_exts->verboseprint->isVerbose());
                } else {
                    var_export($__shell_retval);
                }
            }
            ## cleanup the variable namespace
            unset($__shell_retval);
            $__shell->resetCode();
        }
    } catch(Exception $__shell_exception) {
        print $__shell_exts->colour->getColour("exception");
        printf('%s (code: %d) got thrown'.PHP_EOL, get_class($__shell_exception), $__shell_exception->getCode());
        print $__shell_exception;

        $__shell->resetCode();

        ## cleanup the variable namespace
        unset($__shell_exception);
    }
    print $__shell_exts->colour->getColour("default");
    $__shell_exts->exectime->stopTime();
    if ($__shell_exts->exectime->isShow()) {
        printf(" (parse: %.4fs, exec: %.4fs)",
            $__shell_exts->exectime->getParseTime(),
            $__shell_exts->exectime->getExecTime()
        );
    }
}

print $__shell_exts->colour->getColour("reset");

