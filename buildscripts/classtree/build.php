<?php

$basePath=dirname(__FILE__);
$frameworkPath=realpath($basePath.'/../../framework');
require_once($frameworkPath.'/prado.php');
require_once($basePath.'/DWExtension.php');

//the manager class sets up some dependency paths
Prado::using('System.Data.SqlMap.TSqlMapManager');

$exclusions=array(
	'pradolite.php',
	'prado-cli.php',
	'clientscripts.php',
	'.svn',
	'/I18N/core',
	'/3rdParty',
	'/Web/UI/WebControls/assets',
	);
$a=new ClassTreeBuilder($frameworkPath,$exclusions);
$a->buildTree();
$a->saveToFile($basePath.'/classes.data');
$a->saveAsDWExtension($basePath);

class ClassTreeBuilder
{
	const REGEX_RULES='/^\s*(abstract\s+)?class\s+(\w+)(\s+extends\s+(\w+)\s*|\s*)/msS';
	private $_frameworkPath;
	private $_exclusions;
	private $_classes=array();

	public function __construct($frameworkPath,$exclusions)
	{
		$this->_frameworkPath=realpath($frameworkPath);
		$this->_exclusions=array();
		foreach($exclusions as $exclusion)
		{
			if($exclusion[0]==='/')
				$this->_exclusions[realpath($frameworkPath.'/'.$exclusion)]=true;
			else
				$this->_exclusions[$exclusion]=true;
		}
	}

	public function buildTree()
	{
		$sourceFiles=$this->getSourceFiles($this->_frameworkPath);
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
		$classFile=strtr(substr($sourceFile,strlen($this->_frameworkPath)),'\\','/');
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

	public function saveAsDWExtension($basePath)
	{
		$tagPath=$basePath.'/Configuration/TagLibraries/PRADO';

		// prepare the directory to save tag lib
		@mkdir($basePath.'/Configuration');
		@mkdir($basePath.'/Configuration/TagLibraries');
		@mkdir($basePath.'/Configuration/TagLibraries/PRADO');

		$docMXI = new PradoMXIDocument(Prado::getVersion());
		$tagChooser = new PradoTagChooser;

		$controlClass = new ReflectionClass('TControl');

		foreach($this->_classes as $className=>$classInfo)
		{
			$class = new ReflectionClass($className);
			if($class->isInstantiable() && ($className==='TControl' || $class->isSubclassOf($controlClass)))
			{
				$docMXI->addTag($className);
				$tagChooser->addElement($className);
				$docVTM = new PradoVTMDocument($className);
				foreach($classInfo['Properties'] as $name=>$property)
				{
					$type=$property['type'];
					if(isset($this->_classes[$type]) && ($type==='TFont' || strrpos($type,'Style')===strlen($type)-5 && $type!=='TStyle'))
						$this->processObjectType($type,$this->_classes[$type],$name,$docVTM);
					if($property['readonly'] || $property['protected'])
						continue;
					if(($type=$this->checkType($className,$name,$property['type']))!=='')
						$docVTM->addAttribute($name,$type);
				}
				foreach($classInfo['Events'] as $name=>$event)
				{
					$docVTM->addEvent($name);
				}
				file_put_contents($tagPath.'/'.$className.'.vtm',$docVTM->getXML());
			}
		}

		file_put_contents($basePath.'/PRADO.mxi',$docMXI->getXML());
		file_put_contents($tagPath.'/TagChooser.xml',$tagChooser->getXML());

	}

	private	function processObjectType($objectType,$objectInfo,$prefix,$doc)
	{
		foreach($objectInfo['Properties'] as $name=>$property)
		{
			if($property['type']==='TFont')
				$this->processObjectType('TFont',$this->_classes['TFont'],$prefix.'.'.$name,$doc);
			if($property['readonly'] || $property['protected'])
				continue;
			if(($type=$this->checkType($objectType,$name,$property['type']))!=='')
				$doc->addAttribute($prefix.'.'.$name,$type);
		}
	}

	private	function checkType($className,$propertyName,$type)
	{
		if(strrpos($propertyName,'Color')===strlen($propertyName)-5)
			return 'color';
		if($propertyName==='Style')
			return 'style';
		if($type==='boolean')
			return array('true','false');
		if($type==='string' || $type==='integer' || $type==='ITemplate')
			return 'text';
		return '';
	}
}

?>