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
			$content .= file_get_contents($filename);
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
	$jsMin = new JSMin($content, false);
	try
	{
		return $jsMin->minify();
	}
	catch (Exception $e)
	{
		error_log('Prado client script: unable to strip javascript comments in one or more files in "'.implode(', ', $files).'"');
		return $content;
	}
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

define('JSMIN_AS_LIB', true);

/**
* JSMin_lib.php (for PHP 4, 5)
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
*
* The Software shall be used for Good, not Evil.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*/

/**
* Version of this PHP translation.
*/

define('JSMIN_VERSION', '0.2');

/**
* How fgetc() reports an End Of File.
* N.B. : use === and not == to test the result of fgetc() ! (see manual)
*/

define('EOF', FALSE);

/**
* Some ASCII character ordinals.
* N.B. : PHP identifiers are case-insensitive !
*/

define('ORD_NL', ord("\n"));
define('ORD_space', ord(' '));
define('ORD_cA', ord('A'));
define('ORD_cZ', ord('Z'));
define('ORD_a', ord('a'));
define('ORD_z', ord('z'));
define('ORD_0', ord('0'));
define('ORD_9', ord('9'));

/**
* Generic exception class related to JSMin.
*/
class JSMinException extends Exception {
}

/**
* A JSMin exception indicating that a file provided for input or output could not be properly opened.
*/

class FileOpenFailedJSMinException extends JSMinException {
}

/**
* A JSMin exception indicating that an unterminated comment was encountered in input.
*/

class UnterminatedCommentJSMinException extends JSMinException {
}

/**
* A JSMin exception indicatig that an unterminated string literal was encountered in input.
*/

class UnterminatedStringLiteralJSMinException extends JSMinException {
}

/**
* A JSMin exception indicatig that an unterminated regular expression lieteral was encountered in input.
*/

class UnterminatedRegExpLiteralJSMinException extends JSMinException {
}

/**
 * Constant describing an {@link action()} : Output A. Copy B to A. Get the next B.
 */

define ('JSMIN_ACT_FULL', 1);

/**
 * Constant describing an {@link action()} : Copy B to A. Get the next B. (Delete A).
 */

define ('JSMIN_ACT_BUF', 2);

/**
 * Constant describing an {@link action()} : Get the next B. (Delete B).
 */

define ('JSMIN_ACT_IMM', 3);

/**
* Main JSMin application class.
*
* Example of use :
*
* $jsMin = new JSMin(...input..., ...output...);
* $jsMin->minify();
*
* Do not specify input and/or output (or default to '-') to use stdin and/or stdout.
*/

class JSMin {

    /**
     * The input stream, from which to read a JS file to minimize. Obtained by fopen().
     * NB: might be a string instead of a stream
     * @var SplFileObject | string
     */
    var $in;

    /**
     * The output stream, in which to write the minimized JS file. Obtained by fopen().
     * NB: might be a string instead of a stream
     * @var SplFileObject | string
     */
    var $out;

    /**
     * Temporary I/O character (A).
     * @var string
     */
    var $theA;

    /**
     * Temporary I/O character (B).
     * @var string
     */
    var $theB;

	/** variables used for string-based parsing **/
	var $inLength = 0;
	var $inPos = 0;
	var $isString = false;

    /**
     * Indicates whether a character is alphanumeric or _, $, \ or non-ASCII.
     *
     * @param   string      $c  The single character to test.
     * @return  boolean     Whether the char is a letter, digit, underscore, dollar, backslash, or non-ASCII.
     */
    function isAlphaNum($c) {

        // Get ASCII value of character for C-like comparisons

        $a = ord($c);

        // Compare using defined character ordinals, or between PHP strings
        // Note : === is micro-faster than == when types are known to be the same

        return
            ($a >= ORD_a && $a <= ORD_z) ||
            ($a >= ORD_0 && $a <= ORD_9) ||
            ($a >= ORD_cA && $a <= ORD_cZ) ||
            $c === '_' || $c === '$' || $c === '\\' || $a > 126
        ;
    }

