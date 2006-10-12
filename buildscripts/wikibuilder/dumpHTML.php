<?php
/**
 * @todo document
 * @package MediaWiki
 * @subpackage Maintenance
 */

/**
 * Usage:
 * php dumpHTML.php [options...]
 *
 * -d <dest>      destination directory
 * -s <start>     start ID
 * -e <end>       end ID
 * --images       only do image description pages
 * --categories   only do category pages
 * --special      only do miscellaneous stuff
 * --force-copy   copy commons instead of symlink, needed for Wikimedia
 * --interlang    allow interlanguage links
 */


$optionsWithArgs = array( 's', 'd', 'e' );

require_once($wiki_dir. "/maintenance/commandLine.inc" );
require_once(dirname(__FILE__)."/dumpHTML.inc" );

class DummyUser extends User
{
	function getSkin()
	{
		require_once(dirname(__FILE__).'/DumpSkin.php' );
		$this->mSkin =& new DumpSkin;
		return $this->mSkin;
	}
}

class DumpTitle extends Title
{
	function getHashedDirectory() 
	{
		return strtr(parent::getHashedDirectory(), '~:', '__');
	}
}

error_reporting( E_ALL & (~E_NOTICE) );
define( 'CHUNK_SIZE', 50 );

if ( !empty( $options['s'] ) ) {
	$start = $options['s'];
} else {
	$start = 1280;
}

if ( !empty( $options['e'] ) ) {
	$end = $options['e'];
} else {
	$dbr =& wfGetDB( DB_SLAVE );
	$end = $dbr->selectField( 'page', 'max(page_id)', false );	
}

if ( !empty( $options['d'] ) ) {
	$dest = $options['d'];
} else {
	$dest = $output_dir;
}

class DumpHTMLSkined extends DumpHTML
{
	function setupGlobals( $depth = NULL ) 
	{
		parent::setupGlobals($depth);
		global $wgUser,$wgServer,$wiki_url;
		$wgUser = new DummyUser;
		$wgServer = $wiki_url;
	}
}

$d = new DumpHTMLSkined( array( 
	'dest' => $dest, 
	'forceCopy' => $options['force-copy'],
	'alternateScriptPath' => $options['interlang'],
	'interwiki' => $options['interlang'],
));


if ( $options['special'] ) {
	$d->doSpecials();
} elseif ( $options['images'] ) {
	$d->doImageDescriptions();
} elseif ( $options['categories'] ) {
	$d->doCategories();
} else {
	print("Creating static HTML dump in directory $dest. \n".
		"Starting from page_id $start of $end.\n");
	$d->doArticles( $start, $end );
	$d->doImageDescriptions();
	$d->doCategories();
	$d->doMainPage();
}

?>