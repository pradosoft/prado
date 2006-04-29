<?php

/**
* JSMin.php (for PHP 5)
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
*
* ----------------------------------------------------------------------------
*
* @copyright   No new copyright ; please keep above and following information.
* @author      David Holmes <dholmes@cfdsoftware.net> of CFD Labs, France
* @version     0.1 (PHP translation)   2006-04-11
*
* Please note, this is a brutal and simple conversion : it could undoubtedly be
* improved, as a PHP implementation, by applying more PHP-specific programming
* features.
*
* PHP 5 is required because OO style of programming is used, as well as classes
* from the Standard PHP Library (SPL).
*
* Note : whereas jsmin.c works specifically with the standard input and output
* streams, this implementation only falls back on them if file pathnames are
* not provided to the JSMin() constructor.
*
* Examples comparing with the application compiled from jsmin.c :
*
* jsmin < orig.js > mini.js        JSMin.php orig.js mini.js
*                                  JSMin.php orig.js > mini.js
*                                  JSMin.php - mini.js < orig.js
* jsmin < orig.js                  JSMin.php orig.js
*                                  JSMin.php orig.js -
* jsmin > mini.js                  JSMin.php - mini.js
*                                  JSMin.php > mini.js
* jsmin comm1 comm2 < a.js > b.js  JSMin.php a.js b.js comm1 comm2
*                                  JSMin.php a.js b.js -c comm1 comm2
*                                  JSMin.php a.js --comm comm1 comm2 > b.js
*                                  JSMin.php -c comm1 comm2 < a.js > b.js
* (etc...)
*
* See JSMin.php -h (or --help) for command-line documentation.
*/

/**
* Version of this PHP translation.
*/

define('VERSION', '0.1');

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
* Main JSMin application class.
*
* Example of use :
*
* $jsMin = new JSMin(...input..., ...output...);
* $jsMin -> minify();
*
* Do not specify input and/or output (or default to '-') to use stdin and/or stdout.
*/

class JSMin {

    /**
     * Constant describing an {@link action()} : Output A. Copy B to A. Get the next B.
     */

    const ACT_FULL = 1;

    /**
     * Constant describing an {@link action()} : Copy B to A. Get the next B. (Delete A).
     */

    const ACT_BUF = 2;

    /**
     * Constant describing an {@link action()} : Get the next B. (Delete B).
     */

    const ACT_IMM = 3;

    /**
     * The input stream, from which to read a JS file to minimize. Obtained by fopen().
     * @var SplFileObject
     */

    private $in;

    /**
     * The output stream, in which to write the minimized JS file. Obtained by fopen().
     * @var SplFileObject
     */

    private $out;

    /**
     * Temporary I/O character (A).
     * @var string
     */

    private $theA;

    /**
     * Temporary I/O character (B).
     * @var string
     */

    private $theB;

    /**
     * Indicates whether a character is alphanumeric or _, $, \ or non-ASCII.
     *
     * @param   string      $c  The single character to test.
     * @return  boolean     Whether the char is a letter, digit, underscore, dollar, backslash, or non-ASCII.
     */

