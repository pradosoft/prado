<?php
class TComponent
{
private $_e=array();
public function __construct()
{
}
public function __destruct()
{
}
public function __get($name)
{
$getter='get'.$name;
if(method_exists($this,$getter))
{
return $this->$getter();
}
else if(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
{
$name=strtolower($name);
if(!isset($this->_e[$name]))
$this->_e[$name]=new TList;
return $this->_e[$name];
}
else
{
throw new TInvalidOperationException('component_property_undefined',get_class($this),$name);
}
}
public function __set($name,$value)
{
$setter='set'.$name;
if(method_exists($this,$setter))
{
$this->$setter($value);
}
else if(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
{
$this->attachEventHandler($name,$value);
}
else if(method_exists($this,'get'.$name))
{
throw new TInvalidOperationException('component_property_readonly',get_class($this),$name);
}
else
{
throw new TInvalidOperationException('component_property_undefined',get_class($this),$name);
}
}
public function hasProperty($name)
{
return method_exists($this,'get'.$name) || method_exists($this,'set'.$name);
}
public function canGetProperty($name)
{
return method_exists($this,'get'.$name);
}
public function canSetProperty($name)
{
return method_exists($this,'set'.$name);
}
public function getSubProperty($path)
{
$object=$this;
foreach(explode('.',$path) as $property)
$object=$object->$property;
return $object;
}
public function setSubProperty($path,$value)
{
$object=$this;
if(($pos=strrpos($path,'.'))===false)
$property=$path;
else
{
$object=$this->getSubProperty(substr($path,0,$pos));
$property=substr($path,$pos+1);
}
$object->$property=$value;
}
public function hasEvent($name)
{
return strncasecmp($name,'on',2)===0 && method_exists($this,$name);
}
public function hasEventHandler($name)
{
$name=strtolower($name);
return isset($this->_e[$name]) && $this->_e[$name]->getCount()>0;
}
public function getEventHandlers($name)
{
if(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
{
$name=strtolower($name);
if(!isset($this->_e[$name]))
$this->_e[$name]=new TList;
return $this->_e[$name];
}
else
throw new TInvalidOperationException('component_event_undefined',get_class($this),$name);
}
public function attachEventHandler($name,$handler)
{
$this->getEventHandlers($name)->add($handler);
}
public function detachEventHandler($name,$handler)
{
if($this->hasEventHandler($name))
{
try
{
$this->getEventHandlers($name)->remove($handler);
return true;
}
catch(Exception $e)
{
}
}
return false;
}
public function raiseEvent($name,$sender,$param)
{
$name=strtolower($name);
if(isset($this->_e[$name]))
{
foreach($this->_e[$name] as $handler)
{
if(is_string($handler))
{
call_user_func($handler,$sender,$param);
}
else if(is_callable($handler,true))
{
list($object,$method)=$handler;
if(is_string($object))							call_user_func($handler,$sender,$param);
else
{
if(($pos=strrpos($method,'.'))!==false)
{
$object=$this->getSubProperty(substr($method,0,$pos));
$method=substr($method,$pos+1);
}
$object->$method($sender,$param);
}
}
else
throw new TInvalidDataValueException('component_eventhandler_invalid',get_class($this),$name);
}
}
else if(!$this->hasEvent($name))
throw new TInvalidOperationException('component_event_undefined',get_class($this),$name);
}
public function evaluateExpression($expression)
{
try
{
if(eval("\$result=$expression;")===false)
throw new Exception('');
return $result;
}
catch(Exception $e)
{
throw new TInvalidOperationException('component_expression_invalid',get_class($this),$expression,$e->getMessage());
}
}
public function evaluateStatements($statements)
{
try
{
ob_start();
if(eval($statements)===false)
throw new Exception('');
$content=ob_get_contents();
ob_end_clean();
return $content;
}
catch(Exception $e)
{
throw new TInvalidOperationException('component_statements_invalid',get_class($this),$statements,$e->getMessage());
}
}
public function getApplication()
{
return Prado::getApplication();
}
public function getService()
{
return Prado::getApplication()->getService();
}
public function getRequest()
{
return Prado::getApplication()->getRequest();
}
public function getResponse()
{
return Prado::getApplication()->getResponse();
}
public function getSession()
{
return Prado::getApplication()->getSession();
}
public function getUser()
{
return Prado::getApplication()->getUser();
}
}
class TPropertyValue
{
public static function ensureBoolean($value)
{
if (is_string($value))
return strcasecmp($value,'true')==0 || $value!=0;
else
return (boolean)$value;
}
public static function ensureString($value)
{
if (is_bool($value))
return $value?'true':'false';
else
return (string)$value;
}
public static function ensureInteger($value)
{
return (integer)$value;
}
public static function ensureFloat($value)
{
return (float)$value;
}
public static function ensureArray($value)
{
if(is_string($value))
{
$trimmed = trim($value);
$len = strlen($value);
if ($len >= 2 && $trimmed[0] == '(' && $trimmed[$len-1] == ')')
{
eval('$array=array'.$trimmed.';');
return $array;
}
else
return $len>0?array($value):array();
}
else
return (array)$value;
}
public static function ensureObject($value)
{
return (object)$value;
}
public static function ensureEnum($value,$enums)
{
if(!is_array($enums))
{
$enums=func_get_args();
array_shift($enums);
}
if(in_array($value,$enums,true))
return $value;
else
throw new TInvalidDataValueException('propertyvalue_enumvalue_invalid',$value,implode(' | ',$enums));
}
}
class TEventParameter extends TComponent
{
}

class TException extends Exception
{
private $_errorCode='';
public function __construct($errorMessage)
{
$this->_errorCode=$errorMessage;
$args=func_get_args();
$args[0]=$this->translateErrorMessage($errorMessage);
$str=call_user_func_array('sprintf',$args);
parent::__construct($str);
}
protected function translateErrorMessage($key)
{
$lang=Prado::getPreferredLanguage();
$msgFile=Prado::getFrameworkPath().'/Exceptions/messages-'.$lang.'.txt';
if(!is_file($msgFile))
$msgFile=Prado::getFrameworkPath().'/Exceptions/messages.txt';
if(($entries=@file($msgFile))===false)
return $key;
else
{
foreach($entries as $entry)
{
@list($code,$message)=explode('=',$entry,2);
if(trim($code)===$key)
return trim($message);
}
return $key;
}
}
public function getErrorCode()
{
return $this->_errorCode;
}
public function getErrorMessage()
{
return $this->getMessage();
}
}
class TSystemException extends TException
{
}
class TApplicationException extends TException
{
}
class TInvalidOperationException extends TSystemException
{
}
class TInvalidDataTypeException extends TSystemException
{
}
class TInvalidDataValueException extends TSystemException
{
}
class TInvalidDataFormatException extends TSystemException
{
}
class TConfigurationException extends TSystemException
{
}
class TIOException extends TSystemException
{
}
class TDBException extends TSystemException
{
}
class TSecurityException extends TSystemException
{
}
class TNotSupportedException extends TSystemException
{
}
class TPhpErrorException extends TSystemException
{
public function __construct($errno,$errstr,$errfile,$errline)
{
static $errorTypes=array(
E_ERROR           => "Error",
E_WARNING         => "Warning",
E_PARSE           => "Parsing Error",
E_NOTICE          => "Notice",
E_CORE_ERROR      => "Core Error",
E_CORE_WARNING    => "Core Warning",
E_COMPILE_ERROR   => "Compile Error",
E_COMPILE_WARNING => "Compile Warning",
E_USER_ERROR      => "User Error",
E_USER_WARNING    => "User Warning",
E_USER_NOTICE     => "User Notice",
E_STRICT          => "Runtime Notice"
);
$errorType=isset($errorTypes[$errno])?$errorTypes[$errno]:'Unknown Error';
parent::__construct("[$errorType] $errstr (@line $errline in file $errfile).");
}
}
class THttpException extends TSystemException
{
private $_statusCode;
public function __construct($statusCode,$errorMessage)
{
$args=func_get_args();
array_shift($args);
call_user_func_array(array('TException', '__construct'), $args);
$this->_statusCode=TPropertyValue::ensureInteger($statusCode);
}
public function getStatusCode()
{
return $this->_statusCode;
}
}

class TList extends TComponent implements IteratorAggregate,ArrayAccess
{
private $_d=array();
private $_c=0;
public function __construct($data=null)
{
parent::__construct();
if($data!==null)
$this->copyFrom($data);
}
public function getIterator()
{
return new TListIterator($this->_d);
}
public function getCount()
{
return $this->_c;
}
public function itemAt($index)
{
if($index>=0 && $index<$this->_c)
return $this->_d[$index];
else
throw new TInvalidDataValueException('list_index_invalid',$index);
}
public function add($item)
{
$this->insertAt($this->_c,$item);
}
public function insertAt($index,$item)
{
if($index===$this->_c)
$this->_d[$this->_c++]=$item;
else if($index>=0 && $index<$this->_c)
{
array_splice($this->_d,$index,0,array($item));
$this->_c++;
}
else
throw new TInvalidDataValueException('list_index_invalid',$index);
}
public function remove($item)
{
if(($index=$this->indexOf($item))>=0)
$this->removeAt($index);
else
throw new TInvalidDataValueException('list_item_inexistent');
}
public function removeAt($index)
{
if($index>=0 && $index<$this->_c)
{
$this->_c--;
if($index===$this->_c)
return array_pop($this->_d);
else
{
$item=$this->_d[$index];
array_splice($this->_d,$index,1);
return $item;
}
}
else
throw new TInvalidDataValueException('list_index_invalid',$index);
}
public function clear()
{
for($i=$this->_c-1;$i>=0;--$i)
$this->removeAt($i);
}
public function contains($item)
{
return $this->indexOf($item)>=0;
}
public function indexOf($item)
{
if(($index=array_search($item,$this->_d,true))===false)
return -1;
else
return $index;
}
public function toArray()
{
return $this->_d;
}
public function copyFrom($data)
{
if(is_array($data) || ($data instanceof Traversable))
{
if($this->_c>0)
$this->clear();
foreach($data as $item)
$this->add($item);
}
else if($data!==null)
throw new TInvalidDataTypeException('list_data_not_iterable');
}
public function mergeWith($data)
{
if(is_array($data) || ($data instanceof Traversable))
{
foreach($data as $item)
$this->add($item);
}
else if($data!==null)
throw new TInvalidDataTypeException('list_data_not_iterable');
}
public function offsetExists($offset)
{
return ($offset>=0 && $offset<$this->_c);
}
public function offsetGet($offset)
{
if($offset>=0 && $offset<$this->_c)
return $this->_d[$offset];
else
throw new TInvalidDataValueException('list_index_invalid',$offset);
}
public function offsetSet($offset,$item)
{
if($offset===null || $offset===$this->_c)
$this->insertAt($this->_c,$item);
else
{
$this->removeAt($offset);
$this->insertAt($offset,$item);
}
}
public function offsetUnset($offset)
{
$this->removeAt($offset);
}
}
class TListIterator implements Iterator
{
private $_d;
private $_i;
private $_c;
public function __construct(&$data)
{
$this->_d=&$data;
$this->_i=0;
$this->_c=count($this->_d);
}
public function rewind()
{
$this->_i=0;
}
public function key()
{
return $this->_i;
}
public function current()
{
return $this->_d[$this->_i];
}
public function next()
{
$this->_i++;
}
public function valid()
{
return $this->_i<$this->_c;
}
}

class TMap extends TComponent implements IteratorAggregate,ArrayAccess
{
private $_d=array();
public function __construct($data=null)
{
parent::__construct();
if($data!==null)
$this->copyFrom($data);
}
public function getIterator()
{
return new TMapIterator($this->_d);
}
public function getCount()
{
return count($this->_d);
}
public function getKeys()
{
return array_keys($this->_d);
}
public function itemAt($key)
{
return isset($this->_d[$key]) ? $this->_d[$key] : null;
}
public function add($key,$value)
{
$this->_d[$key]=$value;
}
public function remove($key)
{
if(isset($this->_d[$key]) || array_key_exists($key,$this->_d))
{
$value=$this->_d[$key];
unset($this->_d[$key]);
return $value;
}
else
return null;
}
public function clear()
{
foreach(array_keys($this->_d) as $key)
$this->remove($key);
}
public function contains($key)
{
return isset($this->_d[$key]) || array_key_exists($key,$this->_d);
}
public function toArray()
{
return $this->_d;
}
public function copyFrom($data)
{
if(is_array($data) || $data instanceof Traversable)
{
if($this->getCount()>0)
$this->clear();
foreach($data as $key=>$value)
$this->add($key,$value);
}
else if($data!==null)
throw new TInvalidDataTypeException('map_data_not_iterable');
}
public function mergeWith($data)
{
if(is_array($data) || $data instanceof Traversable)
{
foreach($data as $key=>$value)
$this->add($key,$value);
}
else if($data!==null)
throw new TInvalidDataTypeException('map_data_not_iterable');
}
public function offsetExists($offset)
{
return $this->contains($offset);
}
public function offsetGet($offset)
{
return $this->itemAt($offset);
}
public function offsetSet($offset,$item)
{
$this->add($offset,$item);
}
public function offsetUnset($offset)
{
$this->remove($offset);
}
}
class TMapIterator implements Iterator
{
private $_d;
private $_keys;
private $_key;
public function __construct(&$data)
{
$this->_d=&$data;
$this->_keys=array_keys($data);
}
public function rewind()
{
$this->_key=reset($this->_keys);
}
public function key()
{
return $this->_key;
}
public function current()
{
return $this->_d[$this->_key];
}
public function next()
{
$this->_key=next($this->_keys);
}
public function valid()
{
return $this->_key!==false;
}
}

class TAttributeCollection extends TMap
{
public function __get($name)
{
return $this->contains($name)?$this->itemAt($name):parent::__get($name);
}
public function __set($name,$value)
{
$this->add($name,$value);
}
public function itemAt($key)
{
return parent::itemAt(strtolower($key));
}
public function add($key,$value)
{
parent::add(strtolower($key),$value);
}
public function remove($key)
{
return parent::remove(strtolower($key));
}
public function contains($key)
{
return parent::contains(strtolower($key));
}
public function hasProperty($name)
{
return $this->contains($name) || parent::hasProperty($name);
}
public function canGetProperty($name)
{
return $this->contains($name) || parent::canGetProperty($name);
}
public function canSetProperty($name)
{
return true;
}
}

class TXmlElement extends TComponent
{
private $_parent=null;
private $_tagName;
private $_value;
private $_elements=null;
private $_attributes=null;
public function __construct($tagName)
{
parent::__construct();
$this->setTagName($tagName);
}
public function getParent()
{
return $this->_parent;
}
public function setParent($parent)
{
$this->_parent=$parent;
}
public function getTagName()
{
return $this->_tagName;
}
public function setTagName($tagName)
{
$this->_tagName=$tagName;
}
public function getValue()
{
return $this->_value;
}
public function setValue($value)
{
$this->_value=$value;
}
public function getHasElement()
{
return $this->_elements!==null && $this->_elements->getCount()>0;
}
public function getHasAttribute()
{
return $this->_attributes!==null && $this->_attributes->getCount()>0;
}
public function getAttribute($name)
{
if($this->_attributes!==null)
return $this->_attributes->itemAt($name);
else
return null;
}
public function getElements()
{
if(!$this->_elements)
$this->_elements=new TXmlElementList($this);
return $this->_elements;
}
public function getAttributes()
{
if(!$this->_attributes)
$this->_attributes=new TMap;
return $this->_attributes;
}
public function getElementByTagName($tagName)
{
if($this->_elements)
{
foreach($this->_elements as $element)
if($element->_tagName===$tagName)
return $element;
}
return null;
}
public function getElementsByTagName($tagName)
{
$list=new TList;
if($this->_elements)
{
foreach($this->_elements as $element)
if($element->_tagName===$tagName)
$list->add($element);
}
return $list;
}
public function toString($indent=0)
{
$attr='';
if($this->_attributes!==null)
{
foreach($this->_attributes as $name=>$value)
$attr.=" $name=\"$value\"";
}
$prefix=str_repeat(' ',$indent*4);
if($this->getHasElement())
{
$str=$prefix."<{$this->_tagName}$attr>\n";
foreach($this->getElements() as $element)
$str.=$element->toString($indent+1)."\n";
$str.=$prefix."</{$this->_tagName}>";
return $str;
}
else if($this->getValue()!=='')
{
return $prefix."<{$this->_tagName}$attr>{$this->_value}</{$this->_tagName}>";
}
else
return $prefix."<{$this->_tagName}$attr />";
}
}
class TXmlDocument extends TXmlElement
{
private $_version;
private $_encoding;
public function __construct($version='1.0',$encoding='')
{
parent::__construct('');
$this->setversion($version);
$this->setEncoding($encoding);
}
public function getVersion()
{
return $this->_version;
}
public function setVersion($version)
{
$this->_version=$version;
}
public function getEncoding()
{
return $this->_encoding;
}
public function setEncoding($encoding)
{
$this->_encoding=$encoding;
}
public function loadFromFile($file)
{
if(($str=@file_get_contents($file))!==false)
return $this->loadFromString($str);
else
throw new TIOException('xmldocument_file_read_failed',$file);
}
public function loadFromString($string)
{
$doc=new DOMDocument();
if($doc->loadXML($string)===false)
return false;
$this->setEncoding($doc->encoding);
$this->setVersion($doc->version);
$element=$doc->documentElement;
$this->setTagName($element->tagName);
$this->setValue($element->nodeValue);
$elements=$this->getElements();
$attributes=$this->getAttributes();
$elements->clear();
$attributes->clear();
foreach($element->attributes as $name=>$attr)
$attributes->add($name,$attr->value);
foreach($element->childNodes as $child)
{
if($child instanceof DOMElement)
$elements->add($this->buildElement($child));
}
return true;
}
public function saveToFile($file)
{
if(($fw=fopen($file,'w'))!==false)
{
fwrite($fw,$this->saveToString());
fclose($fw);
}
else
throw new TIOException('xmldocument_file_write_failed',$file);
}
public function saveToString()
{
$version=empty($this->_version)?' version="1.0"':' version="'.$this->_version.'"';
$encoding=empty($this->_encoding)?'':' encoding="'.$this->_encoding.'"';
return "<?xml{$version}{$encoding}?>\n".$this->toString(0);
}
private function buildElement($node)
{
$element=new TXmlElement($node->tagName);
$element->setValue($node->nodeValue);
foreach($node->attributes as $name=>$attr)
$element->getAttributes()->add($name,$attr->value);
foreach($node->childNodes as $child)
{
if($child instanceof DOMElement)
$element->getElements()->add($this->buildElement($child));
}
return $element;
}
}
class TXmlElementList extends TList
{
private $_o;
public function __construct(TXmlElement $owner)
{
parent::__construct();
$this->_o=$owner;
}
protected function getOwner()
{
return $this->_o;
}
public function insertAt($index,$item)
{
if($item instanceof TXmlElement)
{
parent::insertAt($index,$item);
if($item->getParent()!==null)
$item->getParent()->getElements()->remove($item);
$item->setParent($this->_o);
}
else
throw new TInvalidDataTypeException('xmlelementlist_xmlelement_required');
}
public function removeAt($index)
{
$item=parent::removeAt($index);
if($item instanceof TXmlElement)
$item->setParent(null);
return $item;
}
}

class THttpUtility
{
private static $_entityTable=null;
public static function htmlEncode($s)
{
return htmlspecialchars($s);
}
public static function htmlDecode($s)
{
if(!self::$_entityTable)
self::buildEntityTable();
return strtr($s,self::$_entityTable);
}
private static function buildEntityTable()
{
self::$_entityTable=array_flip(get_html_translation_table(HTML_ENTITIES,ENT_QUOTES));
}
public static function quoteJavaScriptString($js,$forUrl=false)
{
if($forUrl)
return strtr($js,array('%'=>'%25',"\t"=>'\t',"\n"=>'\n',"\r"=>'\r','"'=>'\"','\''=>'\\\'','\\'=>'\\\\'));
else
return strtr($js,array("\t"=>'\t',"\n"=>'\n',"\r"=>'\r','"'=>'\"','\''=>'\\\'','\\'=>'\\\\'));
}
public static function trimJavaScriptString($js)
{
if($js!=='' && $js!==null)
{
$js=trim($js);
if(($pos=strpos($js,'javascript:'))===0)
$js=substr($js,11);
$js=rtrim($js,';').';';
}
return $js;
}
}

interface ICache
{
public function get($id);
public function set($id,$value,$expire=0);
public function add($id,$value,$expire=0);
public function replace($id,$value,$expire=0);
public function delete($id);
public function flush();
}
interface IDependency
{
}
class TTimeDependency
{
}
class TFileDependency
{
}
class TDirectoryDependency
{
}

class TLogger extends TComponent
{
const DEBUG=0x01;
const INFO=0x02;
const NOTICE=0x04;
const WARNING=0x08;
const ERROR=0x10;
const ALERT=0x20;
const FATAL=0x40;
private $_logs=array();
private $_levels;
private $_categories;
public function log($message,$level,$category='Uncategorized')
{
$this->_logs[]=array($message,$level,$category,microtime(true));
}
public function getLogs($levels=null,$categories=null)
{
$this->_levels=$levels;
$this->_categories=$categories;
if(empty($levels) && empty($categories))
return $this->_logs;
else if(empty($levels))
return array_values(array_filter(array_filter($this->_logs,array($this,'filterByCategories'))));
else if(empty($categories))
return array_values(array_filter(array_filter($this->_logs,array($this,'filterByLevels'))));
else
{
$ret=array_values(array_filter(array_filter($this->_logs,array($this,'filterByLevels'))));
return array_values(array_filter(array_filter($ret,array($this,'filterByCategories'))));
}
}
private function filterByCategories($value)
{
foreach($this->_categories as $category)
{
if($value[2]===$category || strpos($value[2],$category.'.')===0)
return $value;
}
return false;
}
private function filterByLevels($value)
{
if($value[1] & $this->_levels)
return $value;
else
return false;
}
}

define('PRADO_DIR',dirname(__FILE__));
interface IModule
{
public function init($config);
public function getID();
public function setID($id);
}
interface IService
{
public function init($config);
public function getID();
public function setID($id);
public function run();
}
interface ITextWriter
{
public function write($str);
public function flush();
}
interface ITheme
{
public function applySkin($control);
}
interface ITemplate
{
public function instantiateIn($parent);
}
interface IUser
{
public function getName();
public function setName($value);
public function getIsGuest();
public function setIsGuest($value);
public function getRoles();
public function setRoles($value);
public function isInRole($role);
public function saveToString();
public function loadFromString($string);
}
interface IStatePersister
{
public function load();
public function save($state);
}
abstract class TModule extends TComponent implements IModule
{
private $_id;
public function init($config)
{
}
public function getID()
{
return $this->_id;
}
public function setID($value)
{
$this->_id=$value;
}
}
abstract class TService extends TComponent implements IService
{
private $_id;
public function init($config)
{
}
public function getID()
{
return $this->_id;
}
public function setID($value)
{
$this->_id=$value;
}
public function run()
{
}
}
class PradoBase
{
const CLASS_FILE_EXT='.php';
private static $_aliases=array('System'=>PRADO_DIR);
private static $_usings=array();
private static $_application=null;
private static $_logger=null;
public static function getVersion()
{
return '3.0b';
}
public static function poweredByPrado()
{
return '<a title="Powered by PRADO" href="http://www.pradosoft.com/"><img src="http://www.pradosoft.com/images/powered.gif" style="border-width:0px;" alt="Powered by PRADO" /></a>';
}
public static function phpErrorHandler($errno,$errstr,$errfile,$errline)
{
if(error_reporting()!=0)
throw new TPhpErrorException($errno,$errstr,$errfile,$errline);
}
public static function exceptionHandler($exception)
{
if(self::$_application!==null && ($errorHandler=self::$_application->getErrorHandler())!==null)
{
$errorHandler->handleError(null,$exception);
}
else
{
echo $exception;
}
exit(1);
}
public static function setApplication($application)
{
if(self::$_application!==null)
throw new TInvalidOperationException('prado_application_singleton_required');
self::$_application=$application;
}
public static function getApplication()
{
return self::$_application;
}
public static function getFrameworkPath()
{
return PRADO_DIR;
}
public static function serialize($data)
{
$arr[0]=$data;
return serialize($arr);
}
public static function unserialize($str)
{
$arr=unserialize($str);
return isset($arr[0])?$arr[0]:null;
}
public static function createComponent($type)
{
self::using($type);
if(($pos=strrpos($type,'.'))!==false)
$type=substr($type,$pos+1);
if(($n=func_num_args())>1)
{
$args=func_get_args();
$s='$args[1]';
for($i=2;$i<$n;++$i)
$s.=",\$args[$i]";
eval("\$component=new $type($s);");
return $component;
}
else
return new $type;
}
public static function using($namespace)
{
if(isset(self::$_usings[$namespace]) || class_exists($namespace,false))
return;
if(($pos=strrpos($namespace,'.'))===false)  		{
try
{
include_once($namespace.self::CLASS_FILE_EXT);
}
catch(Exception $e)
{
if(!class_exists($namespace,false))
throw new TInvalidOperationException('prado_component_unknown',$namespace);
else
throw $e;
}
}
else if(($path=self::getPathOfNamespace($namespace,self::CLASS_FILE_EXT))!==null)
{
$className=substr($namespace,$pos+1);
if($className==='*')  			{
if(is_dir($path))
{
self::$_usings[$namespace]=$path;
set_include_path(get_include_path().PATH_SEPARATOR.$path);
}
else
throw new TInvalidDataValueException('prado_using_invalid',$namespace);
}
else  			{
if(is_file($path))
{
self::$_usings[$namespace]=$path;
if(!class_exists($className,false))
{
try
{
include_once($path);
}
catch(Exception $e)
{
if(!class_exists($className,false))
throw new TInvalidOperationException('prado_component_unknown',$className);
else
throw $e;
}
}
}
else
throw new TInvalidDataValueException('prado_using_invalid',$namespace);
}
}
else
throw new TInvalidDataValueException('prado_using_invalid',$namespace);
}
public static function getPathOfNamespace($namespace,$ext='')
{
if(isset(self::$_usings[$namespace]))
return self::$_usings[$namespace];
else if(isset(self::$_aliases[$namespace]))
return self::$_aliases[$namespace];
else
{
$segs=explode('.',$namespace);
$alias=array_shift($segs);
if(($file=array_pop($segs))!==null && ($root=self::getPathOfAlias($alias))!==null)
return rtrim($root.'/'.implode('/',$segs),'/').(($file==='*')?'':'/'.$file.$ext);
else
return null;
}
}
public static function getPathOfAlias($alias)
{
return isset(self::$_aliases[$alias])?self::$_aliases[$alias]:null;
}
public static function setPathOfAlias($alias,$path)
{
if(isset(self::$_aliases[$alias]))
throw new TInvalidOperationException('prado_alias_redefined',$alias);
else if(($rp=realpath($path))!==false && is_dir($rp))
{
if(strpos($alias,'.')===false)
self::$_aliases[$alias]=$rp;
else
throw new TInvalidDataValueException('prado_aliasname_invalid',$alias);
}
else
throw new TInvalidDataValueException('prado_alias_invalid',$alias,$path);
}
public static function fatalError($msg)
{
echo '<h1>Fatal Error</h1>';
echo '<p>'.$msg.'</p>';
if(!function_exists('debug_backtrace'))
return;
echo '<h2>Debug Backtrace</h2>';
echo '<pre>';
$index=-1;
foreach(debug_backtrace() as $t)
{
$index++;
if($index==0)  				continue;
echo '#'.$index.' ';
if(isset($t['file']))
echo basename($t['file']) . ':' . $t['line'];
else
echo '<PHP inner-code>';
echo ' -- ';
if(isset($t['class']))
echo $t['class'] . $t['type'];
echo $t['function'];
if(isset($t['args']) && sizeof($t['args']) > 0)
echo '(...)';
else
echo '()';
echo "\n";
}
echo '</pre>';
exit(1);
}
public static function getUserLanguages()
{
static $languages=null;
if($languages===null)
{
if(!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
$languages[0]='en';
else
{
$languages=array();
foreach(explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']) as $language)
{
$array=split(';q=',trim($language));
$languages[trim($array[0])]=isset($array[1])?(float)$array[1]:1.0;
}
arsort($languages);
$languages=array_keys($languages);
if(empty($languages))
$languages[0]='en';
}
}
return $languages;
}
public static function getPreferredLanguage()
{
static $language=null;
if($language===null)
{
$langs=Prado::getUserLanguages();
$lang=explode('-',$langs[0]);
if(empty($lang[0]) || !ctype_alpha($lang[0]))
$language='en';
else
$language=$lang[0];
}
return $language;
}
public static function trace($msg,$category='Uncategorized')
{
if(self::$_application && self::$_application->getMode()===TApplication::STATE_PERFORMANCE)
return;
if(!self::$_application || self::$_application->getMode()===TApplication::STATE_DEBUG)
{
$trace=debug_backtrace();
if(isset($trace[0]['file']) && isset($trace[0]['line']))
$msg.=" (line {$trace[0]['line']}, {$trace[0]['file']})";
$level=TLogger::DEBUG;
}
else
$level=TLogger::INFO;
self::log($msg,$level,$category);
}
public static function log($msg,$level=TLogger::INFO,$category='Uncategorized')
{
if(self::$_logger===null)
self::$_logger=new TLogger;
self::$_logger->log($msg,$level,$category);
}
public static function getLogger()
{
if(self::$_logger===null)
self::$_logger=new TLogger;
return self::$_logger;
}
}
class TTextWriter extends TComponent implements ITextWriter
{
private $_str='';
public function flush()
{
$str=$this->_str;
$this->_str='';
return $str;
}
public function write($str)
{
$this->_str.=$str;
}
public function writeLine($str='')
{
$this->write($str."\n");
}
}
class TDate extends TComponent
{
}

if(!class_exists('Prado',false))
{
class Prado extends PradoBase
{
}
}
if(!function_exists('__autoload'))
{
function __autoload($className)
{
include_once($className.Prado::CLASS_FILE_EXT);
if(!class_exists($className,false) && !interface_exists($className,false))
Prado::fatalError("Class file for '$className' cannot be found.");
}
}
set_error_handler(array('Prado','phpErrorHandler'),error_reporting());
set_exception_handler(array('Prado','exceptionHandler'));

class TApplication extends TComponent
{
const STATE_OFF='Off';
const STATE_DEBUG='Debug';
const STATE_NORMAL='Normal';
const STATE_PERFORMANCE='Performance';
const PAGE_SERVICE_ID='page';
const CONFIG_FILE='application.xml';
const RUNTIME_PATH='runtime';
const CONFIGCACHE_FILE='config.cache';
const GLOBAL_FILE='global.cache';
private static $_steps=array(
'onBeginRequest',
'onAuthentication',
'onPostAuthentication',
'onAuthorization',
'onPostAuthorization',
'onLoadState',
'onPostLoadState',
'onPreRunService',
'onRunService',
'onPostRunService',
'onSaveState',
'onPostSaveState',
'onEndRequest'
);
private $_id;
private $_uniqueID;
private $_requestCompleted=false;
private $_step;
private $_service=null;
private $_pageService=null;
private $_modules;
private $_parameters;
private $_configFile;
private $_basePath;
private $_runtimePath;
private $_stateChanged=false;
private $_globals=array();
private $_cacheFile;
private $_errorHandler=null;
private $_request=null;
private $_response=null;
private $_session=null;
private $_cache=null;
private $_statePersister=null;
private $_user=null;
private $_globalization=null;
private $_authRules=null;
private $_mode='Debug';
public function __construct($basePath='protected',$cacheConfig=true)
{
parent::__construct();
Prado::setApplication($this);
if(($this->_basePath=realpath($basePath))===false)
throw new TConfigurationException('application_basepath_invalid',$basePath);
if(is_file($this->_basePath))
{
$this->_configFile=$this->_basePath;
$this->_basePath=dirname($this->_basepath);
}
else if(is_file($this->_basePath.'/'.self::CONFIG_FILE))
$this->_configFile=$this->_basePath.'/'.self::CONFIG_FILE;
else
$this->_configFile=null;
$this->_runtimePath=$this->_basePath.'/'.self::RUNTIME_PATH;
if(is_writable($this->_runtimePath))
{
if($this->_configFile!==null)
{
$subdir=basename($this->_configFile);
$this->_runtimePath.='/'.$subdir;
if(!is_dir($this->_runtimePath))
mkdir($this->_runtimePath);
}
}
else
throw new TConfigurationException('application_runtimepath_invalid',$this->_runtimePath);
$this->_cacheFile=$cacheConfig ? $this->_runtimePath.'/'.self::CONFIGCACHE_FILE : null;
$this->_uniqueID=md5($this->_runtimePath);
}
public function __destruct()
{
$this->onExitApplication();
}
public function run()
{
try
{
$this->initApplication();
$n=count(self::$_steps);
$this->_step=0;
$this->_requestCompleted=false;
while($this->_step<$n)
{
if($this->_mode===self::STATE_OFF)
throw new THttpException(503,'application_service_unavailable');
$method=self::$_steps[$this->_step];

$this->$method();
if($this->_requestCompleted && $this->_step<$n-1)
$this->_step=$n-1;
else
$this->_step++;
}
}
catch(Exception $e)
{
$this->onError($e);
}
}
public function completeRequest()
{
$this->_requestCompleted=true;
}
public function getGlobalState($key,$defaultValue=null)
{
return isset($this->_globals[$key])?$this->_globals[$key]:$defaultValue;
}
public function setGlobalState($key,$value,$defaultValue=null)
{
$this->_stateChanged=true;
if($value===$defaultValue)
unset($this->_globals[$key]);
else
$this->_globals[$key]=$value;
}
public function clearGlobalState($key)
{
$this->_stateChanged=true;
unset($this->_globals[$key]);
}
protected function loadGlobals()
{
$this->_globals=$this->getApplicationStatePersister()->load();
}
protected function saveGlobals()
{
if(!$this->_stateChanged)
return;
$this->getApplicationStatePersister()->save($this->_globals);
}
public function getID()
{
return $this->_id;
}
public function setID($value)
{
$this->_id=$value;
}
public function getUniqueID()
{
return $this->_uniqueID;
}
public function getMode()
{
return $this->_mode;
}
public function setMode($value)
{
$this->_mode=TPropertyValue::ensureEnum($value,array(self::STATE_OFF,self::STATE_DEBUG,self::STATE_NORMAL,self::STATE_PERFORMANCE));
}
public function getBasePath()
{
return $this->_basePath;
}
public function getConfigurationFile()
{
return $this->_configFile;
}
public function getRuntimePath()
{
return $this->_runtimePath;
}
public function getService()
{
return $this->_service;
}
public function setModule($id,IModule $module)
{
if(isset($this->_modules[$id]))
throw new TConfigurationException('application_moduleid_duplicated',$id);
else
$this->_modules[$id]=$module;
}
public function getModule($id)
{
return isset($this->_modules[$id])?$this->_modules[$id]:null;
}
public function getModules()
{
return $this->_modules;
}
public function getParameters()
{
return $this->_parameters;
}
public function getPageService()
{
if(!$this->_pageService)
{
$this->_pageService=new TPageService;
$this->_pageService->init(null);
}
return $this->_pageService;
}
public function setPageService(TPageService $service)
{
$this->_pageService=$service;
}
public function getRequest()
{
if(!$this->_request)
{
$this->_request=new THttpRequest;
$this->_request->init(null);
}
return $this->_request;
}
public function setRequest(THttpRequest $request)
{
$this->_request=$request;
}
public function getResponse()
{
if(!$this->_response)
{
$this->_response=new THttpResponse;
$this->_response->init(null);
}
return $this->_response;
}
public function setResponse(THttpResponse $response)
{
$this->_response=$response;
}
public function getSession()
{
if(!$this->_session)
{
$this->_session=new THttpSession;
$this->_session->init(null);
}
return $this->_session;
}
public function setSession(THttpSession $session)
{
$this->_session=$session;
}
public function getErrorHandler()
{
if(!$this->_errorHandler)
{
$this->_errorHandler=new TErrorHandler;
$this->_errorHandler->init(null);
}
return $this->_errorHandler;
}
public function setErrorHandler(TErrorHandler $handler)
{
$this->_errorHandler=$handler;
}
public function getApplicationStatePersister()
{
if(!$this->_statePersister)
{
$this->_statePersister=new TApplicationStatePersister;
$this->_statePersister->init(null);
}
return $this->_statePersister;
}
public function setApplicationStatePersister(IStatePersister $persister)
{
$this->_statePersister=$persister;
}
public function getCache()
{
return $this->_cache;
}
public function setCache(ICache $cache)
{
$this->_cache=$cache;
}
public function getUser()
{
return $this->_user;
}
public function setUser(IUser $user)
{
$this->_user=$user;
}
public function getGlobalization()
{
return $this->_globalization;
}
public function setGlobalization(TGlobalization $handler)
{
$this->_globalization = $handler;
}
public function getAuthorizationRules()
{
if($this->_authRules===null)
$this->_authRules=new TAuthorizationRuleCollection;
return $this->_authRules;
}
protected function initApplication()
{

Prado::setPathOfAlias('Application',$this->_basePath);
if($this->_configFile===null)
{
$this->getRequest()->setAvailableServices(array(self::PAGE_SERVICE_ID));
$this->_service=$this->getPageService();
return;
}
if($this->_cacheFile===null || @filemtime($this->_cacheFile)<filemtime($this->_configFile))
{
$config=new TApplicationConfiguration;
$config->loadFromFile($this->_configFile);
if($this->_cacheFile!==null)
{
if(($fp=fopen($this->_cacheFile,'wb'))!==false)
{
fputs($fp,Prado::serialize($config));
fclose($fp);
}
else
syslog(LOG_WARNING, 'Prado application config cache file "'.$this->_cacheFile.'" cannot be created.');
}
}
else
{
$config=Prado::unserialize(file_get_contents($this->_cacheFile));
}
foreach($config->getAliases() as $alias=>$path)
Prado::setPathOfAlias($alias,$path);
foreach($config->getUsings() as $using)
Prado::using($using);
foreach($config->getProperties() as $name=>$value)
$this->setSubProperty($name,$value);
$this->_parameters=new TMap;
foreach($config->getParameters() as $id=>$parameter)
{
if(is_array($parameter))
{
$component=Prado::createComponent($parameter[0]);
foreach($parameter[1] as $name=>$value)
$component->setSubProperty($name,$value);
$this->_parameters->add($id,$component);
}
else
$this->_parameters->add($id,$parameter);
}
$this->_modules=array();
foreach($config->getModules() as $id=>$moduleConfig)
{

$module=Prado::createComponent($moduleConfig[0]);
$this->setModule($id,$module);
foreach($moduleConfig[1] as $name=>$value)
$module->setSubProperty($name,$value);
$module->init($moduleConfig[2]);
}
$services=$config->getServices();
$serviceIDs=array_keys($services);
array_unshift($serviceIDs,self::PAGE_SERVICE_ID);
$request=$this->getRequest();
$request->setAvailableServices($serviceIDs);
if(($serviceID=$request->getServiceID())===null)
$serviceID=self::PAGE_SERVICE_ID;
if(isset($services[$serviceID]))
{
$serviceConfig=$services[$serviceID];
$service=Prado::createComponent($serviceConfig[0]);
if(!($service instanceof IService))
throw new THttpException(500,'application_service_unknown',$serviceID);
$this->_service=$service;
foreach($serviceConfig[1] as $name=>$value)
$service->setSubProperty($name,$value);
$service->init($serviceConfig[2]);
}
else
$this->_service=$this->getPageService();
}
public function onError($param)
{
Prado::log($param->getMessage(),TLogger::ERROR,'System.TApplication');
$this->getErrorHandler()->handleError($this,$param);
$this->raiseEvent('OnError',$this,$param);
}
public function onBeginRequest()
{
$this->raiseEvent('OnBeginRequest',$this,null);
}
public function onAuthentication()
{
$this->raiseEvent('OnAuthentication',$this,null);
}
public function onPostAuthentication()
{
$this->raiseEvent('OnPostAuthentication',$this,null);
}
public function onAuthorization()
{
$this->raiseEvent('OnAuthorization',$this,null);
}
public function onPostAuthorization()
{
$this->raiseEvent('OnPostAuthorization',$this,null);
}
public function onLoadState()
{
$this->loadGlobals();
$this->raiseEvent('OnLoadState',$this,null);
}
public function onPostLoadState()
{
$this->raiseEvent('OnPostLoadState',$this,null);
}
public function onPreRunService()
{
$this->raiseEvent('OnPreRunService',$this,null);
}
public function onRunService()
{
$this->raiseEvent('OnRunService',$this,null);
if($this->_service)
$this->_service->run();
}
public function onPostRunService()
{
$this->raiseEvent('OnPostRunService',$this,null);
}
public function onSaveState()
{
$this->raiseEvent('OnSaveState',$this,null);
$this->saveGlobals();
}
public function onPostSaveState()
{
$this->raiseEvent('OnPostSaveState',$this,null);
}
public function onEndRequest()
{
$this->raiseEvent('OnEndRequest',$this,null);
}
public function onExitApplication()
{
$this->raiseEvent('OnExitApplication',$this,null);
}
}
class TApplicationConfiguration extends TComponent
{
private $_properties=array();
private $_usings=array();
private $_aliases=array();
private $_modules=array();
private $_services=array();
private $_parameters=array();
public function loadFromFile($fname)
{
$configPath=dirname($fname);
$dom=new TXmlDocument;
$dom->loadFromFile($fname);
foreach($dom->getAttributes() as $name=>$value)
$this->_properties[$name]=$value;
if(($pathsNode=$dom->getElementByTagName('paths'))!==null)
{
foreach($pathsNode->getElementsByTagName('alias') as $aliasNode)
{
if(($id=$aliasNode->getAttribute('id'))!==null && ($path=$aliasNode->getAttribute('path'))!==null)
{
$path=str_replace('\\','/',$path);
if(preg_match('/^\\/|.:\\/|.:\\\\/',$path))							$p=realpath($path);
else
$p=realpath($configPath.'/'.$path);
if($p===false || !is_dir($p))
throw new TConfigurationException('appconfig_aliaspath_invalid',$id,$path);
if(isset($this->_aliases[$id]))
throw new TConfigurationException('appconfig_alias_redefined',$id);
$this->_aliases[$id]=$p;
}
else
throw new TConfigurationException('appconfig_alias_invalid');
}
foreach($pathsNode->getElementsByTagName('using') as $usingNode)
{
if(($namespace=$usingNode->getAttribute('namespace'))!==null)
$this->_usings[]=$namespace;
else
throw new TConfigurationException('appconfig_using_invalid');
}
}
if(($modulesNode=$dom->getElementByTagName('modules'))!==null)
{
foreach($modulesNode->getElementsByTagName('module') as $node)
{
$properties=$node->getAttributes();
if(($id=$properties->itemAt('id'))===null)
throw new TConfigurationException('appconfig_moduleid_required');
if(($type=$properties->remove('class'))===null && isset($this->_modules[$id]) && $this->_modules[$id][2]===null)
$type=$this->_modules[$id][0];
if($type===null)
throw new TConfigurationException('appconfig_moduletype_required',$id);
$node->setParent(null);
$this->_modules[$id]=array($type,$properties->toArray(),$node);
}
}
if(($servicesNode=$dom->getElementByTagName('services'))!==null)
{
foreach($servicesNode->getElementsByTagName('service') as $node)
{
$properties=$node->getAttributes();
if(($id=$properties->itemAt('id'))===null)
throw new TConfigurationException('appconfig_serviceid_required');
if(($type=$properties->remove('class'))===null && isset($this->_services[$id]) && $this->_services[$id][2]===null)
$type=$this->_services[$id][0];
if($type===null)
throw new TConfigurationException('appconfig_servicetype_required',$id);
$node->setParent(null);
$this->_services[$id]=array($type,$properties->toArray(),$node);
}
}
if(($parametersNode=$dom->getElementByTagName('parameters'))!==null)
{
foreach($parametersNode->getElementsByTagName('parameter') as $node)
{
$properties=$node->getAttributes();
if(($id=$properties->remove('id'))===null)
throw new TConfigurationException('appconfig_parameterid_required');
if(($type=$properties->remove('class'))===null)
{
if(($value=$properties->remove('value'))===null)
$this->_parameters[$id]=$node;
else
$this->_parameters[$id]=$value;
}
else
$this->_parameters[$id]=array($type,$properties->toArray());
}
}
}
public function getProperties()
{
return $this->_properties;
}
public function getAliases()
{
return $this->_aliases;
}
public function getUsings()
{
return $this->_usings;
}
public function getModules()
{
return $this->_modules;
}
public function getServices()
{
return $this->_services;
}
public function getParameters()
{
return $this->_parameters;
}
}
class TApplicationStatePersister extends TModule implements IStatePersister
{
const CACHE_NAME='prado:appstate';
public function init($config)
{
$this->getApplication()->setApplicationStatePersister($this);
}
protected function getStateFilePath()
{
return $this->getApplication()->getRuntimePath().'/global.cache';
}
public function load()
{
if(($cache=$this->getApplication()->getCache())!==null && ($value=$cache->get(self::CACHE_NAME))!==false)
return unserialize($value);
else
{
if(($content=@file_get_contents($this->getStateFilePath()))!==false)
return unserialize($content);
else
return null;
}
}
public function save($state)
{
$content=serialize($state);
$saveFile=true;
if(($cache=$this->getApplication()->getCache())!==null)
{
if($cache->get(self::CACHE_NAME)===$content)
$saveFile=false;
else
$cache->set(self::CACHE_NAME,$content);
}
if($saveFile)
{
$fileName=$this->getStateFilePath();
if(version_compare(phpversion(),'5.1.0','>='))
file_put_contents($fileName,$content,LOCK_EX);
else
file_put_contents($fileName,$content);
}
}
}

class TErrorHandler extends TModule
{
const ERROR_FILE_NAME='error';
const EXCEPTION_FILE_NAME='exception';
const SOURCE_LINES=12;
private $_templatePath=null;
public function init($config)
{
$this->getApplication()->setErrorHandler($this);
}
public function getErrorTemplatePath()
{
return $this->_templatePath;
}
public function setErrorTemplatePath($value)
{
if(($templatePath=Prado::getPathOfNamespace($value))!==null && is_dir($templatePath))
$this->_templatePath=$templatePath;
else
throw new TConfigurationException('errorhandler_errortemplatepath_invalid',$value);
}
public function handleError($sender,$param)
{
static $handling=false;
restore_error_handler();
restore_exception_handler();
if($handling)
$this->handleRecursiveError($param);
else
{
$handling=true;
if(($response=$this->getResponse())!==null)
$response->clear();
if(!headers_sent())
header('Content-Type: text/html; charset=UTF-8');
if($param instanceof THttpException)
$this->handleExternalError($param->getStatusCode(),$param);
else if($this->getApplication()->getMode()===TApplication::STATE_DEBUG)
$this->displayException($param);
else
$this->handleExternalError(500,$param);
}
}
protected function handleExternalError($statusCode,$exception)
{
if(!($exception instanceof THttpException))
error_log($exception->__toString());
if($this->_templatePath===null)
$this->_templatePath=Prado::getFrameworkPath().'/Exceptions/templates';
$base=$this->_templatePath.'/'.self::ERROR_FILE_NAME;
$lang=Prado::getPreferredLanguage();
if(is_file("$base$statusCode-$lang.html"))
$errorFile="$base$statusCode-$lang.html";
else if(is_file("$base$statusCode.html"))
$errorFile="$base$statusCode.html";
else if(is_file("$base-$lang.html"))
$errorFile="$base-$lang.html";
else
$errorFile="$base.html";
if(($content=@file_get_contents($errorFile))===false)
die("Unable to open error template file '$errorFile'.");
$serverAdmin=isset($_SERVER['SERVER_ADMIN'])?$_SERVER['SERVER_ADMIN']:'';
$tokens=array(
'%%StatusCode%%' => "$statusCode",
'%%ErrorMessage%%' => htmlspecialchars($exception->getMessage()),
'%%ServerAdmin%%' => $serverAdmin,
'%%Version%%' => $_SERVER['SERVER_SOFTWARE'].' <a href="http://www.pradosoft.com/">PRADO</a>/'.Prado::getVersion(),
'%%Time%%' => @strftime('%Y-%m-%d %H:%M',time())
);
echo strtr($content,$tokens);
}
protected function handleRecursiveError($exception)
{
if($this->getApplication()->getMode()===TApplication::STATE_DEBUG)
{
echo "<html><head><title>Recursive Error</title></head>\n";
echo "<body><h1>Recursive Error</h1>\n";
echo "<pre>".$exception->__toString()."</pre>\n";
echo "</body></html>";
}
else
{
error_log("Error happened while processing an existing error:\n".$param->__toString());
header('HTTP/1.0 500 Internal Error');
}
}
protected function displayException($exception)
{
$lines=file($exception->getFile());
$errorLine=$exception->getLine();
$beginLine=$errorLine-self::SOURCE_LINES>=0?$errorLine-self::SOURCE_LINES:0;
$endLine=$errorLine+self::SOURCE_LINES<=count($lines)?$errorLine+self::SOURCE_LINES:count($lines);
$source='';
for($i=$beginLine-1;$i<$endLine;++$i)
{
if($i===$errorLine-1)
{
$line=htmlspecialchars(sprintf("%04d: %s",$i+1,str_replace("\t",'    ',$lines[$i])));
$source.="<div class=\"error\">".$line."</div>";
}
else
$source.=htmlspecialchars(sprintf("%04d: %s",$i+1,str_replace("\t",'    ',$lines[$i])));
}
$tokens=array(
'%%ErrorType%%' => get_class($exception),
'%%ErrorMessage%%' => htmlspecialchars($exception->getMessage()),
'%%SourceFile%%' => htmlspecialchars($exception->getFile()).' ('.$exception->getLine().')',
'%%SourceCode%%' => $source,
'%%StackTrace%%' => htmlspecialchars($exception->getTraceAsString()),
'%%Version%%' => $_SERVER['SERVER_SOFTWARE'].' <a href="http://www.pradosoft.com/">PRADO</a>/'.Prado::getVersion(),
'%%Time%%' => @strftime('%Y-%m-%d %H:%M',time())
);
$lang=Prado::getPreferredLanguage();
$exceptionFile=Prado::getFrameworkPath().'/Exceptions/templates/'.self::EXCEPTION_FILE_NAME.'-'.$lang.'.html';
if(!is_file($exceptionFile))
$exceptionFile=Prado::getFrameworkPath().'/Exceptions/templates/'.self::EXCEPTION_FILE_NAME.'.html';
if(($content=@file_get_contents($exceptionFile))===false)
die("Unable to open exception template file '$exceptionFile'.");
echo strtr($content,$tokens);
}
}

class THttpRequest extends TMap implements IModule
{
const SERVICE_VAR='sp';
private $_initialized=false;
private $_serviceID=null;
private $_serviceParam=null;
private $_cookies=null;
private $_requestUri;
private $_pathInfo;
private $_urlFormat='Get';
private $_services;
private $_requestResolved=false;
private $_id;
public function getID()
{
return $this->_id;
}
public function setID($value)
{
$this->_id=$value;
}
public function init($config)
{
if(isset($_SERVER['REQUEST_URI']))
$this->_requestUri=$_SERVER['REQUEST_URI'];
else  			$this->_requestUri=$_SERVER['SCRIPT_NAME'].(empty($_SERVER['QUERY_STRING'])?'':'?'.$_SERVER['QUERY_STRING']);
if(isset($_SERVER['PATH_INFO']))
$this->_pathInfo=$_SERVER['PATH_INFO'];
else if(strpos($_SERVER['PHP_SELF'],$_SERVER['SCRIPT_NAME'])===0)
$this->_pathInfo=substr($_SERVER['PHP_SELF'],strlen($_SERVER['SCRIPT_NAME']));
else
$this->_pathInfo='';
if(get_magic_quotes_gpc())
{
if(isset($_GET))
$_GET=$this->stripSlashes($_GET);
if(isset($_POST))
$_POST=$this->stripSlashes($_POST);
if(isset($_REQUEST))
$_REQUEST=$this->stripSlashes($_REQUEST);
if(isset($_COOKIE))
$_COOKIE=$this->stripSlashes($_COOKIE);
}
if($this->getUrlFormat()==='Path' && ($pathInfo=trim($this->_pathInfo,'/'))!=='')
{
$paths=explode('/',$pathInfo);
$n=count($paths);
$getVariables=array();
for($i=0;$i<$n;++$i)
{
if($i+1<$n)
$getVariables[$paths[$i]]=$paths[++$i];
}
$this->copyFrom(array_merge($getVariables,array_merge($_GET,$_POST)));
}
else
$this->copyFrom(array_merge($_GET,$_POST));
$this->_initialized=true;
$this->getApplication()->setRequest($this);
}
public function stripSlashes(&$data)
{
return is_array($data)?array_map(array($this,'stripSlashes'),$data):stripslashes($data);
}
public function getUrl()
{
if($this->_url===null)
{
$secure=$this->getIsSecureConnection();
$url=$secure?'https://':'http://';
if(empty($_SERVER['HTTP_HOST']))
{
$url.=$_SERVER['SERVER_NAME'];
$port=$_SERVER['SERVER_PORT'];
if(($port!=80 && !$secure) || ($port!=443 && $secure))
$url.=':'.$port;
}
else
$url.=$_SERVER['HTTP_HOST'];
$url.=$this->getRequestUri();
$this->_url=new TUri($url);
}
return $this->_url;
}
public function getUrlFormat()
{
return $this->_urlFormat;
}
public function setUrlFormat($value)
{
$this->_urlFormat=TPropertyValue::ensureEnum($value,'Path','Get');
}
public function getRequestType()
{
return $_SERVER['REQUEST_METHOD'];
}
public function getIsSecureConnection()
{
return !empty($_SERVER['HTTPS']);
}
public function getPathInfo()
{
return $this->_pathInfo;
}
public function getQueryString()
{
return isset($_SERVER['QUERY_STRING'])?$_SERVER['QUERY_STRING']:'';
}
public function getRequestUri()
{
return $this->_requestUri;
}
public function getApplicationPath()
{
return $_SERVER['SCRIPT_NAME'];
}
public function getPhysicalApplicationPath()
{
return realpath($_SERVER['SCRIPT_FILENAME']);
}
public function getServerName()
{
return $_SERVER['SERVER_NAME'];
}
public function getServerPort()
{
return $_SERVER['SERVER_PORT'];
}
public function getUrlReferrer()
{
return isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:null;
}
public function getBrowser()
{
return get_browser();
}
public function getUserAgent()
{
return $_SERVER['HTTP_USER_AGENT'];
}
public function getUserHostAddress()
{
return $_SERVER['REMOTE_ADDR'];
}
public function getUserHost()
{
return isset($_SERVER['REMOTE_HOST'])?$_SERVER['REMOTE_HOST']:null;
}
public function getAcceptTypes()
{
return $_SERVER['HTTP_ACCEPT'];
}
public function getUserLanguages()
{
return Prado::getUserLanguages();
}
public function getCookies()
{
if($this->_cookies===null)
{
$this->_cookies=new THttpCookieCollection;
foreach($_COOKIE as $key=>$value)
$this->_cookies->add(new THttpCookie($key,$value));
}
return $this->_cookies;
}
public function getUploadedFiles()
{
return $_FILES;
}
public function getServerVariables()
{
return $_SERVER;
}
public function getEnvironmentVariables()
{
return $_ENV;
}
public function constructUrl($serviceID,$serviceParam,$getItems=null,$encodeAmpersand=false)
{
$url=$serviceID.'='.$serviceParam;
$amp=$encodeAmpersand?'&amp;':'&';
if(is_array($getItems) || $getItems instanceof Traversable)
{
foreach($getItems as $name=>$value)
$url.=$amp.urlencode($name).'='.urlencode($value);
}
if($this->getUrlFormat()==='Path')
{
$url=strtr($url,array($amp=>'/','?'=>'/','='=>'/'));
if(defined('SID') && SID != '')
$url.='?'.SID;
return $this->getApplicationPath().'/'.$url;
}
else
{
if(defined('SID') && SID != '')
$url.=$amp.SID;
return $this->getApplicationPath().'?'.$url;
}
}
protected function resolveRequest()
{

$this->_requestResolved=true;
foreach($this->_services as $id)
{
if($this->contains($id))
{
$this->setServiceID($id);
$this->setServiceParameter($this->itemAt($id));
break;
}
}
}
public function getAvailableServices()
{
return $this->_services;
}
public function setAvailableServices($services)
{
$this->_services=$services;
}
public function getServiceID()
{
if(!$this->_requestResolved)
$this->resolveRequest();
return $this->_serviceID;
}
protected function setServiceID($value)
{
$this->_serviceID=$value;
}
public function getServiceParameter()
{
if(!$this->_requestResolved)
$this->resolveRequest();
return $this->_serviceParam;
}
protected function setServiceParameter($value)
{
$this->_serviceParam=$value;
}
}
class THttpCookieCollection extends TList
{
private $_o;
public function __construct($owner=null)
{
parent::__construct();
$this->_o=$owner;
}
public function insertAt($index,$item)
{
if($item instanceof THttpCookie)
{
parent::insertAt($index,$item);
if($this->_o instanceof THttpResponse)
$this->_o->addCookie($item);
}
else
throw new TInvalidDataTypeException('authorizationrulecollection_authorizationrule_required');
}
public function removeAt($index)
{
$item=parent::removeAt($index);
if($this->_o instanceof THttpResponse)
$this->_o->removeCookie($item);
return $item;
}
}
class THttpCookie extends TComponent
{
private $_domain='';
private $_name;
private $_value=0;
private $_expire=0;
private $_path='/';
private $_secure=false;
public function __construct($name,$value)
{
parent::__construct();
$this->_name=$name;
$this->_value=$value;
}
public function getDomain()
{
return $this->_domain;
}
public function setDomain($value)
{
$this->_domain=$value;
}
public function getExpire()
{
return $this->_expire;
}
public function setExpire($value)
{
$this->_expire=TPropertyValue::ensureInteger($value);
}
public function getName()
{
return $this->_name;
}
public function setName($value)
{
$this->_name=$value;
}
public function getValue()
{
return $this->_value;
}
public function setValue($value)
{
$this->_value=$value;
}
public function getPath()
{
return $this->_path;
}
public function setPath($value)
{
$this->_path=$value;
}
public function getSecure()
{
return $this->_secure;
}
public function setSecure($value)
{
$this->_secure=TPropertyValue::ensureBoolean($value);
}
}
class TUri extends TComponent
{
private static $_defaultPort=array(
'ftp'=>21,
'gopher'=>70,
'http'=>80,
'https'=>443,
'news'=>119,
'nntp'=>119,
'wais'=>210,
'telnet'=>23
);
private $_scheme;
private $_host;
private $_port;
private $_user;
private $_pass;
private $_path;
private $_query;
private $_fragment;
private $_uri;
public function __construct($uri)
{
parent::__construct();
if(($ret=@parse_url($uri))!==false)
{
$this->_scheme=$ret['scheme'];
$this->_host=$ret['host'];
$this->_port=$ret['port'];
$this->_user=$ret['user'];
$this->_pass=$ret['pass'];
$this->_path=$ret['path'];
$this->_query=$ret['query'];
$this->_fragment=$ret['fragment'];
$this->_uri=$uri;
}
else
{
throw new TInvalidDataValueException('uri_format_invalid',$uri);
}
}
public function getUri()
{
return $this->_uri;
}
public function getScheme()
{
return $this->_scheme;
}
public function getHost()
{
return $this->_host;
}
public function getPort()
{
return $this->_port;
}
public function getUser()
{
return $this->_user;
}
public function getPassword()
{
return $this->_pass;
}
public function getPath()
{
return $this->_path;
}
public function getQuery()
{
return $this->_query;
}
public function getFragment()
{
return $this->_fragment;
}
}

class THttpResponse extends TModule implements ITextWriter
{
private $_bufferOutput=true;
private $_initialized=false;
private $_cookies=null;
private $_status=200;
private $_htmlWriterType='System.Web.UI.THtmlWriter';
private $_contentType='text/html';
private $_charset;
public function __destruct()
{
if($this->_bufferOutput)
@ob_end_flush();
parent::__destruct();
}
public function init($config)
{
if($this->_bufferOutput)
ob_start();
$this->_initialized=true;
$this->getApplication()->setResponse($this);
}
public function getCacheExpire()
{
return session_cache_expire();
}
public function setCacheExpire($value)
{
session_cache_expire(TPropertyValue::ensureInteger($value));
}
public function getCacheControl()
{
return session_cache_limiter();
}
public function setCacheControl($value)
{
session_cache_limiter(TPropertyValue::ensureEnum($value,array('none','nocache','private','private_no_expire','public')));
}
public function setContentType($type)
{
$this->_contentType = $type;
}
public function getContentType()
{
return $this->_contentType;
}
public function getCharset()
{
return $this->_charset;
}
public function setCharset($charset)
{
$this->_charset = $charset;
}
public function getBufferOutput()
{
return $this->_bufferOutput;
}
public function setBufferOutput($value)
{
if($this->_initialized)
throw new TInvalidOperationException('httpresponse_bufferoutput_unchangeable');
else
$this->_bufferOutput=TPropertyValue::ensureBoolean($value);
}
public function getStatusCode()
{
return $this->_status;
}
public function setStatusCode($status)
{
$this->_status=TPropertyValue::ensureInteger($status);
}
public function getCookies()
{
if($this->_cookies===null)
$this->_cookies=new THttpCookieCollection($this);
return $this->_cookies;
}
public function write($str)
{
echo $str;
}
public function writeFile($fileName)
{
static $defaultMimeTypes=array(
'css'=>'text/css',
'gif'=>'image/gif',
'jpg'=>'image/jpeg',
'jpeg'=>'image/jpeg',
'htm'=>'text/html',
'html'=>'text/html',
'js'=>'javascript/js'
);
if(!is_file($fileName))
throw new TInvalidDataValueException('httpresponse_file_inexistent',$fileName);
header('Pragma: public');
header('Expires: 0');
header('Cache-Component: must-revalidate, post-check=0, pre-check=0');
$mimeType='text/plain';
if(function_exists('mime_content_type'))
$mimeType=mime_content_type($fileName);
else
{
$ext=array_pop(explode('.',$fileName));
if(isset($defaultMimeTypes[$ext]))
$mimeType=$defaultMimeTypes[$ext];
}
$fn=basename($fileName);
header("Content-type: $mimeType");
header('Content-Length: '.filesize($fileName));
header("Content-Disposition: attachment; filename=\"$fn\"");
header('Content-Transfer-Encoding: binary');
readfile($fileName);
}
public function redirect($url)
{
header('Location:'.$url);
exit();
}
public function flush()
{

$this->sendContentTypeHeader();
if($this->_bufferOutput)
ob_flush();
}
protected function sendContentTypeHeader()
{
$charset = $this->getCharset();
if(empty($charset) && ($globalization=$this->getApplication()->getGlobalization())!==null)
$charset = $globalization->getCharset();
if(!empty($charset))
{
$header='Content-Type: '.$this->getContentType().';charset='.$charset;
$this->appendHeader($header);
}
}
public function clear()
{
if($this->_bufferOutput)
ob_clean();

}
public function appendHeader($value)
{

header($value);
}
public function appendLog($message,$messageType=0,$destination='',$extraHeaders='')
{
error_log($message,$messageType,$destination,$extraHeaders);
}
public function addCookie($cookie)
{
setcookie($cookie->getName(),$cookie->getValue(),$cookie->getExpire(),$cookie->getPath(),$cookie->getDomain(),$cookie->getSecure());
}
public function removeCookie($cookie)
{
setcookie($cookie->getName(),null,0,$cookie->getPath(),$cookie->getDomain(),$cookie->getSecure());
}
public function getHtmlWriterType()
{
return $this->_htmlWriterType;
}
public function setHtmlWriterType($value)
{
$this->_htmlWriterType=$value;
}
public function createHtmlWriter($type=null)
{
if($type===null)
$type=$this->_htmlWriterType;
return Prado::createComponent($type,$this);
}
}

class THttpSession extends TComponent implements IteratorAggregate,ArrayAccess,IModule
{
private $_initialized=false;
private $_started=false;
private $_autoStart=false;
private $_cookie=null;
private $_id;
public function getID()
{
return $this->_id;
}
public function setID($value)
{
$this->_id=$value;
}
public function init($config)
{
if($this->_autoStart)
session_start();
$this->_initialized=true;
$this->getApplication()->setSession($this);
}
public function open()
{
if(!$this->_started)
{
if($this->_cookie!==null)
session_set_cookie_params($this->_cookie->getExpire(),$this->_cookie->getPath(),$this->_cookie->getDomain(),$this->_cookie->getSecure());
session_start();
$this->_started=true;
}
}
public function close()
{
if($this->_started)
{
session_write_close();
$this->_started=false;
}
}
public function destroy()
{
if($this->_started)
{
session_destroy();
$this->_started=false;
}
}
public function getIsStarted()
{
return $this->_started;
}
public function getSessionID()
{
return session_id();
}
public function setSessionID($value)
{
if($this->_started)
throw new TInvalidOperationException('httpsession_sessionid_unchangeable');
else
session_id($value);
}
public function getSessionName()
{
return session_name();
}
public function setSessionName($value)
{
if($this->_started)
throw new TInvalidOperationException('httpsession_sessionname_unchangeable');
else if(ctype_alnum($value))
session_name($value);
else
throw new TInvalidDataValueException('httpsession_sessionname_invalid',$name);
}
public function getSavePath()
{
return session_save_path();
}
public function setSavePath($value)
{
if($this->_started)
throw new TInvalidOperationException('httpsession_savepath_unchangeable');
else if(is_dir($value))
session_save_path($value);
else
throw new TInvalidDataValueException('httpsession_savepath_invalid',$value);
}
public function getStorage()
{
switch(session_module_name())
{
case 'files': return 'File';
case 'mm': return 'SharedMemory';
case 'user': return 'Custom';
default: return 'Unknown';
}
}
public function setStorage($value)
{
if($this->_started)
throw new TInvalidOperationException('httpsession_storage_unchangeable');
else
{
$value=TPropertyValue::ensureEnum($value,array('File','SharedMemory','Custom'));
if($value==='Custom')
session_set_save_handler(array($this,'_open'),array($this,'_close'),array($this,'_read'),array($this,'_write'),array($this,'_destroy'),array($this,'_gc'));
switch($value)
{
case 'Custom':
session_module_name('user');
break;
case 'SharedMemory':
session_module_name('mm');
break;
default:
session_module_name('files');
break;
}
}
}
public function getCookie()
{
if($this->_cookie===null)
$this->_cookie=new THttpCookie($this->getSessionName(),$this->getSessionID());
return $this->_cookie;
}
public function getCookieMode()
{
if(ini_get('session.use_cookies')==='0')
return 'None';
else if(ini_get('session.use_only_cookies')==='0')
return 'Allow';
else
return 'Only';
}
public function setCookieMode($value)
{
if($this->_started)
throw new TInvalidOperationException('httpsession_cookiemode_unchangeable');
else
{
$value=TPropertyValue::ensureEnum($value,array('None','Allow','Only'));
if($value==='None')
ini_set('session.use_cookies','0');
else if($value==='Allow')
{
ini_set('session.use_cookies','1');
ini_set('session.use_only_cookies','0');
}
else
{
ini_set('session.use_cookies','1');
ini_set('session.use_only_cookies','1');
}
}
}
public function getAutoStart()
{
return $this->_autoStart;
}
public function setAutoStart($value)
{
if($this->_initialized)
throw new TInvalidOperationException('httpsession_autostart_unchangeable');
else
$this->_autoStart=TPropertyValue::ensureBoolean($value);
}
public function getGCProbability()
{
return TPropertyValue::ensureInteger(ini_get('session.gc_probability'));
}
public function setGCProbability($value)
{
if($this->_started)
throw new TInvalidOperationException('httpsession_gcprobability_unchangeable');
else
{
$value=TPropertyValue::ensureInteger($value);
if($value>=0 && $value<=100)
{
ini_set('session.gc_probability',$value);
ini_set('session.gc_divisor','100');
}
else
throw new TInvalidDataValueException('httpsession_gcprobability_invalid',$value);
}
}
public function getUseTransparentSessionID()
{
return ini_get('session.use_trans_sid')==='1';
}
public function setUseTransparentSessionID($value)
{
if($this->_started)
throw new TInvalidOperationException('httpsession_transid_unchangeable');
else
ini_set('session.use_only_cookies',TPropertyValue::ensureBoolean($value)?'1':'0');
}
public function getTimeout()
{
return TPropertyValue::ensureInteger(ini_get('session.gc_maxlifetime'));
}
public function setTimeout($value)
{
if($this->_started)
throw new TInvalidOperationException('httpsession_maxlifetime_unchangeable');
else
ini_set('session.gc_maxlifetime',$value);
}
public function _open($savePath,$sessionName)
{
return true;
}
public function _close()
{
return true;
}
public function _read($id)
{
return '';
}
public function _write($id,$data)
{
return true;
}
public function _destroy($id)
{
return true;
}
public function _gc($maxLifetime)
{
return true;
}
public function getIterator()
{
return new TSessionIterator;
}
public function getCount()
{
return count($_SESSION);
}
public function getKeys()
{
return array_keys($_SESSION);
}
public function itemAt($key)
{
return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
}
public function add($key,$value)
{
$_SESSION[$key]=$value;
}
public function remove($key)
{
if(isset($_SESSION[$key]))
{
$value=$_SESSION[$key];
unset($_SESSION[$key]);
return $value;
}
else
return null;
}
public function clear()
{
foreach(array_keys($_SESSION) as $key)
unset($_SESSION[$key]);
}
public function contains($key)
{
return isset($_SESSION[$key]);
}
public function toArray()
{
return $_SESSION;
}
public function offsetExists($offset)
{
return isset($_SESSION[$offset]);
}
public function offsetGet($offset)
{
return isset($_SESSION[$offset]) ? $_SESSION[$offset] : null;
}
public function offsetSet($offset,$item)
{
$_SESSION[$offset]=$item;
}
public function offsetUnset($offset)
{
unset($_SESSION[$offset]);
}
}
class TSessionIterator implements Iterator
{
private $_keys;
private $_key;
public function __construct()
{
$this->_keys=array_keys($_SESSION);
}
public function rewind()
{
$this->_key=reset($this->_keys);
}
public function key()
{
return $this->_key;
}
public function current()
{
return isset($_SESSION[$this->_key])?$_SESSION[$this->_key]:null;
}
public function next()
{
do
{
$this->_key=next($this->_keys);
}
while(!isset($_SESSION[$this->_key]) && $this->_key!==false);
}
public function valid()
{
return $this->_key!==false;
}
}

class TAuthorizationRule extends TComponent
{
private $_action;
private $_users;
private $_roles;
private $_verb;
private $_everyone;
private $_guest;
public function __construct($action,$users,$roles,$verb='')
{
parent::__construct();
$action=strtolower(trim($action));
if($action==='allow' || $action==='deny')
$this->_action=$action;
else
throw new TInvalidDataValueException('authorizationrule_action_invalid',$action);
$this->_users=array();
$this->_roles=array();
$this->_everyone=false;
$this->_guest=false;
foreach(explode(',',$users) as $user)
{
if(($user=trim(strtolower($user)))!=='')
{
if($user==='*')
$this->_everyone=true;
else if($user==='?')
$this->_guest=true;
else
$this->_users[]=$user;
}
}
foreach(explode(',',$roles) as $role)
{
if(($role=trim(strtolower($role)))!=='')
$this->_roles[]=$role;
}
$verb=trim(strtolower($verb));
if($verb==='' || $verb==='get' || $verb==='post')
$this->_verb=$verb;
else
throw new TInvalidDataValueException('authorizationrule_verb_invalid',$verb);
}
public function getAction()
{
return $this->_action;
}
public function getUsers()
{
return $this->_users;
}
public function getRoles()
{
return $this->_roles;
}
public function getVerb()
{
return $this->_verb;
}
public function getGuestApplied()
{
return $this->_guest;
}
public function getEveryoneApplied()
{
return $this->_everyone;
}
public function isUserAllowed(IUser $user,$verb)
{
$decision=($this->_action==='allow')?1:-1;
if($this->_verb==='' || strcasecmp($verb,$this->_verb)===0)
{
if($this->_everyone || ($this->_guest && $user->getIsGuest()))
return $decision;
if(in_array(strtolower($user->getName()),$this->_users))
return $decision;
foreach($this->_roles as $role)
if($user->isInRole($role))
return $decision;
}
return 0;
}
}
class TAuthorizationRuleCollection extends TList
{
public function isUserAllowed($user,$verb)
{
if($user instanceof IUser)
{
$verb=strtolower(trim($verb));
foreach($this as $rule)
{
if(($decision=$rule->isUserAllowed($user,$verb))!==0)
return ($decision>0);
}
return true;
}
else
return false;
}
public function insertAt($index,$item)
{
if($item instanceof TAuthorizationRule)
parent::insertAt($index,$item);
else
throw new TInvalidDataTypeException('authorizationrulecollection_authorizationrule_required');
}
}

class TPageService extends TService
{
const CONFIG_FILE='config.xml';
const DEFAULT_BASEPATH='pages';
const CONFIG_CACHE_PREFIX='prado:pageservice:';
const PAGE_FILE_EXT='.page';
private $_id='page';
private $_basePath=null;
private $_defaultPage='Home';
private $_pagePath;
private $_page=null;
private $_properties;
private $_initialized=false;
private $_assetManager=null;
private $_themeManager=null;
private $_templateManager=null;
private $_pageStatePersister=null;
public function init($config)
{

$application=$this->getApplication();
$application->setPageService($this);
if($this->_basePath===null)
{
$basePath=$application->getBasePath().'/'.self::DEFAULT_BASEPATH;
if(($this->_basePath=realpath($basePath))===false || !is_dir($this->_basePath))
throw new TConfigurationException('pageservice_basepath_invalid',$basePath);
}
$this->_pagePath=$application->getRequest()->getServiceParameter();
if(empty($this->_pagePath))
$this->_pagePath=$this->_defaultPage;
if(empty($this->_pagePath))
throw new THttpException(404,'pageservice_page_required');
if(($cache=$application->getCache())===null)
{
$pageConfig=new TPageConfiguration;
if($config!==null)
$pageConfig->loadXmlElement($config,$application->getBasePath(),null);
$pageConfig->loadConfigurationFiles($this->_pagePath,$this->_basePath);
}
else
{
$configCached=true;
$currentTimestamp=array();
$arr=$cache->get(self::CONFIG_CACHE_PREFIX.$this->_pagePath);
if(is_array($arr))
{
list($pageConfig,$timestamps)=$arr;
if($application->getMode()!==TApplication::STATE_PERFORMANCE)
{
foreach($timestamps as $fileName=>$timestamp)
{
if($fileName===0) 						{
$appConfigFile=$application->getConfigurationFile();
$currentTimestamp[0]=$appConfigFile===null?0:@filemtime($appConfigFile);
if($currentTimestamp[0]>$timestamp || ($timestamp>0 && !$currentTimestamp[0]))
$configCached=false;
}
else
{
$currentTimestamp[$fileName]=@filemtime($fileName);
if($currentTimestamp[$fileName]>$timestamp || ($timestamp>0 && !$currentTimestamp[$fileName]))
$configCached=false;
}
}
}
}
else
{
$configCached=false;
$paths=explode('.',$this->_pagePath);
array_pop($paths);
$configPath=$this->_basePath;
foreach($paths as $path)
{
$configFile=$configPath.'/'.self::CONFIG_FILE;
$currentTimestamp[$configFile]=@filemtime($configFile);
$configPath.='/'.$path;
}
$appConfigFile=$application->getConfigurationFile();
$currentTimestamp[0]=$appConfigFile===null?0:@filemtime($appConfigFile);
}
if(!$configCached)
{
$pageConfig=new TPageConfiguration;
if($config!==null)
$pageConfig->loadXmlElement($config,$application->getBasePath(),null);
$pageConfig->loadConfigurationFiles($this->_pagePath,$this->_basePath);
$cache->set(self::CONFIG_CACHE_PREFIX.$this->_pagePath,array($pageConfig,$currentTimestamp));
}
}
foreach($pageConfig->getAliases() as $alias=>$path)
Prado::setPathOfAlias($alias,$path);
foreach($pageConfig->getUsings() as $using)
Prado::using($using);
$this->_properties=$pageConfig->getProperties();
$parameters=$application->getParameters();
foreach($pageConfig->getParameters() as $id=>$parameter)
{
if(is_string($parameter))
$parameters->add($id,$parameter);
else
{
$component=Prado::createComponent($parameter[0]);
foreach($parameter[1] as $name=>$value)
$component->setSubProperty($name,$value);
$parameters->add($id,$component);
}
}
foreach($pageConfig->getModules() as $id=>$moduleConfig)
{

$module=Prado::createComponent($moduleConfig[0]);
$application->setModule($id,$module);
foreach($moduleConfig[1] as $name=>$value)
$module->setSubProperty($name,$value);
$module->init($moduleConfig[2]);
}
$application->getAuthorizationRules()->mergeWith($pageConfig->getRules());
$this->_initialized=true;
}
public function getID()
{
return $this->_id;
}
public function setID($value)
{
$this->_id=$value;
}
public function getTemplateManager()
{
if(!$this->_templateManager)
{
$this->_templateManager=new TTemplateManager;
$this->_templateManager->init(null);
}
return $this->_templateManager;
}
public function setTemplateManager(TTemplateManager $value)
{
$this->_templateManager=$value;
}
public function getAssetManager()
{
if(!$this->_assetManager)
{
$this->_assetManager=new TAssetManager;
$this->_assetManager->init(null);
}
return $this->_assetManager;
}
public function setAssetManager(TAssetManager $value)
{
$this->_assetManager=$value;
}
public function getThemeManager()
{
if(!$this->_themeManager)
{
$this->_themeManager=new TThemeManager;
$this->_themeManager->init(null);
}
return $this->_themeManager;
}
public function setThemeManager(TThemeManager $value)
{
$this->_themeManager=$value;
}
public function getPageStatePersister()
{
if(!$this->_pageStatePersister)
{
$this->_pageStatePersister=new TPageStatePersister;
$this->_pageStatePersister->init(null);
}
return $this->_pageStatePersister;
}
public function setPageStatePersister(IStatePersister $value)
{
$this->_pageStatePersister=$value;
}
public function getRequestedPagePath()
{
return $this->_pagePath;
}
public function getRequestedPage()
{
return $this->_page;
}
public function getDefaultPage()
{
return $this->_defaultPage;
}
public function setDefaultPage($value)
{
if($this->_initialized)
throw new TInvalidOperationException('pageservice_defaultpage_unchangeable');
else
$this->_defaultPage=$value;
}
public function getBasePath()
{
return $this->_basePath;
}
public function setBasePath($value)
{
if($this->_initialized)
throw new TInvalidOperationException('pageservice_basepath_unchangeable');
else if(($this->_basePath=realpath(Prado::getPathOfNamespace($value)))===false || !is_dir($this->_basePath))
throw new TConfigurationException('pageservice_basepath_invalid',$value);
}
public function run()
{

$page=null;
$path=$this->_basePath.'/'.strtr($this->_pagePath,'.','/');
if(is_file($path.self::PAGE_FILE_EXT))
{
if(is_file($path.Prado::CLASS_FILE_EXT))
{
$className=basename($path);
if(!class_exists($className,false))
include_once($path.Prado::CLASS_FILE_EXT);
if(!class_exists($className,false))
throw new TConfigurationException('pageservice_pageclass_unknown',$className);
}
else
$className='TPage';
$this->_page=new $className();
foreach($this->_properties as $name=>$value)
$this->_page->setSubProperty($name,$value);
$this->_page->setTemplate($this->getTemplateManager()->getTemplateByFileName($path.self::PAGE_FILE_EXT));
}
else
throw new THttpException(404,'pageservice_page_unknown',$this->_pagePath);
$writer=$this->getResponse()->createHtmlWriter();
$this->_page->run($writer);
$writer->flush();
}
public function constructUrl($pagePath,$getParams=null,$encodeAmpersand=false)
{
return $this->getRequest()->constructUrl($this->_id,$pagePath,$getParams,$encodeAmpersand);
}
public function getAsset($path)
{
return $this->getAssetManager()->publishFilePath($path);
}
}
class TPageConfiguration extends TComponent
{
private $_properties=array();
private $_usings=array();
private $_aliases=array();
private $_modules=array();
private $_parameters=array();
private $_rules=array();
public function getProperties()
{
return $this->_properties;
}
public function getAliases()
{
return $this->_aliases;
}
public function getUsings()
{
return $this->_usings;
}
public function getModules()
{
return $this->_modules;
}
public function getParameters()
{
return $this->_parameters;
}
public function getRules()
{
return $this->_rules;
}
public function loadConfigurationFiles($pagePath,$basePath)
{
$paths=explode('.',$pagePath);
$page=array_pop($paths);
$path=$basePath;
foreach($paths as $p)
{
$this->loadFromFile($path.'/'.TPageService::CONFIG_FILE,null);
$path.='/'.$p;
}
$this->loadFromFile($path.'/'.TPageService::CONFIG_FILE,$page);
$this->_rules=new TAuthorizationRuleCollection($this->_rules);
}
private function loadFromFile($fname,$page)
{

if(empty($fname) || !is_file($fname))
return;
$dom=new TXmlDocument;
if($dom->loadFromFile($fname))
$this->loadXmlElement($dom,dirname($fname),$page);
else
throw new TConfigurationException('pageserviceconf_file_invalid',$fname);
}
public function loadXmlElement($dom,$configPath,$page)
{
if(($pathsNode=$dom->getElementByTagName('paths'))!==null)
{
foreach($pathsNode->getElementsByTagName('alias') as $aliasNode)
{
if(($id=$aliasNode->getAttribute('id'))!==null && ($p=$aliasNode->getAttribute('path'))!==null)
{
$p=str_replace('\\','/',$p);
$path=realpath(preg_match('/^\\/|.:\\//',$p)?$p:$configPath.'/'.$p);
if($path===false || !is_dir($path))
throw new TConfigurationException('pageserviceconf_aliaspath_invalid',$id,$p,$configPath);
if(isset($this->_aliases[$id]))
throw new TConfigurationException('pageserviceconf_alias_redefined',$id,$configPath);
$this->_aliases[$id]=$path;
}
else
throw new TConfigurationException('pageserviceconf_alias_invalid',$configPath);
}
foreach($pathsNode->getElementsByTagName('using') as $usingNode)
{
if(($namespace=$usingNode->getAttribute('namespace'))!==null)
$this->_usings[]=$namespace;
else
throw new TConfigurationException('pageserviceconf_using_invalid',$configPath);
}
}
if(($modulesNode=$dom->getElementByTagName('modules'))!==null)
{
foreach($modulesNode->getElementsByTagName('module') as $node)
{
$properties=$node->getAttributes();
$type=$properties->remove('class');
if(($id=$properties->itemAt('id'))===null)
throw new TConfigurationException('pageserviceconf_module_invalid',$configPath);
if(isset($this->_modules[$id]))
{
if($type===null || $type===$this->_modules[$id][0])
{
$this->_modules[$id][1]=array_merge($this->_modules[$id][1],$properties->toArray());
$elements=$this->_modules[$id][2]->getElements();
foreach($node->getElements() as $element)
$elements->add($element);
}
else
{
$node->setParent(null);
$this->_modules[$id]=array($type,$properties->toArray(),$node);
}
}
else if($type===null)
throw new TConfigurationException('pageserviceconf_moduletype_required',$id,$configPath);
else
{
$node->setParent(null);
$this->_modules[$id]=array($type,$properties->toArray(),$node);
}
}
}
if(($parametersNode=$dom->getElementByTagName('parameters'))!==null)
{
foreach($parametersNode->getElementsByTagName('parameter') as $node)
{
$properties=$node->getAttributes();
if(($id=$properties->remove('id'))===null)
throw new TConfigurationException('pageserviceconf_parameter_invalid',$configPath);
if(($type=$properties->remove('class'))===null)
$this->_parameters[$id]=$node->getValue();
else
$this->_parameters[$id]=array($type,$properties->toArray());
}
}
if(($authorizationNode=$dom->getElementByTagName('authorization'))!==null)
{
$rules=array();
foreach($authorizationNode->getElements() as $node)
{
$pages=$node->getAttribute('pages');
$ruleApplies=false;
if(empty($pages))
$ruleApplies=true;
else if($page!==null)
{
$ps=explode(',',$pages);
foreach($ps as $p)
{
if($page===trim($p))
{
$ruleApplies=true;
break;
}
}
}
if($ruleApplies)
$rules[]=new TAuthorizationRule($node->getTagName(),$node->getAttribute('users'),$node->getAttribute('roles'),$node->getAttribute('verb'));
}
$this->_rules=array_merge($rules,$this->_rules);
}
if(($pagesNode=$dom->getElementByTagName('pages'))!==null)
{
$this->_properties=array_merge($this->_properties,$pagesNode->getAttributes()->toArray());
if($page!==null)   			{
foreach($pagesNode->getElementsByTagName('page') as $node)
{
$properties=$node->getAttributes();
if(($id=$properties->itemAt('id'))===null)
throw new TConfigurationException('pageserviceconf_page_invalid',$configPath);
if($id===$page)
$this->_properties=array_merge($this->_properties,$properties->toArray());
}
}
}
}
}

class THtmlWriter extends TComponent implements ITextWriter
{
const TAG_INLINE=0;
const TAG_NONCLOSING=1;
const TAG_OTHER=2;
const CHAR_NEWLINE="\n";
const CHAR_TAB="\t";
private static $_tagTypes=array(
'*'=>2,
'a'=>0,
'acronym'=>0,
'address'=>2,
'area'=>1,
'b'=>0,
'base'=>1,
'basefont'=>1,
'bdo'=>0,
'bgsound'=>1,
'big'=>0,
'blockquote'=>2,
'body'=>2,
'br'=>2,
'button'=>0,
'caption'=>2,
'center'=>2,
'cite'=>0,
'code'=>0,
'col'=>1,
'colgroup'=>2,
'del'=>0,
'dd'=>0,
'dfn'=>0,
'dir'=>2,
'div'=>2,
'dl'=>2,
'dt'=>0,
'em'=>0,
'embed'=>1,
'fieldset'=>2,
'font'=>0,
'form'=>2,
'frame'=>1,
'frameset'=>2,
'h1'=>2,
'h2'=>2,
'h3'=>2,
'h4'=>2,
'h5'=>2,
'h6'=>2,
'head'=>2,
'hr'=>1,
'html'=>2,
'i'=>0,
'iframe'=>2,
'img'=>1,
'input'=>1,
'ins'=>0,
'isindex'=>1,
'kbd'=>0,
'label'=>0,
'legend'=>2,
'li'=>0,
'link'=>1,
'map'=>2,
'marquee'=>2,
'menu'=>2,
'meta'=>1,
'nobr'=>0,
'noframes'=>2,
'noscript'=>2,
'object'=>2,
'ol'=>2,
'option'=>2,
'p'=>0,
'param'=>2,
'pre'=>2,
'ruby'=>2,
'rt'=>2,
'q'=>0,
's'=>0,
'samp'=>0,
'script'=>2,
'select'=>2,
'small'=>2,
'span'=>0,
'strike'=>0,
'strong'=>0,
'style'=>2,
'sub'=>0,
'sup'=>0,
'table'=>2,
'tbody'=>2,
'td'=>0,
'textarea'=>0,
'tfoot'=>2,
'th'=>0,
'thead'=>2,
'title'=>2,
'tr'=>2,
'tt'=>0,
'u'=>0,
'ul'=>2,
'var'=>0,
'wbr'=>1,
'xml'=>2
);
private static $_attrEncode=array(
'abbr'=>true,
'accesskey'=>true,
'alt'=>true,
'axis'=>true,
'background'=>true,
'class'=>true,
'content'=>true,
'headers'=>true,
'href'=>true,
'longdesc'=>true,
'onclick'=>true,
'onchange'=>true,
'src'=>true,
'title'=>true,
'value'=>true
);
private static $_styleEncode=array(
'background-image'=>true,
'list-style-image'=>true
);
private $_attributes=array();
private $_openTags=array();
private $_writer=null;
private $_styles=array();
public function __construct($writer)
{
$this->_writer=$writer;
}
public function isValidFormAttribute($name)
{
return true;
}
public function addAttributes($attrs)
{
foreach($attrs as $name=>$value)
$this->_attributes[$name]=isset(self::$_attrEncode[$name])?THttpUtility::htmlEncode($value):$value;
}
public function addAttribute($name,$value)
{
$this->_attributes[$name]=isset(self::$_attrEncode[$name])?THttpUtility::htmlEncode($value):$value;
}
public function addStyleAttribute($name,$value)
{
$this->_styles[$name]=isset(self::$_styleEncode[$name])?THttpUtility::htmlEncode($value):$value;
}
public function flush()
{
$this->_writer->flush();
}
public function write($str)
{
$this->_writer->write($str);
}
public function writeLine($str='')
{
$this->_writer->write($str.self::CHAR_NEWLINE);
}
public function writeBreak()
{
$this->_writer->write('<br/>');
}
public function writeAttribute($name,$value,$encode=false)
{
$this->_writer->write(' '.$name.='"'.($encode?THttpUtility::htmlEncode($value):$value).'"');
}
public function renderBeginTag($tagName)
{
$tagType=isset(self::$_tagTypes[$tagName])?self::$_tagTypes[$tagName]:self::TAG_OTHER;
$str='<'.$tagName;
foreach($this->_attributes as $name=>$value)
$str.=' '.$name.'="'.$value.'"';
if(!empty($this->_styles))
{
$str.=' style="';
foreach($this->_styles as $name=>$value)
$str.=$name.':'.$value.';';
$str.='"';
}
if($tagType===self::TAG_NONCLOSING)
{
$str.=' />';
array_push($this->_openTags,'');
}
else
{
$str.='>';
array_push($this->_openTags,$tagName);
}
$this->_writer->write($str);
$this->_attributes=array();
$this->_styles=array();
}
public function renderEndTag()
{
if(!empty($this->_openTags) && ($tagName=array_pop($this->_openTags))!=='')
$this->_writer->write('</'.$tagName.'>');
}
}

class TTemplateManager extends TModule
{
const TEMPLATE_FILE_EXT='.tpl';
const TEMPLATE_CACHE_PREFIX='prado:template:';
public function init($config)
{
$this->getService()->setTemplateManager($this);
}
public function getTemplateByClassName($className)
{
$class=new ReflectionClass($className);
$tplFile=dirname($class->getFileName()).'/'.$className.self::TEMPLATE_FILE_EXT;
return $this->getTemplateByFileName($tplFile);
}
public function getTemplateByFileName($fileName)
{
if(($fileName=$this->getLocalizedTemplate($fileName))!==null)
{

if(($cache=$this->getApplication()->getCache())===null)
return new TTemplate(file_get_contents($fileName),dirname($fileName),$fileName);
else
{
$array=$cache->get(self::TEMPLATE_CACHE_PREFIX.$fileName);
if(is_array($array))
{
list($template,$timestamp)=$array;
if(filemtime($fileName)<$timestamp)
return $template;
}
$template=new TTemplate(file_get_contents($fileName),dirname($fileName),$fileName);
$cache->set(self::TEMPLATE_CACHE_PREFIX.$fileName,array($template,time()));
return $template;
}
}
else
return null;
}
protected function getLocalizedTemplate($filename)
{
$app = $this->getApplication()->getGlobalization();
if(is_null($app)) return $filename;
foreach($app->getLocalizedResource($filename) as $file)
{
if(($file=realpath($file))!==false && is_file($file))
return $file;
}
return null;
}
}
class TTemplate extends TComponent implements ITemplate
{
const REGEX_RULES='/<!.*?!>|<!--.*?-->|<\/?com:([\w\.]+)((?:\s*[\w\.]+=\'.*?\'|\s*[\w\.]+=".*?"|\s*[\w\.]+=<%.*?%>)*)\s*\/?>|<\/?prop:([\w\.]+)\s*>|<%@\s*((?:\s*[\w\.]+=\'.*?\'|\s*[\w\.]+=".*?")*)\s*%>|<%[%#~\\$=\\[](.*?)%>/msS';
const CONFIG_DATABIND=0;
const CONFIG_EXPRESSION=1;
const CONFIG_ASSET=2;
const CONFIG_PARAMETER=3;
const CONFIG_LOCALIZATION=4;
const CONFIG_TEMPLATE=5;
private $_tpl=array();
private $_directive=array();
private $_contextPath;
private $_tplFile=null;
private $_assetManager;
private $_startingLine=0;
private $_content;
public function __construct($template,$contextPath,$tplFile=null,$startingLine=0)
{
$this->_contextPath=$contextPath;
$this->_tplFile=$tplFile;
$this->_startingLine=$startingLine;
$this->_content=$template;
$this->parse($template);
$this->_content=null; 	}
public function getContextPath()
{
return $this->_contextPath;
}
public function getDirective()
{
return $this->_directive;
}
public function &getItems()
{
return $this->_tpl;
}
public function instantiateIn($tplControl)
{
if(($page=$tplControl->getPage())===null)
$page=$this->getService()->getRequestedPage();
$this->_assetManager=$this->getService()->getAssetManager();
$controls=array();
foreach($this->_tpl as $key=>$object)
{
if(isset($object[2]))				{
$component=Prado::createComponent($object[1]);
if($component instanceof TControl)
{
$controls[$key]=$component;
$component->setTemplateControl($tplControl);
if(isset($object[2]['id']))
$tplControl->registerObject($object[2]['id'],$component);
if(isset($object[2]['skinid']))
{
$component->setSkinID($object[2]['skinid']);
unset($object[2]['skinid']);
}
$component->applyStyleSheetSkin($page);
foreach($object[2] as $name=>$value)
$this->configureControl($component,$name,$value);
$parent=isset($controls[$object[0]])?$controls[$object[0]]:$tplControl;
$component->createdOnTemplate($parent);
}
else if($component instanceof TComponent)
{
if(isset($object[2]['id']))
{
$tplControl->registerObject($object[2]['id'],$component);
if(!$component->hasProperty('id'))
unset($object[2]['id']);
}
foreach($object[2] as $name=>$value)
$this->configureComponent($component,$name,$value);
$parent=isset($controls[$object[0]])?$controls[$object[0]]:$tplControl;
$parent->addParsedObject($component);
}
}
else				{
if(isset($controls[$object[0]]))
$controls[$object[0]]->addParsedObject($object[1]);
else
$tplControl->addParsedObject($object[1]);
}
}
}
protected function configureControl($control,$name,$value)
{
if(strncasecmp($name,'on',2)===0)					$this->configureEvent($control,$name,$value);
else if(strpos($name,'.')===false)				$this->configureProperty($control,$name,$value);
else				$this->configureSubProperty($control,$name,$value);
}
protected function configureComponent($component,$name,$value)
{
if(strpos($name,'.')===false)				$this->configureProperty($component,$name,$value);
else				$this->configureSubProperty($component,$name,$value);
}
protected function configureEvent($component,$name,$value)
{
if(strpos($value,'.')===false)
$component->attachEventHandler($name,array($component,'TemplateControl.'.$value));
else
$component->attachEventHandler($name,array($component,$value));
}
protected function configureProperty($component,$name,$value)
{
$setter='set'.$name;
if(is_array($value))
{
switch($value[0])
{
case self::CONFIG_DATABIND:
$component->bindProperty($name,$value[1]);
break;
case self::CONFIG_EXPRESSION:
$component->$setter($component->evaluateExpression($value[1]));
break;
case self::CONFIG_TEMPLATE:
$component->$setter($value[1]);
break;
case self::CONFIG_ASSET:							$url=$this->_assetManager->publishFilePath($this->_contextPath.'/'.$value[1]);
$component->$setter($url);
break;
case self::CONFIG_PARAMETER:							$component->$setter($this->getApplication()->getParameters()->itemAt($value[1]));
break;
case self::CONFIG_LOCALIZATION:
Prado::using('System.I18N.Translation');
$component->$setter(localize(trim($value[1])));
break;
default:						break;
}
}
else
$component->$setter($value);
}
protected function configureSubProperty($component,$name,$value)
{
if(is_array($value))
{
switch($value[0])
{
case self::CONFIG_DATABIND:							$component->bindProperty($name,$value[1]);
break;
case self::CONFIG_EXPRESSION:							$component->setSubProperty($name,$component->evaluateExpression($value[1]));
break;
case self::CONFIG_TEMPLATE:
$component->setSubProperty($name,$value[1]);
break;
case self::CONFIG_ASSET:							$url=$this->_assetManager->publishFilePath($this->_contextPath.'/'.$value[1]);
$component->setSubProperty($name,$url);
break;
case self::CONFIG_PARAMETER:							$component->setSubProperty($name,$this->getApplication()->getParameters()->itemAt($value[1]));
break;
case self::CONFIG_LOCALIZATION:
$component->setSubProperty($name,localize($value[1]));
break;
default:						break;
}
}
else
$component->setSubProperty($name,$value);
}
protected function parse($input)
{
$tpl=&$this->_tpl;
$n=preg_match_all(self::REGEX_RULES,$input,$matches,PREG_SET_ORDER|PREG_OFFSET_CAPTURE);
$expectPropEnd=false;
$textStart=0;
$stack=array();
$container=-1;
$matchEnd=0;
$c=0;
try
{
for($i=0;$i<$n;++$i)
{
$match=&$matches[$i];
$str=$match[0][0];
$matchStart=$match[0][1];
$matchEnd=$matchStart+strlen($str)-1;
if(strpos($str,'<com:')===0)					{
if($expectPropEnd)
continue;
if($matchStart>$textStart)
$tpl[$c++]=array($container,substr($input,$textStart,$matchStart-$textStart));
$textStart=$matchEnd+1;
$type=$match[1][0];
$attributes=$this->parseAttributes($match[2][0],$match[2][1]);
$this->validateAttributes($type,$attributes);
$tpl[$c++]=array($container,$type,$attributes);
if($str[strlen($str)-2]!=='/')  					{
array_push($stack,$type);
$container=$c-1;
}
}
else if(strpos($str,'</com:')===0)					{
if($expectPropEnd)
continue;
if($matchStart>$textStart)
$tpl[$c++]=array($container,substr($input,$textStart,$matchStart-$textStart));
$textStart=$matchEnd+1;
$type=$match[1][0];
if(empty($stack))
throw new TConfigurationException('template_closingtag_unexpected',"</com:$type>");
$name=array_pop($stack);
if($name!==$type)
{
$tag=$name[0]==='@' ? '</prop:'.substr($name,1).'>' : "</com:$name>";
throw new TConfigurationException('template_closingtag_expected',$tag);
}
$container=$tpl[$container][0];
}
else if(strpos($str,'<%@')===0)					{
if($expectPropEnd)
continue;
if($matchStart>$textStart)
$tpl[$c++]=array($container,substr($input,$textStart,$matchStart-$textStart));
$textStart=$matchEnd+1;
if(isset($tpl[0]))
throw new TConfigurationException('template_directive_nonunique');
$this->_directive=$this->parseAttributes($match[4][0],$match[4][1]);
}
else if(strpos($str,'<%')===0)					{
if($expectPropEnd)
continue;
if($matchStart>$textStart)
$tpl[$c++]=array($container,substr($input,$textStart,$matchStart-$textStart));
$textStart=$matchEnd+1;
if($str[2]==='=')							$tpl[$c++]=array($container,'TExpression',array('Expression'=>THttpUtility::htmlDecode($match[5][0])));
else if($str[2]==='%')  						$tpl[$c++]=array($container,'TStatements',array('Statements'=>THttpUtility::htmlDecode($match[5][0])));
else
$tpl[$c++]=array($container,'TLiteral',array('Text'=>$this->parseAttribute($str)));
}
else if(strpos($str,'<prop:')===0)					{
$prop=strtolower($match[3][0]);
array_push($stack,'@'.$prop);
if(!$expectPropEnd)
{
if($matchStart>$textStart)
$tpl[$c++]=array($container,substr($input,$textStart,$matchStart-$textStart));
$textStart=$matchEnd+1;
$expectPropEnd=true;
}
}
else if(strpos($str,'</prop:')===0)					{
$prop=strtolower($match[3][0]);
if(empty($stack))
throw new TConfigurationException('template_closingtag_unexpected',"</prop:$prop>");
$name=array_pop($stack);
if($name!=='@'.$prop)
{
$tag=$name[0]==='@' ? '</prop:'.substr($name,1).'>' : "</com:$name>";
throw new TConfigurationException('template_closingtag_expected',$tag);
}
if(($last=count($stack))<1 || $stack[$last-1][0]!=='@')
{
if($matchStart>$textStart && $container>=0)
{
$value=substr($input,$textStart,$matchStart-$textStart);
if(strrpos($prop,'template')===strlen($prop)-8)
$value=$this->parseTemplateProperty($value,$textStart);
else
$value=$this->parseAttribute($value);
$type=$tpl[$container][1];
$this->validateAttributes($type,array($prop=>$value));
$tpl[$container][2][$prop]=$value;
$textStart=$matchEnd+1;
}
$expectPropEnd=false;
}
}
else if(strpos($str,'<!--')===0)					{
$state=0;
}
else if(strpos($str,'<!')===0)						{
if($expectPropEnd)
throw new TConfigurationException('template_comments_forbidden');
if($matchStart>$textStart)
$tpl[$c++]=array($container,substr($input,$textStart,$matchStart-$textStart));
$textStart=$matchEnd+1;
}
else
throw new TConfigurationException('template_matching_unexpected',$match);
}
if(!empty($stack))
{
$name=array_pop($stack);
$tag=$name[0]==='@' ? '</prop:'.substr($name,1).'>' : "</com:$name>";
throw new TConfigurationException('template_closingtag_expected',$tag);
}
if($textStart<strlen($input))
$tpl[$c++]=array($container,substr($input,$textStart));
}
catch(Exception $e)
{
if($e->getErrorCode()==='template_format_invalid' || $e->getErrorCode()==='template_format_invalid2')
throw $e;
if($matchEnd===0)
$line=$this->_startingLine+1;
else
$line=$this->_startingLine+count(explode("\n",substr($input,0,$matchEnd+1)));
if(empty($this->_tplFile))
throw new TConfigurationException('template_format_invalid2',$line,$e->getMessage(),$input);
else
throw new TConfigurationException('template_format_invalid',$this->_tplFile,$line,$e->getMessage());
}
return $tpl;
}
protected function parseAttributes($str,$offset)
{
if($str==='')
return array();
$pattern='/([\w\.]+)=(\'.*?\'|".*?"|<%.*?%>)/msS';
$attributes=array();
$n=preg_match_all($pattern,$str,$matches,PREG_SET_ORDER|PREG_OFFSET_CAPTURE);
for($i=0;$i<$n;++$i)
{
$match=&$matches[$i];
$name=strtolower($match[1][0]);
$value=$match[2][0];
if(strrpos($name,'template')===strlen($name)-8)
{
if($value[0]==='\'' || $value[0]==='"')
$attributes[$name]=$this->parseTemplateProperty(substr($value,1,strlen($value)-2),$match[2][1]+1);
else
$attributes[$name]=$this->parseTemplateProperty($value,$match[2][1]);
}
else
{
if($value[0]==='\'' || $value[0]==='"')
$attributes[$name]=$this->parseAttribute(substr($value,1,strlen($value)-2));
else
$attributes[$name]=$this->parseAttribute($value);
}
}
return $attributes;
}
protected function parseTemplateProperty($content,$offset)
{
$line=$this->_startingLine+count(explode("\n",substr($this->_content,0,$offset)))-1;
return array(self::CONFIG_TEMPLATE,new TTemplate($content,$this->_contextPath,$this->_tplFile,$line));
}
protected function parseAttribute($value)
{
$matches=array();
if(!preg_match('/\\s*(<%#.*?%>|<%=.*?%>|<%~.*?%>|<%\\$.*?%>|<%\\[.*?\\]%>)\\s*/msS',$value,$matches) || $matches[0]!==$value)
return THttpUtility::htmlDecode($value);
$value=THttpUtility::htmlDecode($matches[1]);
if($value[2]==='#') 			return array(self::CONFIG_DATABIND,substr($value,3,strlen($value)-5));
else if($value[2]==='=') 			return array(self::CONFIG_EXPRESSION,substr($value,3,strlen($value)-5));
else if($value[2]==='~') 			return array(self::CONFIG_ASSET,trim(substr($value,3,strlen($value)-5)));
else if($value[2]==='[')
return array(self::CONFIG_LOCALIZATION,trim(substr($value,3,strlen($value)-6)));
else if($value[2]==='$')
return array(self::CONFIG_PARAMETER,trim(substr($value,3,strlen($value)-5)));
}
protected function validateAttributes($type,$attributes)
{
Prado::using($type);
if(($pos=strrpos($type,'.'))!==false)
$className=substr($type,$pos+1);
else
$className=$type;
if(is_subclass_of($className,'TControl') || $className==='TControl')
{
foreach($attributes as $name=>$att)
{
if(($pos=strpos($name,'.'))!==false)
{
$subname=substr($name,0,$pos);
if(!is_callable(array($className,'get'.$subname)))
throw new TConfigurationException('template_property_unknown',$type,$subname);
}
else if(strncasecmp($name,'on',2)===0)
{
if(!is_callable(array($className,$name)))
throw new TConfigurationException('template_event_unknown',$type,$name);
else if(!is_string($att))
throw new TConfigurationException('template_eventhandler_invalid',$type,$name);
}
else
{
if(!is_callable(array($className,'set'.$name)))
{
if(is_callable(array($className,'get'.$name)))
throw new TConfigurationException('template_property_readonly',$type,$name);
else
throw new TConfigurationException('template_property_unknown',$type,$name);
}
}
}
}
else if(is_subclass_of($className,'TComponent') || $className==='TComponent')
{
foreach($attributes as $name=>$att)
{
if($att[0]===self::CONFIG_DATABIND)
throw new TConfigurationException('template_databind_forbidden',$type,$name);
if(($pos=strpos($name,'.'))!==false)
{
$subname=substr($name,0,$pos);
if(!is_callable(array($className,'get'.$subname)))
throw new TConfigurationException('template_property_unknown',$type,$subname);
}
else if(strncasecmp($name,'on',2)===0)
throw new TConfigurationException('template_event_forbidden',$type,$name);
else
{
if(strcasecmp($name,'id')!==0 && !is_callable(array($className,'set'.$name)))
{
if(is_callable(array($className,'get'.$name)))
throw new TConfigurationException('template_property_readonly',$type,$name);
else
throw new TConfigurationException('template_property_unknown',$type,$name);
}
}
}
}
else
throw new TConfigurationException('template_component_required',$type);
}
}

class TThemeManager extends TModule
{
const DEFAULT_BASEPATH='themes';
private $_initialized=false;
private $_basePath=null;
private $_baseUrl=null;
public function init($config)
{
$this->_initialized=true;
$this->getService()->setThemeManager($this);
}
public function getTheme($name)
{
$themePath=$this->getBasePath().'/'.$name;
$themeUrl=rtrim($this->getBaseUrl(),'/').'/'.$name;
return new TTheme($themePath,$themeUrl);
}
public function getBasePath()
{
if($this->_basePath===null)
{
$this->_basePath=dirname($this->getRequest()->getPhysicalApplicationPath()).'/'.self::DEFAULT_BASEPATH;
if(($basePath=realpath($this->_basePath))===false || !is_dir($basePath))
throw new TConfigurationException('thememanager_basepath_invalid',$this->_basePath);
$this->_basePath=$basePath;
}
return $this->_basePath;
}
public function setBasePath($value)
{
if($this->_initialized)
throw new TInvalidOperationException('thememanager_basepath_unchangeable');
else
{
$this->_basePath=Prado::getPathOfAlias($value);
if($this->_basePath===null || !is_dir($this->_basePath))
throw new TInvalidDataValueException('thememanager_basepath_invalid',$value);
$this->_basePath=$value;
}
}
public function getBaseUrl()
{
if($this->_baseUrl===null)
{
$appPath=dirname($this->getRequest()->getPhysicalApplicationPath());
$basePath=$this->getBasePath();
if(strpos($basePath,$appPath)===false)
throw new TConfigurationException('thememanager_baseurl_required');
$appUrl=rtrim(dirname($this->getRequest()->getApplicationPath()),'/');
$this->_baseUrl=$appUrl.strtr(substr($basePath,strlen($appPath)),'\\','/');
}
return $this->_baseUrl;
}
public function setBaseUrl($value)
{
$this->_baseUrl=rtrim($value,'/');
}
}
class TTheme extends TComponent implements ITheme
{
const THEME_CACHE_PREFIX='prado:theme:';
const SKIN_FILE_EXT='.skin';
private $_themePath;
private $_themeUrl;
private $_skins=null;
private $_name='';
private $_cssFiles=array();
private $_jsFiles=array();
public function __construct($themePath,$themeUrl)
{
$this->_themeUrl=$themeUrl;
$this->_name=basename($themePath);
if(($cache=$this->getApplication()->getCache())!==null)
{
$array=$cache->get(self::THEME_CACHE_PREFIX.$themePath);
if(is_array($array))
{
list($skins,$cssFiles,$jsFiles,$timestamp)=$array;
$cacheValid=true;
if($this->getApplication()->getMode()!==TApplication::STATE_PERFORMANCE)
{
if(($dir=opendir($themePath))===false)
throw new TIOException('theme_path_inexistent',$themePath);
while(($file=readdir($dir))!==false)
{
if($file==='.' || $file==='..')
continue;
else if(basename($file,'.css')!==$file)
$this->_cssFiles[]=$themeUrl.'/'.$file;
else if(basename($file,'.js')!==$file)
$this->_jsFiles[]=$themeUrl.'/'.$file;
else if(basename($file,self::SKIN_FILE_EXT)!==$file && filemtime($themePath.'/'.$file)>$timestamp)
{
$cacheValid=false;
break;
}
}
closedir($dir);
if($cacheValid)
$this->_skins=$skins;
}
else
{
$this->_cssFiles=$cssFiles;
$this->_jsFiles=$jsFiles;
$this->_skins=$skins;
}
}
}
if($this->_skins===null)
{
if(($dir=opendir($themePath))===false)
throw new TIOException('theme_path_inexistent',$themePath);
while(($file=readdir($dir))!==false)
{
if($file==='.' || $file==='..')
continue;
else if(basename($file,'.css')!==$file)
$this->_cssFiles[]=$themeUrl.'/'.$file;
else if(basename($file,'.js')!==$file)
$this->_jsFiles[]=$themeUrl.'/'.$file;
else if(basename($file,self::SKIN_FILE_EXT)!==$file)
{
$template=new TTemplate(file_get_contents($themePath.'/'.$file),$themePath,$themePath.'/'.$file);
foreach($template->getItems() as $skin)
{
if($skin[0]!==-1)
throw new TConfigurationException('theme_control_nested',$skin[1],dirname($themePath));
else if(!isset($skin[2]))  							continue;
$type=$skin[1];
$id=isset($skin[2]['skinid'])?$skin[2]['skinid']:0;
unset($skin[2]['skinid']);
if(isset($this->_skins[$type][$id]))
throw new TConfigurationException('theme_skinid_duplicated',$type,$id,dirname($themePath));
foreach($skin[2] as $name=>$value)
{
if(is_array($value) && ($value[0]===TTemplate::CONFIG_DATABIND || $value[0]===TTemplate::CONFIG_PARAMETER))
throw new TConfigurationException('theme_databind_forbidden',dirname($themePath),$type,$id);
}
$this->_skins[$type][$id]=$skin[2];
}
}
}
closedir($dir);
if($cache!==null)
$cache->set(self::THEME_CACHE_PREFIX.$themePath,array($this->_skins,$this->_cssFiles,$this->_jsFiles,time()));
}
}
public function getName()
{
return $this->_name;
}
public function applySkin($control)
{
$type=get_class($control);
if(($id=$control->getSkinID())==='')
$id=0;
if(isset($this->_skins[$type][$id]))
{
foreach($this->_skins[$type][$id] as $name=>$value)
{

if(is_array($value))
{
if($value[0]===TTemplate::CONFIG_EXPRESSION)
$value=$this->evaluateExpression($value[1]);
else if($value[0]===TTemplate::CONFIG_ASSET)
$value=$this->_themeUrl.'/'.ltrim($value[1],'/');
}
if(strpos($name,'.')===false)					{
if($control->hasProperty($name))
{
if($control->canSetProperty($name))
{
$setter='set'.$name;
$control->$setter($value);
}
else
throw new TConfigurationException('theme_property_readonly',$type,$name);
}
else
throw new TConfigurationException('theme_property_undefined',$type,$name);
}
else						$control->setSubProperty($name,$value);
}
return true;
}
else
return false;
}
public function getStyleSheetFiles()
{
return $this->_cssFiles;
}
public function getJavaScriptFiles()
{
return $this->_jsFiles;
}
}

class TAssetManager extends TModule
{
const DEFAULT_BASEPATH='assets';
private $_basePath=null;
private $_baseUrl=null;
private $_checkTimestamp=false;
private $_application;
private $_published=array();
public function init($config)
{
$application=$this->getApplication();
if($this->_basePath===null)
$this->_basePath=dirname($application->getRequest()->getPhysicalApplicationPath()).'/'.self::DEFAULT_BASEPATH;
if(!is_writable($this->_basePath) || !is_dir($this->_basePath))
throw new TConfigurationException('assetmanager_basepath_invalid',$this->_basePath);
if($this->_baseUrl===null)
$this->_baseUrl=rtrim(dirname($application->getRequest()->getApplicationPath()),'/').'/'.self::DEFAULT_BASEPATH;
$application->getService()->setAssetManager($this);
}
public function getBasePath()
{
return $this->_basePath;
}
public function setBasePath($value)
{
if($this->_initialized)
throw new TInvalidOperationException('assetmanager_basepath_unchangeable');
else
{
$this->_basePath=Prado::getPathOfAlias($value);
if($this->_basePath===null || !is_dir($this->_basePath) || !is_writable($this->_basePath))
throw new TInvalidDataValueException('assetmanage_basepath_invalid',$value);
}
}
public function getBaseUrl()
{
return $this->_baseUrl;
}
public function setBaseUrl($value)
{
if($this->_initialized)
throw new TInvalidOperationException('assetmanager_baseurl_unchangeable');
else
$this->_baseUrl=rtrim($value,'/');
}
public function getPublishedUrl($path)
{
if(($fullpath=realpath($path))!==false)
{
$dir=$this->hash(dirname($fullpath));
$file=$this->_basePath.'/'.$dir.'/'.basename($fullpath);
if(is_file($file) || is_dir($file))
return $this->_baseUrl.'/'.$dir.'/'.basename($fullpath);
}
return null;
}
public function isPublished($path)
{
return $this->getPublishedUrl($path) !== null;
}
public function publishFilePath($path,$checkTimestamp=false)
{
if(isset($this->_published[$path]))
return $this->_published[$path];
else if(($fullpath=realpath($path))===false)
return '';
else if(is_file($fullpath))
{
$dir=$this->hash(dirname($fullpath));
$file=$this->_basePath.'/'.$dir.'/'.basename($fullpath);
if(!is_file($file) || $checkTimestamp || $this->getApplication()->getMode()!==TApplication::STATE_PERFORMANCE)
{
if(!is_dir($this->_basePath.'/'.$dir))
@mkdir($this->_basePath.'/'.$dir);
if(!is_file($file) || @filemtime($file)<@filemtime($fullpath))
{

@copy($fullpath,$file);
}
}
$this->_published[$path]=$this->_baseUrl.'/'.$dir.'/'.basename($fullpath);
return $this->_published[$path];
}
else
{
$dir=$this->hash($fullpath);
if(!is_dir($this->_basePath.'/'.$dir) || $checkTimestamp || $this->getApplication()->getMode()!==TApplication::STATE_PERFORMANCE)
{

$this->copyDirectory($fullpath,$this->_basePath.'/'.$dir);
}
$this->_published[$path]=$this->_baseUrl.'/'.$dir;
return $this->_published[$path];
}
}
protected function hash($dir)
{
return sprintf('%x',crc32($dir));
}
protected function copyDirectory($src,$dst)
{
if(!is_dir($dst))
@mkdir($dst);
$folder=@opendir($src);
while($file=@readdir($folder))
{
if($file==='.' || $file==='..')
continue;
else if(is_file($src.'/'.$file))
{
if(@filemtime($dst.'/'.$file)<@filemtime($src.'/'.$file))
@copy($src.'/'.$file,$dst.'/'.$file);
}
else
$this->copyDirectory($src.'/'.$file,$dst.'/'.$file);
}
closedir($folder);
}
}

class TPageStatePersister extends TModule implements IStatePersister
{
private $_privateKey=null;
public function init($config)
{
$this->getService()->setPageStatePersister($this);
}
public function save($state)
{

$data=Prado::serialize($state);
$hmac=$this->computeHMAC($data,$this->getPrivateKey());
if(extension_loaded('zlib'))
$data=gzcompress($hmac.$data);
else
$data=$hmac.$data;
$this->getService()->getRequestedPage()->getClientScript()->registerHiddenField(TPage::FIELD_PAGESTATE,base64_encode($data));
}
public function load()
{

$str=base64_decode($this->getRequest()->itemAt(TPage::FIELD_PAGESTATE));
if($str==='')
return null;
if(extension_loaded('zlib'))
$data=gzuncompress($str);
else
$data=$str;
if($data!==false && strlen($data)>32)
{
$hmac=substr($data,0,32);
$state=substr($data,32);
if($hmac===$this->computeHMAC($state,$this->getPrivateKey()))
return Prado::unserialize($state);
}
throw new THttpException(400,'pagestatepersister_pagestate_corrupted');
}
protected function generatePrivateKey()
{
$v1=rand();
$v2=rand();
$v3=rand();
return md5("$v1$v2$v3");
}
public function getPrivateKey()
{
if(empty($this->_privateKey))
{
if(($this->_privateKey=$this->getApplication()->getGlobalState('prado:pagestatepersister:privatekey'))===null)
{
$this->_privateKey=$this->generatePrivateKey();
$this->getApplication()->setGlobalState('prado:pagestatepersister:privatekey',$this->_privateKey,null);
}
}
return $this->_privateKey;
}
public function setPrivateKey($value)
{
if(strlen($value)<8)
throw new TInvalidDataValueException('pagestatepersister_privatekey_invalid');
$this->_privateKey=$value;
}
private function computeHMAC($data,$key)
{
if (strlen($key) > 64)
$key = pack('H32', md5($key));
else if (strlen($key) < 64)
$key = str_pad($key, 64, "\0");
return md5((str_repeat("\x5c", 64) ^ substr($key, 0, 64)) . pack('H32', md5((str_repeat("\x36", 64) ^ substr($key, 0, 64)) . $data)));
}
}

class TControl extends TComponent
{
const ID_FORMAT='/^\\w*$/';
const ID_SEPARATOR='$';
const CLIENT_ID_SEPARATOR='_';
const AUTOMATIC_ID_PREFIX='ctl';
const CS_CONSTRUCTED=0;
const CS_CHILD_INITIALIZED=1;
const CS_INITIALIZED=2;
const CS_STATE_LOADED=3;
const CS_LOADED=4;
const CS_PRERENDERED=5;
const IS_ID_SET=0x01;
const IS_DISABLE_VIEWSTATE=0x02;
const IS_SKIN_APPLIED=0x04;
const IS_STYLESHEET_APPLIED=0x08;
const IS_DISABLE_THEMING=0x10;
const IS_CHILD_CREATED=0x20;
const IS_CREATING_CHILD=0x40;
const RF_CONTROLS=0;				const RF_CHILD_STATE=1;				const RF_NAMED_CONTROLS=2;			const RF_NAMED_CONTROLS_ID=3;		const RF_SKIN_ID=4;					const RF_DATA_BINDINGS=5;			const RF_EVENTS=6;					const RF_CONTROLSTATE=7;			const RF_NAMED_OBJECTS=8;
private $_id='';
private $_uid='';
private $_parent=null;
private $_page=null;
private $_namingContainer=null;
private $_tplControl=null;
private $_viewState=array();
private $_stage=0;
private $_flags=0;
private $_rf=array();
public function __get($name)
{
if(isset($this->_rf[self::RF_NAMED_OBJECTS][$name]))
return $this->_rf[self::RF_NAMED_OBJECTS][$name];
else
return parent::__get($name);
}
public function getParent()
{
return $this->_parent;
}
public function getNamingContainer()
{
if(!$this->_namingContainer && $this->_parent)
{
if($this->_parent instanceof INamingContainer)
$this->_namingContainer=$this->_parent;
else
$this->_namingContainer=$this->_parent->getNamingContainer();
}
return $this->_namingContainer;
}
public function getPage()
{
if(!$this->_page)
{
if($this->_parent)
$this->_page=$this->_parent->getPage();
else if($this->_tplControl)
$this->_page=$this->_tplControl->getPage();
}
return $this->_page;
}
public function setPage($page)
{
$this->_page=$page;
}
public function setTemplateControl($control)
{
$this->_tplControl=$control;
}
public function getTemplateControl()
{
if(!$this->_tplControl && $this->_parent)
$this->_tplControl=$this->_parent->getTemplateControl();
return $this->_tplControl;
}
public function getAsset($assetPath)
{
$class=new ReflectionClass(get_class($this));
$assetPath=dirname($class->getFileName()).'/'.$assetPath;
return $this->getService()->getAsset($assetPath);
}
public function getID($hideAutoID=true)
{
if($hideAutoID)
return ($this->_flags & self::IS_ID_SET) ? $this->_id : '';
else
return $this->_id;
}
public function setID($id)
{
if(!preg_match(self::ID_FORMAT,$id))
throw new TInvalidDataValueException('control_id_invalid',get_class($this),$id);
$this->_id=$id;
$this->_flags |= self::IS_ID_SET;
$this->clearCachedUniqueID($this instanceof INamingContainer);
if($this->_namingContainer)
$this->_namingContainer->clearNameTable();
}
public function getUniqueID()
{
if($this->_uid==='')			{
if($namingContainer=$this->getNamingContainer())
{
if($this->getPage()===$namingContainer)
return ($this->_uid=$this->_id);
else if(($prefix=$namingContainer->getUniqueID())==='')
return $this->_id;
else
return ($this->_uid=$prefix.self::ID_SEPARATOR.$this->_id);
}
else					return $this->_id;
}
else
return $this->_uid;
}
public function focus()
{
$this->getPage()->setFocus($this);
}
public function getClientID()
{
return strtr($this->getUniqueID(),self::ID_SEPARATOR,self::CLIENT_ID_SEPARATOR);
}
public function getSkinID()
{
return isset($this->_rf[self::RF_SKIN_ID])?$this->_rf[self::RF_SKIN_ID]:'';
}
public function setSkinID($value)
{
if(($this->_flags & self::IS_SKIN_APPLIED) || $this->_stage>=self::CS_CHILD_INITIALIZED)
throw new TInvalidOperationException('control_skinid_unchangeable',get_class($this));
else
$this->_rf[self::RF_SKIN_ID]=$value;
}
public function getEnableTheming()
{
if($this->_flags & self::IS_DISABLE_THEMING)
return false;
else
return $this->_parent?$this->_parent->getEnableTheming():true;
}
public function setEnableTheming($value)
{
if($this->_stage>=self::CS_CHILD_INITIALIZED)
throw new TInvalidOperationException('control_enabletheming_unchangeable',get_class($this),$this->getUniqueID());
else if(TPropertyValue::ensureBoolean($value))
$this->_flags &= ~self::IS_DISABLE_THEMING;
else
$this->_flags |= self::IS_DISABLE_THEMING;
}
public function getHasControls()
{
return isset($this->_rf[self::RF_CONTROLS]) && $this->_rf[self::RF_CONTROLS]->getCount()>0;
}
public function getControls()
{
if(!isset($this->_rf[self::RF_CONTROLS]))
$this->_rf[self::RF_CONTROLS]=new TControlList($this);
return $this->_rf[self::RF_CONTROLS];
}
public function getVisible($checkParents=true)
{
if($checkParents)
{
for($control=$this;$control;$control=$control->_parent)
if(!$control->getViewState('Visible',true))
return false;
return true;
}
else
return $this->getViewState('Visible',true);
}
public function setVisible($value)
{
$this->setViewState('Visible',TPropertyValue::ensureBoolean($value),true);
}
public function getEnabled($checkParents=false)
{
if($checkParents)
{
for($control=$this;$control;$control=$control->_parent)
if(!$control->getViewState('Enabled',true))
return false;
return true;
}
else
return $this->getViewState('Enabled',true);
}
public function setEnabled($value)
{
$this->setViewState('Enabled',TPropertyValue::ensureBoolean($value),true);
}
public function getHasAttributes()
{
if($attributes=$this->getViewState('Attributes',null))
return $attributes->getCount()>0;
else
return false;
}
public function getAttributes()
{
if($attributes=$this->getViewState('Attributes',null))
return $attributes;
else
{
$attributes=new TAttributeCollection;
$this->setViewState('Attributes',$attributes,null);
return $attributes;
}
}
public function hasAttribute($name)
{
if($attributes=$this->getViewState('Attributes',null))
return $attributes->contains($name);
else
return false;
}
public function getAttribute($name)
{
if($attributes=$this->getViewState('Attributes',null))
return $attributes->itemAt($name);
else
return null;
}
public function setAttribute($name,$value)
{
$this->getAttributes()->add($name,$value);
}
public function removeAttribute($name)
{
if($attributes=$this->getViewState('Attributes',null))
return $attributes->remove($name);
else
return null;
}
public function getEnableViewState($checkParents=false)
{
if($checkParents)
{
for($control=$this;$control!==null;$control=$control->getParent())
if($control->_flags & self::IS_DISABLE_VIEWSTATE)
return false;
return true;
}
else
return !($this->_flags & self::IS_DISABLE_VIEWSTATE);
}
public function setEnableViewState($value)
{
if(TPropertyValue::ensureBoolean($value))
$this->_flags &= ~self::IS_DISABLE_VIEWSTATE;
else
$this->_flags |= self::IS_DISABLE_VIEWSTATE;
}
protected function getControlState($key,$defaultValue=null)
{
return isset($this->_rf[self::RF_CONTROLSTATE][$key])?$this->_rf[self::RF_CONTROLSTATE][$key]:$defaultValue;
}
protected function setControlState($key,$value,$defaultValue=null)
{
if($value===$defaultValue)
unset($this->_rf[self::RF_CONTROLSTATE][$key]);
else
$this->_rf[self::RF_CONTROLSTATE][$key]=$value;
}
protected function clearControlState($key)
{
unset($this->_rf[self::RF_CONTROLSTATE][$key]);
}
protected function getViewState($key,$defaultValue=null)
{
return isset($this->_viewState[$key])?$this->_viewState[$key]:$defaultValue;
}
protected function setViewState($key,$value,$defaultValue=null)
{
if($value===$defaultValue)
unset($this->_viewState[$key]);
else
$this->_viewState[$key]=$value;
}
protected function clearViewState($key)
{
unset($this->_viewState[$key]);
}
public function bindProperty($name,$expression)
{
$this->_rf[self::RF_DATA_BINDINGS][$name]=$expression;
}
public function unbindProperty($name)
{
unset($this->_rf[self::RF_DATA_BINDINGS][$name]);
}
public function dataBind()
{

$this->dataBindProperties();

$this->onDataBinding(null);

$this->dataBindChildren();
}
protected function dataBindProperties()
{
if(isset($this->_rf[self::RF_DATA_BINDINGS]))
{
foreach($this->_rf[self::RF_DATA_BINDINGS] as $property=>$expression)
$this->setSubProperty($property,$this->evaluateExpression($expression));
}
}
protected function dataBindChildren()
{
if(isset($this->_rf[self::RF_CONTROLS]))
{
foreach($this->_rf[self::RF_CONTROLS] as $control)
if($control instanceof TControl)
$control->dataBind();
}
}
final protected function getChildControlsCreated()
{
return ($this->_flags & self::IS_CHILD_CREATED)!==0;
}
final protected function setChildControlsCreated($value)
{
if($value)
$this->_flags |= self::IS_CHILD_CREATED;
else
{
if($this->hasControl() && ($this->_flags & self::IS_CHILD_CREATED))
$this->getControls()->clear();
$this->_flags &= ~self::IS_CHILD_CREATED;
}
}
public function ensureChildControls()
{
if(!($this->_flags & self::IS_CHILD_CREATED) && !($this->_flags & self::IS_CREATING_CHILD))
{
try
{
$this->_flags |= self::IS_CREATING_CHILD;
$this->createChildControls();
$this->_flags &= ~self::IS_CREATING_CHILD;
$this->_flags |= self::IS_CHILD_CREATED;
}
catch(Exception $e)
{
$this->_flags &= ~self::IS_CREATING_CHILD;
$this->_flags |= self::IS_CHILD_CREATED;
throw $e;
}
}
}
protected function createChildControls()
{
}
public function findControl($id)
{
$id=strtr($id,'.',self::ID_SEPARATOR);
$container=($this instanceof INamingContainer)?$this:$this->getNamingContainer();
if(!$container || !$container->getHasControls())
return null;
if(!isset($container->_rf[self::RF_NAMED_CONTROLS]))
{
$container->_rf[self::RF_NAMED_CONTROLS]=array();
$container->fillNameTable($container,$container->_rf[self::RF_CONTROLS]);
}
if(($pos=strpos($id,self::ID_SEPARATOR))===false)
return isset($container->_rf[self::RF_NAMED_CONTROLS][$id])?$container->_rf[self::RF_NAMED_CONTROLS][$id]:null;
else
{
$cid=substr($id,0,$pos);
$sid=substr($id,$pos+1);
if(isset($container->_rf[self::RF_NAMED_CONTROLS][$cid]))
return $container->_rf[self::RF_NAMED_CONTROLS][$cid]->findControl($sid);
else
return null;
}
}
public function findControlsByType($type)
{
$controls=array();
if($this->getHasControls())
{
foreach($this->_rf[self::RF_CONTROLS] as $control)
{
if($control instanceof $type)
$controls[]=$control;
if(($control instanceof TControl) && $control->getHasControls())
$controls=array_merge($controls,$control->findControlsByType($type));
}
}
return $controls;
}
public function clearNamingContainer()
{
unset($this->_rf[self::RF_NAMED_CONTROLS_ID]);
$this->clearNameTable();
}
public function registerObject($name,$object)
{
if(isset($this->_rf[self::RF_NAMED_OBJECTS][$name]))
throw new TInvalidOperationException('control_object_reregistered',$name);
$this->_rf[self::RF_NAMED_OBJECTS][$name]=$object;
}
public function unregisterObject($name)
{
unset($this->_rf[self::RF_NAMED_OBJECTS][$name]);
}
public function isObjectRegistered($name)
{
return isset($this->_rf[self::RF_NAMED_OBJECTS][$name]);
}
public function getRegisteredObject($name)
{
return isset($this->_rf[self::RF_NAMED_OBJECTS][$name])?$this->_rf[self::RF_NAMED_OBJECTS][$name]:null;
}
public function createdOnTemplate($parent)
{
$parent->addParsedObject($this);
}
public function addParsedObject($object)
{
$this->getControls()->add($object);
}
final protected function clearChildState()
{
unset($this->_rf[self::RF_CHILD_STATE]);
}
final protected function isDescendentOf($ancestor)
{
$control=$this;
while($control!==$ancestor && $control->_parent)
$control=$control->_parent;
return $control===$ancestor;
}
public function addedControl($control)
{
if($control->_parent)
$control->_parent->getControls()->remove($control);
$control->_parent=$this;
$control->_page=$this->getPage();
$namingContainer=($this instanceof INamingContainer)?$this:$this->_namingContainer;
if($namingContainer)
{
$control->_namingContainer=$namingContainer;
if($control->_id==='')
$control->generateAutomaticID();
else
$namingContainer->clearNameTable();
}
if($this->_stage>=self::CS_CHILD_INITIALIZED)
{
$control->initRecursive($namingContainer);
if($this->_stage>=self::CS_STATE_LOADED)
{
if(isset($this->_rf[self::RF_CHILD_STATE][$control->_id]))
{
$state=$this->_rf[self::RF_CHILD_STATE][$control->_id];
unset($this->_rf[self::RF_CHILD_STATE][$control->_id]);
}
else
$state=null;
$control->loadStateRecursive($state,!($this->_flags & self::IS_DISABLE_VIEWSTATE));
if($this->_stage>=self::CS_LOADED)
{
$control->loadRecursive();
if($this->_stage>=self::CS_PRERENDERED)
$control->preRenderRecursive();
}
}
}
}
public function removedControl($control)
{
if($this->_namingContainer)
$this->_namingContainer->clearNameTable();
$control->unloadRecursive();
$control->_parent=null;
$control->_page=null;
$control->_namingContainer=null;
$control->_tplControl=null;
$control->_stage=self::CS_CONSTRUCTED;
if(!($control->_flags & self::IS_ID_SET))
$control->_id='';
$control->clearCachedUniqueID(true);
}
protected function initRecursive($namingContainer=null)
{
if($this->getHasControls())
{
if($this instanceof INamingContainer)
$namingContainer=$this;
$page=$this->getPage();
foreach($this->_rf[self::RF_CONTROLS] as $control)
{
if($control instanceof TControl)
{
$control->_namingContainer=$namingContainer;
$control->_page=$page;
if($control->_id==='' && $namingContainer)
$control->generateAutomaticID();
$control->initRecursive($namingContainer);
}
}
}
if($this->_stage<self::CS_INITIALIZED)
{
$this->_stage=self::CS_CHILD_INITIALIZED;
if(($page=$this->getPage()) && $this->getEnableTheming() && !($this->_flags & self::IS_SKIN_APPLIED))
{
$page->applyControlSkin($this);
$this->_flags |= self::IS_SKIN_APPLIED;
}
$this->onInit(null);
$this->_stage=self::CS_INITIALIZED;
}
}
protected function loadRecursive()
{
if($this->_stage<self::CS_LOADED)
$this->onLoad(null);
if($this->getHasControls())
{
foreach($this->_rf[self::RF_CONTROLS] as $control)
if($control instanceof TControl)
$control->loadRecursive();
}
if($this->_stage<self::CS_LOADED)
$this->_stage=self::CS_LOADED;
}
protected function preRenderRecursive()
{
if($this->getVisible(false))
{
$this->ensureChildControls();
$this->onPreRender(null);
if($this->getHasControls())
{
foreach($this->_rf[self::RF_CONTROLS] as $control)
if($control instanceof TControl)
$control->preRenderRecursive();
}
}
$this->_stage=self::CS_PRERENDERED;
}
protected function unloadRecursive()
{
if(!($this->_flags & self::IS_ID_SET))
$this->_id='';
if($this->getHasControls())
{
foreach($this->_rf[self::RF_CONTROLS] as $control)
if($control instanceof TControl)
$control->unloadRecursive();
}
$this->onUnload(null);
}
public function onInit($param)
{
$this->raiseEvent('OnInit',$this,$param);
}
public function onLoad($param)
{
$this->raiseEvent('OnLoad',$this,$param);
}
public function onDataBinding($param)
{
$this->raiseEvent('OnDataBinding',$this,$param);
}
public function onUnload($param)
{
$this->raiseEvent('OnUnload',$this,$param);
}
public function onPreRender($param)
{
$this->raiseEvent('OnPreRender',$this,$param);
}
protected function raiseBubbleEvent($sender,$param)
{
$control=$this;
while($control=$control->_parent)
{
if($control->onBubbleEvent($sender,$param))
break;
}
}
public function onBubbleEvent($sender,$param)
{
return false;
}
protected function broadcastEvent($sender,TBroadCastEventParameter $param)
{
$origin=(($page=$this->getPage())===null)?$this:$page;
$origin->broadcastEventInternal($sender,$param);
}
final protected function broadcastEventInternal($sender,$param)
{
if($this instanceof IBroadcastEventReceiver)
$this->broadcastEventReceived($sender,$param);
if($this->getHasControls())
{
foreach($this->_rf[self::RF_CONTROLS] as $control)
{
if($control instanceof TControl)
$control->broadcastEventInternal($sender,$param);
}
}
}
protected function renderControl($writer)
{
if($this->getVisible(false))
$this->render($writer);
}
protected function render($writer)
{
$this->renderChildren($writer);
}
protected function renderChildren($writer)
{
if($this->getHasControls())
{
foreach($this->_rf[self::RF_CONTROLS] as $control)
{
if($control instanceof TControl)
$control->renderControl($writer);
else if(is_string($control))
$writer->write($control);
}
}
}
public function saveState()
{
}
public function loadState()
{
}
final protected function loadStateRecursive(&$state,$needViewState=true)
{
if($state!==null)
{
$needViewState=($needViewState && !($this->_flags & self::IS_DISABLE_VIEWSTATE));
if(isset($state[1]))
{
$this->_rf[self::RF_CONTROLSTATE]=&$state[1];
unset($state[1]);
}
else
unset($this->_rf[self::RF_CONTROLSTATE]);
if($needViewState)
{
if(isset($state[0]))
$this->_viewState=&$state[0];
else
$this->_viewState=array();
}
unset($state[0]);
if($this->getHasControls())
{
foreach($this->_rf[self::RF_CONTROLS] as $control)
{
if($control instanceof TControl)
{
if(isset($state[$control->_id]))
{
$control->loadStateRecursive($state[$control->_id],$needViewState);
unset($state[$control->_id]);
}
else
{
$s=array();
$control->loadStateRecursive($s,$needViewState);
}
}
}
}
if(!empty($state))
$this->_rf[self::RF_CHILD_STATE]=&$state;
$this->_stage=self::CS_STATE_LOADED;
}
else
$this->_stage=self::CS_STATE_LOADED;
$this->loadState();
}
final protected function &saveStateRecursive($needViewState=true)
{
$this->saveState();
$needViewState=($needViewState && !($this->_flags & self::IS_DISABLE_VIEWSTATE));
$state=array();
if($this->getHasControls())
{
foreach($this->_rf[self::RF_CONTROLS] as $control)
{
if($control instanceof TControl)
{
$cs=&$control->saveStateRecursive($needViewState);
if(!empty($cs))
$state[$control->_id]=&$cs;
}
}
}
if($needViewState && !empty($this->_viewState))
$state[0]=&$this->_viewState;
if(isset($this->_rf[self::RF_CONTROLSTATE]))
$state[1]=&$this->_rf[self::RF_CONTROLSTATE];
return $state;
}
public function applyStyleSheetSkin($page)
{
if($page && !($this->_flags & self::IS_STYLESHEET_APPLIED))
{
$page->applyControlStyleSheet($this);
$this->_flags |= self::IS_STYLESHEET_APPLIED;
}
else if($this->_flags & self::IS_STYLESHEET_APPLIED)
throw new TInvalidOperationException('control_stylesheet_applied',get_class($this));
}
private function clearCachedUniqueID($recursive)
{
$this->_uid='';
if($recursive && isset($this->_rf[self::RF_CONTROLS]))
{
foreach($this->_rf[self::RF_CONTROLS] as $control)
if($control instanceof TControl)
$control->clearCachedUniqueID($recursive);
}
}
private function generateAutomaticID()
{
$this->_flags &= ~self::IS_ID_SET;
if(!isset($this->_namingContainer->_rf[self::RF_NAMED_CONTROLS_ID]))
$this->_namingContainer->_rf[self::RF_NAMED_CONTROLS_ID]=0;
$id=$this->_namingContainer->_rf[self::RF_NAMED_CONTROLS_ID]++;
$this->_id=self::AUTOMATIC_ID_PREFIX . $id;
$this->_namingContainer->clearNameTable();
}
private function clearNameTable()
{
unset($this->_rf[self::RF_NAMED_CONTROLS]);
}
private function fillNameTable($container,$controls)
{
foreach($controls as $control)
{
if($control instanceof TControl)
{
if($control->_id!=='')
{
if(isset($container->_rf[self::RF_NAMED_CONTROLS][$control->_id]))
throw new TInvalidDataValueException('control_id_nonunique',get_class($control),$control->_id);
else
$container->_rf[self::RF_NAMED_CONTROLS][$control->_id]=$control;
}
if(!($control instanceof INamingContainer) && $control->getHasControls())
$this->fillNameTable($container,$control->_rf[self::RF_CONTROLS]);
}
}
}
}
class TControlList extends TList
{
private $_o;
public function __construct(TControl $owner)
{
parent::__construct();
$this->_o=$owner;
}
protected function getOwner()
{
return $this->_o;
}
public function insertAt($index,$item)
{
if(is_string($item))
parent::insertAt($index,$item);
else if($item instanceof TControl)
{
parent::insertAt($index,$item);
$this->_o->addedControl($item);
}
else
throw new TInvalidDataTypeException('controllist_control_required');
}
public function removeAt($index)
{
$item=parent::removeAt($index);
if($item instanceof TControl)
$this->_o->removedControl($item);
return $item;
}
public function clear()
{
parent::clear();
if($this->_o instanceof INamingContainer)
$this->_o->clearNamingContainer();
}
}
interface INamingContainer
{
}
interface IPostBackEventHandler
{
public function raisePostBackEvent($param);
public function getPostBackOptions();
}
interface IPostBackDataHandler
{
public function loadPostData($key,$values);
public function raisePostDataChangedEvent();
}
interface IValidator
{
public function validate();
public function getIsValid();
public function setIsValid($value);
public function getErrorMessage();
public function setErrorMessage($value);
}
interface IValidatable
{
public function getValidationPropertyValue();
}
interface IBroadcastEventReceiver
{
public function broadcastEventReceived($sender,$param);
}
class TBroadcastEventParameter extends TEventParameter
{
private $_name;
private $_param;
public function __construct($name='',$parameter=null)
{
$this->_name=$name;
$this->_param=$parameter;
}
public function getName()
{
return $this->_name;
}
public function setName($value)
{
$this->_name=$value;
}
public function getParameter()
{
return $this->_param;
}
public function setParameter($value)
{
$this->_param=$value;
}
}
class TCommandEventParameter extends TEventParameter
{
private $_name;
private $_param;
public function __construct($name='',$parameter='')
{
$this->_name=$name;
$this->_param=$parameter;
}
public function getCommandName()
{
return $this->_name;
}
public function getCommandParameter()
{
return $this->_param;
}
}

class TTemplateControl extends TControl implements INamingContainer
{
const EXT_TEMPLATE='.tpl';
protected static $_template=array();
protected $_localTemplate=null;
private $_master=null;
private $_masterClass='';
private $_contents=array();
private $_placeholders=array();
public function getTemplate()
{
if($this->_localTemplate===null)
{
$class=get_class($this);
if(!isset(self::$_template[$class]))
self::$_template[$class]=$this->loadTemplate();
return self::$_template[$class];
}
else
return $this->_localTemplate;
}
public function setTemplate($value)
{
$this->_localTemplate=$value;
}
protected function loadTemplate()
{

$template=$this->getService()->getTemplateManager()->getTemplateByClassName(get_class($this));
self::$_template[get_class($this)]=$template;
return $template;
}
protected function createChildControls()
{
if($tpl=$this->getTemplate(true))
{
foreach($tpl->getDirective() as $name=>$value)
$this->setSubProperty($name,$value);
$tpl->instantiateIn($this);
}
}
public function registerContent(TContent $object)
{
$this->_contents[$object->getID()]=$object;
}
public function getMasterClass()
{
return $this->_masterClass;
}
public function setMasterClass($value)
{
$this->_masterClass=$value;
}
public function getMaster()
{
return $this->_master;
}
public function registerContentPlaceHolder($id,$parent,$loc)
{
$this->_placeholders[$id]=array($parent,$loc);
}
public function injectContent($id,$content)
{
if(isset($this->_placeholders[$id]))
{
list($parent,$loc)=$this->_placeholders[$id];
$parent->getControls()->insertAt($loc,$content);
}
}
protected function initRecursive($namingContainer=null)
{
$this->ensureChildControls();
if($this->_masterClass!=='')
{
$master=Prado::createComponent($this->_masterClass);
if(!($master instanceof TTemplateControl))
throw new TInvalidDataValueException('tplcontrol_required',get_class($master));
$this->_master=$master;
$this->getControls()->clear();
$this->getControls()->add($master);
$master->ensureChildControls();
foreach($this->_contents as $id=>$content)
$master->injectContent($id,$content);
}
parent::initRecursive($namingContainer);
}
}

class TForm extends TControl
{
public function onInit($param)
{
parent::onInit($param);
$this->getPage()->setForm($this);
}
protected function addAttributesToRender($writer)
{
$attributes=$this->getAttributes();
$writer->addAttribute('method',$this->getMethod());
$writer->addAttribute('action',$this->getRequest()->getRequestURI());
if(($enctype=$this->getEnctype())!=='')
$writer->addAttribute('enctype',$enctype);
$attributes->remove('action');
$page=$this->getPage();
if($this->getDefaultButton()!=='')
{		
}
$writer->addAttribute('id',$this->getClientID());
foreach($attributes as $name=>$value)
$writer->addAttribute($name,$value);
}
protected function render($writer)
{
$this->addAttributesToRender($writer);
$writer->renderBeginTag('form');
$page=$this->getPage();
$page->beginFormRender($writer);
$this->renderChildren($writer);
$page->endFormRender($writer);
$writer->renderEndTag();
}
public function getDefaultButton()
{
return $this->getViewState('DefaultButton','');
}
public function setDefaultButton($value)
{
$this->setViewState('DefaultButton',$value,'');
}
public function getDefaultFocus()
{
return $this->getViewState('DefaultFocus','');
}
public function setDefaultFocus($value)
{
$this->setViewState('DefaultFocus',$value,'');
}
public function getMethod()
{
return $this->getViewState('Method','post');
}
public function setMethod($value)
{
$this->setViewState('Method',$value,'post');
}
public function getEnctype()
{
return $this->getViewState('Enctype','');
}
public function setEnctype($value)
{
$this->setViewState('Enctype',$value,'');
}
public function getName()
{
return $this->getUniqueID();
}
public function getTarget()
{
return $this->getViewState('Target','');
}
public function setTarget($value)
{
$this->setViewState('Target',$value,'');
}
}

Prado::using('System.Web.Javascripts.*');
class TClientScriptManager extends TComponent
{
const SCRIPT_DIR='Web/Javascripts/js';
private $_page;
private $_hiddenFields=array();
private $_beginScripts=array();
private $_endScripts=array();
private $_scriptFiles=array();
private $_styleSheetFiles=array();
private $_styleSheets=array();
private $_client;
private $_publishedScriptFiles=array();
public function __construct(TPage $owner)
{
$this->_page=$owner;
$this->_client = new TClientScript($this);
}
public function registerPostBackControl($control,$namespace='Prado.WebUI')
{
$options = $this->getPostBackOptions($control);
$type = get_class($control);
$namespace = empty($namespace) ? "window" : $namespace;
$code = "new {$namespace}.{$type}($options);";
$this->registerEndScript(sprintf('%08X', crc32($code)), $code);
$this->registerHiddenField(TPage::FIELD_POSTBACK_TARGET,'');
$this->registerHiddenField(TPage::FIELD_POSTBACK_PARAMETER,'');
$this->registerClientScript('prado');
}
protected function getPostBackOptions($control)
{
$postback = $control->getPostBackOptions();
if(!isset($postback['ID'])) 
$postback['ID'] = $control->getClientID();
if(!isset($postback['FormID']))
$postback['FormID'] = $this->_page->getForm()->getClientID();
$options = new TJavascriptSerializer($postback);
return $options->toJavascript();
}
public function registerDefaultButton($panel, $button)
{
$serializer = new TJavascriptSerializer(
$this->getDefaultButtonOptions($panel, $button));
$options = $serializer->toJavascript();
$code = "new Prado.WebUI.DefaultButton($options);";
$scripts = $this->_page->getClientScript();
$scripts->registerEndScript("prado:".$panel->getClientID(), $code);
}
protected function getDefaultButtonOptions($panel, $button)
{
$options['Panel'] = $panel->getClientID();
$options['Target'] = $button->getClientID();
$options['Event'] = 'click';
return $options;
}
public function registerClientScript($script)
{
static $scripts = array();
$scripts = array_unique(array_merge($scripts, 
TClientScript::getScripts($script)));
$this->publishClientScriptAssets($scripts);
$url = $this->publishClientScriptCompressorAsset();
$url .= '?js='.implode(',', $scripts);
if(Prado::getApplication()->getMode() == TApplication::STATE_DEBUG)
$url .= '&__nocache';
$this->registerScriptFile('prado:gzipscripts', $url);
}
protected function publishClientScriptAssets($scripts)
{
foreach($scripts as $lib)
{
if(!isset($this->_publishedScriptFiles[$lib]))
{
$base = Prado::getFrameworkPath();
$clientScripts = self::SCRIPT_DIR;
$assetManager = $this->_page->getService()->getAssetManager();
$file = "{$base}/{$clientScripts}/{$lib}.js";
$assetManager->publishFilePath($file);
$this->_publishedScriptFiles[$lib] = true;
}
}
}
protected function publishClientScriptCompressorAsset()
{
$scriptFile = 'clientscripts.php';
if(isset($this->_publishedScriptFiles[$scriptFile]))
return $this->_publishedScriptFiles[$scriptFile];
else
{
$base = Prado::getFrameworkPath();
$clientScripts = self::SCRIPT_DIR;
$assetManager = $this->_page->getService()->getAssetManager();
$file = "{$base}/{$clientScripts}/{$scriptFile}";
$url= $assetManager->publishFilePath($file);
$this->_publishedScriptFiles[$scriptFile] = $url;
return $url;
}
}
public function isHiddenFieldRegistered($key)
{
return isset($this->_hiddenFields[$key]);
}
public function isScriptRegistered($key)
{
return isset($this->_scripts[$key]);
}
public function isScriptFileRegistered($key)
{
return isset($this->_scriptFiles[$key]);
}
public function isBeginScriptRegistered($key)
{
return isset($this->_beginScripts[$key]);
}
public function isEndScriptRegistered($key)
{
return isset($this->_endScripts[$key]);
}
public function isStyleSheetFileRegistered($key)
{
return isset($this->_styleSheetFiles[$key]);
}
public function isStyleSheetRegistered($key)
{
return isset($this->_styleSheets[$key]);
}
public function registerScriptFile($key,$url)
{
$this->_scriptFiles[$key]=$url;
}
public function registerHiddenField($name,$value)
{
if(!isset($this->_hiddenFields[$name]) || $this->_hiddenFields[$name]!==null)
$this->_hiddenFields[$name]=$value;
}
public function registerBeginScript($key,$script)
{
$this->_beginScripts[$key]=$script;
}
public function registerEndScript($key,$script)
{
$this->_endScripts[$key]=$script;
}
public function registerStyleSheetFile($key,$url)
{
$this->_styleSheetFiles[$key]=$url;
}
public function registerStyleSheet($key,$css)
{
$this->_styleSheets[$key]=$css;
}
public function renderScriptFiles($writer)
{
$str='';
foreach($this->_scriptFiles as $include)
$str.="<script type=\"text/javascript\" src=\"".THttpUtility::htmlEncode($include)."\"></script>\n";
$writer->write($str);
}
public function renderBeginScripts($writer)
{
if(count($this->_beginScripts))
$writer->write("<script type=\"text/javascript\">\n//<![CDATA[\n".implode("\n",$this->_beginScripts)."\n//]]>\n</script>\n");
}
public function renderEndScripts($writer)
{
if(count($this->_endScripts))
$writer->write("<script type=\"text/javascript\">\n//<![CDATA[\n".implode("\n",$this->_endScripts)."\n//]]>\n</script>\n");
}
public function renderHiddenFields($writer)
{
$str='';
foreach($this->_hiddenFields as $name=>$value)
{
if($value!==null)
{
$value=THttpUtility::htmlEncode($value);
$str.="<input type=\"hidden\" name=\"$name\" id=\"$name\" value=\"$value\" />\n";
$this->_hiddenFields[$name]=null;
}
}
if($str!=='')
$writer->write("<div>\n".$str."</div>\n");
}
public function renderJavascriptBlock($code)
{
return "<script type=\"text/javascript\">\n/*<![CDATA[*/\n{$code}\n/*]]>*/\n</script>";
}
public function renderStyleSheetFiles($writer)
{
$str='';
foreach($this->_styleSheetFiles as $url)
{
$str.="<link rel=\"stylesheet\" type=\"text/css\" href=\"".THttpUtility::htmlEncode($url)."\" />\n";
}
$writer->write($str);
}
public function renderStyleSheets($writer)
{
if(count($this->_styleSheets))
$writer->write("<style type=\"text/css\">\n".implode("\n",$this->_styleSheets)."\n</style>\n");
}
public function getHasHiddenFields()
{
return count($this->_hiddenFields)>0;
}
}

Prado::using('System.Web.UI.WebControls.*');
class TPage extends TTemplateControl
{
const FIELD_POSTBACK_TARGET='PRADO_POSTBACK_TARGET';
const FIELD_POSTBACK_PARAMETER='PRADO_POSTBACK_PARAMETER';
const FIELD_LASTFOCUS='PRADO_LASTFOCUS';
const FIELD_PAGESTATE='PRADO_PAGESTATE';
const FIELD_SCROLLX='PRADO_SCROLLX';
const FIELD_SCROLLY='PRADO_SCROLLY';
private static $_systemPostFields=array(
'PRADO_POSTBACK_TARGET'=>true,
'PRADO_POSTBACK_PARAMETER'=>true,
'PRADO_LASTFOCUS'=>true,
'PRADO_PAGESTATE'=>true,
'PRADO_SCROLLX'=>true,
'PRADO_SCROLLY'=>true,
'__PREVPAGE','__CALLBACKID','__CALLBACKPARAM'
);
private $_form=null;
private $_head=null;
private $_templateFile=null;
private $_validators=array();
private $_validated=false;
private $_theme=null;
private $_styleSheet=null;
private $_clientScript=null;
private $_postData;
private $_restPostData;
private $_controlsPostDataChanged=array();
private $_controlsRequiringPostData=array();
private $_controlsRegisteredForPostData=array();
private $_postBackEventTarget=null;
private $_postBackEventParameter=null;
private $_formRendered=false;
private $_inFormRender=false;
private $_focus=null;
private $_maintainScrollPosition=false;
private $_maxPageStateFieldLength=10;
private $_enableViewStateMac=true;
private $_isCrossPagePostBack=false;
private $_previousPagePath='';
public function __construct()
{
parent::__construct();
$this->setPage($this);
}
public function run($writer)
{

$this->determinePostBackMode();

$this->onPreInit(null);

$this->initRecursive();

$this->onInitComplete(null);
if($this->getIsPostBack())
{
$this->_restPostData=new TMap;

$this->loadPageState();

$this->processPostData($this->_postData,true);

$this->onPreLoad(null);

$this->loadRecursive();

$this->processPostData($this->_restPostData,false);

$this->raiseChangedEvents();

$this->raisePostBackEvent();

$this->onLoadComplete(null);
}
else
{

$this->onPreLoad(null);

$this->loadRecursive();

$this->onLoadComplete(null);
}

$this->preRenderRecursive();

$this->onPreRenderComplete(null);

$this->savePageState();

$this->onSaveStateComplete(null);

$this->renderControl($writer);

$this->unloadRecursive();
}
protected function loadTemplate()
{
if($this->_templateFile===null)
return parent::loadTemplate();
else
{
$template=$this->getService()->getTemplateManager()->getTemplateByFileName($this->_templateFile);
$this->setTemplate($template);
return $template;
}
}
public function getTemplateFile()
{
return $this->_templateFile;
}
public function setTemplateFile($value)
{
if(($templateFile=Prado::getPathOfNamespace($value,TTemplateManager::TEMPLATE_FILE_EXT))===null || !is_file($templateFile))
throw new TInvalidDataValueException('page_templatefile_invalid',$value);
else
$this->_templateFile=$templateFile;
}
public function setForm(TForm $form)
{
if($this->_form===null)
$this->_form=$form;
else
throw new TInvalidOperationException('page_form_duplicated');
}
public function getForm()
{
return $this->_form;
}
public function getValidators($validationGroup=null)
{
if(!$this->_validators)
$this->_validators=new TList;
if($validationGroup===null)
return $this->_validators;
else
{
$list=new TList;
foreach($this->_validators as $validator)
if($validator->getValidationGroup()===$validationGroup)
$list->add($validator);
return $list;
}
}
public function validate($validationGroup='')
{
$this->_validated=true;
if($this->_validators && $this->_validators->getCount())
{

foreach($this->_validators as $validator)
{
if($validator->getValidationGroup()===$validationGroup)
$validator->validate();
}
}
}
public function getIsValid()
{
if($this->_validated)
{
if($this->_validators && $this->_validators->getCount())
{
foreach($this->_validators as $validator)
if(!$validator->getIsValid())
return false;
}
return true;
}
else
throw new TInvalidOperationException('page_isvalid_unknown');
}
public function getTheme()
{
if(is_string($this->_theme))
$this->_theme=$this->getService()->getThemeManager()->getTheme($this->_theme);
return $this->_theme;
}
public function setTheme($value)
{
$this->_theme=$value;
}
public function getStyleSheetTheme()
{
if(is_string($this->_styleSheet))
$this->_styleSheet=$this->getService()->getThemeManager()->getTheme($this->_styleSheet);
return $this->_styleSheet;
}
public function setStyleSheetTheme($value)
{
$this->_styleSheet=$value;
}
public function applyControlSkin($control)
{
if(($theme=$this->getTheme())!==null)
$theme->applySkin($control);
}
public function applyControlStyleSheet($control)
{
if(($theme=$this->getStyleSheetTheme())!==null)
$theme->applySkin($control);
}
public function getClientScript()
{
if(!$this->_clientScript)
$this->_clientScript=new TClientScriptManager($this);
return $this->_clientScript;
}
public function onPreInit($param)
{
$this->raiseEvent('OnPreInit',$this,$param);
}
public function onInitComplete($param)
{
$this->raiseEvent('OnInitComplete',$this,$param);
}
public function onPreLoad($param)
{
$this->raiseEvent('OnPreLoad',$this,$param);
}
public function onLoadComplete($param)
{
$this->raiseEvent('OnLoadComplete',$this,$param);
}
public function onPreRenderComplete($param)
{
$this->raiseEvent('OnPreRenderComplete',$this,$param);
$cs=$this->getClientScript();
if($this->_theme instanceof ITheme)
{
foreach($this->_theme->getStyleSheetFiles() as $url)
$cs->registerStyleSheetFile($url,$url);
foreach($this->_theme->getJavaScriptFiles() as $url)
$cs->registerHeadScriptFile($url,$url);
}
if($this->_styleSheet instanceof ITheme)
{
foreach($this->_styleSheet->getStyleSheetFiles() as $url)
$cs->registerStyleSheetFile($url,$url);
foreach($this->_styleSheet->getJavaScriptFiles() as $url)
$cs->registerHeadScriptFile($url,$url);
}
}
public function onSaveStateComplete($param)
{
$this->raiseEvent('OnSaveStateComplete',$this,$param);
}
private function determinePostBackMode()
{
$postData=$this->getRequest();
if($postData->contains(self::FIELD_PAGESTATE) || $postData->contains(self::FIELD_POSTBACK_TARGET))
$this->_postData=$postData;
}
public function getIsPostBack()
{
return $this->_postData!==null;
}
protected function getPageStatePersister()
{
return $this->getService()->getPageStatePersister();
}
public function saveState()
{
parent::saveState();
$this->setViewState('ControlsRequiringPostBack',$this->_controlsRegisteredForPostData,array());
}
public function loadState()
{
parent::loadState();
$this->_controlsRequiringPostData=$this->getViewState('ControlsRequiringPostBack',array());
}
protected function loadPageState()
{
$state=$this->getPageStatePersister()->load();
$this->loadStateRecursive($state,$this->getEnableViewState());
}
protected function savePageState()
{
$state=&$this->saveStateRecursive($this->getEnableViewState());
$this->getPageStatePersister()->save($state);
}
protected function isSystemPostField($field)
{
return isset(self::$_systemPostFields[$field]);
}
public function registerRequiresPostData(TControl $control)
{
$this->_controlsRegisteredForPostData[$control->getUniqueID()]=true;
}
public function getPostBackEventTarget()
{
if($this->_postBackEventTarget===null)
{
$eventTarget=$this->_postData->itemAt(self::FIELD_POSTBACK_TARGET);
if(!empty($eventTarget))
$this->_postBackEventTarget=$this->findControl($eventTarget);
}
return $this->_postBackEventTarget;
}
public function setPostBackEventTarget(TControl $control)
{
$this->_postBackEventTarget=$control;
}
public function getPostBackEventParameter()
{
if($this->_postBackEventParameter===null)
$this->_postBackEventParameter=$this->_postData->itemAt(self::FIELD_POSTBACK_PARAMETER);
return $this->_postBackEventParameter;
}
public function setPostBackEventParameter($value)
{
$this->_postBackEventParameter=$value;
}
public function registerAutoPostBackControl(TControl $control)
{
$this->_autoPostBackControl=$control;
}
protected function processPostData($postData,$beforeLoad)
{
if($beforeLoad)
$this->_restPostData=new TMap;
foreach($postData as $key=>$value)
{
if($this->isSystemPostField($key))
continue;
else if($control=$this->findControl($key))
{
if($control instanceof IPostBackDataHandler)
{
if($control->loadPostData($key,$postData))
$this->_controlsPostDataChanged[]=$control;
}
else if($control instanceof IPostBackEventHandler)
$this->setPostBackEventTarget($control);
unset($this->_controlsRequiringPostData[$key]);
}
else if($beforeLoad)
$this->_restPostData->add($key,$value);
}
foreach($this->_controlsRequiringPostData as $key=>$value)
{
if($control=$this->findControl($key))
{
if($control instanceof IPostBackDataHandler)
{
if($control->loadPostData($key,$this->_postData))
$this->_controlsPostDataChanged[]=$control;
}
else
throw new TInvalidDataValueException('page_postbackcontrol_invalid',$key);
unset($this->_controlsRequiringPostData[$key]);
}
}
}
private function raiseChangedEvents()
{
foreach($this->_controlsPostDataChanged as $control)
$control->raisePostDataChangedEvent();
}
private function raisePostBackEvent()
{
if(($postBackHandler=$this->getPostBackEventTarget())===null)
$this->validate();
else if($postBackHandler instanceof IPostBackEventHandler)
$postBackHandler->raisePostBackEvent($this->getPostBackEventParameter());
}
public function ensureRenderInForm($control)
{
if(!$this->_inFormRender)
throw new TConfigurationException('page_control_outofform',get_class($control),$control->getID(false));
}
public function beginFormRender($writer)
{
if($this->_formRendered)
throw new TConfigurationException('page_singleform_required');
$this->_formRendered=true;
$this->_inFormRender=true;
$cs=$this->getClientScript();
$cs->renderHiddenFields($writer);
$cs->renderBeginScripts($writer);
}
public function endFormRender($writer)
{
$cs=$this->getClientScript();
if($this->getClientSupportsJavaScript())
{
if($this->_focus)
{
if(is_string($this->_focus))
$cs->registerFocusScript($this->_focus);
else if(($this->_focus instanceof TControl) && $this->_focus->getVisible(true))
$cs->registerFocusScript($this->_focus->getClientID());
}
else if($this->_postData && ($lastFocus=$this->_postData->itemAt(self::FIELD_LASTFOCUS))!==null)
$cs->registerFocusScript($lastFocus);
if($this->_maintainScrollPosition && $this->_postData)
{
$x=TPropertyValue::ensureInteger($this->_postData->itemAt(self::PRADO_SCROLLX));
$y=TPropertyValue::ensureInteger($this->_postData->itemAt(self::PRADO_SCROLLY));
$cs->registerScrollScript($x,$y);
}
$cs->renderHiddenFields($writer);
$cs->renderScriptFiles($writer);
$cs->renderEndScripts($writer);
}
else
$cs->renderHiddenFields($writer);
$this->_inFormRender=false;
}
public function setFocus($value)
{
$this->_focus=$value;
}
public function getMaintainScrollPosition()
{
return $this->_maintainScrollPosition;
}
public function setMaintainScrollPosition($value)
{
$this->_maintainScrollPosition=TPropertyValue::ensureBoolean($value);
}
public function getClientSupportsJavaScript()
{
return true;
}
protected function initializeCulture()
{
}
public function getHead()
{
return $this->_head;
}
public function setHead(THead $value)
{
if($this->_head)
throw new TInvalidOperationException('page_head_duplicated');
$this->_head=$value;
}
public function getTitle()
{
return $this->getViewState('Title','');
}
public function setTitle($value)
{
$this->setViewState('Title',$value,'');
}
}

class TFont extends TComponent
{
const IS_BOLD=0x01;
const IS_ITALIC=0x02;
const IS_OVERLINE=0x04;
const IS_STRIKEOUT=0x08;
const IS_UNDERLINE=0x10;
const IS_SET_BOLD=0x01000;
const IS_SET_ITALIC=0x02000;
const IS_SET_OVERLINE=0x04000;
const IS_SET_STRIKEOUT=0x08000;
const IS_SET_UNDERLINE=0x10000;
const IS_SET_SIZE=0x20000;
const IS_SET_NAME=0x40000;
private $_flags=0;
private $_name='';
private $_size='';
public function getBold()
{
return ($this->_flags & self::IS_BOLD)!==0;
}
public function setBold($value)
{
$this->_flags |= self::IS_SET_BOLD;
if(TPropertyValue::ensureBoolean($value))
$this->_flags |= self::IS_BOLD;
else
$this->_flags &= ~self::IS_BOLD;
}
public function getItalic()
{
return ($this->_flags & self::IS_ITALIC)!==0;
}
public function setItalic($value)
{
$this->_flags |= self::IS_SET_ITALIC;
if(TPropertyValue::ensureBoolean($value))
$this->_flags |= self::IS_ITALIC;
else
$this->_flags &= ~self::IS_ITALIC;
}
public function getOverline()
{
return ($this->_flags & self::IS_OVERLINE)!==0;
}
public function setOverline($value)
{
$this->_flags |= self::IS_SET_OVERLINE;
if(TPropertyValue::ensureBoolean($value))
$this->_flags |= self::IS_OVERLINE;
else
$this->_flags &= ~self::IS_OVERLINE;
}
public function getSize()
{
return $this->_size;
}
public function setSize($value)
{
$this->_flags |= self::IS_SET_SIZE;
$this->_size=$value;
}
public function getStrikeout()
{
return ($this->_flags & self::IS_STRIKEOUT)!==0;
}
public function setStrikeout($value)
{
$this->_flags |= self::IS_SET_STRIKEOUT;
if(TPropertyValue::ensureBoolean($value))
$this->_flags |= self::IS_STRIKEOUT;
else
$this->_flags &= ~self::IS_STRIKEOUT;
}
public function getUnderline()
{
return ($this->_flags & self::IS_UNDERLINE)!==0;
}
public function setUnderline($value)
{
$this->_flags |= self::IS_SET_UNDERLINE;
if(TPropertyValue::ensureBoolean($value))
$this->_flags |= self::IS_UNDERLINE;
else
$this->_flags &= ~self::IS_UNDERLINE;
}
public function getName()
{
return $this->_name;
}
public function setName($value)
{
$this->_flags |= self::IS_SET_NAME;
$this->_name=$value;
}
public function getIsEmpty()
{
return !$this->_flags;
}
public function reset()
{
$this->_flags=0;
$this->_name='';
$this->_size='';
}
public function mergeWith($font)
{
if($font===null || $font->_flags===0)
return;
if($font->_flags & self::IS_SET_BOLD)
$this->setBold($font->getBold());
if($font->_flags & self::IS_SET_ITALIC)
$this->setItalic($font->getItalic());
if($font->_flags & self::IS_SET_OVERLINE)
$this->setOverline($font->getOverline());
if($font->_flags & self::IS_SET_STRIKEOUT)
$this->setStrikeout($font->getStrikeout());
if($font->_flags & self::IS_SET_UNDERLINE)
$this->setUnderline($font->getUnderline());
if($font->_flags & self::IS_SET_SIZE)
$this->setSize($font->getSize());
if($font->_flags & self::IS_SET_NAME)
$this->setName($font->getName());
}
public function copyFrom($font)
{
$this->_flags=$font->_flags;
$this->_name=$font->_name;
$this->_size=$font->_size;
}
public function toString()
{
if($this->_flags===0)
return '';
$str='';
if($this->_flags & self::IS_SET_BOLD)
$str.='font-weight:'.(($this->_flags & self::IS_BOLD)?'bold;':'normal;');
if($this->_flags & self::IS_SET_ITALIC)
$str.='font-style:'.(($this->_flags & self::IS_ITALIC)?'italic;':'normal;');
$textDec='';
if($this->_flags & self::IS_UNDERLINE)
$textDec.='underline';
if($this->_flags & self::IS_OVERLINE)
$textDec.=' overline';
if($this->_flags & self::IS_STRIKEOUT)
$textDec.=' line-through';
$textDec=ltrim($textDec);
if($textDec!=='')
$str.='text-decoration:'.$textDec.';';
if($this->_size!=='')
$str.='font-size:'.$this->_size.';';
if($this->_name!=='')
$str.='font-family:'.$this->_name.';';
return $str;
}
public function addAttributesToRender($writer)
{
if($this->_flags===0)
return;
if($this->_flags & self::IS_SET_BOLD)
$writer->addStyleAttribute('font-weight',(($this->_flags & self::IS_BOLD)?'bold':'normal'));
if($this->_flags & self::IS_SET_ITALIC)
$writer->addStyleAttribute('font-style',(($this->_flags & self::IS_ITALIC)?'italic':'normal'));
$textDec='';
if($this->_flags & self::IS_UNDERLINE)
$textDec.='underline';
if($this->_flags & self::IS_OVERLINE)
$textDec.=' overline';
if($this->_flags & self::IS_STRIKEOUT)
$textDec.=' line-through';
$textDec=ltrim($textDec);
if($textDec!=='')
$writer->addStyleAttribute('text-decoration',$textDec);
if($this->_size!=='')
$writer->addStyleAttribute('font-size',$this->_size);
if($this->_name!=='')
$writer->addStyleAttribute('font-family',$this->_name);
}
}

class TStyle extends TComponent
{
private $_fields=array();
private $_font=null;
private $_class=null;
private $_customStyle=null;
public function __construct($style=null)
{
if($style!==null)
$this->copyFrom($style);
}
public function getBackColor()
{
return isset($this->_fields['background-color'])?$this->_fields['background-color']:'';
}
public function setBackColor($value)
{
if(trim($value)==='')
unset($this->_fields['background-color']);
else
$this->_fields['background-color']=$value;
}
public function getBorderColor()
{
return isset($this->_fields['border-color'])?$this->_fields['border-color']:'';
}
public function setBorderColor($value)
{
if(trim($value)==='')
unset($this->_fields['border-color']);
else
$this->_fields['border-color']=$value;
}
public function getBorderStyle()
{
return isset($this->_fields['border-style'])?$this->_fields['border-style']:'';
}
public function setBorderStyle($value)
{
if(trim($value)==='')
unset($this->_fields['border-style']);
else
$this->_fields['border-style']=$value;
}
public function getBorderWidth()
{
return isset($this->_fields['border-width'])?$this->_fields['border-width']:'';
}
public function setBorderWidth($value)
{
if(trim($value)==='')
unset($this->_fields['border-width']);
else
$this->_fields['border-width']=$value;
}
public function getCssClass()
{
return $this->_class===null?'':$this->_class;
}
public function setCssClass($value)
{
$this->_class=trim($value)===''?null:$value;
}
public function getFont()
{
if($this->_font===null)
$this->_font=new TFont;
return $this->_font;
}
public function getForeColor()
{
return isset($this->_fields['color'])?$this->_fields['color']:'';
}
public function setForeColor($value)
{
if(trim($value)==='')
unset($this->_fields['color']);
else
$this->_fields['color']=$value;
}
public function getHeight()
{
return isset($this->_fields['height'])?$this->_fields['height']:'';
}
public function setHeight($value)
{
if(trim($value)==='')
unset($this->_fields['height']);
else
$this->_fields['height']=$value;
}
public function getCustomStyle()
{
return $this->_customStyle===null?'':$this->_customStyle;
}
public function setCustomStyle($value)
{
$this->_customStyle=trim($value)===''?null:$value;
}
public function getStyleField($name)
{
return isset($this->_fields[$name])?$this->_fields[$name]:'';
}
public function setStyleField($name,$value)
{
$this->_fields[$name]=$value;
}
public function clearStyleField($name)
{
unset($this->_fields[$name]);
}
public function hasStyleField($name)
{
return isset($this->_fields[$name]);
}
public function getWidth()
{
return isset($this->_fields['width'])?$this->_fields['width']:'';
}
public function setWidth($value)
{
$this->_fields['width']=$value;
}
public function reset()
{
$this->_fields=array();
$this->_font=null;
$this->_class=null;
$this->_customStyle=null;
}
public function copyFrom($style)
{
$this->reset();
if($style instanceof TStyle)
{
$this->_fields=$style->_fields;
$this->_class=$style->_class;
$this->_customStyle=$style->_customStyle;
if($style->_font!==null)
$this->getFont()->copyFrom($style->_font);
}
}
public function mergeWith($style)
{
if($style!==null)
{
$this->_fields=array_merge($this->_fields,$style->_fields);
if($style->_class!==null)
$this->_class=$style->_class;
if($style->_customStyle!==null)
$this->_customStyle=$style->_customStyle;
if($style->_font!==null)
$this->getFont()->mergeWith($style->_font);
}
}
public function addAttributesToRender($writer)
{
if($this->_customStyle!==null)
{
foreach(explode(';',$this->_customStyle) as $style)
{
$arr=explode(':',$style);
if(isset($arr[1]) && trim($arr[0])!=='')
$writer->addStyleAttribute(trim($arr[0]),trim($arr[1]));
}
}
foreach($this->_fields as $name=>$value)
$writer->addStyleAttribute($name,$value);
if($this->_font!==null)
$this->_font->addAttributesToRender($writer);
if($this->_class!==null)
$writer->addAttribute('class',$this->_class);
}
}
class TTableStyle extends TStyle
{
private $_backImageUrl=null;
private $_horizontalAlign=null;
private $_cellPadding=null;
private $_cellSpacing=null;
private $_gridLines=null;
public function reset()
{
$this->_backImageUrl=null;
$this->_horizontalAlign=null;
$this->_cellPadding=null;
$this->_cellSpacing=null;
$this->_gridLines=null;
}
public function copyFrom($style)
{
parent::copyFrom($style);
if($style instanceof TTableStyle)
{
$this->_backImageUrl=$style->_backImageUrl;
$this->_horizontalAlign=$style->_horizontalAlign;
$this->_cellPadding=$style->_cellPadding;
$this->_cellSpacing=$style->_cellSpacing;
$this->_gridLines=$style->_gridLines;
}
}
public function mergeWith($style)
{
parent::mergeWith($style);
if($style instanceof TTableStyle)
{
if($style->_backImageUrl!==null)
$this->_backImageUrl=$style->_backImageUrl;
if($style->_horizontalAlign!==null)
$this->_horizontalAlign=$style->_horizontalAlign;
if($style->_cellPadding!==null)
$this->_cellPadding=$style->_cellPadding;
if($style->_cellSpacing!==null)
$this->_cellSpacing=$style->_cellSpacing;
if($style->_gridLines!==null)
$this->_gridLines=$style->_gridLines;
}
}
public function addAttributesToRender($writer)
{
if(($url=trim($this->getBackImageUrl()))!=='')
$writer->addStyleAttribute('background-image','url('.$url.')');
if(($horizontalAlign=$this->getHorizontalAlign())!=='NotSet')
$writer->addStyleAttribute('text-align',strtolower($horizontalAlign));
if(($cellPadding=$this->getCellPadding())>=0)
$writer->addAttribute('cellpadding',"$cellPadding");
if(($cellSpacing=$this->getCellSpacing())>=0)
{
$writer->addAttribute('cellspacing',"$cellSpacing");
if($this->getCellSpacing()===0)
$writer->addStyleAttribute('border-collapse','collapse');
}
switch($this->getGridLines())
{
case 'Horizontal' : $writer->addAttribute('rules','rows'); break;
case 'Vertical' : $writer->addAttribute('rules','cols'); break;
case 'Both' : $writer->addAttribute('rules','all'); break;
}
parent::addAttributesToRender($writer);
}
public function getBackImageUrl()
{
return $this->_backImageUrl===null?'':$this->_backImageUrl;
}
public function setBackImageUrl($value)
{
$this->_backImageUrl=trim($value)===''?null:$value;
}
public function getHorizontalAlign()
{
return $this->_horizontalAlign===null?'NotSet':$this->_horizontalAlign;
}
public function setHorizontalAlign($value)
{
$this->_horizontalAlign=TPropertyValue::ensureEnum($value,array('NotSet','Left','Right','Center','Justify'));
if($this->_horizontalAlign==='NotSet')
$this->_horizontalAlign=null;
}
public function getCellPadding()
{
return $this->_cellPadding===null?-1:$this->_cellPadding;
}
public function setCellPadding($value)
{
if(($this->_cellPadding=TPropertyValue::ensureInteger($value))<-1)
throw new TInvalidDataValueException('tablestyle_cellpadding_invalid');
if($this->_cellPadding===-1)
$this->_cellPadding=null;
}
public function getCellSpacing()
{
return $this->_cellSpacing===null?-1:$this->_cellSpacing;
}
public function setCellSpacing($value)
{
if(($this->_cellSpacing=TPropertyValue::ensureInteger($value))<-1)
throw new TInvalidDataValueException('tablestyle_cellspacing_invalid');
if($this->_cellSpacing===-1)
$this->_cellSpacing=null;
}
public function getGridLines()
{
return $this->_gridLines===null?'None':$this->_gridLines;
}
public function setGridLines($value)
{
$this->_gridLines=TPropertyValue::ensureEnum($value,array('None', 'Horizontal', 'Vertical', 'Both'));
}
}
class TTableItemStyle extends TStyle
{
private $_horizontalAlign=null;
private $_verticalAlign=null;
private $_wrap=null;
public function reset()
{
parent::reset();
$this->_verticalAlign=null;
$this->_horizontalAlign=null;
$this->_wrap=null;
}
public function copyFrom($style)
{
parent::copyFrom($style);
if($style instanceof TTableItemStyle)
{
$this->_verticalAlign=$style->_verticalAlign;
$this->_horizontalAlign=$style->_horizontalAlign;
$this->_wrap=$style->_wrap;
}
}
public function mergeWith($style)
{
parent::mergeWith($style);
if($style instanceof TTableItemStyle)
{
if($style->_verticalAlign!==null)
$this->_verticalAlign=$style->_verticalAlign;
if($style->_horizontalAlign!==null)
$this->_horizontalAlign=$style->_horizontalAlign;
if($style->_wrap!==null)
$this->_wrap=$style->_wrap;
}
}
public function addAttributesToRender($writer)
{
if(!$this->getWrap())
$writer->addStyleAttribute('white-space','nowrap');
if(($horizontalAlign=$this->getHorizontalAlign())!=='NotSet')
$writer->addAttribute('align',strtolower($horizontalAlign));
if(($verticalAlign=$this->getVerticalAlign())!=='NotSet')
$writer->addAttribute('valign',strtolower($verticalAlign));
parent::addAttributesToRender($writer);
}
public function getHorizontalAlign()
{
return $this->_horizontalAlign===null?'NotSet':$this->_horizontalAlign;
}
public function setHorizontalAlign($value)
{
$this->_horizontalAlign=TPropertyValue::ensureEnum($value,array('NotSet','Left','Right','Center','Justify'));
if($this->_horizontalAlign==='NotSet')
$this->_horizontalAlign=null;
}
public function getVerticalAlign()
{
return $this->_verticalAlign===null?'NotSet':$this->_verticalAlign;
}
public function setVerticalAlign($value)
{
$this->_verticalAlign=TPropertyValue::ensureEnum($value,array('NotSet','Top','Bottom','Middel'));
if($this->_verticalAlign==='NotSet')
$this->_verticalAlign=null;
}
public function getWrap()
{
return $this->_wrap===null?true:$this->_wrap;
}
public function setWrap($value)
{
$this->_wrap=TPropertyValue::ensureBoolean($value);
}
}

class TWebControl extends TControl
{
public function copyBaseAttributes(TWebControl $control)
{
$this->setAccessKey($control->getAccessKey());
$this->setToolTip($control->getToolTip());
$this->setTabIndex($control->getTabIndex());
if(!$control->getEnabled())
$this->setEnabled(false);
if($control->getHasAttributes())
$this->getAttributes()->copyFrom($control->getAttributes());
}
public function getAccessKey()
{
return $this->getViewState('AccessKey','');
}
public function setAccessKey($value)
{
if(strlen($value)>1)
throw new TInvalidDataValueException('webcontrol_accesskey_invalid',get_class($this),$value);
$this->setViewState('AccessKey',$value,'');
}
public function getBackColor()
{
if($style=$this->getViewState('Style',null))
return $style->getBackColor();
else
return '';
}
public function setBackColor($value)
{
$this->getStyle()->setBackColor($value);
}
public function getBorderColor()
{
if($style=$this->getViewState('Style',null))
return $style->getBorderColor();
else
return '';
}
public function setBorderColor($value)
{
$this->getStyle()->setBorderColor($value);
}
public function getBorderStyle()
{
if($style=$this->getViewState('Style',null))
return $style->getBorderStyle();
else
return '';
}
public function setBorderStyle($value)
{
$this->getStyle()->setBorderStyle($value);
}
public function getBorderWidth()
{
if($style=$this->getViewState('Style',null))
return $style->getBorderWidth();
else
return '';
}
public function setBorderWidth($value)
{
$this->getStyle()->setBorderWidth($value);
}
public function getFont()
{
return $this->getStyle()->getFont();
}
public function getForeColor()
{
if($style=$this->getViewState('Style',null))
return $style->getForeColor();
else
return '';
}
public function setForeColor($value)
{
$this->getStyle()->setForeColor($value);
}
public function getHeight()
{
if($style=$this->getViewState('Style',null))
return $style->getHeight();
else
return '';
}
public function setCssClass($value)
{
$this->getStyle()->setCssClass($value);
}
public function getCssClass()
{
if($style=$this->getViewState('Style',null))
return $style->getCssClass();
else
return '';
}
public function setHeight($value)
{
$this->getStyle()->setHeight($value);
}
public function getHasStyle()
{
return $this->getViewState('Style',null)!==null;
}
protected function createStyle()
{
return new TStyle;
}
public function getStyle()
{
if($style=$this->getViewState('Style',null))
return $style;
else
{
$style=$this->createStyle();
$this->setViewState('Style',$style,null);
return $style;
}
}
public function setStyle($value)
{
if(is_string($value))
$this->getStyle()->setCustomStyle($value);
else
throw new TInvalidDataValueException('webcontrol_style_invalid',get_class($this));
}
public function getTabIndex()
{
return $this->getViewState('TabIndex',0);
}
public function setTabIndex($value)
{
$this->setViewState('TabIndex',TPropertyValue::ensureInteger($value),0);
}
protected function getTagName()
{
return 'span';
}
public function getToolTip()
{
return $this->getViewState('ToolTip','');
}
public function setToolTip($value)
{
$this->setViewState('ToolTip',$value,'');
}
public function getWidth()
{
if($style=$this->getViewState('Style',null))
return $style->getWidth();
else
return '';
}
public function setWidth($value)
{
$this->getStyle()->setWidth($value);
}
protected function addAttributesToRender($writer)
{
if($this->getID()!=='')
$writer->addAttribute('id',$this->getClientID());
if(($accessKey=$this->getAccessKey())!=='')
$writer->addAttribute('accesskey',$accessKey);
if(!$this->getEnabled())
$writer->addAttribute('disabled','disabled');
if(($tabIndex=$this->getTabIndex())>0)
$writer->addAttribute('tabindex',"$tabIndex");
if(($toolTip=$this->getToolTip())!=='')
$writer->addAttribute('title',$toolTip);
if($style=$this->getViewState('Style',null))
$style->addAttributesToRender($writer);
if($this->getHasAttributes())
{
foreach($this->getAttributes() as $name=>$value)
$writer->addAttribute($name,$value);
}
}
protected function render($writer)
{
$this->renderBeginTag($writer);
$this->renderContents($writer);
$this->renderEndTag($writer);
}
public function renderBeginTag($writer)
{
$this->addAttributesToRender($writer);
$writer->renderBeginTag($this->getTagName());
}
protected function renderContents($writer)
{
parent::renderChildren($writer);
}
public function renderEndTag($writer)
{
$writer->renderEndTag();
}
}

class TContent extends TControl implements INamingContainer
{
public function createdOnTemplate($parent)
{
if(($id=$this->getID())==='')
throw new TConfigurationException('content_id_required');
$this->getTemplateControl()->registerContent($this);
}
}

class TContentPlaceHolder extends TControl
{
public function createdOnTemplate($parent)
{
if(($id=$this->getID())==='')
throw new TConfigurationException('contentplaceholder_id_required');
$loc=$parent->getHasControls()?$parent->getControls()->getCount():0;
$this->getTemplateControl()->registerContentPlaceHolder($id,$parent,$loc);
$parent->unregisterObject($id);
}
}

class TExpression extends TControl
{
private $_e='';
public function getExpression()
{
return $this->_e;
}
public function setExpression($value)
{
$this->_e=$value;
}
protected function render($writer)
{
if($this->_e!=='')
$writer->write($this->evaluateExpression($this->_e));
}
}

class TStatements extends TControl
{
private $_s='';
public function getStatements()
{
return $this->_s;
}
public function setStatements($value)
{
$this->_s=$value;
}
protected function render($writer)
{
if($this->_s!=='')
$writer->write($this->evaluateStatements($this->_s));
}
}

class THead extends TControl
{
private $_metaTags=array();
public function onInit($param)
{
parent::onInit($param);
$this->getPage()->setHead($this);
}
public function getTitle()
{
return $this->getViewState('Title','');
}
public function setTitle($value)
{
$this->setViewState('Title',$value,'');
}
public function registerMetaTag($key,$metaTag)
{
$this->_metaTags[$key]=$metaTag;
}
public function isMetaTagRegistered($key)
{
return isset($this->_metaTags[$key]);
}
public function render($writer)
{
$page=$this->getPage();
if(($title=$page->getTitle())==='')
$title=$this->getTitle();
$writer->write("<head>\n<title>".THttpUtility::htmlEncode($title)."</title>\n");
foreach($this->_metaTags as $metaTag)
{
$metaTag->render($writer);
$writer->writeLine();
}
$cs=$page->getClientScript();
$cs->renderStyleSheetFiles($writer);
$cs->renderStyleSheets($writer);
$cs->renderScriptFiles($writer);
parent::render($writer);
$writer->write("</head>\n");
}
}
class TMetaTag extends TComponent
{
private $_id='';
private $_httpEquiv='';
private $_name='';
private $_content='';
private $_scheme='';
public function getID()
{
return $this->_id;
}
public function setID($value)
{
$this->_id=$value;
}
public function getHttpEquiv()
{
return $this->_httpEquiv;
}
public function setHttpEquiv($value)
{
$this->_httpEquiv=$value;
}
public function getName()
{
return $this->_name;
}
public function setName($value)
{
$this->_name=$value;
}
public function getContent()
{
return $this->_content;
}
public function setContent($value)
{
$this->_content=$value;
}
public function getScheme()
{
return $this->_scheme;
}
public function setScheme($value)
{
$this->_scheme=$value;
}
public function render($writer)
{
if($this->_id!=='')
$writer->addAttribute('id',$this->_id);
if($this->_name!=='')
$writer->addAttribute('name',$this->_name);
if($this->_httpEquiv!=='')
$writer->addAttribute('http-equiv',$this->_name);
if($this->_scheme!=='')
$writer->addAttribute('scheme',$this->_name);
$writer->addAttribute('content',$this->_name);
$writer->renderBeginTag('meta');
$writer->renderEndTag();
}
}
?>