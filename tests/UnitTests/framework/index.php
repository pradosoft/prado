<?php

class TestFolder
{
	public $name='';
	public $url='';
	public $subFolders=array();
	public $testFiles=array();

	public function __construct($path,$rootPath,$rootUri)
	{
		$this->url="$rootUri/index.php?target=".strtr(substr($path,strlen($rootPath)+1),"\\",'/');
		$this->name=basename($path);
		$dir=opendir($path);
		while(($entry=readdir($dir))!==false)
		{
			$fullpath="$path/$entry";
			if($entry!=='.' && $entry!=='..' && $entry!=='.svn' && is_dir($fullpath))
			{
				$folder=new TestFolder($fullpath,$rootPath,$rootUri);
				if(!empty($folder->subFolders) || !empty($folder->testFiles))
					$this->subFolders[]=$folder;
			}
			else if(is_file($fullpath) && (strncmp($entry,'ut',2)===0 
						|| preg_match('/test.*\.php/', strtolower($entry))))
			{
				$this->testFiles[$entry]="$rootUri/index.php?target=".strtr(substr($fullpath,strlen($rootPath)+1),"\\",'/');
			}
		}
		closedir($dir);
	}

	public function getHtml($level=0)
	{
		$str=str_repeat('&nbsp;',$level*4)."[ <a href=\"{$this->url}\">{$this->name}</a> ]<br/>\n";
		foreach($this->subFolders as $folder)
			$str.=$folder->getHtml($level+1);
		foreach($this->testFiles as $name=>$url)
			$str.=str_repeat('&nbsp;',($level+1)*4)."<a href=\"$url\">$name</a><br/>\n";
		return $str;
	}
}


function addTests($test,$path,$recursive)
{
	$dir=opendir($path);
	while(($entry=readdir($dir))!==false)
	{
		if(is_file($path.'/'.$entry) && (strncmp($entry,'ut',2)===0||preg_match('/test.*\.php/', strtolower($entry))))
			$test->addTestFile($path.'/'.$entry);
		else if($entry!=='.' && $entry!=='..' && $entry!=='.svn' && is_dir($path.'/'.$entry) && $recursive)
			addTests($test,$path.'/'.$entry,$recursive);
	}
	closedir($dir);
}


$rootPath=realpath(dirname(__FILE__));
$rootUri=dirname($_SERVER['PHP_SELF']);

if(isset($_GET['target']))
{
	$target=$_GET['target'];
	$recursive=true;
	$fullpath=realpath("$rootPath/$target");
	if($fullpath===false || strpos($fullpath,$rootPath)!==0)
		die('invalid test target');
	include_once($rootPath.'/common.php');
	
	if(is_dir($fullpath))
	{

		$test=new GroupTest(basename($rootPath)."/$target");
		addTests($test,$fullpath,$recursive);
		$test->run(new HtmlReporter());
		//$test->run(new HtmlReporterWithCoverage('index.php',$rootPath));
	}
	else
	{
		$testClass=basename($fullpath,'.php');
		include_once($fullpath);
		$test=new $testClass(basename($rootPath)."/$target");

		$test->run(new HtmlReporter());
	}
}
else
{
	echo "<html>
<head>
<title>Prado Framework Unit Tests</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
</head>
<body>
<h1>Prado Framework Unit Tests</h1>
";
	$root=new TestFolder($rootPath,$rootPath,$rootUri);
	echo $root->getHtml();
	echo "</body>\n</html>";
}

?>