    private static function isAlphaNum($c) {

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

    private function get() {

        // Get next input character and advance position in file

        $c = $this -> in -> fgetc();

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

    private function peek() {

        // Get next input character

        $c = $this -> in -> fgetc();

        // Regress position in file

        $this -> in -> fseek(-1, SEEK_CUR);

        // Return character obtained

        return $c;
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

    private function next() {

        // Get next char from input, translated if necessary

        $c = $this -> get();

        // Check comment possibility

        if ($c == '/') {

            // Look ahead : a comment is two slashes or slashes followed by asterisk (to be closed)

            switch ($this -> peek()) {

                case '/' :

                    // Comment is up to the end of the line
                    // TOTEST : simple $this -> in -> fgets()

                    while (true) {

                        $c = $this -> get();

                        if (ord($c) <= ORD_NL) {
                            return $c;
                        }
                    }

                case '*' :

                    // Comment is up to comment close.
                    // Might not be terminated, if we hit the end of file.

                    while (true) {

                        // N.B. not using switch() because of having to test EOF with ===

                        $c = $this -> get();

                        if ($c == '*') {

                            // Comment termination if the char ahead is a slash

                            if ($this -> peek() == '/') {

                                // Advance again and make into a single space

                                $this -> get();
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
     * JSMin :: ACT_FULL : Output A. Copy B to A. Get the next B.
     * JSMin :: ACT_BUF  : Copy B to A. Get the next B. (Delete A).
     * JSMin :: ACT_IMM  : Get the next B. (Delete B).
     *
     * A string is treated as a single character. Also, regular expressions are recognized if preceded
     * by '(', ',' or '='.
     *
     * @param   int     $action     The action to perform : one of the JSMin :: ACT_* constants.
     */

    private function action($action) {

        // Choice of possible actions
        // Note the frequent fallthroughs : the actions are decrementally "long"

        switch ($action) {

            case self :: ACT_FULL :

                // Write A to output, then fall through

                $this -> out -> fwrite($this -> theA);

            case self :: ACT_BUF : // N.B. possible fallthrough from above

                // Copy B to A

                $tmpA = $this -> theA = $this -> theB;

                // Treating a string as a single char : outputting it whole
                // Note that the string-opening char (" or ') is memorized in B

                if ($tmpA == '\'' || $tmpA == '"') {

                    while (true) {

                        // Output string contents

                        $this -> out -> fwrite($tmpA);

                        // Get next character, watching out for termination of the current string,
                        // new line & co (then the string is not terminated !), or a backslash
                        // (upon which the following char is directly output to serve the escape mechanism)

                        $tmpA = $this -> theA = $this -> get();

                        if ($tmpA == $this -> theB) {

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

                            $this -> out -> fwrite($tmpA);
                            $tmpA = $this -> theA = $this -> get();
                        }
                    }
                }

            case self :: ACT_IMM : // N.B. possible fallthrough from above

                // Get the next B

                $this -> theB = $this -> next();

                // Special case of recognising regular expressions (beginning with /) that are
                // preceded by '(', ',' or '='

                $tmpA = $this -> theA;

                if ($this -> theB == '/' && ($tmpA == '(' || $tmpA == ',' || $tmpA == '=')) {

                    // Output the two successive chars

                    $this -> out -> fwrite($tmpA);
                    $this -> out -> fwrite($this -> theB);

                    // Look for the end of the RE literal, watching out for escaped chars or a control /
                    // end of line char (the RE literal then being unterminated !)

                    while (true) {

                        $tmpA = $this -> theA = $this -> get();

                        if ($tmpA == '/') {

                            // RE literal terminated

                            break; // from while(true)
                        }

                        // else

                        if ($tmpA == '\\') {

                            // Escape next char immediately

                            $this -> out -> fwrite($tmpA);
                            $tmpA = $this -> theA = $this -> get();
                        }
                        else if (ord($tmpA) <= ORD_NL) {

                            // Whoopsie

                            throw new UnterminatedRegExpLiteralJSMinException();
                        }

                        // Output RE characters

                        $this -> out -> fwrite($tmpA);
                    }

                    // Move forward after the RE literal

                    $this -> theB = $this -> next();
                }

            break;
            default : throw new JSMinException('Expected a JSMin :: ACT_* constant in action().');
        }
    }

    /**
     * Run the JSMin application : minify some JS code.
     *
     * The code is read from the input stream, and its minified version is written to the output one.
     * That is : characters which are insignificant to JavaScript are removed, as well as comments ;
     * tabs are replaced with spaces ; carriage returns are replaced with linefeeds, and finally most
     * spaces and linefeeds are deleted.
     *
     * Note : name was changed from jsmin() because PHP identifiers are case-insensitive, and it is already
     * the name of this class.
     *
     * @see     __construct()
     */

    public function minify() {

        // Initialize A and run the first (minimal) action

        $this -> theA = "\n";
        $this -> action(self :: ACT_IMM);

        // Proceed all the way to the end of the input file

        while ($this -> theA !== EOF) {

            switch ($this -> theA) {

                case ' ' :

                    if (self :: isAlphaNum($this -> theB)) {
                        $this -> action(self :: ACT_FULL);
                    }
                    else {
                        $this -> action(self :: ACT_BUF);
                    }

                break;
                case "\n" :

                    switch ($this -> theB) {

                        case '{' : case '[' : case '(' :
                        case '+' : case '-' :

                            $this -> action(self :: ACT_FULL);

                        break;
                        case ' ' :

                            $this -> action(self :: ACT_IMM);

                        break;
                        default :

                            if (self :: isAlphaNum($this -> theB)) {
                                $this -> action(self :: ACT_FULL);
                            }
                            else {
                                $this -> action(self :: ACT_BUF);
                            }

                        break;
                    }

                break;
                default :

                    switch ($this -> theB) {

                        case ' ' :

                            if (self :: isAlphaNum($this -> theA)) {

                                $this -> action(self :: ACT_FULL);
                                break;
                            }

                            // else

                            $this -> action(self :: ACT_IMM);

                        break;
                        case "\n" :

                            switch ($this -> theA) {

                                case '}' : case ']' : case ')' : case '+' :
                                case '-' : case '"' : case '\'' :

                                    $this -> action(self :: ACT_FULL);

                                break;
                                default :

                                    if (self :: isAlphaNum($this -> theA)) {
                                        $this -> action(self :: ACT_FULL);
                                    }
                                    else {
                                        $this -> action(self :: ACT_IMM);
                                    }

                                break;
                            }

                        break;
                        default :

                            $this -> action(self :: ACT_FULL);

                        break;
                    }

                break;
            }
        }
    }

    /**
     * Prepare a new JSMin application.
     *
     * The next step is to {@link minify()} the input into the output.
     *
     * @param   string  $inFileName     The pathname of the input (unminified JS) file. STDIN if '-' or absent.
     * @param   string  $outFileName    The pathname of the output (minified JS) file. STDOUT if '-' or absent.
     * @param   array   $comments       Optional lines to present as comments at the beginning of the output.
     * @throws  FileOpenFailedJSMinException    If the input and/or output file pathname is not provided, and
     *      respectively STDIN and/or STDOUT are not available (ie, script is not being used in CLI).
     */

    public function __construct($inFileName = '-', $outFileName = '-', $comments = NULL) {

        // Recuperate input and output streams.
        // Use STDIN and STDOUT by default, if they are defined (CLI mode) and no file names are provided

        if ($inFileName == '-')  $inFileName  = 'php://stdin';
        if ($outFileName == '-') $outFileName = 'php://stdout';

        try {

            $this -> in = new SplFileObject($inFileName, 'rb', TRUE);
        }
        catch (Exception $e) {

            throw new FileOpenFailedJSMinException(
                'Failed to open "'.$inFileName.'" for reading only.'
            );
        }

        try {

            $this -> out = new SplFileObject($outFileName, 'wb', TRUE);
        }
        catch (Exception $e) {

            throw new FileOpenFailedJSMinException(
                'Failed to open "'.$outFileName.'" for writing only.'
            );
        }

        // Present possible initial comments

        if ($comments !== NULL) {
            foreach ($comments as $comm) {
                $this -> out -> fwrite('// '.$comm."\n");
            }
        }        
    }
}

?>