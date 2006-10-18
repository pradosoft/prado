<?php
/**
 * MonoBook nouveau
 *
 * Translated from gwicke's previous TAL template version to remove
 * dependency on PHPTAL.
 *
 * @todo document
 * @package MediaWiki
 * @subpackage Skins
 */

/**
 * MonoBook modified and redesigned by JasonPearce.com for FraternityManuals.com
 * Launched April 7, 2005
 */

if( !defined( 'MEDIAWIKI' ) )
	die();

/** */
require_once('includes/SkinTemplate.php');

/**
 * Inherit main code from SkinTemplate, set the CSS and template filter.
 * @todo document
 * @package MediaWiki
 * @subpackage Skins
 */
class DumpSkin extends SkinTemplate {
	/** Using monobook. */
	function initPage( &$out ) {
		SkinTemplate::initPage( $out );
		$this->skinname  = 'dump';
		$this->stylename = '';
		$this->template  = 'DumpTemplate';
	}
}
	
class DumpTemplate extends QuickTemplate {
	/**
	 * Template filter callback for MonoBook skin.
	 * Takes an associative array of data set from a SkinTemplate-based
	 * class, and a wrapper for MediaWiki's localization database, and
	 * outputs a formatted page.
	 *
	 * @access private
	 */
	function execute() {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php $this->text('lang') ?>" lang="<?php $this->text('lang') ?>" dir="<?php $this->text('dir') ?>">
<head>
<meta http-equiv="Content-Type" content="<?php $this->text('mimetype') ?>; charset=<?php $this->text('charset') ?>" />
<title>WIKI: <?php $this->text('pagetitle') ?>
</title>
<link rel="stylesheet" type="text/css" href="<?php $this->text('stylepath') ?>main.css" />
</head>
<body>
<div id="globalWrapper">
  <div id="column-content">
    <div id="content"> <a name="top" id="contentTop"></a>
      
	  <h1 class="firstHeading">
        <?php $this->text('title') ?>
      </h1>
      <h3 id="siteSub">
        <?php $this->msg('tagline') ?>
      </h3>
      
	  <div id="bodyContent">
        <div id="contentSub">
          <?php $this->html('subtitle') ?>
        </div>
        
        <!-- BEGIN content -->
        <?php $this->html('bodytext') ?>
        <?php if($this->data['catlinks']) { ?>
        <div id="catlinks">
          <?php $this->html('catlinks') ?>
        </div>
        <?php } ?>

        <!-- END content -->
      </div>

    </div>
  </div>
</div>
<!-- end of the left (by default at least) column -->
<img src="<?php $this->text('stylepath') ?>external.png" style="display:none"/>
</body></html>
<?php } } ?>
