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
 * Linking to element documentation is performed by the classes in this file.
 *
 * abstractLink descendants contain enough information to differentiate every
 * documentable element, and so can be converted to a link string by
 * {@link Converter::returnSee()}
 * @package phpDocumentor
 * @subpackage Links
 */

/**
 * linking classes parent
 * @package phpDocumentor
 * @subpackage Links
 */
class abstractLink
{
    /**#@+ @var string */
    var $path;
    /**
     * phpdoc alias _phpdoc_inc for phpdoc.inc
     */
    var $fileAlias = '';
    /**
     * element type linked to.
     * can only be a documentable element
     */
    var $type = '';
    var $name = '';
    var $category = '';
    var $package = '';
    var $subpackage = '';
    /**#@-*/

    /**
     * @param string full path to file containing element
     * @param string page name, as configured by {@link Parser::parse}
     * @param string element name
     * @param string package element is in
     * @param string subpackage element is in
     * @param string optional category that documentation is in
     */
    function addLink($path, $fileAlias, $name, $package, $subpackage, $category = false)
    {
        $this->path = $path;
        $this->fileAlias = $fileAlias;
        $this->name = $name;
        $this->category = $category;
        $this->package = $package;
        $this->subpackage = $subpackage;
    }
}

/**
 * procedural page link
 * @package phpDocumentor
 * @subpackage Links
 */
class pageLink extends abstractLink
{
    /** @var string */
    var $type = 'page';
}

/**
 * function link
 * @package phpDocumentor
 * @subpackage Links
 */
class functionLink extends abstractLink
{
    /** @var string */
    var $type = 'function';
}

/**
 * define link
 * @package phpDocumentor
 * @subpackage Links
 */
class defineLink extends abstractLink
{
    /** @var string */
    var $type = 'define';
}

/**
 * global variable link
 * @package phpDocumentor
 * @subpackage Links
 */
class globalLink extends abstractLink
{
    /** @var string */
    var $type = 'global';
}

/**
 * class link
 * @package phpDocumentor
 * @subpackage Links
 */
class classLink extends abstractLink
{
    /** @var string */
    var $type = 'class';
}

/**
 * method link
 * @package phpDocumentor
 * @subpackage Links
 */
class methodLink extends abstractLink
{
    /** @var string */
    var $type = 'method';
    /** @var string */
    var $class = '';
    
    /**
     * @param string class name
     * @param string full path to file containing element
     * @param string page name, as configured by {@link Parser::parse}
     * @param string element name
     * @param string package element is in
     * @param string subpackage element is in
     */
    function addLink($class, $path ,$fileAlias,$name,$package,$subpackage, $category = false)
    {
        $this->class = $class;
        abstractLink::addLink($path, $fileAlias,$name,$package,$subpackage, $category);
    }
}

/**
 * class variable link
 * @package phpDocumentor
 * @subpackage Links
 */
class varLink extends methodLink
{
    /** @var string */
    var $type = 'var';
}

/**
 * class constant link
 * @package phpDocumentor
 * @subpackage Links
 */
class constLink extends methodLink
{
    /** @var string */
    var $type = 'const';
}

/**
 * tutorial link
 * @package phpDocumentor
 * @subpackage Links
 */
class tutorialLink extends abstractLink
{
    /**#@+ @var string */
    var $type = 'tutorial';
    var $section = '';
    var $title = false;
    /**#@-*/
    
    /**
     * @param string section/subsection name
     * @param string full path to file containing element
     * @param string page name, as configured by {@link Parser::parse}
     * @param string element name
     * @param string package element is in
     * @param string subpackage element is in
     * @param string title of tutorial
     */
    function addLink($section,$path,$name,$package,$subpackage,$title = false, $category = false)
    {
        $this->section = $section;
        $this->title = $title;
        parent::addLink($path,'',$name,$package,$subpackage, $category);
    }
}
?>