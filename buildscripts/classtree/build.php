<?php

$rootPath=dirname(__FILE__).'/../../framework';
require_once($rootPath.'/prado.php');
$exclusions=array(
	'prado.php',
	'pradolite.php',
	'PradoBase.php',
	'clientscripts.php',
	'.svn',
	'/I18N/core',
	'/3rdParty',
	);
$a=new ClassTreeBuilder($rootPath,$exclusions);
$a->buildTree();
$a->saveToFile('classtree.data');

class ClassTreeBuilder
{
	const REGEX_RULES='/^\s*(abstract\s+)?class\s+(\w+)(\s+extends\s+(\w+)\s*|\s*)/msS';
	private $_basePath;
	private $_exclusions;
	private $_classes=array();

	public function __construct($basePath,$exclusions)
	{
		$this->_basePath=realpath($basePath);
		$this->_exclusions=array();
		foreach($exclusions as $exclusion)
		{
			if($exclusion[0]==='/')
				$this->_exclusions[realpath($basePath.'/'.$exclusion)]=true;
			else
				$this->_exclusions[$exclusion]=true;
		}
	}

	public function buildTree()
	{
		$sourceFiles=$this->getSourceFiles($this->_basePath);
		foreach($sourceFiles as $sourceFile)
			$this->parseFile($sourceFile);
		ksort($this->_classes);
		foreach(array_keys($this->_classes) as $className)
		{
			$parentClass=$this->_classes[$className]['ParentClass'];
			if(isset($this->_classes[$parentClass]))
				$this->_classes[$parentClass]['ChildClasses'][]=$className;
		}
		echo "\nClass tree built successfully. Total ".count($this->_classes)." classes found.\n";
	}

	public function saveToFile($fileName)
	{
		file_put_contents($fileName,serialize($this->_classes));
	}

	public function displayTree()
	{
		$this->displayTreeInternal(array_keys($this->_baseClasses),0);
	}

	public function displayTreeInternal($classNames,$level)
	{
		foreach($classNames as $className)
		{
			echo str_repeat(' ',$level*4);
			echo $className.':'.$this->_classes[$className]->Package."\n";
			$this->displayTreeInternal(array_keys($this->_classes[$className]->ChildClasses),$level+1);
		}
	}

	protected function parseFile($sourceFile)
	{
		include_once($sourceFile);
		$classFile=strtr(substr($sourceFile,strlen($this->_basePath)),'\\','/');
		echo "Parsing $classFile...\n";
		$content=file_get_contents($sourceFile);
		if(preg_match('/@package\s+([\w\.]+)\s*/msS',$content,$matches)>0)
			$package=$matches[1];
		else
			$package='';
		$n=preg_match_all(self::REGEX_RULES,$content,$matches,PREG_SET_ORDER);
		for($i=0;$i<$n;++$i)
		{
			$className=$matches[$i][2];
			if(isset($this->_classes[$className]))
				throw new Exception("Class $className is defined in both $sourceFile and ".$this->_classes[$className]->ClassFile);
			$c=new TComponentReflection($className);
			$properties=$c->getProperties();
			$this->parseMethodComments($properties);
			$events=$c->getEvents();
			$this->parseMethodComments($events);
			$methods=$c->getMethods();
			$this->parseMethodComments($methods);
			$this->_classes[$className]=array(
				'ClassFile'=>$classFile,
				'Package'=>$package,
				'ParentClass'=>isset($matches[$i][4])?$matches[$i][4]:'',
				'ChildClasses'=>array(),
				'Properties'=>$properties,
				'Events'=>$events,
				'Methods'=>$methods);
		}
	}

	protected function parseMethodComments(&$methods)
	{
		foreach(array_keys($methods) as $key)
		{
			$method=&$methods[$key];
			$comments=$method['comments'];
			$s='';
			foreach(explode("\n",$comments) as $line)
			{
				$line=trim($line);
				$line=trim($line,'/*');
				$s.=' '.$line;
			}
			$s=trim($s);
			$s=preg_replace('/\{@link.*?([\w\(\)]+)\}/i','$1',$s);
			$pos1=strpos($s,'@');
			$pos2=strpos($s,'.');
			if($pos1===false)
			{
				if($pos2!==false)
					$method['comments']=substr($s,0,$pos2);
				else
					$method['comments']=$s;
			}
			else if($pos1>0)
			{
				if($pos2 && $pos2<$pos1)	// use the first line as comment
					$method['comments']=substr($s,0,$pos2);
				else
					$method['comments']=substr($s,0,$pos1);
			}
			else
			{
				$matches=array();
				if(preg_match('/@return\s+[\w\|]+\s+([^\.]*)/',$s,$matches)>0)
					$method['comments']=$matches[1];
				else
					$method['comments']='';
			}
		}
	}

	protected function isValidPath($path)
	{
		if(is_dir($path))
			return !isset($this->_exclusions[basename($path)]) && !isset($this->_exclusions[$path]);
		else
			return basename($path)!==basename($path,'.php') && !isset($this->_exclusions[basename($path)]);
	}

	public function getSourceFiles($path)
	{
		$files=array();
		$folder=opendir($path);
		while($file=readdir($folder))
		{
			if($file==='.' || $file==='..')
				continue;
			$fullPath=realpath($path.'/'.$file);
			if($this->isValidPath($fullPath))
			{
				if(is_file($fullPath))
					$files[]=$fullPath;
				else
					$files=array_merge($files,$this->getSourceFiles($fullPath));
			}
		}
		closedir($folder);
		return $files;
	}
}

?>