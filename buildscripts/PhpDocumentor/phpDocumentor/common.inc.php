<?php
//
// +------------------------------------------------------------------------+
// | phpDocumentor                                                          |
// +------------------------------------------------------------------------+
// | Copyright (c) 2000-2003 Joshua Eichorn, Gregory Beaver                 |
// | Email         jeichorn@phpdoc.org, cellog@phpdoc.org                   |
// | Web           http://www.phpdoc.org                                    |
// | Mirror        http://phpdocu.sourceforge.net/                          |
// | PEAR          http://pear.php.net/package-info.php?pacid=137           |
// +------------------------------------------------------------------------+
// | This source file is subject to version 3.00 of the PHP License,        |
// | that is available at http://www.php.net/license/3_0.txt.               |
// | If you did not receive a copy of the PHP license and are unable to     |
// | obtain it through the world-wide-web, please send a note to            |
// | license@php.net so we can mail you a copy immediately.                 |
// +------------------------------------------------------------------------+
//

/**
 * Common variables/functions used by other files in phpDocumentor
 * @package phpDocumentor
 * @filesource
 */
/** phpDocumentor version */
if ('@VER@' != '@'.'VER@')
{
    define("PHPDOCUMENTOR_VER","@VER@");
} else {
    define("PHPDOCUMENTOR_VER","1.3.0RC4");
}
/** phpDocumentor version */
define("PHPDOCUMENTOR_WEBSITE","http://www.phpdoc.org");
define('SMART_PATH_DELIMITER', DIRECTORY_SEPARATOR ); // set the correct path delimiter
define('tokenizer_ext', extension_loaded('tokenizer') && version_compare(phpversion(),"4.3.0",">="));
// we just replace all the \ with / so that we can just operate on /
define('PATH_DELIMITER', '/' ); // set the correct path delimiter
define('PHPDOCUMENTOR_WINDOWS',substr(PHP_OS, 0, 3) == 'WIN');
define('_IN_PHP5', phpversion() == '5.0.0RC1-dev' || phpversion() == '5.0.0RC2-dev' ||
    version_compare(phpversion(), '5.0.0', 'ge'));
if (/*@donotremove@*/0)
{
    if (_IN_PHP5) {
        require_once 'PhpDocumentor/phpDocumentor/clone5.inc.php';
    } else {
        require_once 'PhpDocumentor/phpDocumentor/clone.inc.php';
    }
} else {
    if (_IN_PHP5) {
        require_once dirname(__FILE__) . '/clone5.inc.php';
    } else {
        require_once dirname(__FILE__) . '/clone.inc.php';
    }
}
if (isset($_SERVER['argv'])) {
    $argv = $_SERVER['argv'];
    $argc = $_SERVER['argc'];
}

/** used in phpdoc.php and new_phpdoc.php */
function phpDocumentor_ConfigFileList($directory)
{
    $ret = array();
    if (@is_dir($directory))
    {
        $ret = array();
        $d = @dir($directory); // thanks to Jason E Sweat (jsweat@users.sourceforge.net) for fix
        while($d && $entry=$d->read())
        {
            $getentry = false;
            if (strcmp($entry,".") != 0 && strcmp($entry,"..") != 0)
            {
                if (substr($entry,0,1) != ".") $getentry = true;
            }
            if ($getentry == true)
            {
                if (strpos($entry,'.ini'))
                if (is_file($directory . PATH_DELIMITER . $entry))
                {
                    $ret[] = str_replace('.ini','',$entry);
                }
            }
        }
        if ($d) $d->close();
    } else
    {
    }
    return $ret;
}


/**
 * Parse an .ini file
 * 
 * Works like {@link parse_ini_file}, except it will take a section like:
 *
 * <pre>
 * [MYVAR]
 * value1
 * value2
 * value3
 * </pre>
 *
 * and return an associative array(MYVAR => array(value1, value2, value3))
 * @return array
 * @param string full path to the ini file
 * @param boolean add an associative index for each section [in brackets]
 */
function phpDocumentor_parse_ini_file($filename, $process_sections = false)
{
    $ini_array = array();
    $sec_name = "";
    $lines = @file($filename);
    if( !$lines) return $lines;
    foreach($lines as $line)
    {
        // code by Greg Beaver, ignore comments
        if ($line[0] == ';') continue;
        $line = trim($line);
        
        if($line == "")
        {
            continue;
        }
        if($line[0] == "[" && $line[strlen($line) - 1] == "]")
        {
            $sec_name = substr($line, 1, strlen($line) - 2);
        } else
        {
            if (strpos($line,"="))
            {
                $pos = strpos($line, "=");
                $property = trim(substr($line, 0, $pos));
                // code by Greg Beaver
                if (substr($property, 0, 1) == '"' && substr($property, -1) == '"') {
                    $property = stripcslashes(substr($property,1,count($property) - 2));
                }
                $value = trim(substr($line, $pos + 1));
                if ($value == 'false') $value = false;
                if ($value == 'true') $value = true;
                if (substr($value, 0, 1) == '"' && substr($value,-1) == '"')
                {
                    $value = stripcslashes(substr($value,1,count($value) - 2));
                }
                // done additions
                
                if($process_sections)
                {
                    if ($sec_name != '')
                    $ini_array[$sec_name][$property] = $value;
                    else
                    $ini_array[$property] = $value;
                } else
                {
                    $ini_array[$property] = $value;
                }
            } else
            {
                // code by Greg Beaver
                if (trim($line[0]) == ';') continue;
                if($process_sections)
                {
                    $ini_array[$sec_name][] = trim($line);
                }
                // done additions
            }
        }
    }
    return $ini_array;
}


if (!function_exists('array_key_exists'))
{
/** @ignore */
function array_key_exists($key, $search)
{
    foreach($search as $keys => $nul)
    {
        if ($key == $keys) return true;
    }
    return false;
}
}
if (!function_exists('is_a'))
{
/** @ignore */
function is_a($classname, $classquery)
{
    $father = get_parent_class($classname);
    if (strtolower($father) == strtolower($classquery))
    {
        return true;
    } elseif (!empty($father))
    {
        return is_a($father, $classquery);
    } else
    {
        return false;
    }
}
}


/** Debugging output */
function debug($s)
{
    echo "$s\n";
}

/**
 * Returns a formatted var_dump for debugging purposes.
 * @param string $s string to display
 * @param mixed $v variable to display with var_dump()
 * @param mixed $v... unlimited number of additional variables to display with var_dump()
 */
function fancy_debug($s,$v)
{
    if (isset($GLOBALS['dont_debug']) && $GLOBALS['dont_debug']) return;
    debug($s."\n\n</pre><blockquote><pre>");
    var_dump($v);
    if (func_num_args()>2)
    {
        for($i=2;$i<func_num_args();$i++)
        {
            $a = func_get_arg($i);
//            debug(" ");
            var_dump($a);
        }
    }
    debug("</pre></blockquote><pre>\n\n");
}

/**
 * Returns a lower-cased version of get_class for PHP 5
 *
 * get_class() returns case as declared in the file in PHP 5
 */
function phpDocumentor_get_class($object)
{
    return strtolower(get_class($object));
}

?>