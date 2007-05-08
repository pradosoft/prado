<?php

/**
 * Combines multiple javascript files and serve up as gzip if possible.
 * Allowable scripts and script dependencies can be specified in a "packages.php" file
 * with the following format. This "packages.php" is optional, if absent the filenames
 * without ".js" extension are used.
 *
 * <code>
 * <?php
 *  $packages = array(
 *     'package1' => array('file1.js', 'file2.js'),
 *     'package2' => array('file3.js', 'file4.js'));
 *
 *  $dependencies = array(
 *     'package1' => array('package1'),
 *     'package2' => array('package1', 'package2')); //package2 requires package1 first.
 * </code>
 *
 * To serve up 'package1', specify the url, a maxium of 25 packages separated with commas is allows.
 *
 * clientscripts.php?js=package1
 *
 * for 'package2' (automatically resolves 'package1') dependency
 *
 * clientscripts.php?js=package2
 *
 * The scripts comments are removed and whitespaces removed appropriately. The
 * scripts may be served as zipped if the browser and php server allows it. Cache
 * headers are also sent to inform the browser and proxies to cache the file.
 * Moreover, the post-processed (comments removed and zipped) are saved in this
 * current directory for the next requests.
 *
 * If the url contains the parameter "mode=debug", the comments are not removed
 * and headers invalidating the cache are sent. In debug mode, the script can still
 * be zipped if the browser and server supports it.
 *
 * E.g. clientscripts.php?js=package2&mode=debug
 *
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.Web.Javascripts
 * @since 3.1
 */

@error_reporting(E_ERROR | E_WARNING | E_PARSE);

function get_client_script_files()
{
	$package_file = dirname(__FILE__).'/packages.php';
	if(is_file($package_file))
		return get_package_files($package_file, get_script_requests());
	else
		return get_javascript_files(get_script_requests());
}

/**
 * @param array list of requested libraries
 */
function get_script_requests($max=25)
{
	$param = isset($_GET['js']) ? $_GET['js'] : '';
	if(preg_match('/([a-zA-z0-9\-_])+(,[a-zA-z0-9\-_]+)*/', $param))
		return array_unique(explode(',', $param, $max));
	return array();
}

/**
 * @param string packages.php filename
 * @param array packages requests
 */
function get_package_files($package_file, $request)
{
	list($packages, $dependencies) = include($package_file);
	if(!(is_array($packages) && is_array($dependencies)))
	{
		error_log('Prado client script: invalid package file '.$package_file);
		return array();
	}
	$result = array();
	$found = array();
	foreach($request as $library)
	{
		if(isset($dependencies[$library]))
		{
			foreach($dependencies[$library] as $dep)
			{
				if(isset($found[$dep]))
					continue;
				$result = array_merge($result, (array)$packages[$dep]);
				$found[$dep]=true;
			}
		}
		else
			error_log('Prado client script: no such javascript library "'.$library.'"');
	}
	return $result;
}

/**
 * @param string requested javascript files
 * @array array list of javascript files.
 */
function get_javascript_files($request)
{
	$result = array();
	foreach($request as $file)
		$result[] = $file.'.js';
	return $result;
}

/**
 * @param array list of javascript files.
 * @return string combined the available javascript files.
 */
function combine_javascript($files)
{
	$content = '';
	$base = dirname(__FILE__);
	foreach($files as $file)
	{
		$filename = $base.'/'.$file;
		if(is_file($filename)) //relies on get_client_script_files() for security
			$content .= "\x0D\x0A".file_get_contents($filename); //add CR+LF
		else
			error_log('Prado client script: missing file '.$filename);
	}
	return $content;
}

/**
 * @param string javascript code
 * @param array files names
 * @return string javascript code without comments.
 */
function minify_javascript($content, $files)
{
	return jsm_minify($content);
}

/**
 * @param boolean true if in debug mode.
 */
function is_debug_mode()
{
	return isset($_GET['mode']) && $_GET['mode']==='debug';
}

/**
 * @param string javascript code
 * @param string gzip code
 */
function gzip_content($content)
{
	return gzencode($content, 9, FORCE_GZIP);
}

/**
 * @param string javascript code.
 * @param string filename
 */
function save_javascript($content, $filename)
{
	file_put_contents($filename, $content);
	if(supports_gzip_encoding())
		file_put_contents($filename.'.gz', gzip_content($content));
}

/**
 * @param string comprssed javascript file to be read
 * @param string javascript code, null if file is not found.
 */
function get_saved_javascript($filename)
{
	if(supports_gzip_encoding())
		$filename .= '.gz';
	if(is_file($filename))
		return file_get_contents($filename);
	else
		error_log('Prado client script: no such file '.$filename);
}

