<?php

$wiki_dir = 'c:/Wei/workspace/wiki/';
$wiki_url = 'http://www.pradosoft.com/wiki/';

$ROOT = dirname(__FILE__);

$output_dir = $ROOT.'/../../build/docs/wiki';

include_once(dirname(__FILE__).'/dumpHTML.php');

if(!is_file($output_dir.'/external.png'))
{
	copy($ROOT.'/external.png', $output_dir.'/external.png');
	copy($ROOT.'/main.css', $output_dir.'/main.css');
}

?>