    /**
     * Get the next character from the input stream.
     *
     * If said character is a control character, translate it to a space or linefeed.
     *
     * @return  string      The next character from the specified input stream.
     * @see     $in
     * @see     peek()
     */
    function get() {

        // Get next input character and advance position in file
		if ($this->isString) {
			if ($this->inPos < $this->inLength) {
				$c = $this->in[$this->inPos];
				++$this->inPos;
			}
			else {
				return EOF;
			}
		}
		else
	        $c = $this->in->fgetc();

        // Test for non-problematic characters

        if ($c === "\n" || $c === EOF || ord($c) >= ORD_space) {
            return $c;
        }

        // else
        // Make linefeeds into newlines

        if ($c === "\r") {
            return "\n";
        }

        // else
        // Consider space

        return ' ';
    }

    /**
     * Get the next character from the input stream, without gettng it.
     *
     * @return  string      The next character from the specified input stream, without advancing the position
     *                      in the underlying file.
     * @see     $in
     * @see     get()
     */
    function peek() {

		if ($this->isString) {
			if ($this->inPos < $this->inLength) {
				$c = $this->in[$this->inPos];
			}
			else {
				return EOF;
			}
		}
		else {
	        // Get next input character

	        $c = $this->in->fgetc();

	        // Regress position in file

	        $this->in->fseek(-1, SEEK_CUR);

	        // Return character obtained
	    }

        return $c;
    }

	/**
	 * Adds a char to the output steram / string
	 * @see $out
	 */
	function put($c)
	{
		if ($this->isString) {
			$this->out .= $c;
		}
		else {
			$this->out->fwrite($c);
		}
	}

    /**
     * Get the next character from the input stream, excluding comments.
     *
     * {@link peek()} is used to see if a '/' is followed by a '*' or '/'.
     * Multiline comments are actually returned as a single space.
     *
     * @return  string  The next character from the specified input stream, skipping comments.
     * @see     $in
     */
    function next() {

        // Get next char from input, translated if necessary

        $c = $this->get();

        // Check comment possibility

        if ($c == '/') {

            // Look ahead : a comment is two slashes or slashes followed by asterisk (to be closed)

            switch ($this->peek()) {

                case '/' :

                    // Comment is up to the end of the line
                    // TOTEST : simple $this->in->fgets()

                    while (true) {

                        $c = $this->get();

                        if (ord($c) <= ORD_NL) {
                            return $c;
                        }
                    }

                case '*' :

                    // Comment is up to comment close.
                    // Might not be terminated, if we hit the end of file.

                    while (true) {

                        // N.B. not using switch() because of having to test EOF with ===

                        $c = $this->get();

                        if ($c == '*') {

                            // Comment termination if the char ahead is a slash

                            if ($this->peek() == '/') {

                                // Advance again and make into a single space

                                $this->get();
                                return ' ';
                            }
                        }
                        else if ($c === EOF) {

                            // Whoopsie
                            throw new UnterminatedCommentJSMinException();
                        }
                    }

                default :

                    // Not a comment after all

                    return $c;
            }
        }

        // No risk of a comment

        return $c;
    }

