<?php

if(isset($_GET['keyword']))
	$keyword=trim($_GET['keyword']);
else
	$keyword='';

$zend_path=realpath(dirname(__FILE__).'/../../demos/quickstart/protected/index');
set_include_path(get_include_path().PATH_SEPARATOR.$zend_path);
require_once('Zend/Search/Lucene.php');

if($keyword!=='')
{
	$search=new Zend_Search_Lucene(realpath(dirname(__FILE__)));		
	$results=$search->find(strtolower($keyword));
	$content='';
	foreach($results as $entry)
		$content.="<li><a href=\"{$entry->link}\">{$entry->title}</a></li>\n";
	if($content!=='')
	{
		$count=count($results);
		$content="<p>Total <b>$count</b> pages matching keyword <b>".htmlentities($keyword)."</b>.\n<ol>\n$content</ol>\n";
	}
	else
		$content="<p>No page matches <b>".htmlentities($keyword)."</b>.</p>";
}
else
	$content="<p>Please specify a keyword to search for.</p>";

$page=file_get_contents(dirname(__FILE__).'/index.html');
$page=preg_replace('/<!-- content begin -->.*<!-- content end -->/ms',$content,$page);
if($keyword!=='')
	$page=preg_replace('/<input type="text" name="keyword"/','<input type="text" name="keyword" value="'.htmlentities($keyword).'"',$page);
echo $page;

?>