/**
 * @return string compressed javascript file name.
 */
function compressed_js_filename()
{
	$files = get_client_script_files();
	if(count($files) > 0)
	{
		$filename = sprintf('%x',crc32(implode(',',($files))));
		return dirname(__FILE__).'/clientscript_'.$filename.'.js';
	}
}

/**
 * @param boolean true to strip comments from javascript code
 * @return string javascript code
 */
function get_javascript_code($minify=false)
{
	$files = get_client_script_files();
	if(count($files) > 0)
	{
		$content = combine_javascript($files);
		if($minify)
			return minify_javascript($content, $files);
		else
			return $content;
	}
}

/**
 * Prints headers to serve javascript
 */
function print_headers()
{
	$expiresOffset = is_debug_mode() ? -10000 : 3600 * 24 * 10; //no cache
	header("Content-type: text/javascript");
	header("Vary: Accept-Encoding");  // Handle proxies
	header("Expires: " . @gmdate("D, d M Y H:i:s", @time() + $expiresOffset) . " GMT");
	if(($enc = supports_gzip_encoding()) !== null)
		header("Content-Encoding: " . $enc);
}

/**
 * @return string 'x-gzip' or 'gzip' if php supports gzip and browser supports gzip response, null otherwise.
 */
function supports_gzip_encoding()
{
	if(isset($_GET['gzip']) && $_GET['gzip']==='false')
		return false;

	if (isset($_SERVER['HTTP_ACCEPT_ENCODING']))
	{
		$encodings = explode(',', strtolower(preg_replace("/\s+/", "", $_SERVER['HTTP_ACCEPT_ENCODING'])));
		$allowsZipEncoding = in_array('gzip', $encodings) || in_array('x-gzip', $encodings) || isset($_SERVER['---------------']);
		$hasGzip = function_exists('ob_gzhandler');
		$noZipBuffer = !ini_get('zlib.output_compression');
		$noZipBufferHandler = ini_get('output_handler') != 'ob_gzhandler';

		if ( $allowsZipEncoding && $hasGzip && $noZipBuffer && $noZipBufferHandler)
			$enc = in_array('x-gzip', $encodings) ? "x-gzip" : "gzip";
		return $enc;
	}
}

/**
 * JSMin: the following jsm_* functions are adapted from JSMin_lib.php
 * written by Douglas Crockford. Below is the original copyright notice:
 *
 * PHP adaptation of JSMin, published by Douglas Crockford as jsmin.c, also based
 * on its Java translation by John Reilly.
 *
 * Permission is hereby granted to use the PHP version under the same conditions
 * as jsmin.c, which has the following notice :
 *
 * ----------------------------------------------------------------------------
 *
 * Copyright (c) 2002 Douglas Crockford  (www.crockford.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 */

$jsm_in='';
$jsm_out='';
$jsm_length=0;
$jsm_pos=0;
$jsm_c1='';
$jsm_c2='';

function jsm_minify($jsCode)
{
	global $jsm_in, $jsm_out, $jsm_length, $jsm_pos, $jsm_c1, $jsm_c2;

	$jsm_in=$jsCode;
	$jsm_out='';
	$jsm_length=strlen($jsCode);
	$jsm_pos=0;

    // Initialize A and run the first (minimal) action
    $jsm_c1="\n";
    jsm_next();

    // Proceed all the way to the end of the input file
	while($jsm_c1!==false)
	{
		if($jsm_c1===' ')
		{
			if(jsm_isAlphaNum($jsm_c2))
                jsm_write($jsm_c1);
            else
                jsm_buffer();
		}
		else if($jsm_c1==="\n")
		{
			if($jsm_c2===' ')
				jsm_next();
			else if(jsm_isAlphaNum($jsm_c2) || $jsm_c2==='{' || $jsm_c2==='[' || $jsm_c2==='(' || $jsm_c2==='+' || $jsm_c2==='-')
				jsm_write($jsm_c1);
			else
				jsm_buffer();
		}
		else
		{
			if($jsm_c2===' ')
			{
				if(jsm_isAlphaNum($jsm_c1))
					jsm_write($jsm_c1);
				else
					jsm_next();
			}
			else if($jsm_c2==="\n")
			{
				if(jsm_isAlphaNum($jsm_c1) || $jsm_c1==='}' || $jsm_c1===']' || $jsm_c1===')' || $jsm_c1==='+' || $jsm_c1==='-' || $jsm_c1==='"' || $jsm_c1==='\'')
					jsm_write($jsm_c1);
				else
					jsm_next();
			}
			else
				jsm_write($jsm_c1);
		}
	}

    return $jsm_out;
}

function jsm_write()
{
	global $jsm_c1;

	jsm_put($jsm_c1);
	jsm_buffer();
}