    /**
     * Do something !
     *
     * The action to perform is determined by the argument :
     *
     * JSMin::ACT_FULL : Output A. Copy B to A. Get the next B.
     * JSMin::ACT_BUF  : Copy B to A. Get the next B. (Delete A).
     * JSMin::ACT_IMM  : Get the next B. (Delete B).
     *
     * A string is treated as a single character. Also, regular expressions are recognized if preceded
     * by '(', ',' or '='.
     *
     * @param   int     $action     The action to perform : one of the JSMin::ACT_* constants.
     */
    function action($action) {

        // Choice of possible actions
        // Note the frequent fallthroughs : the actions are decrementally "long"

        switch ($action) {

            case JSMIN_ACT_FULL :

                // Write A to output, then fall through

                $this->put($this->theA);

            case JSMIN_ACT_BUF : // N.B. possible fallthrough from above

                // Copy B to A

                $tmpA = $this->theA = $this->theB;

                // Treating a string as a single char : outputting it whole
                // Note that the string-opening char (" or ') is memorized in B

                if ($tmpA == '\'' || $tmpA == '"') {

                    while (true) {

                        // Output string contents

                        $this->put($tmpA);

                        // Get next character, watching out for termination of the current string,
                        // new line & co (then the string is not terminated !), or a backslash
                        // (upon which the following char is directly output to serve the escape mechanism)

                        $tmpA = $this->theA = $this->get();

                        if ($tmpA == $this->theB) {

                            // String terminated

                            break; // from while(true)
                        }

                        // else

                        if (ord($tmpA) <= ORD_NL) {

                            // Whoopsie

                            throw new UnterminatedStringLiteralJSMinException();
                        }

                        // else

                        if ($tmpA == '\\') {

                            // Escape next char immediately

                            $this->put($tmpA);
                            $tmpA = $this->theA = $this->get();
                        }
                    }
                }

            case JSMIN_ACT_IMM : // N.B. possible fallthrough from above

                // Get the next B

                $this->theB = $this->next();

                // Special case of recognising regular expressions (beginning with /) that are
                // preceded by '(', ',' or '='

                $tmpA = $this->theA;

                if ($this->theB == '/' && ($tmpA == '(' || $tmpA == ',' || $tmpA == '=')) {

                    // Output the two successive chars

                    $this->put($tmpA);
                    $this->put($this->theB);

                    // Look for the end of the RE literal, watching out for escaped chars or a control /
                    // end of line char (the RE literal then being unterminated !)

                    while (true) {

                        $tmpA = $this->theA = $this->get();

                        if ($tmpA == '/') {

                            // RE literal terminated

                            break; // from while(true)
                        }

                        // else

                        if ($tmpA == '\\') {

                            // Escape next char immediately

                            $this->put($tmpA);
                            $tmpA = $this->theA = $this->get();
                        }
                        else if (ord($tmpA) <= ORD_NL) {

                            // Whoopsie

                            throw new UnterminatedRegExpLiteralJSMinException();
                        }

                        // Output RE characters

                        $this->put($tmpA);
                    }

                    // Move forward after the RE literal

                    $this->theB = $this->next();
                }

            break;
            default :
				throw new JSMinException('Expected a JSMin::ACT_* constant in action().');
        }
    }

