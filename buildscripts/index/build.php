<?php
/*
 * Created on 10/05/2006
 */

/**
 * Building search index for quickstart tutorials and the API documentation.
 */


//quickstart source and the index data target directories.
$quickstart_source = realpath(dirname(__FILE__).'/../texbuilder/pages.php');
$quickstart_base = realpath(dirname(__FILE__).'/../../demos/quickstart/protected/pages/');
$quickstart_target = realpath(dirname(__FILE__).'/../../demos/quickstart/protected/index/quickstart/');

//API source and the index data target directories.
$api_source = realpath(dirname(__FILE__).'/../../build/docs/manual/');
$api_target = realpath(dirname(__FILE__).'/../../demos/quickstart/protected/index/api/');

//get the ZEND framework
$zend_path = realpath(dirname(__FILE__).'/../../demos/quickstart/protected/index');
set_include_path(get_include_path().';'.$zend_path);
require_once ('Zend/Search/Lucene.php');

//get the indexers.
include('quickstart_index.php');
include('API_index.php');

if(isset($argv[1]))
{
	if(strtolower($argv[1]) == "quickstart")
	{
		$quickstart = new quickstart_index($quickstart_target, $quickstart_base, $quickstart_source);
		$quickstart->create_index();
	}
	else if(strtolower($argv[1]) == "api")
	{
		$api = new api_index($api_target, $api_source);
		$api->create_index();
	}
	else
	{
		$q = new Zend_Search_Lucene($quickstart_target);
		$query = $argv[1];
		$hits = $q->find(strtolower($query));
		echo "Found ".count($hits)." for ".$query." in quick start\n";
		foreach($hits as $hit)
			echo "   ".$hit->title."\n";
			
		$a = new Zend_Search_Lucene($api_target);
		$query = $argv[1];
		$hits = $a->find(strtolower($query));
		echo "\nFound ".count($hits)." for ".$query." in API\n";
		foreach($hits as $hit)
		{
			echo "   ".$hit->link."\n";
		}
	}
}
else
{
	echo "Usage: 'php build.php quickstart' or 'php build.php api'\n";
}

?>