function jsm_buffer()
{
	global $jsm_c1, $jsm_c2;

    $tmpA=$jsm_c1=$jsm_c2;

    // Treating a string as a single char : outputting it whole
    // Note that the string-opening char (" or ') is memorized in B

    if($tmpA==='\'' || $tmpA==='"')
    {
	    while (true)
	    {
			// Output string contents
	        jsm_put($tmpA);

	        // Get next character, watching out for termination of the current string,
	        // new line & co (then the string is not terminated !), or a backslash
	        // (upon which the following char is directly output to serve the escape mechanism)

	        $tmpA=$jsm_c1=jsm_get();

	        if($tmpA===$jsm_c2) // String terminated
	            break; // from while(true)

	        if(ord($tmpA) <= ord("\n"))
	            die('Unterminated string literal');

	        if($tmpA==='\\')
	        {
	            // Escape next char immediately
	            jsm_put($tmpA);
	            $tmpA=$jsm_c1=jsm_get();
	        }
	    }
	}

    jsm_next();
}

function jsm_next()
{
	global $jsm_c1, $jsm_c2;

    // Get the next B
    $jsm_c2=jsm_get2();

    // Special case of recognising regular expressions (beginning with /) that are
    // preceded by '(', ',' or '='
    $tmpA=$jsm_c1;

    if($jsm_c2==='/' && ($tmpA==='(' || $tmpA===',' || $tmpA==='='))
    {
        // Output the two successive chars
        jsm_put($tmpA);
        jsm_put($jsm_c2);

        // Look for the end of the RE literal, watching out for escaped chars or a control /
        // end of line char (the RE literal then being unterminated !)
        while (true)
        {
		    $tmpA=$jsm_c1=jsm_get();

            if($tmpA==='/')
                break; // from while(true)

            if($tmpA==='\\')
            {
                // Escape next char immediately
                jsm_put($tmpA);
                $tmpA=$jsm_c1=jsm_get();
            }
            else if(ord($tmpA) <= ord("\n"))
                die('Unterminated regexp literal');

            // Output RE characters
            jsm_put($tmpA);
        }

        // Move forward after the RE literal
        $jsm_c2=jsm_get2();
    }
}

function jsm_get()
{
	global $jsm_pos, $jsm_length, $jsm_in;

    // Get next input character and advance position in file
	if($jsm_pos < $jsm_length)
	{
		$c=$jsm_in[$jsm_pos++];
	    if($c==="\n" || ord($c) >= ord(' '))
	        return $c;
		else if($c==="\r")
	        return "\n";
		else
		    return ' ';
	}
	else
		return false;

}

function jsm_peek()
{
	global $jsm_pos, $jsm_length, $jsm_in;
	if($jsm_pos < $jsm_length)
		return $jsm_in[$jsm_pos];
	else
		return false;
}

function jsm_put($c)
{
	global $jsm_out;
	$jsm_out .= $c;
}

/**
 * Get the next character from the input stream, excluding comments.
 */
function jsm_get2()
{
    // Get next char from input, translated if necessary
    if(($c=jsm_get())!=='/')
    	return $c;

    // Look ahead : a comment is two slashes or slashes followed by asterisk (to be closed)
	if(($c=jsm_peek())==='/')
	{
        // Comment is up to the end of the line
        while (true)
        {
            $c=jsm_get();
            if(ord($c)<=ord("\n"))
                return $c;
        }
	}
	else if($c==='*')
	{
        // Comment is up to comment close.
        // Might not be terminated, if we hit the end of file.
        while(true)
        {
            // N.B. not using switch() because of having to test EOF with ===
            $c=jsm_get();
            if($c==='*')
            {
			    // Comment termination if the char ahead is a slash
                if(jsm_peek()==='/')
                {
                    // Advance again and make into a single space
                    jsm_get();
                    return ' ';
                }
            }
            else if($c===false)
                die('Unterminated comment');
        }
	}
	else
		return '/';
}

/**
 * Indicates whether a character is alphanumeric or _, $, \ or non-ASCII.
 */
function jsm_isAlphaNum($c)
{
    return ctype_alnum($c) || $c==='_' || $c==='$' || $c==='\\' || ord($c)>126;
}

/************** OUTPUT *****************/

if(count(get_script_requests()) > 0)
{
	if(is_debug_mode())
	{
		if(($code = get_javascript_code()) !== null)
		{
			print_headers();
			echo supports_gzip_encoding() ? gzip_content($code) : $code;
		}
	}
	else
	{
		if(($filename = compressed_js_filename()) !== null)
		{
			if(!is_file($filename))
				save_javascript(get_javascript_code(true), $filename);
			print_headers();
			echo get_saved_javascript($filename);
		}
	}
}

?>