    /**
     * Run the JSMin application : minify some JS code.
     *
     * The code is read from the input stream, and its minified version is written to the output one.
     * In case input is a string, minified vesrions is also returned by this function as string.
     * That is : characters which are insignificant to JavaScript are removed, as well as comments ;
     * tabs are replaced with spaces ; carriage returns are replaced with linefeeds, and finally most
     * spaces and linefeeds are deleted.
     *
     * Note : name was changed from jsmin() because PHP identifiers are case-insensitive, and it is already
     * the name of this class.
     *
     * @see     JSMin()
     * @return null | string
     */
    function minify() {

        // Initialize A and run the first (minimal) action

        $this->theA = "\n";
        $this->action(JSMIN_ACT_IMM);

        // Proceed all the way to the end of the input file

        while ($this->theA !== EOF) {

            switch ($this->theA) {

                case ' ' :

                    if (JSMin::isAlphaNum($this->theB)) {
                        $this->action(JSMIN_ACT_FULL);
                    }
                    else {
                        $this->action(JSMIN_ACT_BUF);
                    }

                break;
                case "\n" :

                    switch ($this->theB) {

                        case '{' : case '[' : case '(' :
                        case '+' : case '-' :

                            $this->action(JSMIN_ACT_FULL);

                        break;
                        case ' ' :

                            $this->action(JSMIN_ACT_IMM);

                        break;
                        default :

                            if (JSMin::isAlphaNum($this->theB)) {
                                $this->action(JSMIN_ACT_FULL);
                            }
                            else {
                                $this->action(JSMIN_ACT_BUF);
                            }

                        break;
                    }

                break;
                default :

                    switch ($this->theB) {

                        case ' ' :

                            if (JSMin::isAlphaNum($this->theA)) {

                                $this->action(JSMIN_ACT_FULL);
                                break;
                            }

                            // else

                            $this->action(JSMIN_ACT_IMM);

                        break;
                        case "\n" :

                            switch ($this->theA) {

                                case '}' : case ']' : case ')' : case '+' :
                                case '-' : case '"' : case '\'' :

                                    $this->action(JSMIN_ACT_FULL);

                                break;
                                default :

                                    if (JSMin::isAlphaNum($this->theA)) {
                                        $this->action(JSMIN_ACT_FULL);
                                    }
                                    else {
                                        $this->action(JSMIN_ACT_IMM);
                                    }

                                break;
                            }

                        break;
                        default :

                            $this->action(JSMIN_ACT_FULL);

                        break;
                    }

                break;
            }
        }

	    if ($this->isString) {
		    return $this->out;

	    }
    }

    /**
     * Prepare a new JSMin application.
     *
     * The next step is to {@link minify()} the input into the output.
     *
     * @param   string  $inFileName     The pathname of the input (unminified JS) file. STDIN if '-' or absent.
     * @param   string  $outFileName    The pathname of the output (minified JS) file. STDOUT if '-' or absent.
     *                                  If outFileName === FALSE, we assume that inFileName is in fact the string to be minified!!!
     * @param   array   $comments       Optional lines to present as comments at the beginning of the output.
     */
	function JSMin($inFileName = '-', $outFileName = '-', $comments = NULL) {
		if ($outFileName === FALSE) {
			$this->JSMin_String($inFileName, $comments);
		}
		else {
			$this->JSMin_File($inFileName, $outFileName, $comments);
		}
	}

    function JSMin_File($inFileName = '-', $outFileName = '-', $comments = NULL) {

        // Recuperate input and output streams.
        // Use STDIN and STDOUT by default, if they are defined (CLI mode) and no file names are provided

            if ($inFileName == '-')  $inFileName  = 'php://stdin';
            if ($outFileName == '-') $outFileName = 'php://stdout';

            try {

                $this->in = new SplFileObject($inFileName, 'rb', TRUE);
            }
            catch (Exception $e) {

                throw new FileOpenFailedJSMinException(
                    'Failed to open "'.$inFileName.'" for reading only.'
                );
            }

            try {

                $this->out = new SplFileObject($outFileName, 'wb', TRUE);
            }
            catch (Exception $e) {

                throw new FileOpenFailedJSMinException(
                    'Failed to open "'.$outFileName.'" for writing only.'
                );
            }

			/*$this->in = fopen($inFileName, 'rb');
			if (!$this->in) {
				trigger_error('Failed to open "'.$inFileName, E_USER_ERROR);
			}
			$this->out = fopen($outFileName, 'wb');
			if (!$this->out) {
				trigger_error('Failed to open "'.$outFileName, E_USER_ERROR);
			}*/

            // Present possible initial comments

            if ($comments !== NULL) {
                foreach ($comments as $comm) {
                    $this->out->fwrite('// '.str_replace("\n", " ", $comm)."\n");
                }
            }

    }

    function JSMin_String($inString, $comments = NULL) {
        $this->in = $inString;
        $this->out = '';
		$this->inLength = strlen($inString);
		$this->inPos = 0;
		$this->isString = true;

        if ($comments !== NULL) {
            foreach ($comments as $comm) {
                $this->out .= '// '.str_replace("\n", " ", $comm)."\n";
            }
        }
	}
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