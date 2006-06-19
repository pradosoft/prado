<?php
set_time_limit(0);
require_once('PEAR/PackageFileManager.php');
PEAR::setErrorHandling(PEAR_ERROR_DIE);
$test = new PEAR_PackageFileManager;

$packagedir = dirname(dirname(__FILE__));

$e = $test->setOptions(
array('baseinstalldir' => 'PhpDocumentor',
'version' => '1.3.0RC4',
'packagedirectory' => $packagedir,
'state' => 'beta',
'filelistgenerator' => 'cvs',
'notes' => 'PHP 5 support and more, fix bugs

This will be the last release in the 1.x series.  2.0 is next

Features added to this release include:

 * Full PHP 5 support, phpDocumentor both runs in and parses Zend Engine 2
   language constructs.  Note that you must be running phpDocumentor in
   PHP 5 in order to parse PHP 5 code
 * XML:DocBook/peardoc2:default converter now beautifies the source using
   PEAR\'s XML_Beautifier if available
 * inline {@example} tag - this works just like {@source} except that
   it displays the contents of another file.  In tutorials, it works
   like <programlisting>
 * customizable README/INSTALL/CHANGELOG files
 * phpDocumentor tries to run .ini files out of the current directory
   first, to allow you to put them anywhere you want to
 * multi-national characters are now allowed in package/subpackage names
 * images in tutorials with the <graphic> tag
 * un-modified output with <programlisting role="html">
 * html/xml source highlighting with <programlisting role="tutorial">

From both Windows and Unix, both the command-line version
of phpDocumentor and the web interface will work
out of the box by using command phpdoc - guaranteed :)

WARNING: in order to use the web interface through PEAR, you must set your
data_dir to a subdirectory of your document root.

$ pear config-set data_dir /path/to/public_html/pear

on Windows with default apache setup, it might be

C:\> pear config-set data_dir "C:\Program Files\Apache\htdocs\pear"

After this, install/upgrade phpDocumentor

$ pear upgrade phpDocumentor

and you can browse to:

http://localhost/pear/PhpDocumentor/

for the web interface

------
WARNING: The PDF Converter will not work in PHP5.  The PDF library that it relies upon
segfaults with the simplest of files.  Generation still works great in PHP4
------

- WARNING: phpDocumentor installs phpdoc in the
  scripts directory, and this will conflict with PHPDoc,
  you can\'t have both installed at the same time
- Switched to Smarty 2.6.0, now it will work in PHP 5.  Other
  changes made to the code to make it work in PHP 5, including parsing
  of private/public/static/etc. access modifiers
- fixed these bugs:
 [ not entered ] XMLDocBookpeardoc2 beautifier removes comments
 [ 896444 ] Bad line numbers
 [ 937235 ] duplicated /** after abstract method declaration
 [ 962319 ] Define : don\'t show the assigned value
 [ 977674 ] Parser error
 [ 989258 ] wrong interfaces parsing
 [ 1150809 ] Infinite loop when class extends itself
 [ 1151196 ] PHP Fatal error: Cannot re-assign $this
 [ 1151650 ] UTF8 decoding for DocBook packages
 [ 1152781 ] PHP_NOTICE: Uninitialized string offset in ParserDescCleanup
 [ 1153593 ] string id="...." in tutorials
 [ 1164253 ] Inherited Class Constants are not displayed
 [ 1171583 ] CHM wrong filesource
 [ 1180200 ] HighlightParser does not handle Heredoc Blocks.
 [ 1202772 ] missing parentheses in Parser.inc line 946
 [ 1203445 ] Call to a member function on a non-object in Parser.inc
 [ 1203451 ] array to string conversion notice in InlineTags.inc
- fixed these bugs reported in PEAR:
 Bug #2288: Webfrontend ignores more than one dir in "Files to ignore"
 Bug #5011: PDF generation warning on uksort
',
'package' => 'PhpDocumentor',
'dir_roles' => array(
    'Documentation' => 'doc',
    'Documentation/tests' => 'test',
    'docbuilder' => 'data',
    'HTML_TreeMenu-1.1.2' => 'data',
    'tutorials' => 'doc',
    'phpDocumentor/Converters/CHM/default/templates/default/templates_c' => 'data',
    'phpDocumentor/Converters/PDF/default/templates/default/templates_c' => 'data',
    'phpDocumentor/Converters/HTML/frames/templates/default/templates_c' => 'data',
    'phpDocumentor/Converters/HTML/frames/templates/l0l33t/templates_c' => 'data',
    'phpDocumentor/Converters/HTML/frames/templates/phpdoc.de/templates_c' => 'data',
    'phpDocumentor/Converters/HTML/frames/templates/phphtmllib/templates_c' => 'data',
    'phpDocumentor/Converters/HTML/frames/templates/phpedit/templates_c' => 'data',
    'phpDocumentor/Converters/HTML/frames/templates/earthli/templates_c' => 'data',
    'phpDocumentor/Converters/HTML/frames/templates/DOM/default/templates_c' => 'data',
    'phpDocumentor/Converters/HTML/frames/templates/DOM/l0l33t/templates_c' => 'data',
    'phpDocumentor/Converters/HTML/frames/templates/DOM/phpdoc.de/templates_c' => 'data',
    'phpDocumentor/Converters/HTML/frames/templates/DOM/phphtmllib/templates_c' => 'data',
    'phpDocumentor/Converters/HTML/frames/templates/DOM/earthli/templates_c' => 'data',
    'phpDocumentor/Converters/HTML/Smarty/templates/default/templates_c' => 'data',
    'phpDocumentor/Converters/HTML/Smarty/templates/PHP/templates_c' => 'data',
    'phpDocumentor/Converters/HTML/Smarty/templates/HandS/templates_c' => 'data',
    'phpDocumentor/Converters/XML/DocBook/peardoc2/templates/default/templates_c' => 'data',
    ),
'simpleoutput' => true,
'exceptions' =>
    array(
        'index.html' => 'data',
        'README' => 'doc',
        'ChangeLog' => 'doc',
        'PHPLICENSE.txt' => 'doc',
        'poweredbyphpdoc.gif' => 'data',
        'INSTALL' => 'doc',
        'FAQ' => 'doc',
        'Authors' => 'doc',
        'Release-1.2.0beta1' => 'doc',
        'Release-1.2.0beta2' => 'doc',
        'Release-1.2.0beta3' => 'doc',
        'Release-1.2.0rc1' => 'doc',
        'Release-1.2.0rc2' => 'doc',
        'Release-1.2.0' => 'doc',
        'Release-1.2.1' => 'doc',
        'Release-1.2.2' => 'doc',
        'Release-1.2.3' => 'doc',
        'Release-1.2.3.1' => 'doc',
        'Release-1.3.0' => 'doc',
        'pear-phpdoc' => 'script',
        'pear-phpdoc.bat' => 'script',
        'HTML_TreeMenu-1.1.2/TreeMenu.php' => 'php',
        'phpDocumentor/Smarty-2.6.0/libs/debug.tpl' => 'php',
        'new_phpdoc.php' => 'data',
        'phpdoc.php' => 'data',
        ),
'ignore' =>
    array('package.xml', 
          "$packagedir/phpdoc",
          'phpdoc.bat', 
          'LICENSE',
          '*templates/PEAR/*',
          'phpDocumentor/Smarty-2.5.0/*',
          '*CSV*',
          'makedocs.ini',
          'publicweb-PEAR-1.2.1.patch.txt',
          ),
'installas' =>
    array('pear-phpdoc' => 'phpdoc',
          'pear-phpdoc.bat' => 'phpdoc.bat',
          'user/pear-makedocs.ini' => 'user/makedocs.ini',
          ),
'installexceptions' => array('pear-phpdoc' => '/', 'pear-phpdoc.bat' => '/', 'scripts/makedoc.sh' => '/'),
));
if (PEAR::isError($e)) {
    echo $e->getMessage();
    exit;
}
$test->addPlatformException('pear-phpdoc.bat', 'windows');
$test->addDependency('php', '4.1.0', 'ge', 'php');
// optional dep for peardoc2 converter
$test->addDependency('XML_Beautifier', '1.1', 'ge', 'pkg', true);
// replace @PHP-BIN@ in this file with the path to php executable!  pretty neat
$test->addReplacement('pear-phpdoc', 'pear-config', '@PHP-BIN@', 'php_bin');
$test->addReplacement('pear-phpdoc.bat', 'pear-config', '@PHP-BIN@', 'php_bin');
$test->addReplacement('pear-phpdoc.bat', 'pear-config', '@BIN-DIR@', 'bin_dir');
$test->addReplacement('pear-phpdoc.bat', 'pear-config', '@PEAR-DIR@', 'php_dir');
$test->addReplacement('pear-phpdoc.bat', 'pear-config', '@DATA-DIR@', 'data_dir');
$test->addReplacement('docbuilder/includes/utilities.php', 'pear-config', '@DATA-DIR@', 'data_dir');
$test->addReplacement('docbuilder/builder.php', 'pear-config', '@DATA-DIR@', 'data_dir');
$test->addReplacement('docbuilder/file_dialog.php', 'pear-config', '@DATA-DIR@', 'data_dir');
$test->addReplacement('docbuilder/file_dialog.php', 'pear-config', '@WEB-DIR@', 'data_dir');
$test->addReplacement('docbuilder/actions.php', 'pear-config', '@WEB-DIR@', 'data_dir');
$test->addReplacement('docbuilder/top.php', 'pear-config', '@DATA-DIR@', 'data_dir');
$test->addReplacement('docbuilder/config.php', 'pear-config', '@DATA-DIR@', 'data_dir');
$test->addReplacement('docbuilder/config.php', 'pear-config', '@WEB-DIR@', 'data_dir');
$test->addReplacement('phpDocumentor/Setup.inc.php', 'pear-config', '@DATA-DIR@', 'data_dir');
$test->addReplacement('phpDocumentor/Converter.inc', 'pear-config', '@DATA-DIR@', 'data_dir');
$test->addReplacement('phpDocumentor/common.inc.php', 'package-info', '@VER@', 'version');
$test->addReplacement('phpDocumentor/IntermediateParser.inc', 'package-info', '@VER@', 'version');
$test->addReplacement('user/pear-makedocs.ini', 'pear-config', '@PEAR-DIR@', 'php_dir');
$test->addReplacement('user/pear-makedocs.ini', 'pear-config', '@DOC-DIR@', 'doc_dir');
$test->addReplacement('user/pear-makedocs.ini', 'package-info', '@VER@', 'version');
$test->addRole('inc', 'php');
$test->addRole('sh', 'script');
if (isset($_GET['make'])) {
    $test->writePackageFile();
} else {
    $test->debugPackageFile();
}
if (!isset($_GET['make'])) {
    echo '<a href="' . $_SERVER['PHP_SELF'] . '?make=1">Make this file</a>';
}
?>