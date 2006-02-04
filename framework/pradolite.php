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
if(isset($this->_d[$index]))
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
if(isset($this->_d[$index]))
{
$item=$this->_d[$index];
if($index===$this->_c-1)
unset($this->_d[$index]);
else
array_splice($this->_d,$index,1);
$this->_c--;
return $item;
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
return isset($this->_d[$offset]);
}
public function offsetGet($offset)
{
if(isset($this->_d[$offset]))
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
public function __construct(&$data)
{
$this->_d=&$data;
$this->_i=0;
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
return isset($this->_d[$this->_i]);
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
if(isset($this->_d[$key]))
$this->remove($key);
$this->_d[$key]=$value;
}
public function remove($key)
{
if(isset($this->_d[$key]))
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
return isset($this->_d[$key]);
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
do
{
$this->_key=next($this->_keys);
}
while(!isset($this->_d[$this->_key]) && $this->_key!==false);
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

class TPagedDataSource extends TComponent implements IteratorAggregate
{
private $_dataSource=null;
private $_pageSize=10;
private $_currentPageIndex=0;
private $_allowPaging=false;
private $_allowCustomPaging=false;
private $_virtualCount=0;
public function getDataSource()
{
return $this->_dataSource;
}
public function setDataSource($value)
{
if(!($value instanceof TMap) && !($value instanceof TList))
{
if(is_array($value))
$value=new TMap($value);
else if($value instanceof Traversable)
$value=new TList($value);
else if($value!==null)
throw new TInvalidDataTypeException('pageddatasource_datasource_invalid');
}
$this->_dataSource=$value;
}
public function getPageSize()
{
return $this->_pageSize;
}
public function setPageSize($value)
{
if(($value=TPropertyValue::ensureInteger($value))>0)
$this->_pageSize=$value;
else
throw new TInvalidDataValueException('pageddatasource_pagesize_invalid');
}
public function getCurrentPageIndex()
{
return $this->_currentPageIndex;
}
public function setCurrentPageIndex($value)
{
if(($value=TPropertyValue::ensureInteger($value))<0)
$value=0;
$this->_currentPageIndex=$value;
}
public function getAllowPaging()
{
return $this->_allowPaging;
}
public function setAllowPaging($value)
{
$this->_allowPaging=TPropertyValue::ensureBoolean($value);
}
public function getAllowCustomPaging()
{
return $this->_allowCustomPaging;
}
public function setAllowCustomPaging($value)
{
$this->_allowCustomPaging=TPropertyValue::ensureBoolean($value);
}
public function getVirtualCount()
{
return $this->_virtualCount;
}
public function setVirtualCount($value)
{
if(($value=TPropertyValue::ensureInteger($value))>=0)
$this->_virtualCount=$value;
else
throw new TInvalidDataValueException('pageddatasource_virtualcount_invalid');
}
public function getCount()
{
if($this->_dataSource===null)
return 0;
if(!$this->_allowPaging)
return $this->getDataSourceCount();
if(!$this->_allowCustomPaging && $this->getIsLastPage())
return $this->getDataSourceCount()-$this->getFirstIndexInPage();
return $this->_pageSize;
}
public function getPageCount()
{
if($this->_dataSource===null)
return 0;
$count=$this->getDataSourceCount();
if(!$this->_allowPaging || $count<=0)
return 1;
return (int)(($count+$this->_pageSize-1)/$this->_pageSize);
}
public function getIsFirstPage()
{
if($this->_allowPaging)
return $this->_currentPageIndex===0;
else
return true;
}
public function getIsLastPage()
{
if($this->_allowPaging)
return $this->_currentPageIndex===$this->getPageCount()-1;
else
return true;
}
public function getFirstIndexInPage()
{
if($this->_dataSource!==null && $this->_allowPaging && !$this->_allowCustomPaging)
return $this->_currentPageIndex*$this->_pageSize;
else
return 0;
}
public function getDataSourceCount()
{
if($this->_dataSource===null)
return 0;
else if($this->_allowCustomPaging)
return $this->_virtualCount;
else
return $this->_dataSource->getCount();
}
public function getIterator()
{
if($this->_dataSource instanceof TList)
return new TPagedListIterator($this->_dataSource,$this->getFirstIndexInPage(),$this->getCount());
else if($this->_dataSource instanceof TMap)
return new TPagedMapIterator($this->_dataSource,$this->getFirstIndexInPage(),$this->getCount());
else
return null;
}
}
class TPagedListIterator implements Iterator
{
private $_list;
private $_startIndex;
private $_count;
private $_index;
public function __construct(TList $list,$startIndex,$count)
{
$this->_list=$list;
$this->_index=0;
$this->_startIndex=$startIndex;
if($startIndex+$count>$list->getCount())
$this->_count=$list->getCount()-$startIndex;
else
$this->_count=$count;
}
public function rewind()
{
$this->_index=0;
}
public function key()
{
return $this->_index;
}
public function current()
{
return $this->_list->itemAt($this->_startIndex+$this->_index);
}
public function next()
{
$this->_index++;
}
public function valid()
{
return $this->_index<$this->_count;
}
}
class TPagedMapIterator implements Iterator
{
private $_map;
private $_startIndex;
private $_count;
private $_index;
private $_iterator;
public function __construct(TMap $map,$startIndex,$count)
{
$this->_map=$map;
$this->_index=0;
$this->_startIndex=$startIndex;
if($startIndex+$count>$map->getCount())
$this->_count=$map->getCount()-$startIndex;
else
$this->_count=$count;
$this->_iterator=$map->getIterator();
}
public function rewind()
{
$this->_iterator->rewind();
for($i=0;$i<$this->_startIndex;++$i)
$this->_iterator->next();
$this->_index=0;
}
public function key()
{
return $this->_iterator->key();
}
public function current()
{
return $this->_iterator->current();
}
public function next()
{
$this->_index++;
$this->_iterator->next();
}
public function valid()
{
return $this->_index<$this->_count;
}
}

class TDummyDataSource extends TComponent implements IteratorAggregate
{
private $_count;
public function __construct($count)
{
$this->_count=$count;
}
public function getCount()
{
return $this->_count;
}
public function getIterator()
{
return new TDummyDataSourceIterator($this->_count);
}
}
class TDummyDataSourceIterator implements Iterator
{
private $_index;
private $_count;
public function __construct($count)
{
$this->_count=$count;
$this->_index=0;
}
public function rewind()
{
$this->_index=0;
}
public function key()
{
return $this->_index;
}
public function current()
{
return null;
}
public function next()
{
$this->_index++;
}
public function valid()
{
return $this->_index<$this->_count;
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
if(!class_exists($className,false))
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
'BeginRequest',
'Authentication',
'PostAuthentication',
'Authorization',
'PostAuthorization',
'LoadState',
'PostLoadState',
'PreRunService',
'RunService',
'PostRunService',
'SaveState',
'PostSaveState',
'EndRequest'
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
if(($this->_basePath=realpath($basePath))===false || !is_dir($this->_basePath))
throw new TConfigurationException('application_basepath_invalid',$basePath);
$configFile=$this->_basePath.'/'.self::CONFIG_FILE;
$this->_configFile=is_file($configFile) ? $configFile : null;
$this->_runtimePath=$this->_basePath.'/'.self::RUNTIME_PATH;
if(!is_dir($this->_runtimePath) || !is_writable($this->_runtimePath))
throw new TConfigurationException('application_runtimepath_invalid',$this->_runtimePath);
$this->_cacheFile=$cacheConfig ? $this->_runtimePath.'/'.self::CONFIGCACHE_FILE : null;
$this->_uniqueID=md5($this->_basePath);
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
$method='on'.self::$_steps[$this->_step];

$this->$method($this);
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
public function onBeginRequest($param)
{
$this->raiseEvent('OnBeginRequest',$this,$param);
}
public function onAuthentication($param)
{
$this->raiseEvent('OnAuthentication',$this,$param);
}
public function onPostAuthentication($param)
{
$this->raiseEvent('OnPostAuthentication',$this,$param);
}
public function onAuthorization($param)
{
$this->raiseEvent('OnAuthorization',$this,$param);
}
public function onPostAuthorization($param)
{
$this->raiseEvent('OnPostAuthorization',$this,$param);
}
public function onLoadState($param)
{
$this->loadGlobals();
$this->raiseEvent('OnLoadState',$this,$param);
}
public function onPostLoadState($param)
{
$this->raiseEvent('OnPostLoadState',$this,$param);
}
public function onPreRunService($param)
{
$this->raiseEvent('OnPreRunService',$this,$param);
}
public function onRunService($param)
{
$this->raiseEvent('OnRunService',$this,$param);
if($this->_service)
$this->_service->run();
}
public function onPostRunService($param)
{
$this->raiseEvent('OnPostRunService',$this,$param);
}
public function onSaveState($param)
{
$this->raiseEvent('OnSaveState',$this,$param);
$this->saveGlobals();
}
public function onPostSaveState($param)
{
$this->raiseEvent('OnPostSaveState',$this,$param);
}
public function onEndRequest($param)
{
$this->raiseEvent('OnEndRequest',$this,$param);
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
if(($templatePath=Prado::getPathOfNamespace($this->_templatePath))!==null && is_dir($templatePath))
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
$url=$this->getApplicationPath();
$url.='?'.$serviceID.'=';
if(!empty($serviceParam))
$url.=$serviceParam;
$amp=$encodeAmpersand?'&amp;':'&';
if(is_array($getItems) || $getItems instanceof Traversable)
{
foreach($getItems as $name=>$value)
$url.=$amp.urlencode($name).'='.urlencode($value);
}
if(defined('SID') && SID != '')
$url.=$amp.SID;
return $url;
}
protected function resolveRequest()
{

$this->_requestResolved=true;
foreach($this->_services as $id)
{
if(isset($_GET[$id]))
{
$this->setServiceID($id);
$this->setServiceParameter($_GET[$id]);
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
$header = $this->getContentTypeHeader();
$this->appendHeader($header);
if($this->_bufferOutput)
ob_flush();

}
protected function getContentTypeHeader()
{
$app = $this->getApplication()->getGlobalization();
$charset = $this->getCharset();
if(empty($charset))
$charset = !is_null($app) ? $app->getCharset() : 'UTF-8';
$type = $this->getContentType();
return "Content-Type: $type; charset=$charset";
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
public function sendContentTypeHeader($type=null)
{
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
$appUrl=dirname($this->getRequest()->getApplicationPath());
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
$this->_baseUrl=dirname($application->getRequest()->getApplicationPath()).'/'.self::DEFAULT_BASEPATH;
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
$this->_baseUrl=$value;
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

class TPlaceHolder extends TControl
{
}

class TLiteral extends TControl
{
public function getText()
{
return $this->getViewState('Text','');
}
public function setText($value)
{
$this->setViewState('Text',$value,'');
}
public function getEncode()
{
return $this->getViewState('Encode',false);
}
public function setEncode($value)
{
$this->setViewState('Encode',TPropertyValue::ensureBoolean($value),false);
}
protected function render($writer)
{
if(($text=$this->getText())!=='')
{
if($this->getEncode())
$writer->write(THttpUtility::htmlEncode($text));
else
$writer->write($text);
}
}
}

class TLabel extends TWebControl
{
protected function getTagName()
{
return ($this->getForControl()==='')?'span':'label';
}
protected function addAttributesToRender($writer)
{
if(($aid=$this->getForControl())!=='')
{
if($control=$this->findControl($aid))
$writer->addAttribute('for',$control->getClientID());
else
throw new TInvalidDataValueException('label_associatedcontrol_invalid',$aid);
}
parent::addAttributesToRender($writer);
}
protected function renderContents($writer)
{
if(($text=$this->getText())==='')
parent::renderContents($writer);
else
$writer->write($text);
}
public function getText()
{
return $this->getViewState('Text','');
}
public function setText($value)
{
$this->setViewState('Text',$value,'');
}
public function getForControl()
{
return $this->getViewState('ForControl','');
}
public function setForControl($value)
{
$this->setViewState('ForControl',$value,'');
}
}

class TImage extends TWebControl
{
protected function getTagName()
{
return 'img';
}
protected function addAttributesToRender($writer)
{
$writer->addAttribute('src',$this->getImageUrl());
$writer->addAttribute('alt',$this->getAlternateText());
if(($desc=$this->getDescriptionUrl())!=='')
$writer->addAttribute('longdesc',$desc);
if(($align=$this->getImageAlign())!=='')
$writer->addAttribute('align',$align);
if(($width=$this->getBorderWidth())==='')
$writer->addStyleAttribute('border-width','0px');
parent::addAttributesToRender($writer);
}
protected function renderContents($writer)
{
}
public function getAlternateText()
{
return $this->getViewState('AlternateText','');
}
public function setAlternateText($value)
{
$this->setViewState('AlternateText',$value,'');
}
public function getImageAlign()
{
return $this->getViewState('ImageAlign','');
}
public function setImageAlign($value)
{
$this->setViewState('ImageAlign',$value,'');
}
public function getImageUrl()
{
return $this->getViewState('ImageUrl','');
}
public function setImageUrl($value)
{
$this->setViewState('ImageUrl',$value,'');
}
public function getDescriptionUrl()
{
return $this->getViewState('DescriptionUrl','');
}
public function setDescriptionUrl($value)
{
$this->setViewState('DescriptionUrl',$value,'');
}
}

class TImageButton extends TImage implements IPostBackDataHandler, IPostBackEventHandler
{
private $_x=0;
private $_y=0;
protected function getTagName()
{
return 'input';
}
protected function addAttributesToRender($writer)
{
$page=$this->getPage();
$page->ensureRenderInForm($this);
$writer->addAttribute('type','image');
if(($uniqueID=$this->getUniqueID())!=='')
$writer->addAttribute('name',$uniqueID);
if($this->getEnabled(true))
{
if($this->canCauseValidation())
{
$writer->addAttribute('id',$this->getClientID());
$this->getPage()->getClientScript()->registerPostBackControl($this);
}
}
else if($this->getEnabled()) 			$writer->addAttribute('disabled','disabled');
parent::addAttributesToRender($writer);
}
protected function canCauseValidation()
{
if($this->getCausesValidation())
{
$group=$this->getValidationGroup();
return $this->getPage()->getValidators($group)->getCount()>0;
}
else
return false;
}
public function getPostBackOptions()
{
$options['CausesValidation'] = $this->getCausesValidation();
$options['ValidationGroup'] = $this->getValidationGroup();
return $options;
}
public function loadPostData($key,$values)
{
$uid=$this->getUniqueID();
if(isset($values["{$uid}_x"]) && isset($values["{$uid}_y"]))
{
$this->_x=intval($values["{$uid}_x"]);
$this->_y=intval($values["{$uid}_y"]);
$this->getPage()->setPostBackEventTarget($this);
}
return false;
}
public function raisePostDataChangedEvent()
{
}
public function onClick($param)
{
$this->raiseEvent('OnClick',$this,$param);
}
public function onCommand($param)
{
$this->raiseEvent('OnCommand',$this,$param);
$this->raiseBubbleEvent($this,$param);
}
public function raisePostBackEvent($param)
{
if($this->getCausesValidation())
$this->getPage()->validate($this->getValidationGroup());
$this->onClick(new TImageClickEventParameter($this->_x,$this->_y));
$this->onCommand(new TCommandEventParameter($this->getCommandName(),$this->getCommandParameter()));
}
public function getCausesValidation()
{
return $this->getViewState('CausesValidation',true);
}
public function setCausesValidation($value)
{
$this->setViewState('CausesValidation',TPropertyValue::ensureBoolean($value),true);
}
public function getCommandName()
{
return $this->getViewState('CommandName','');
}
public function setCommandName($value)
{
$this->setViewState('CommandName',$value,'');
}
public function getCommandParameter()
{
return $this->getViewState('CommandParameter','');
}
public function setCommandParameter($value)
{
$this->setViewState('CommandParameter',$value,'');
}
public function getValidationGroup()
{
return $this->getViewState('ValidationGroup','');
}
public function setValidationGroup($value)
{
$this->setViewState('ValidationGroup',$value,'');
}
public function getText()
{
return $this->getAlternateText();
}
public function setText($value)
{
$this->setAlternateText($value);
}
public function onPreRender($param)
{
parent::onPreRender($param);
$this->getPage()->registerRequiresPostData($this);
}
protected function renderContents($writer)
{
}
}
class TImageClickEventParameter extends TEventParameter
{
public $_x=0;
public $_y=0;
public function __construct($x,$y)
{
$this->_x=$x;
$this->_y=$y;
}
public function getX()
{
return $this->_x;
}
public function setX($value)
{
$this->_x=TPropertyValue::ensureInteger($value);
}
public function getY()
{
return $this->_y;
}
public function setY($value)
{
$this->_y=TPropertyValue::ensureInteger($value);
}
}

class TButton extends TWebControl implements IPostBackEventHandler
{
protected function getTagName()
{
return 'input';
}
protected function addAttributesToRender($writer)
{
$page=$this->getPage();
$page->ensureRenderInForm($this);
$writer->addAttribute('type','submit');
if(($uniqueID=$this->getUniqueID())!=='')
$writer->addAttribute('name',$uniqueID);
$writer->addAttribute('value',$this->getText());
if($this->getEnabled(true))
{
if($this->canCauseValidation())
{
$writer->addAttribute('id',$this->getClientID());
$this->getPage()->getClientScript()->registerPostBackControl($this);
}
}
else if($this->getEnabled()) 			$writer->addAttribute('disabled','disabled');
parent::addAttributesToRender($writer);
}
protected function canCauseValidation()
{
if($this->getCausesValidation())
{
$group=$this->getValidationGroup();
return $this->getPage()->getValidators($group)->getCount()>0;
}
else
return false;
}
public function getPostBackOptions()
{
$options['CausesValidation'] = $this->getCausesValidation();
$options['ValidationGroup'] = $this->getValidationGroup();
return $options;
}
protected function renderContents($writer)
{
}
public function onClick($param)
{
$this->raiseEvent('OnClick',$this,$param);
}
public function onCommand($param)
{
$this->raiseEvent('OnCommand',$this,$param);
$this->raiseBubbleEvent($this,$param);
}
public function raisePostBackEvent($param)
{
if($this->getCausesValidation())
$this->getPage()->validate($this->getValidationGroup());
$this->onClick(null);
$this->onCommand(new TCommandEventParameter($this->getCommandName(),$this->getCommandParameter()));
}
public function getText()
{
return $this->getViewState('Text','');
}
public function setText($value)
{
$this->setViewState('Text',$value,'');
}
public function getCausesValidation()
{
return $this->getViewState('CausesValidation',true);
}
public function setCausesValidation($value)
{
$this->setViewState('CausesValidation',TPropertyValue::ensureBoolean($value),true);
}
public function getCommandName()
{
return $this->getViewState('CommandName','');
}
public function setCommandName($value)
{
$this->setViewState('CommandName',$value,'');
}
public function getCommandParameter()
{
return $this->getViewState('CommandParameter','');
}
public function setCommandParameter($value)
{
$this->setViewState('CommandParameter',$value,'');
}
public function getValidationGroup()
{
return $this->getViewState('ValidationGroup','');
}
public function setValidationGroup($value)
{
$this->setViewState('ValidationGroup',$value,'');
}
}

class TCheckBox extends TWebControl implements IPostBackDataHandler, IValidatable
{
protected function getTagName()
{
return 'input';
}
public function loadPostData($key,$values)
{
$checked=$this->getChecked();
if(isset($values[$key])!=$checked)
{
$this->setChecked(!$checked);
return true;
}
else
return false;
}
public function raisePostDataChangedEvent()
{
if($this->getAutoPostBack() && $this->getCausesValidation())
$this->getPage()->validate($this->getValidationGroup());
$this->onCheckedChanged(null);
}
public function onCheckedChanged($param)
{
$this->raiseEvent('OnCheckedChanged',$this,$param);
}
public function onPreRender($param)
{
parent::onPreRender($param);
if($this->getEnabled(true))
$this->getPage()->registerRequiresPostData($this);
}
public function getValidationPropertyValue()
{
return $this->getChecked();
}
public function getText()
{
return $this->getViewState('Text','');
}
public function setText($value)
{
$this->setViewState('Text',$value,'');
}
public function getTextAlign()
{
return $this->getViewState('TextAlign','Right');
}
public function setTextAlign($value)
{
$this->setViewState('TextAlign',TPropertyValue::ensureEnum($value,array('Left','Right')),'Right');
}
public function getChecked()
{
return $this->getViewState('Checked',false);
}
public function setChecked($value)
{
$this->setViewState('Checked',TPropertyValue::ensureBoolean($value),false);
}
public function getAutoPostBack()
{
return $this->getViewState('AutoPostBack',false);
}
public function setAutoPostBack($value)
{
$this->setViewState('AutoPostBack',TPropertyValue::ensureBoolean($value),false);
}
public function getCausesValidation()
{
return $this->getViewState('CausesValidation',true);
}
public function setCausesValidation($value)
{
$this->setViewState('CausesValidation',TPropertyValue::ensureBoolean($value),true);
}
public function getValidationGroup()
{
return $this->getViewState('ValidationGroup','');
}
public function setValidationGroup($value)
{
$this->setViewState('ValidationGroup',$value,'');
}
protected function render($writer)
{
$this->addAttributesToRender($writer);
$this->getPage()->ensureRenderInForm($this);
$needSpan=false;
if($this->getHasStyle())
{
$this->getStyle()->addAttributesToRender($writer);
$needSpan=true;
}
if(!$this->getEnabled(true))
{
$writer->addAttribute('disabled','disabled');
$needSpan=true;
}
if(($tooltip=$this->getToolTip())!=='')
{
$writer->addAttribute('title',$tooltip);
$needSpan=true;
}
if($this->getHasAttributes())
{
$attributes=$this->getAttributes();
$value=$attributes->remove('value');
if($attributes->getCount())
{
$writer->addAttributes($attributes);
$needSpan=true;
}
if($value!==null)
$attributes->add('value',$value);
}
if($needSpan)
$writer->renderBeginTag('span');
$clientID=$this->getClientID();
if(($text=$this->getText())!=='')
{
if($this->getTextAlign()==='Left')
{
$this->renderLabel($writer,$clientID,$text);
$this->renderInputTag($writer,$clientID);
}
else
{
$this->renderInputTag($writer,$clientID);
$this->renderLabel($writer,$clientID,$text);
}
}
else
$this->renderInputTag($writer,$clientID);
if($needSpan)
$writer->renderEndTag();
}
public function getLabelAttributes()
{
if($attributes=$this->getViewState('LabelAttributes',null))
return $attributes;
else
{
$attributes=new TAttributeCollection;
$this->setViewState('LabelAttributes',$attributes,null);
return $attributes;
}
}
public function getInputAttributes()
{
if($attributes=$this->getViewState('InputAttributes',null))
return $attributes;
else
{
$attributes=new TAttributeCollection;
$this->setViewState('InputAttributes',$attributes,null);
return $attributes;
}
}
protected function getValueAttribute()
{
$attributes=$this->getViewState('InputAttributes',null);
if($attributes && $attributes->contains('value'))
$value=$attributes->itemAt('value');
else if($this->hasAttribute('value'))
$value=$this->getAttribute('value');
else
$value='';
return $value===''?$this->getUniqueID():$value;
}
protected function renderLabel($writer,$clientID,$text)
{
$writer->addAttribute('for',$clientID);
if($attributes=$this->getViewState('LabelAttributes',null))
$writer->addAttributes($attributes);
$writer->renderBeginTag('label');
$writer->write($text);
$writer->renderEndTag();
}
protected function renderInputTag($writer,$clientID)
{
if($clientID!=='')
$writer->addAttribute('id',$clientID);
$writer->addAttribute('type','checkbox');
$writer->addAttribute('value',$this->getValueAttribute());
if(($uniqueID=$this->getUniqueID())!=='')
$writer->addAttribute('name',$uniqueID);
if($this->getChecked())
$writer->addAttribute('checked','checked');
if(!$this->getEnabled(true))
$writer->addAttribute('disabled','disabled');
$page=$this->getPage();
if($this->getEnabled(true) && $this->getAutoPostBack() && $page->getClientSupportsJavaScript())
$page->getClientScript()->registerPostBackControl($this);
if(($accesskey=$this->getAccessKey())!=='')
$writer->addAttribute('accesskey',$accesskey);
if(($tabindex=$this->getTabIndex())>0)
$writer->addAttribute('tabindex',"$tabindex");
if($attributes=$this->getViewState('InputAttributes',null))
$writer->addAttributes($attributes);
$writer->renderBeginTag('input');
$writer->renderEndTag();
}
public function getPostBackOptions()
{
$options['ValidationGroup'] = $this->getValidationGroup();
$options['CausesValidation'] = $this->getCausesValidation();
$options['EventTarget'] = $this->getUniqueID();
return $options;
}
}

class TRadioButton extends TCheckBox
{
private $_uniqueGroupName=null;
public function loadPostData($key,$values)
{
$uniqueGroupName=$this->getUniqueGroupName();
$value=isset($values[$uniqueGroupName])?$values[$uniqueGroupName]:null;
if($value!==null && $value===$this->getValueAttribute())
{
if(!$this->getChecked())
{
$this->setChecked(true);
return true;
}
else
return false;
}
else if($this->getChecked())
$this->setChecked(false);
return false;
}
public function getGroupName()
{
return $this->getViewState('GroupName','');
}
public function setGroupName($value)
{
$this->setViewState('GroupName',$value,'');
}
private function getUniqueGroupName()
{
if($this->_uniqueGroupName===null)
{
$groupName=$this->getGroupName();
$uniqueID=$this->getUniqueID();
if($uniqueID!=='')
{
if(($pos=strrpos($uniqueID,TControl::ID_SEPARATOR))!==false)
{
if($groupName!=='')
$groupName=substr($uniqueID,0,$pos+1).$groupName;
else if($this->getNamingContainer() instanceof TRadioButtonList)
$groupName=substr($uniqueID,0,$pos);
}
if($groupName==='')
$groupName=$uniqueID;
}
$this->_uniqueGroupName=$groupName;
}
return $this->_uniqueGroupName;
}
protected function renderInputTag($writer,$clientID)
{
if($clientID!=='')
$writer->addAttribute('id',$clientID);
$writer->addAttribute('type','radio');
$writer->addAttribute('name',$this->getUniqueGroupName());
$writer->addAttribute('value',$this->getValueAttribute());
if($this->getChecked())
$writer->addAttribute('checked','checked');
if(!$this->getEnabled(true))
$writer->addAttribute('disabled','disabled');
$page=$this->getPage();
if($this->getEnabled(true) && $this->getAutoPostBack() && $page->getClientSupportsJavaScript())
$page->getClientScript()->registerPostBackControl($this);
if(($accesskey=$this->getAccessKey())!=='')
$writer->addAttribute('accesskey',$accesskey);
if(($tabindex=$this->getTabIndex())>0)
$writer->addAttribute('tabindex',"$tabindex");
if($attributes=$this->getViewState('InputAttributes',null))
$writer->addAttributes($attributes);
$writer->renderBeginTag('input');
$writer->renderEndTag();
}
}

class TTextBox extends TWebControl implements IPostBackDataHandler, IValidatable
{
const DEFAULT_ROWS=4;
const DEFAULT_COLUMNS=20;
private static $_autoCompleteTypes=array('BusinessCity','BusinessCountryRegion','BusinessFax','BusinessPhone','BusinessState','BusinessStreetAddress','BusinessUrl','BusinessZipCode','Cellular','Company','Department','Disabled','DisplayName','Email','FirstName','Gender','HomeCity','HomeCountryRegion','HomeFax','Homepage','HomePhone','HomeState','HomeStreetAddress','HomeZipCode','JobTitle','LastName','MiddleName','None','Notes','Office','Pager','Search');
private static $_safeTextParser=null;
private $_safeText;
protected function getTagName()
{
return ($this->getTextMode()==='MultiLine')?'textarea':'input';
}
protected function addAttributesToRender($writer)
{
$page=$this->getPage();
$page->ensureRenderInForm($this);
if(($uid=$this->getUniqueID())!=='')
$writer->addAttribute('name',$uid);
if(($textMode=$this->getTextMode())==='MultiLine')
{
if(($rows=$this->getRows())<=0)
$rows=self::DEFAULT_ROWS;
if(($cols=$this->getColumns())<=0)
$cols=self::DEFAULT_COLUMNS;
$writer->addAttribute('rows',"$rows");
$writer->addAttribute('cols',"$cols");
if(!$this->getWrap())
$writer->addAttribute('wrap','off');
}
else
{
if($textMode==='SingleLine')
{
$writer->addAttribute('type','text');
if(($text=$this->getText())!=='')
$writer->addAttribute('value',$text);
if(($act=$this->getAutoCompleteType())!=='None')
{
if($act==='Disabled')
$writer->addAttribute('autocomplete','off');
else if($act==='Search')
$writer->addAttribute('vcard_name','search');
else if($act==='HomeCountryRegion')
$writer->addAttribute('vcard_name','HomeCountry');
else if($act==='BusinessCountryRegion')
$writer->addAttribute('vcard_name','BusinessCountry');
else
{
if(($pos=strpos($act,'Business'))===0)
$act='Business'.'.'.substr($act,8);
else if(($pos=strpos($act,'Home'))===0)
$act='Home'.'.'.substr($act,4);
$writer->addAttribute('vcard_name','vCard.'.$act);
}
}
}
else
{
$writer->addAttribute('type','password');
}
if(($cols=$this->getColumns())>0)
$writer->addAttribute('size',"$cols");
if(($maxLength=$this->getMaxLength())>0)
$writer->addAttribute('maxlength',"$maxLength");
}
if($this->getReadOnly())
$writer->addAttribute('readonly','readonly');
if(!$this->getEnabled(true) && $this->getEnabled())  			$writer->addAttribute('disabled','disabled');
if($this->getEnabled(true) && $this->getAutoPostBack() && $page->getClientSupportsJavaScript())
{
$writer->addAttribute('id',$this->getClientID());
$this->getPage()->getClientScript()->registerPostBackControl($this);
}
parent::addAttributesToRender($writer);
}
public function getPostBackOptions()
{
$options['EventTarget'] = $this->getUniqueID();
$options['CausesValidation'] = $this->getCausesValidation();
$options['ValidationGroup'] = $this->getValidationGroup();
$options['TextMode'] = $this->getTextMode();
return $options;
}
public function loadPostData($key,$values)
{
$value=$values[$key];
if(!$this->getReadOnly() && $this->getText()!==$value)
{
$this->setText($value);
return true;
}
else
return false;
}
public function getValidationPropertyValue()
{
return $this->getText();
}
public function onTextChanged($param)
{
$this->raiseEvent('OnTextChanged',$this,$param);
}
public function raisePostDataChangedEvent()
{
if($this->getAutoPostBack() && $this->getCausesValidation())
$this->getPage()->validate($this->getValidationGroup());
$this->onTextChanged(null);
}
protected function renderContents($writer)
{
if($this->getTextMode()==='MultiLine')
$writer->write(THttpUtility::htmlEncode($this->getText()));
}
public function getAutoCompleteType()
{
return $this->getViewState('AutoCompleteType','None');
}
public function setAutoCompleteType($value)
{
$this->setViewState('AutoCompleteType',TPropertyValue::ensureEnum($value,self::$_autoCompleteTypes),'None');
}
public function getAutoPostBack()
{
return $this->getViewState('AutoPostBack',false);
}
public function setAutoPostBack($value)
{
$this->setViewState('AutoPostBack',TPropertyValue::ensureBoolean($value),false);
}
public function getCausesValidation()
{
return $this->getViewState('CausesValidation',true);
}
public function setCausesValidation($value)
{
$this->setViewState('CausesValidation',TPropertyValue::ensureBoolean($value),true);
}
public function getColumns()
{
return $this->getViewState('Columns',0);
}
public function setColumns($value)
{
$this->setViewState('Columns',TPropertyValue::ensureInteger($value),0);
}
public function getMaxLength()
{
return $this->getViewState('MaxLength',0);
}
public function setMaxLength($value)
{
$this->setViewState('MaxLength',TPropertyValue::ensureInteger($value),0);
}
public function getReadOnly()
{
return $this->getViewState('ReadOnly',false);
}
public function setReadOnly($value)
{
$this->setViewState('ReadOnly',TPropertyValue::ensureBoolean($value),false);
}
public function getRows()
{
return $this->getViewState('Rows',self::DEFAULT_ROWS);
}
public function setRows($value)
{
$this->setViewState('Rows',TPropertyValue::ensureInteger($value),self::DEFAULT_ROWS);
}
public function getText()
{
return $this->getViewState('Text','');
}
public function setText($value)
{
$this->setViewState('Text',$value,'');
$this->_safeText = null;
}
public function getSafeText()
{
if($this->_safeText===null)
$this->_safeText=$this->getSafeTextParser()->parse($this->getText());
return $this->_safeText;
}
protected function getSafeTextParser()
{
if(!self::$_safeTextParser)
self::$_safeTextParser=Prado::createComponent('System.3rdParty.SafeHtml.TSafeHtmlParser');
return self::$_safeTextParser;
}
public function getTextMode()
{
return $this->getViewState('TextMode','SingleLine');
}
public function setTextMode($value)
{
$this->setViewState('TextMode',TPropertyValue::ensureEnum($value,array('SingleLine','MultiLine','Password')),'SingleLine');
}
public function getValidationGroup()
{
return $this->getViewState('ValidationGroup','');
}
public function setValidationGroup($value)
{
$this->setViewState('ValidationGroup',$value,'');
}
public function getWrap()
{
return $this->getViewState('Wrap',true);
}
public function setWrap($value)
{
$this->setViewState('Wrap',TPropertyValue::ensureBoolean($value),true);
}
}

class TPanel extends TWebControl
{
private $_defaultButton='';
protected function getTagName()
{
return 'div';
}
protected function createStyle()
{
return new TPanelStyle;
}
protected function addAttributesToRender($writer)
{
parent::addAttributesToRender($writer);
if(($butt=$this->getDefaultButton())!=='')
{
if(($button=$this->findControl($butt))===null)
throw new TInvalidDataValueException('panel_defaultbutton_invalid',$butt);
else
{
$writer->addAttribute('id',$this->getClientID());
$this->getPage()->getClientScript()->registerDefaultButton($this, $button);
}
}
}
public function getWrap()
{
return $this->getStyle()->getWrap();
}
public function setWrap($value)
{
$this->getStyle()->setWrap($value);
}
public function getHorizontalAlign()
{
return $this->getStyle()->getHorizontalAlign();
}
public function setHorizontalAlign($value)
{
$this->getStyle()->setHorizontalAlign($value);
}
public function getBackImageUrl()
{
return $this->getStyle()->getBackImageUrl();
}
public function setBackImageUrl($value)
{
$this->getStyle()->setBackImageUrl($value);
}
public function getDirection()
{
return $this->getStyle()->getDirection();
}
public function setDirection($value)
{
$this->getStyle()->setDirection($value);
}
public function getDefaultButton()
{
return $this->_defaultButton;
}
public function setDefaultButton($value)
{
$this->_defaultButton=$value;
}
public function getGroupingText()
{
return $this->getViewState('GroupingText','');
}
public function setGroupingText($value)
{
$this->setViewState('GroupingText',$value,'');
}
public function getScrollBars()
{
return $this->getStyle()->getScrollBars();
}
public function setScrollBars($value)
{
$this->getStyle()->setScrollBars($value);
}
public function renderBeginTag($writer)
{
parent::renderBeginTag($writer);
if(($text=$this->getGroupingText())!=='')
{
$writer->renderBeginTag('fieldset');
$writer->renderBeginTag('legend');
$writer->write($text);
$writer->renderEndTag();
}
}
public function renderEndTag($writer)
{
if($this->getGroupingText()!=='')
$writer->renderEndTag();
parent::renderEndTag($writer);
}
}
class TPanelStyle extends TStyle
{
private $_backImageUrl='';
private $_direction='NotSet';
private $_horizontalAlign='NotSet';
private $_scrollBars='None';
private $_wrap=true;
public function addAttributesToRender($writer)
{
if(($url=trim($this->_backImageUrl))!=='')
$this->setStyleField('background-image','url('.$url.')');
switch($this->_scrollBars)
{
case 'Horizontal': $this->setStyleField('overflow-x','scroll'); break;
case 'Vertical': $this->setStyleField('overflow-y','scroll'); break;
case 'Both': $this->setStyleField('overflow','scroll'); break;
case 'Auto': $this->setStyleField('overflow','auto'); break;
}
if($this->_horizontalAlign!=='NotSet')
$this->setStyleField('text-align',strtolower($this->_horizontalAlign));
if(!$this->_wrap)
$this->setStyleField('white-space','nowrap');
if($this->_direction==='LeftToRight')
$this->setStyleField('direction','ltr');
else if($this->_direction==='RightToLeft')
$this->setStyleField('direction','rtl');
parent::addAttributesToRender($writer);
}
public function getBackImageUrl()
{
return $this->_backImageUrl;
}
public function setBackImageUrl($value)
{
$this->_backImageUrl=$value;
}
public function getDirection()
{
return $this->_direction;
}
public function setDirection($value)
{
$this->_direction=TPropertyValue::ensureEnum($value,array('NotSet','LeftToRight','RightToLeft'));
}
public function getWrap()
{
return $this->_wrap;
}
public function setWrap($value)
{
$this->_wrap=TPropertyValue::ensureBoolean($value);
}
public function getHorizontalAlign()
{
return $this->_horizontalAlign;
}
public function setHorizontalAlign($value)
{
$this->_horizontalAlign=TPropertyValue::ensureEnum($value,array('NotSet','Left','Right','Center','Justify'));
}
public function getScrollBars()
{
return $this->_scrollBars;
}
public function setScrollBars($value)
{
$this->_scrollBars=TPropertyValue::ensureEnum($value,array('None','Auto','Both','Horizontal','Vertical'));
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

class TFileUpload extends TWebControl implements IPostBackDataHandler, IValidatable
{
const MAX_FILE_SIZE=1048576;
private $_fileSize=0;
private $_fileName='';
private $_localName='';
private $_fileType='';
private $_errorCode=UPLOAD_ERR_NO_FILE;
protected function getTagName()
{
return 'input';
}
protected function addAttributesToRender($writer)
{
$this->getPage()->ensureRenderInForm($this);
parent::addAttributesToRender($writer);
$writer->addAttribute('type','file');
$writer->addAttribute('name',$this->getUniqueID());
}
public function onPreRender($param)
{
parent::onPreRender($param);
if(($form=$this->getPage()->getForm())!==null)
$form->setEnctype('multipart/form-data');
$this->getPage()->getClientScript()->registerHiddenField('MAX_FILE_SIZE',$this->getMaxFileSize());
if($this->getEnabled(true))
$this->getPage()->registerRequiresPostData($this);
}
public function getMaxFileSize()
{
return $this->getViewState('MaxFileSize',self::MAX_FILE_SIZE);
}
public function setMaxFileSize($size)
{
$this->setViewState('MaxFileSize',TPropertyValue::ensureInteger($size),self::MAX_FILE_SIZE);
}
public function getFileName()
{
return $this->_fileName;
}
public function getFileSize()
{
return $this->_fileSize;
}
public function getFileType()
{
return $this->_fileType;
}
public function getLocalName()
{
return $this->_localName;
}
public function getErrorCode()
{
return $this->_errorCode;
}
public function getHasFile()
{
return $this->_errorCode===UPLOAD_ERR_OK;
}
public function saveAs($fileName,$deleteTempFile=true)
{
if($this->_errorCode===UPLOAD_ERR_OK)
{
if($deleteTempFile)
move_uploaded_file($this->_localName,$fileName);
else if(is_uploaded_file($this->_localName))
file_put_contents($fileName,file_get_contents($this->_localName));
else
throw new TInvalidOperationException('fileupload_saveas_failed');
}
else
throw new TInvalidOperation('fileupload_saveas_forbidden');
}
public function loadPostData($key,$values)
{
if(isset($_FILES[$key]))
{
$this->_fileName=$_FILES[$key]['name'];
$this->_fileSize=$_FILES[$key]['size'];
$this->_fileType=$_FILES[$key]['type'];
$this->_errorCode=$_FILES[$key]['error'];
$this->_localName=$_FILES[$key]['tmp_name'];
return true;
}
else
return false;
}
public function raisePostDataChangedEvent()
{
$this->onFileUpload(null);
}
public function onFileUpload($param)
{
$this->raiseEvent('OnFileUpload',$this,$param);
}
public function getValidationPropertyValue()
{
return $this->getFileName();
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

class THiddenField extends TControl implements IPostBackDataHandler
{
protected function getTagName()
{
return 'input';
}
public function focus()
{
throw new TNotSupportedException('hiddenfield_focus_unsupported');
}
protected function render($writer)
{
$uniqueID=$this->getUniqueID();
$this->getPage()->ensureRenderInForm($this);
$writer->addAttribute('type','hidden');
if($uniqueID!=='')
$writer->addAttribute('name',$uniqueID);
if($this->getID()!=='')
$writer->addAttribute('id',$this->getClientID());
if(($value=$this->getValue())!=='')
$writer->addAttribute('value',$value);
$writer->renderBeginTag('input');
$writer->renderEndTag();
}
public function loadPostData($key,$values)
{
$value=$values[$key];
if($value===$this->getValue())
return false;
else
{
$this->setValue($value);
return true;
}
}
public function raisePostDataChangedEvent()
{
$this->onValueChanged(null);
}
public function onValueChanged($param)
{
$this->raiseEvent('OnValueChanged',$this,$param);
}
public function getValue()
{
return $this->getViewState('Value','');
}
public function setValue($value)
{
$this->setViewState('Value',$value,'');
}
public function getEnableTheming()
{
return false;
}
public function setEnableTheming($value)
{
throw new TNotSupportedException('hiddenfield_theming_unsupported');
}
public function setSkinID($value)
{
throw new TNotSupportedException('hiddenfield_skinid_unsupported');
}
}

class THyperLink extends TWebControl
{
protected function getTagName()
{
return 'a';
}
protected function addAttributesToRender($writer)
{
$isEnabled=$this->getEnabled(true);
if($this->getEnabled() && !$isEnabled)
$writer->addAttribute('disabled','disabled');
parent::addAttributesToRender($writer);
if(($url=$this->getNavigateUrl())!=='' && $isEnabled)
$writer->addAttribute('href',$url);
if(($target=$this->getTarget())!=='')
$writer->addAttribute('target',$target);
}
protected function renderContents($writer)
{
if(($imageUrl=$this->getImageUrl())==='')
{
if(($text=$this->getText())!=='')
$writer->write($text);
else
parent::renderContents($writer);
}
else
{
$image=Prado::createComponent('System.Web.UI.WebControls.TImage');
$image->setImageUrl($imageUrl);
if(($toolTip=$this->getToolTip())!=='')
$image->setToolTip($toolTip);
if(($text=$this->getText())!=='')
$image->setAlternateText($text);
$image->renderControl($writer);
}
}
public function getText()
{
return $this->getViewState('Text','');
}
public function setText($value)
{
$this->setViewState('Text',$value,'');
}
public function getImageUrl()
{
return $this->getViewState('ImageUrl','');
}
public function setImageUrl($value)
{
$this->setViewState('ImageUrl',$value,'');
}
public function getNavigateUrl()
{
return $this->getViewState('NavigateUrl','');
}
public function setNavigateUrl($value)
{
$this->setViewState('NavigateUrl',$value,'');
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

class TTableCell extends TWebControl
{
protected function getTagName()
{
return 'td';
}
protected function createStyle()
{
return new TTableItemStyle;
}
public function getHorizontalAlign()
{
if($this->getHasStyle())
return $this->getStyle()->getHorizontalAlign();
else
return 'NotSet';
}
public function setHorizontalAlign($value)
{
$this->getStyle()->setHorizontalAlign($value);
}
public function getVerticalAlign()
{
if($this->getHasStyle())
return $this->getStyle()->getVerticalAlign();
else
return 'NotSet';
}
public function setVerticalAlign($value)
{
$this->getStyle()->setVerticalAlign($value);
}
public function getColumnSpan()
{
return $this->getViewState('ColumnSpan', 0);
}
public function setColumnSpan($value)
{
$this->setViewState('ColumnSpan', TPropertyValue::ensureInteger($value), 0);
}
public function getRowSpan()
{
return $this->getViewState('RowSpan', 0);
}
public function setRowSpan($value)
{
$this->setViewState('RowSpan', TPropertyValue::ensureInteger($value), 0);
}
public function getWrap()
{
if($this->getHasStyle())
return $this->getStyle()->getWrap();
else
return true;
}
public function setWrap($value)
{
$this->getStyle()->setWrap($value);
}
public function getText()
{
return $this->getViewState('Text','');
}
public function setText($value)
{
$this->setViewState('Text',$value,'');
}
protected function addAttributesToRender($writer)
{
parent::addAttributesToRender($writer);
if(($colspan=$this->getColumnSpan())>0)
$writer->addAttribute('colspan',"$colspan");
if(($rowspan=$this->getRowSpan())>0)
$writer->addAttribute('rowspan',"$rowspan");
}
protected function renderContents($writer)
{
if(($text=$this->getText())==='')
parent::renderContents($writer);
else
$writer->write($text);
}
}

class TTableHeaderCell extends TTableCell
{
protected function getTagName()
{
return 'th';
}
protected function addAttributesToRender($writer)
{
parent::addAttributesToRender($writer);
if(($scope=$this->getScope())!=='NotSet')
$writer->addAttribute('scope',$scope==='Row'?'row':'col');
if(($text=$this->getAbbreviatedText())!=='')
$writer->addAttribute('abbr',$text);
if(($text=$this->getCategoryText())!=='')
$writer->addAttribute('axis',$text);
}
public function getScope()
{
return $this->getViewState('Scope','NotSet');
}
public function setScope($value)
{
$this->setViewState('Scope',TPropertyValue::ensureEnum($value,'NotSet','Row','Column'),'NotSet');
}
public function getAbbreviatedText()
{
return $this->getViewState('AbbreviatedText','');
}
public function setAbbreviatedText($value)
{
$this->setViewState('AbbreviatedText',$value,'');
}
public function getCategoryText()
{
return $this->getViewState('CategoryText','');
}
public function setCategoryText($value)
{
$this->setViewState('CategoryText',$value,'');
}
}

class TTableRow extends TWebControl
{
private $_cells=null;
protected function getTagName()
{
return 'tr';
}
public function addParsedObject($object)
{
if($object instanceof TTableCell)
$this->getCells()->add($object);
}
protected function createStyle()
{
return new TTableItemStyle;
}
public function getCells()
{
if(!$this->_cells)
$this->_cells=new TTableCellCollection($this);
return $this->_cells;
}
public function getHorizontalAlign()
{
if($this->getHasStyle())
return $this->getStyle()->getHorizontalAlign();
else
return 'NotSet';
}
public function setHorizontalAlign($value)
{
$this->getStyle()->setHorizontalAlign($value);
}
public function getVerticalAlign()
{
if($this->getHasStyle())
return $this->getStyle()->getVerticalAlign();
else
return 'NotSet';
}
public function setVerticalAlign($value)
{
$this->getStyle()->setVerticalAlign($value);
}
protected function renderContents($writer)
{
if($this->_cells)
{
$writer->writeLine();
foreach($this->_cells as $cell)
{
$cell->renderControl($writer);
$writer->writeLine();
}
}
}
}
class TTableCellCollection extends TList
{
private $_owner=null;
public function __construct($owner=null)
{
$this->_owner=$owner;
}
public function insertAt($index,$item)
{
if($item instanceof TTableCell)
{
parent::insertAt($index,$item);
if($this->_owner)
$this->_owner->getControls()->insertAt($index,$item);
}
else
throw new TInvalidDataTypeException('tablecellcollection_tablecell_required');
}
public function removeAt($index)
{
$item=parent::removeAt($index);
if($item instanceof TTableCell)
$this->_owner->getControls()->remove($item);
return $item;
}
}

class TTable extends TWebControl
{
private $_rows=null;
protected function getTagName()
{
return 'table';
}
public function addParsedObject($object)
{
if($object instanceof TTableRow)
$this->getRows()->add($object);
}
protected function createStyle()
{
return new TTableStyle;
}
protected function addAttributesToRender($writer)
{
parent::addAttributesToRender($writer);
$border=0;
if($this->getHasStyle())
{
if($this->getGridLines()!=='None')
{
if(($border=$this->getBorderWidth())==='')
$border=1;
else
$border=(int)$border;
}
}
$writer->addAttribute('border',"$border");
}
public function getRows()
{
if(!$this->_rows)
$this->_rows=new TTableRowCollection($this);
return $this->_rows;
}
public function getCaption()
{
return $this->getViewState('Caption','');
}
public function setCaption($value)
{
$this->setViewState('Caption',$value,'');
}
public function getCaptionAlign()
{
return $this->getViewState('CaptionAlign','NotSet');
}
public function setCaptionAlign($value)
{
$this->setViewState('CaptionAlign',TPropertyValue::ensureEnum($value,'NotSet','Top','Bottom','Left','Right'),'NotSet');
}
public function getCellSpacing()
{
if($this->getHasStyle())
return $this->getStyle()->getCellSpacing();
else
return -1;
}
public function setCellSpacing($value)
{
$this->getStyle()->setCellSpacing($value);
}
public function getCellPadding()
{
if($this->getHasStyle())
return $this->getStyle()->getCellPadding();
else
return -1;
}
public function setCellPadding($value)
{
$this->getStyle()->setCellPadding($value);
}
public function getHorizontalAlign()
{
if($this->getHasStyle())
return $this->getStyle()->getHorizontalAlign();
else
return 'NotSet';
}
public function setHorizontalAlign($value)
{
$this->getStyle()->setHorizontalAlign($value);
}
public function getGridLines()
{
if($this->getHasStyle())
return $this->getStyle()->getGridLines();
else
return 'None';
}
public function setGridLines($value)
{
$this->getStyle()->setGridLines($value);
}
public function getBackImageUrl()
{
if($this->getHasStyle())
return $this->getStyle()->getBackImageUrl();
else
return '';
}
public function setBackImageUrl($value)
{
$this->getStyle()->setBackImageUrl($value);
}
public function renderBeginTag($writer)
{
parent::renderBeginTag($writer);
if(($caption=$this->getCaption())!=='')
{
if(($align=$this->getCaptionAlign())!=='NotSet')
$writer->addAttribute('align',strtolower($align));
$writer->renderBeginTag('caption');
$writer->write($caption);
$writer->renderEndTag();
}
}
protected function renderContents($writer)
{
if($this->_rows)
{
$writer->writeLine();
foreach($this->_rows as $row)
{
$row->renderControl($writer);
$writer->writeLine();
}
}
}
}
class TTableRowCollection extends TList
{
private $_owner=null;
public function __construct($owner=null)
{
$this->_owner=$owner;
}
public function insertAt($index,$item)
{
if($item instanceof TTableRow)
{
parent::insertAt($index,$item);
if($this->_owner)
$this->_owner->getControls()->insertAt($index,$item);
}
else
throw new TInvalidDataTypeException('tablerowcollection_tablerow_required');
}
public function removeAt($index)
{
$item=parent::removeAt($index);
if($item instanceof TTableRow)
$this->_owner->getControls()->remove($item);
return $item;
}
}

interface IDataSource
{
public function getView($viewName);
public function getViewNames();
public function onDataSourceChanged($param);
}
abstract class TDataSourceControl extends TControl implements IDataSource
{
public function getView($viewName)
{
return null;
}
public function getViewNames()
{
return array();
}
public function onDataSourceChanged($param)
{
$this->raiseEvent('OnDataSourceChanged',$this,$param);
}
public function focus()
{
throw new TNotSupportedException('datasourcecontrol_focus_unsupported');
}
public function getEnableTheming()
{
return false;
}
public function setEnableTheming($value)
{
throw new TNotSupportedException('datasourcecontrol_enabletheming_unsupported');
}
public function getSkinID()
{
return '';
}
public function setSkinID($value)
{
throw new TNotSupportedException('datasourcecontrol_skinid_unsupported');
}
public function getVisible($checkParents=true)
{
return false;
}
public function setVisible($value)
{
throw new TNotSupportedException('datasourcecontrol_visible_unsupported');
}
}
class TReadOnlyDataSource extends TDataSourceControl
{
private $_dataSource;
private $_dataMember;
public function __construct($dataSource,$dataMember)
{
if(!is_array($dataSource) && !($dataSource instanceof IDataSource) && !($dataSource instanceof Traversable))
throw new TInvalidDataTypeException('readonlydatasource_datasource_invalid');
$this->_dataSource=$dataSource;
$this->_dataMember=$dataMember;
}
public function getView($viewName)
{
if($this->_dataSource instanceof IDataSource)
return $this->_dataSource->getView($viewName);
else
return new TReadOnlyDataSourceView($this,$this->_dataMember,$this->_dataSource);
}
}

class TDataSourceSelectParameters extends TComponent
{
private $_retrieveTotalRowCount=false;
private $_startRowIndex=0;
private $_totalRowCount=0;
private $_maximumRows=0;
public function getStartRowIndex()
{
return $this->_startRowIndex;
}
public function setStartRowIndex($value)
{
if(($value=TPropertyValue::ensureInteger($value))<0)
$value=0;
$this->_startRowIndex=$value;
}
public function getMaximumRows()
{
return $this->_maximumRows;
}
public function setMaximumRows($value)
{
if(($value=TPropertyValue::ensureInteger($value))<0)
$value=0;
$this->_maximumRows=$value;
}
public function getRetrieveTotalRowCount()
{
return $this->_retrieveTotalRowCount;
}
public function setRetrieveTotalRowCount($value)
{
$this->_retrieveTotalRowCount=TPropertyValue::ensureBoolean($value);
}
public function getTotalRowCount()
{
return $this->_totalRowCount;
}
public function setTotalRowCount($value)
{
if(($value=TPropertyValue::ensureInteger($value))<0)
$value=0;
$this->_totalRowCount=$value;
}
}
abstract class TDataSourceView extends TComponent
{
private $_owner;
private $_name;
public function __construct(IDataSource $owner,$viewName)
{
$this->_owner=$owner;
$this->_name=$viewName;
}
abstract public function select($parameters);
public function insertAt($values)
{
throw new TNotSupportedException('datasourceview_insert_unsupported');
}
public function update($keys,$values)
{
throw new TNotSupportedException('datasourceview_update_unsupported');
}
public function delete($keys)
{
throw new TNotSupportedException('datasourceview_delete_unsupported');
}
public function getCanDelete()
{
return false;
}
public function getCanInsert()
{
return false;
}
public function getCanPage()
{
return false;
}
public function getCanGetRowCount()
{
return false;
}
public function getCanSort()
{
return false;
}
public function getCanUpdate()
{
return false;
}
public function getName()
{
return $this->_name;
}
public function getDataSource()
{
return $this->_owner;
}
public function onDataSourceViewChanged($param)
{
$this->raiseEvent('OnDataSourceViewChanged',$this,$param);
}
}
class TReadOnlyDataSourceView extends TDataSourceView
{
private $_dataSource=null;
public function __construct(IDataSource $owner,$viewName,$dataSource)
{
parent::__construct($owner,$viewName);
if($dataSource===null || is_array($dataSource))
$this->_dataSource=new TMap($dataSource);
else if($dataSource instanceof Traversable)
$this->_dataSource=$dataSource;
else
throw new TInvalidDataTypeException('readonlydatasourceview_datasource_invalid');
}
public function select($parameters)
{
return $this->_dataSource;
}
}

abstract class TDataBoundControl extends TWebControl
{
private $_initialized=false;
private $_dataSource=null;
private $_requiresBindToNull=false;
private $_requiresDataBinding=false;
private $_prerendered=false;
private $_currentView=null;
private $_currentDataSource=null;
private $_currentViewValid=false;
private $_currentDataSourceValid=false;
private $_currentViewIsFromDataSourceID=false;
private $_parameters=null;
private $_isDataBound=false;
public function getDataSource()
{
return $this->_dataSource;
}
public function setDataSource($value)
{
$this->_dataSource=$this->validateDataSource($value);;
$this->onDataSourceChanged();
}
public function getDataSourceID()
{
return $this->getViewState('DataSourceID','');
}
public function setDataSourceID($value)
{
$dsid=$this->getViewState('DataSourceID','');
if($dsid!=='' && $value==='')
$this->_requiresBindToNull=true;
$this->setViewState('DataSourceID',$value,'');
$this->onDataSourceChanged();
}
protected function getUsingDataSourceID()
{
return $this->getDataSourceID()!=='';
}
public function onDataSourceChanged()
{
$this->_currentViewValid=false;
$this->_currentDataSourceValid=false;
if($this->getInitialized())
$this->setRequiresDataBinding(true);
}
protected function getInitialized()
{
return $this->_initialized;
}
protected function setInitialized($value)
{
$this->_initialized=TPropertyValue::ensureBoolean($value);
}
protected function getIsDataBound()
{
return $this->_isDataBound;
}
protected function setIsDataBound($value)
{
$this->_isDataBound=$value;
}
protected function getRequiresDataBinding()
{
return $this->_requiresDataBinding;
}
protected function setRequiresDataBinding($value)
{
$value=TPropertyValue::ensureBoolean($value);
if($value && $this->_prerendered)
{
$this->_requiresDataBinding=true;
$this->ensureDataBound();
}
else
$this->_requiresDataBinding=$value;
}
protected function ensureDataBound()
{
if($this->_requiresDataBinding && ($this->getUsingDataSourceID() || $this->_requiresBindToNull))
{
$this->dataBind();
$this->_requiresBindToNull=false;
}
}
public function dataBind()
{
$this->setRequiresDataBinding(false);
$this->dataBindProperties();
$this->onDataBinding(null);
$data=$this->getData();
if($data instanceof Traversable)
$this->performDataBinding($data);
else if($data!==null)
throw new TInvalidDataTypeException('databoundcontrol_data_nontraversable');
$this->setIsDataBound(true);
$this->onDataBound(null);
}
public function dataSourceViewChanged($sender,$param)
{
if(!$this->_ignoreDataSourceViewChanged)
$this->setRequiresDataBinding(true);
}
protected function getData()
{
if(($view=$this->getDataSourceView())!==null)
return $view->select($this->getSelectParameters());
else
return null;
}
protected function getDataSourceView()
{
if(!$this->_currentViewValid)
{
if($this->_currentView && $this->_currentViewIsFromDataSourceID)
$this->_currentView->detachEventHandler('DataSourceViewChanged',array($this,'dataSourceViewChanged'));
if(($dataSource=$this->determineDataSource())!==null)
{
if(($view=$dataSource->getView($this->getDataMember()))===null)
throw new TInvalidDataValueException('databoundcontrol_datamember_invalid',$this->getDataMember());
if($this->_currentViewIsFromDataSourceID=$this->getUsingDataSourceID())
$view->attachEventHandler('OnDataSourceViewChanged',array($this,'dataSourceViewChanged'));
$this->_currentView=$view;
}
else
$this->_currentView=null;
$this->_currentViewValid=true;
}
return $this->_currentView;
}
protected function determineDataSource()
{
if(!$this->_currentDataSourceValid)
{
if(($dsid=$this->getDataSourceID())!=='')
{
if(($dataSource=$this->getNamingContainer()->findControl($dsid))===null)
throw new TInvalidDataValueException('databoundcontrol_datasourceid_inexistent',$dsid);
else if(!($dataSource instanceof IDataSource))
throw new TInvalidDataValueException('databoundcontrol_datasourceid_invalid',$dsid);
else
$this->_currentDataSource=$dataSource;
}
else if(($dataSource=$this->getDataSource())!==null)
$this->_currentDataSource=new TReadOnlyDataSource($dataSource,$this->getDataMember());
else
$this->_currentDataSource=null;
$this->_currentDataSourceValid=true;
}
return $this->_currentDataSource;
}
abstract protected function performDataBinding($data);
public function onDataBound($param)
{
$this->raiseEvent('OnDataBound',$this,$param);
}
public function onInit($param)
{
parent::onInit($param);
$page=$this->getPage();
$page->attachEventHandler('OnPreLoad',array($this,'onPagePreLoad'));
}
public function onPagePreLoad($sender,$param)
{
$this->_initialized=true;
$isPostBack=$this->getPage()->getIsPostBack();
if(!$isPostBack || ($isPostBack && (!$this->getEnableViewState(true) || !$this->getIsDataBound())))
$this->setRequiresDataBinding(true);
}
public function onPreRender($param)
{
$this->_prerendered=true;
$this->ensureDataBound();
parent::onPreRender($param);
}
protected function validateDataSource($value)
{
if(is_string($value))
{
$list=new TList;
foreach(TPropertyValue::ensureArray($value) as $key=>$value)
{
if(is_array($value))
$list->add($value);
else
$list->add(array($value,is_string($key)?$key:$value));
}
return $list;
}
else if(is_array($value))
return new TMap($value);
else if(($value instanceof Traversable) || $value===null)
return $value;
else
throw new TInvalidDataTypeException('databoundcontrol_datasource_invalid');
}
public function getDataMember()
{
return $this->getViewState('DataMember','');
}
public function setDataMember($value)
{
$this->setViewState('DataMember',$value,'');
}
public function getSelectParameters()
{
if(!$this->_parameters)
$this->_parameters=new TDataSourceSelectParameters;
return $this->_parameters;
}
}

class TCheckBoxList extends TListControl implements IRepeatInfoUser, INamingContainer, IPostBackDataHandler
{
private $_repeatedControl;
private $_isEnabled;
private $_changedEventRaised=false;
public function __construct()
{
parent::__construct();
$this->_repeatedControl=$this->createRepeatedControl();
$this->_repeatedControl->setEnableViewState(false);
$this->_repeatedControl->setID('0');
$this->getControls()->add($this->_repeatedControl);
}
protected function createRepeatedControl()
{
return new TCheckBox;
}
public function findControl($id)
{
return $this;
}
protected function getIsMultiSelect()
{
return true;
}
protected function createStyle()
{
return new TTableStyle;
}
public function getTextAlign()
{
return $this->getViewState('TextAlign','Right');
}
public function setTextAlign($value)
{
$this->setViewState('TextAlign',TPropertyValue::ensureEnum($value,array('Left','Right')),'Right');
}
protected function getRepeatInfo()
{
if(($repeatInfo=$this->getViewState('RepeatInfo',null))===null)
{
$repeatInfo=new TRepeatInfo;
$this->setViewState('RepeatInfo',$repeatInfo,null);
}
return $repeatInfo;
}
public function getRepeatColumns()
{
return $this->getRepeatInfo()->getRepeatColumns();
}
public function setRepeatColumns($value)
{
$this->getRepeatInfo()->setRepeatColumns($value);
}
public function getRepeatDirection()
{
return $this->getRepeatInfo()->getRepeatDirection();
}
public function setRepeatDirection($value)
{
$this->getRepeatInfo()->setRepeatDirection($value);
}
public function getRepeatLayout()
{
return $this->getRepeatInfo()->getRepeatLayout();
}
public function setRepeatLayout($value)
{
$this->getRepeatInfo()->setRepeatLayout($value);
}
public function getCellSpacing()
{
if($this->getHasStyle())
return $this->getStyle()->getCellSpacing();
else
return -1;
}
public function setCellSpacing($value)
{
$this->getStyle()->setCellSpacing($value);
}
public function getCellPadding()
{
if($this->getHasStyle())
return $this->getStyle()->getCellPadding();
else
return -1;
}
public function setCellPadding($value)
{
$this->getStyle()->setCellPadding($value);
}
public function getHasHeader()
{
return false;
}
public function getHasFooter()
{
return false;
}
public function getHasSeparators()
{
return false;
}
public function generateItemStyle($itemType,$index)
{
return null;
}
public function renderItem($writer,$repeatInfo,$itemType,$index)
{
$item=$this->getItems()->itemAt($index);
if($item->getHasAttributes())
$this->_repeatedControl->getAttributes()->copyFrom($item->getAttributes());
else if($this->_repeatedControl->getHasAttributes())
$this->_repeatedControl->getAttributes()->clear();
$this->_repeatedControl->setID("$index");
$this->_repeatedControl->setText($item->getText());
$this->_repeatedControl->setChecked($item->getSelected());
$this->_repeatedControl->setAttribute('value',$item->getValue());
$this->_repeatedControl->setEnabled($this->_isEnabled && $item->getEnabled());
$this->_repeatedControl->renderControl($writer);
}
public function loadPostData($key,$values)
{
if($this->getEnabled(true))
{
$index=(int)substr($key,strlen($this->getUniqueID())+1);
$this->ensureDataBound();
if($index>=0 && $index<$this->getItemCount())
{
$item=$this->getItems()->itemAt($index);
if($item->getEnabled())
{
$checked=isset($values[$key]);
if($item->getSelected()!=$checked)
{
$item->setSelected($checked);
if(!$this->_changedEventRaised)
{
$this->_changedEventRaised=true;
return true;
}
}
}
}
}
return false;
}
public function raisePostDataChangedEvent()
{
if($this->getAutoPostBack() && $this->getCausesValidation())
$this->getPage()->validate($this->getValidationGroup());
$this->onSelectedIndexChanged(null);
}
public function onPreRender($param)
{
parent::onPreRender($param);
$this->_repeatedControl->setAutoPostBack($this->getAutoPostBack());
$this->_repeatedControl->setCausesValidation($this->getCausesValidation());
$this->_repeatedControl->setValidationGroup($this->getValidationGroup());
$page=$this->getPage();
$n=$this->getItemCount();
for($i=0;$i<$n;++$i)
{
$this->_repeatedControl->setID("$i");
$page->registerRequiresPostData($this->_repeatedControl);
}
}
protected function render($writer)
{
if($this->getItemCount()>0)
{
$this->_isEnabled=$this->getEnabled(true);
$repeatInfo=$this->getRepeatInfo();
$accessKey=$this->getAccessKey();
$tabIndex=$this->getTabIndex();
$this->_repeatedControl->setTextAlign($this->getTextAlign());
$this->_repeatedControl->setAccessKey($accessKey);
$this->_repeatedControl->setTabIndex($tabIndex);
$this->setAccessKey('');
$this->setTabIndex(0);
$repeatInfo->renderRepeater($writer,$this);
$this->setAccessKey($accessKey);
$this->setTabIndex($tabIndex);
}
}
}

class TRadioButtonList extends TCheckBoxList
{
protected function getIsMultiSelect()
{
return false;
}
protected function createRepeatedControl()
{
return new TRadioButton;
}
public function loadPostData($key,$values)
{
$value=isset($values[$key])?$values[$key]:'';
$oldSelection=$this->getSelectedIndex();
$this->ensureDataBound();
foreach($this->getItems() as $index=>$item)
{
if($item->getEnabled() && $item->getValue()===$value)
{
if($index===$oldSelection)
return false;
else
{
$this->setSelectedIndex($index);
return true;
}
}
}
return false;
}
public function setSelectedIndices($indices)
{
throw new TNotSupportedException('radiobuttonlist_selectedindices_unsupported');
}
}

class TBulletedList extends TListControl implements IPostBackEventHandler
{
private $_isEnabled;
private $_postBackOptions;
private $_currentRenderItemIndex;
public function raisePostBackEvent($param)
{
if($this->getCausesValidation())
$this->getPage()->validate($this->getValidationGroup());
$this->onClick(new TBulletedListEventParameter((int)$param));
}
protected function getTagName()
{
switch($this->getBulletStyle())
{
case 'Numbered':
case 'LowerAlpha':
case 'UpperAlpha':
case 'LowerRoman':
case 'UpperRoman':
return 'ol';
}
return 'ul';
}
protected function addAttributesToRender($writer)
{
$needStart=false;
switch($this->getBulletStyle())
{
case 'Numbered':
$writer->addStyleAttribute('list-style-type','decimal');
$needStart=true;
break;
case 'LowerAlpha':
$writer->addStyleAttribute('list-style-type','lower-alpha');
$needStart=true;
break;
case 'UpperAlpha':
$writer->addStyleAttribute('list-style-type','upper-alpha');
$needStart=true;
break;
case 'LowerRoman':
$writer->addStyleAttribute('list-style-type','lower-roman');
$needStart=true;
break;
case 'UpperRoman':
$writer->addStyleAttribute('list-style-type','upper-roman');
$needStart=true;
break;
case 'Disc':
$writer->addStyleAttribute('list-style-type','disc');
break;
case 'Circle':
$writer->addStyleAttribute('list-style-type','circle');
break;
case 'Square':
$writer->addStyleAttribute('list-style-type','square');
break;
case 'CustomImage':
$url=$this->getBulletImageUrl();
$writer->addStyleAttribute('list-style-image',"url($url)");
break;
}
if($needStart && ($start=$this->getFirstBulletNumber())!=1)
$writer->addAttribute('start',"$start");
parent::addAttributesToRender($writer);
}
public function getBulletImageUrl()
{
return $this->getViewState('BulletImageUrl','');
}
public function setBulletImageUrl($value)
{
$this->setViewState('BulletImageUrl',$value,'');
}
public function getBulletStyle()
{
return $this->getViewState('BulletStyle','NotSet');
}
public function setBulletStyle($value)
{
$this->setViewState('BulletStyle',TPropertyValue::ensureEnum($value,'NotSet','Numbered','LowerAlpha','UpperAlpha','LowerRoman','UpperRoman','Disc','Circle','Square','CustomImage'),'NotSet');
}
public function getDisplayMode()
{
return $this->getViewState('DisplayMode','Text');
}
public function setDisplayMode($value)
{
$this->setViewState('DisplayMode',TPropertyValue::ensureEnum($value,'Text','HyperLink','LinkButton'),'Text');
}
public function getFirstBulletNumber()
{
return $this->getViewState('FirstBulletNumber',1);
}
public function setFirstBulletNumber($value)
{
$this->setViewState('FirstBulletNumber',TPropertyValue::ensureInteger($value),1);
}
public function onClick($param)
{
$this->raiseEvent('OnClick',$this,$param);
}
public function getTarget()
{
return $this->getViewState('Target','');
}
public function setTarget($value)
{
$this->setViewState('Target',$value,'');
}
protected function render($writer)
{
if($this->getHasItems())
parent::render($writer);
}
protected function renderContents($writer)
{
$this->_isEnabled=$this->getEnabled(true);
$this->_postBackOptions=$this->getPostBackOptions();
$writer->writeLine();
foreach($this->getItems() as $index=>$item)
{
if($item->getHasAttributes())
{
foreach($item->getAttributes() as $name=>$value)
$writer->addAttribute($name,$value);
}
$writer->renderBeginTag('li');
$this->renderBulletText($writer,$item,$index);
$writer->renderEndTag();
$writer->writeLine();
}
}
protected function renderBulletText($writer,$item,$index)
{
switch($this->getDisplayMode())
{
case 'Text':
return $this->renderTextItem($writer, $item, $index);
case 'HyperLink':
$this->renderHyperLinkItem($writer, $item, $index);
break;
case 'LinkButton':
$this->renderLinkButtonItem($writer, $item, $index);
}
if(($accesskey=$this->getAccessKey())!=='')
$writer->addAttribute('accesskey',$accesskey);
$writer->renderBeginTag('a');
$writer->write(THttpUtility::htmlEncode($item->getText()));
$writer->renderEndTag();
}
protected function renderTextItem($writer, $item, $index)
{
if($item->getEnabled())
$writer->write(THttpUtility::htmlEncode($item->getText()));
else
{
$writer->addAttribute('disabled','disabled');
$writer->renderBeginTag('span');
$writer->write(THttpUtility::htmlEncode($item->getText()));
$writer->renderEndTag();
}
}
protected function renderHyperLinkItem($writer, $item, $index)
{
if(!$this->_isEnabled || !$item->getEnabled())
$writer->addAttribute('disabled','disabled');
else
{
$writer->addAttribute('href',$item->getValue());
if(($target=$this->getTarget())!=='')
$writer->addAttribute('target',$target);
}
}
protected function renderLinkButtonItem($writer, $item, $index)
{
if(!$this->_isEnabled || !$item->getEnabled())
$writer->addAttribute('disabled','disabled');
else
{
$this->_currentRenderItemIndex = $index;
$this->getPage()->getClientScript()->registerPostbackControl($this);
$writer->addAttribute('id', $this->getClientID().$index);
$writer->addAttribute('href', "javascript:;//".$this->getClientID().$index);
}
}
public function getPostBackOptions()
{
$options['ValidationGroup'] = $this->getValidationGroup();
$options['CausesValidation'] = $this->getCausesValidation();
$options['EventTarget'] = $this->getUniqueID();
$options['EventParameter'] = $this->_currentRenderItemIndex;
$options['ID'] = $this->getClientID().$this->_currentRenderItemIndex;
return $options;
}
protected function canCauseValidation()
{
$group = $this->getValidationGroup();
$hasValidators = $this->getPage()->getValidators($group)->getCount()>0;
return $this->getCausesValidation() && $hasValidators;
}
public function setAutoPostBack($value)
{
throw new TNotSupportedException('bulletedlist_autopostback_unsupported');
}
public function setSelectedIndex($index)
{
throw new TNotSupportedException('bulletedlist_selectedindex_unsupported');
}
public function setSelectedIndices($indices)
{
throw new TNotSupportedException('bulletedlist_selectedindices_unsupported');
}
public function setSelectedValue($value)
{
throw new TNotSupportedException('bulletedlist_selectedvalue_unsupported');
}
}
class TBulletedListEventParameter extends TEventParameter
{
private $_index;
public function __construct($index)
{
$this->_index=$index;
}
public function getIndex()
{
return $this->_index;
}
}

abstract class TListControl extends TDataBoundControl
{
private $_items=null;
private $_loadedFromState=false;
protected function getTagName()
{
return 'select';
}
protected function addAttributesToRender($writer)
{
$page=$this->getPage();
$page->ensureRenderInForm($this);
if($this->getIsMultiSelect())
$writer->addAttribute('multiple','multiple');
if($this->getEnabled(true) && $this->getAutoPostBack() && $page->getClientSupportsJavaScript())
{
$writer->addAttribute('id',$this->getClientID());
$this->getPage()->getClientScript()->registerPostBackControl($this);
}
if($this->getEnabled(true) && !$this->getEnabled())
$writer->addAttribute('disabled','disabled');
parent::addAttributesToRender($writer);
}
public function getPostBackOptions()
{
$options['CausesValidation'] = $this->getCausesValidation();
$options['ValidationGroup'] = $this->getValidationGroup();
$options['EventTarget'] = $this->getUniqueID();
return $options;
}
public function addParsedObject($object)
{
if(!$this->_loadedFromState && ($object instanceof TListItem))
$this->getItems()->add($object);
}
protected function performDataBinding($data)
{
$items=$this->getItems();
if(!$this->getAppendDataBoundItems())
$items->clear();
$textField=$this->getDataTextField();
if($textField==='')
$textField=0;
$valueField=$this->getDataValueField();
if($valueField==='')
$valueField=1;
$textFormat=$this->getDataTextFormatString();
foreach($data as $key=>$object)
{
$item=new TListItem;
if(!is_string($object) && isset($object[$textField]))
$text=$object[$textField];
else
$text=TPropertyValue::ensureString($object);
$item->setText($textFormat===''?$text:sprintf($textFormat,$text));
if(!is_string($object) && isset($object[$valueField]))
$item->setValue($object[$valueField]);
else if(!is_integer($key))
$item->setValue($key);
$items->add($item);
}
}
public function saveState()
{
parent::saveState();
if($this->_items)
$this->setViewState('Items',$this->_items->saveState(),null);
else
$this->clearViewState('Items');
}
public function loadState()
{
parent::loadState();
$this->_loadedFromState=true;
if(!$this->getIsDataBound())
{
$this->_items=new TListItemCollection;
$this->_items->loadState($this->getViewState('Items',null));
}
$this->clearViewState('Items');
}
protected function getIsMultiSelect()
{
return false;
}
public function getAppendDataBoundItems()
{
return $this->getViewState('AppendDataBoundItems',false);
}
public function setAppendDataBoundItems($value)
{
$this->setViewState('AppendDataBoundItems',TPropertyValue::ensureBoolean($value),false);
}
public function getAutoPostBack()
{
return $this->getViewState('AutoPostBack',false);
}
public function setAutoPostBack($value)
{
$this->setViewState('AutoPostBack',TPropertyValue::ensureBoolean($value),false);
}
public function getCausesValidation()
{
return $this->getViewState('CausesValidation',true);
}
public function setCausesValidation($value)
{
$this->setViewState('CausesValidation',TPropertyValue::ensureBoolean($value),true);
}
public function getDataTextField()
{
return $this->getViewState('DataTextField','');
}
public function setDataTextField($value)
{
$this->setViewState('DataTextField',$value,'');
}
public function getDataTextFormatString()
{
return $this->getViewState('DataTextFormatString','');
}
public function setDataTextFormatString($value)
{
$this->setViewState('DataTextFormatString',$value,'');
}
public function getDataValueField()
{
return $this->getViewState('DataValueField','');
}
public function setDataValueField($value)
{
$this->setViewState('DataValueField',$value,'');
}
public function getItemCount()
{
return $this->_items?$this->_items->getCount():0;
}
public function getHasItems()
{
return ($this->_items && $this->_items->getCount()>0);
}
public function getItems()
{
if(!$this->_items)
$this->_items=new TListItemCollection;
return $this->_items;
}
public function getSelectedIndex()
{
if($this->_items)
{
$n=$this->_items->getCount();
for($i=0;$i<$n;++$i)
if($this->_items->itemAt($i)->getSelected())
return $i;
}
return -1;
}
public function setSelectedIndex($index)
{
$index=TPropertyValue::ensureInteger($index);
if($this->_items)
{
$this->clearSelection();
if($index>=0 && $index<$this->_items->getCount())
$this->_items->itemAt($index)->setSelected(true);
}
}
public function getSelectedIndices()
{
$selections=array();
if($this->_items)
{
$n=$this->_items->getCount();
for($i=0;$i<$n;++$i)
if($this->_items->itemAt($i)->getSelected())
$selections[]=$i;
}
return $selections;
}
public function setSelectedIndices($indices)
{
if($this->_items)
{
$this->clearSelection();
$n=$this->_items->getCount();
foreach($indices as $index)
{
if($index>=0 && $index<$n)
$this->_items->itemAt($index)->setSelected(true);
}
}
}
public function getSelectedItem()
{
if(($index=$this->getSelectedIndex())>=0)
return $this->_items->itemAt($index);
else
return null;
}
public function getSelectedValue()
{
$index=$this->getSelectedIndex();
return $index>=0?$this->getItems()->itemAt($index)->getValue():'';
}
public function setSelectedValue($value)
{
if($this->_items)
{
if($value===null)
$this->clearSelection();
else if(($item=$this->_items->findItemByValue($value))!==null)
{
$this->clearSelection();
$item->setSelected(true);
}
}
}
public function getText()
{
return $this->getSelectedValue();
}
public function setText($value)
{
$this->setSelectedValue($value);
}
public function clearSelection()
{
if($this->_items)
{
foreach($this->_items as $item)
$item->setSelected(false);
}
}
public function getValidationGroup()
{
return $this->getViewState('ValidationGroup','');
}
public function setValidationGroup($value)
{
$this->setViewState('ValidationGroup',$value,'');
}
public function onSelectedIndexChanged($param)
{
$this->raiseEvent('OnSelectedIndexChanged',$this,$param);
}
public function onTextChanged($param)
{
$this->raiseEvent('OnTextChanged',$this,$param);
}
protected function renderContents($writer)
{
if($this->_items)
{
$writer->writeLine();
foreach($this->_items as $item)
{
if($item->getEnabled())
{
if($item->getSelected())
$writer->addAttribute('selected','selected');
$writer->addAttribute('value',$item->getValue());
if($item->getHasAttributes())
{
foreach($item->getAttributes() as $name=>$value)
$writer->addAttribute($name,$value);
}
$writer->renderBeginTag('option');
$writer->write(THttpUtility::htmlEncode($item->getText()));
$writer->renderEndTag();
$writer->writeLine();
}
}
}
}
}
class TListItemCollection extends TList
{
public function insertAt($index,$item)
{
if(is_string($item))
parent::insertAt($index,new TListItem($item));
else if($item instanceof TListItem)
parent::insertAt($index,$item);
else
throw new TInvalidDataTypeException('listitemcollection_item_invalid');
}
public function findIndexByValue($value,$includeDisabled=true)
{
$value=TPropertyValue::ensureString($value);
$index=0;
foreach($this as $item)
{
if($item->getValue()===$value && ($includeDisabled || $item->getEnabled()))
return $index;
$index++;
}
return -1;
}
public function findIndexByText($text,$includeDisabled=true)
{
$text=TPropertyValue::ensureString($text);
$index=0;
foreach($this as $item)
{
if($item->getText()===$text && ($includeDisabled || $item->getEnabled()))
return $index;
$index++;
}
return -1;
}
public function findItemByValue($value,$includeDisabled=true)
{
if(($index=$this->findIndexByValue($value,$includeDisabled))>=0)
return $this->itemAt($index);
else
return null;
}
public function findItemByText($text,$includeDisabled=true)
{
if(($index=$this->findIndexByText($text,$includeDisabled))>=0)
return $this->itemAt($index);
else
return null;
}
public function loadState($state)
{
$this->clear();
if($state!==null)
{
foreach($state as $item)
$this->add(new TListItem($item[0],$item[1],$item[2],$item[3]));
}
}
public function saveState()
{
if($this->getCount()>0)
{
$state=array();
foreach($this as $item)
$state[]=array($item->getText(),$item->getValue(),$item->getEnabled(),$item->getSelected());
return $state;
}
else
return null;
}
}
class TListItem extends TComponent
{
private $_attributes=null;
private $_text;
private $_value;
private $_enabled;
private $_selected;
public function __construct($text='',$value='',$enabled=true,$selected=false)
{
$this->setText($text);
$this->setValue($value);
$this->setEnabled($enabled);
$this->setSelected($selected);
}
public function getEnabled()
{
return $this->_enabled;
}
public function setEnabled($value)
{
$this->_enabled=TPropertyValue::ensureBoolean($value);
}
public function getSelected()
{
return $this->_selected;
}
public function setSelected($value)
{
$this->_selected=TPropertyValue::ensureBoolean($value);
}
public function getText()
{
return $this->_text===''?$this->_value:$this->_text;
}
public function setText($value)
{
$this->_text=TPropertyValue::ensureString($value);
}
public function getValue()
{
return $this->_value===''?$this->_text:$this->_value;
}
public function setValue($value)
{
$this->_value=TPropertyValue::ensureString($value);
}
public function getAttributes()
{
if(!$this->_attributes)
$this->_attributes=new TAttributeCollection;
return $this->_attributes;
}
public function getHasAttributes()
{
return $this->_attributes && $this->_attributes->getCount()>0;
}
public function hasAttribute($name)
{
return $this->_attributes?$this->_attributes->contains($name):false;
}
public function getAttribute($name)
{
return $this->_attributes?$this->_attributes->itemAt($name):null;
}
public function setAttribute($name,$value)
{
$this->getAttributes()->add($name,$value);
}
public function removeAttribute($name)
{
return $this->_attributes?$this->_attributes->remove($name):null;
}
}

class TListBox extends TListControl implements IPostBackDataHandler, IValidatable
{
protected function addAttributesToRender($writer)
{
$rows=$this->getRows();
$writer->addAttribute('size',"$rows");
if($this->getSelectionMode()==='Multiple')
$writer->addAttribute('name',$this->getUniqueID().'[]');
else
$writer->addAttribute('name',$this->getUniqueID());
parent::addAttributesToRender($writer);
}
public function onPreRender($param)
{
parent::onPreRender($param);
if($this->getEnabled(true))
$this->getPage()->registerRequiresPostData($this);
}
public function loadPostData($key,$values)
{
if(!$this->getEnabled(true))
return false;
$this->ensureDataBound();
$selections=isset($values[$key])?$values[$key]:null;
if($selections!==null)
{
$items=$this->getItems();
if($this->getSelectionMode()==='Single')
{
$selection=is_array($selections)?$selections[0]:$selections;
$index=$items->findIndexByValue($selection,false);
if($this->getSelectedIndex()!==$index)
{
$this->setSelectedIndex($index);
return true;
}
else
return false;
}
if(!is_array($selections))
$selections=array($selections);
$list=array();
foreach($selections as $selection)
$list[]=$items->findIndexByValue($selection,false);
$list2=$this->getSelectedIndices();
$n=count($list);
$flag=false;
if($n===count($list2))
{
sort($list,SORT_NUMERIC);
for($i=0;$i<$n;++$i)
{
if($list[$i]!==$list2[$i])
{
$flag=true;
break;
}
}
}
else
$flag=true;
if($flag)
$this->setSelectedIndices($list);
return $flag;
}
else if($this->getSelectedIndex()!==-1)
{
$this->clearSelection();
return true;
}
else
return false;
}
public function raisePostDataChangedEvent()
{
if($this->getAutoPostBack() && $this->getCausesValidation())
$this->getPage()->validate($this->getValidationGroup());
$this->onSelectedIndexChanged(null);
}
protected function getIsMultiSelect()
{
return $this->getSelectionMode()==='Multiple';
}
public function getRows()
{
return $this->getViewState('Rows', 4);
}
public function setRows($value)
{
$value=TPropertyValue::ensureInteger($value);
if($value<=0)
$value=4;
$this->setViewState('Rows', $value, 4);
}
public function getSelectionMode()
{
return $this->getViewState('SelectionMode', 'Single');
}
public function setSelectionMode($value)
{
$this->setViewState('SelectionMode',TPropertyValue::ensureEnum($value,array('Single','Multiple')),'Single');
}
public function getValidationPropertyValue()
{
return $this->getSelectedValue();
}
}

class TDropDownList extends TListControl implements IPostBackDataHandler, IValidatable
{
protected function addAttributesToRender($writer)
{
$writer->addAttribute('name',$this->getUniqueID());
parent::addAttributesToRender($writer);
}
public function loadPostData($key,$values)
{
if(!$this->getEnabled(true))
return false;
$this->ensureDataBound();
$selection=isset($values[$key])?$values[$key]:null;
if($selection!==null)
{
$index=$this->getItems()->findIndexByValue($selection,false);
if($this->getSelectedIndex()!==$index)
{
$this->setSelectedIndex($index);
return true;
}
}
return false;
}
public function raisePostDataChangedEvent()
{
if($this->getAutoPostBack() && $this->getCausesValidation())
$this->getPage()->validate($this->getValidationGroup());
$this->onSelectedIndexChanged(null);
}
public function getSelectedIndex()
{
$index=parent::getSelectedIndex();
if($index<0 && $this->getHasItems())
{
$this->setSelectedIndex(0);
return 0;
}
else
return $index;
}
public function setSelectedIndices($indices)
{
throw new TNotSupportedException('dropdownlist_selectedindices_unsupported');
}
public function getValidationPropertyValue()
{
return $this->getSelectedValue();
}
}

class TJavascriptLogger extends TWebControl
{
protected function getTagName()
{
return 'div';
}
protected function renderContents($writer)
{
$this->Page->ClientScript->registerClientScript('logger');
$info = '(<a href="http://gleepglop.com/javascripts/logger/" target="_blank">more info</a>).';
$usage = 'Press ALT-D (Or CTRL-D on OS X) to toggle the javascript log console';
$writer->write("{$usage} {$info}");
parent::renderContents($writer);
}
}

class TLinkButton extends TWebControl implements IPostBackEventHandler
{
protected function getTagName()
{
return 'a';
}
protected function addAttributesToRender($writer)
{
$page=$this->getPage();
$page->ensureRenderInForm($this);
$writer->addAttribute('id',$this->getClientID());
parent::addAttributesToRender($writer);
if($this->getEnabled(true))
{
$nop = "#".$this->getClientID();
$writer->addAttribute('href', $nop);
$this->getPage()->getClientScript()->registerPostBackControl($this);
}
else if($this->getEnabled()) 			$writer->addAttribute('disabled','disabled');
}
public function getPostBackOptions()
{
$options['EventTarget'] = $this->getUniqueID();
$options['CausesValidation'] = $this->getCausesValidation();
$options['ValidationGroup'] = $this->getValidationGroup();
$options['StopEvent'] = true;
return $options;
}
protected function renderContents($writer)
{
if(($text=$this->getText())==='')
parent::renderContents($writer);
else
$writer->write($text);
}
public function getText()
{
return $this->getViewState('Text','');
}
public function setText($value)
{
$this->setViewState('Text',$value,'');
}
public function getCommandName()
{
return $this->getViewState('CommandName','');
}
public function setCommandName($value)
{
$this->setViewState('CommandName',$value,'');
}
public function getCommandParameter()
{
return $this->getViewState('CommandParameter','');
}
public function setCommandParameter($value)
{
$this->setViewState('CommandParameter',$value,'');
}
public function getCausesValidation()
{
return $this->getViewState('CausesValidation',true);
}
public function setCausesValidation($value)
{
$this->setViewState('CausesValidation',TPropertyValue::ensureBoolean($value),true);
}
public function getValidationGroup()
{
return $this->getViewState('ValidationGroup','');
}
public function setValidationGroup($value)
{
$this->setViewState('ValidationGroup',$value,'');
}
public function raisePostBackEvent($param)
{
if($this->getCausesValidation())
$this->getPage()->validate($this->getValidationGroup());
$this->onClick(null);
$this->onCommand(new TCommandEventParameter($this->getCommandName(),$this->getCommandParameter()));
}
public function onClick($param)
{
$this->raiseEvent('OnClick',$this,$param);
}
public function onCommand($param)
{
$this->raiseEvent('OnCommand',$this,$param);
$this->raiseBubbleEvent($this,$param);
}
}

abstract class TBaseValidator extends TLabel implements IValidator
{
private $_isValid=true;
private $_registered=false;
public function __construct()
{
parent::__construct();
$this->setForeColor('red');
}
public function onInit($param)
{
parent::onInit($param);
$this->getPage()->getValidators()->add($this);
$this->_registered=true;
}
public function onUnload($param)
{
if($this->_registered && ($page=$this->getPage())!==null)
$page->getValidators()->remove($this);
$this->_registered=false;
parent::onUnload($param);
}
protected function addAttributesToRender($writer)
{
$display=$this->getDisplay();
$visible=$this->getEnabled(true) && !$this->getIsValid();
if($display==='None' || (!$visible && $display==='Dynamic'))
$writer->addStyleAttribute('display','none');
else if(!$visible)
$writer->addStyleAttribute('visibility','hidden');
$writer->addAttribute('id',$this->getClientID());
parent::addAttributesToRender($writer);
}
protected function getClientScriptOptions()
{
$options['id'] = $this->getClientID();
$options['display'] = $this->getDisplay();
$options['errormessage'] = $this->getErrorMessage();
$options['focusonerror'] = $this->getFocusOnError();
$options['focuselementid'] = $this->getFocusElementID();
$options['validationgroup'] = $this->getValidationGroup();
$options['controltovalidate'] = $this->getValidationTarget()->getClientID();
return $options;
}
public function onPreRender($param)
{
$scripts = $this->getPage()->getClientScript();
$formID=$this->getPage()->getForm()->getClientID();
$scriptKey = "TBaseValidator:$formID";
if($this->getEnableClientScript() && !$scripts->isEndScriptRegistered($scriptKey))
{
$scripts->registerClientScript('validator');
$scripts->registerEndScript($scriptKey, "Prado.Validation.AddForm('$formID');");
}
if($this->getEnableClientScript())
$this->registerClientScriptValidator();
parent::onPreRender($param);
}
protected function registerClientScriptValidator()
{
if($this->getEnabled(true))
{
$class = get_class($this);
$scriptKey = "prado:".$this->getClientID();
$scripts = $this->getPage()->getClientScript();
$serializer = new TJavascriptSerializer($this->getClientScriptOptions());
$options = $serializer->toJavascript();
$js = "new Prado.Validation(Prado.Validation.{$class}, {$options});";
$scripts->registerEndScript($scriptKey, $js);
}
}
public function setForControl($value)
{
throw new TNotSupportedException('basevalidator_forcontrol_unsupported',get_class($this));
}
public function setEnabled($value)
{
$value=TPropertyValue::ensureBoolean($value);
parent::setEnabled($value);
if(!$value)
$this->_isValid=true;
}
public function getDisplay()
{
return $this->getViewState('Display','Static');
}
public function setDisplay($value)
{
$this->setViewState('Display',TPropertyValue::ensureEnum($value,array('None','Static','Dynamic')),'Static');
}
public function getEnableClientScript()
{
return $this->getViewState('EnableClientScript',true);
}
public function setEnableClientScript($value)
{
$this->setViewState('EnableClientScript',TPropertyValue::ensureBoolean($value),true);
}
public function getErrorMessage()
{
return $this->getViewState('ErrorMessage','');
}
public function setErrorMessage($value)
{
$this->setViewState('ErrorMessage',$value,'');
}
public function getControlToValidate()
{
return $this->getViewState('ControlToValidate','');
}
public function setControlToValidate($value)
{
$this->setViewState('ControlToValidate',$value,'');
}
public function getFocusOnError()
{
return $this->getViewState('FocusOnError',false);
}
public function setFocusOnError($value)
{
$this->setViewState('FocusOnError',TPropertyValue::ensureBoolean($value),false);
}
public function getFocusElementID()
{
if(($id=$this->getViewState('FocusElementID',''))==='')
$id=$this->getValidationTarget()->getClientID();
return $id;
}
public function setFocusElementID($value)
{
$this->setViewState('FocusElementID', $value, '');
}
public function getValidationGroup()
{
return $this->getViewState('ValidationGroup','');
}
public function setValidationGroup($value)
{
$this->setViewState('ValidationGroup',$value,'');
}
public function getIsValid()
{
return $this->_isValid;
}
public function setIsValid($value)
{
$this->_isValid=TPropertyValue::ensureBoolean($value);
}
protected function getValidationTarget()
{
if(($id=$this->getControlToValidate())!=='' && ($control=$this->findControl($id))!==null)
return $control;
else
throw new TConfigurationException('basevalidator_controltovalidate_invalid');
}
protected function getValidationValue($control)
{
if($control instanceof IValidatable)
return $control->getValidationPropertyValue();
else
throw new TInvalidDataTypeException('basevalidator_validatable_required');
}
public function validate()
{
$this->setIsValid(true);
$control=$this->getValidationTarget();
if($control && $this->getVisible(true) && $this->getEnabled())
$this->setIsValid($this->evaluateIsValid());
return $this->getIsValid();
}
abstract protected function evaluateIsValid();
protected function renderContents($writer)
{
if(($text=$this->getText())!=='')
$writer->write($text);
else if(($text=$this->getErrorMessage())!=='')
$writer->write($text);
else
parent::renderContents($writer);
}
}

class TRequiredFieldValidator extends TBaseValidator
{
public function getInitialValue()
{
return $this->getViewState('InitialValue','');
}
public function setInitialValue($value)
{
$this->setViewState('InitialValue',TPropertyValue::ensureString($value),'');
}
protected function evaluateIsValid()
{
$value=$this->getValidationValue($this->getValidationTarget());
return trim($value)!==trim($this->getInitialValue()) || (is_bool($value) && $value);
}
protected function getClientScriptOptions()
{
$options = parent::getClientScriptOptions();
$options['initialvalue']=$this->getInitialValue();
return $options;
}
}

class TCompareValidator extends TBaseValidator
{
public function getValueType()
{
return $this->getViewState('ValueType','String');
}
public function setValueType($value)
{
$this->setViewState('ValueType',TPropertyValue::ensureEnum($value,'Integer','Double','Date','Currency','String'),'String');
}
public function getControlToCompare()
{
return $this->getViewState('ControlToCompare','');
}
public function setControlToCompare($value)
{
$this->setViewState('ControlToCompare',$value,'');
}
public function getValueToCompare()
{
return $this->getViewState('ValueToCompare','');
}
public function setValueToCompare($value)
{
$this->setViewState('ValueToCompare',$value,'');
}
public function getOperator()
{
return $this->getViewState('Operator','Equal');
}
public function setOperator($value)
{
$this->setViewState('Operator',TPropertyValue::ensureEnum($value,'Equal','NotEqual','GreaterThan','GreaterThanEqual','LessThan','LessThanEqual'),'Equal');
}
public function setDateFormat($value)
{
$this->setViewState('DateFormat', $value, '');
}
public function getDateFormat()
{
return $this->getViewState('DateFormat', '');
}
public function evaluateIsValid()
{
if(($value=$this->getValidationValue($this->getValidationTarget()))==='')
return true;
if(($controlToCompare=$this->getControlToCompare())!=='')
{
if(($control2=$this->findControl($controlToCompare))===null)
throw new TInvalidDataValueException('comparevalidator_controltocompare_invalid');
if(($value2=$this->getValidationValue($control2))==='')
return false;
}
else
$value2=$this->getValueToCompare();
$values = $this->getComparisonValues($value, $value2);
switch($this->getOperator())
{
case 'Equal':
return $values[0] == $values[1];
case 'NotEqual':
return $values[0] != $values[1];
case 'GreaterThan':
return $values[0] > $values[1];
case 'GreaterThanEqual':
return $values[0] >= $values[1];
case 'LessThan':
return $values[0] < $values[1];
case 'LessThanEqual':
return $values[0] <= $values[1];
}
return false;
}
protected function getComparisonValues($value1, $value2)
{
switch($this->getValueType())
{
case 'Integer':
return array(intval($value1), intval($value2));
case 'Float':
case 'Double':
return array(floatval($value1), floatval($value2));
case 'Currency':
if(preg_match('/[-+]?([0-9]*\.)?[0-9]+([eE][-+]?[0-9]+)?/',$value1,$matches))
$value1=floatval($matches[0]);
else
$value1=0;
if(preg_match('/[-+]?([0-9]*\.)?[0-9]+([eE][-+]?[0-9]+)?/',$value2,$matches))
$value2=floatval($matches[0]);
else
$value2=0;
return array($value1, $value2);
case 'Date':
throw new TNotSupportedException('Date comparison for TCompareValidator is currently not supported. It will be supported in future.');
$dateFormat = $this->getDateFormat();
if (strlen($dateFormat))
return array(pradoParseDate($value1, $dateFormat), pradoParseDate($value2, $dateFormat));
else
return array(strtotime($value1), strtotime($value2));
}
return array($value1, $value2);
}
protected function getClientScriptOptions()
{
$options = parent::getClientScriptOptions();
if(($name=$this->getControlToCompare())!=='')
{
if(($control=$this->findControl($name))!==null)
$options['controltocompare']=$options['controlhookup']=$control->getClientID();
}
if(($value=$this->getValueToCompare())!=='')
$options['valuetocompare']=$value;
if(($operator=$this->getOperator())!=='Equal')
$options['operator']=$operator;
$options['type']=$this->getValueType();
if(($dateFormat=$this->getDateFormat())!=='')
$options['dateformat']=$dateFormat;
return $options;
}
}

class TRegularExpressionValidator extends TBaseValidator
{
public function getRegularExpression()
{
return $this->getViewState('RegularExpression','');
}
public function setRegularExpression($value)
{
$this->setViewState('RegularExpression',$value,'');
}
public function evaluateIsValid()
{
if(($value=$this->getValidationValue($this->getValidationTarget()))==='')
return true;
if(($expression=$this->getRegularExpression())!=='')
return preg_match("/^$expression\$/",$value);
else
return true;
}
protected function getClientScriptOptions()
{
$options = parent::getClientScriptOptions();
$options['validationexpression']=$this->getRegularExpression();
return $options;
}
}

class TEmailAddressValidator extends TRegularExpressionValidator
{
const EMAIL_REGEXP="\\w+([-+.]\\w+)*@\\w+([-.]\\w+)*\\.\\w+([-.]\\w+)*";
public function getRegularExpression()
{
$regex=parent::getRegularExpression();
return $regex===''?self::EMAIL_REGEXP:$regex;
}
public function evaluateIsValid()
{
$valid=parent::evaluateIsValid();
if($valid && function_exists('checkdnsrr'))
{
if(($value=$this->getValidationValue($this->getValidationTarget()))!=='')
{
if(($pos=strpos($value,'@'))!==false)
{
$domain=substr($value,$pos+1);
return $domain===''?false:checkdnsrr($domain,'MX');
}
else
return false;
}
}
return $valid;
}
}

class TCustomValidator extends TBaseValidator
{
public function getClientValidationFunction()
{
return $this->getViewState('ClientValidationFunction','');
}
public function setClientValidationFunction($value)
{
$this->setViewState('ClientValidationFunction',$value,'');
}
public function evaluateIsValid()
{
$value=$this->getValidationValue($this->getValidationTarget());
return $this->onServerValidate($value);
}
public function onServerValidate($value)
{
$param=new TServerValidateEventParameter($value,true);
$this->raiseEvent('OnServerValidate',$this,$param);
return $param->getIsValid();
}
protected function getClientScriptOptions()
{
$options=parent::getClientScriptOptions();
if(($clientJs=$this->getClientValidationFunction())!=='')
$options['clientvalidationfunction']=$clientJs;
return $options;
}
}
class TServerValidateEventParameter extends TEventParameter
{
private $_value='';
private $_isValid=true;
public function __construct($value,$isValid)
{
$this->_value=$value;
$this->setIsValid($isValid);
}
public function getValue()
{
return $this->_value;
}
public function getIsValid()
{
return $this->_isValid;
}
public function setIsValid($value)
{
$this->_isValid=TPropertyValue::ensureBoolean($value);
}
}

class TValidationSummary extends TWebControl
{
public function getHeaderText()
{
return $this->getViewState('HeaderText','');
}
public function setHeaderText($value)
{
$this->setViewState('HeaderText',$value,'');
}
public function getDisplayMode()
{
return $this->getViewState('DisplayMode','BulletList');
}
public function setDisplayMode($value)
{
$this->setViewState('DisplayMode',TPropertyValue::ensureEnum($value,'List','SingleParagraph','BulletList'),'BulletList');
}
public function getEnableClientScript()
{
return $this->getViewState('EnableClientScript',true);
}
public function setEnableClientScript($value)
{
$this->setViewState('EnableClientScript',TPropertyValue::ensureBoolean($value),true);
}
public function getShowMessageBox()
{
return $this->getViewState('ShowMessageBox',false);
}
public function setShowMessageBox($value)
{
$this->setViewState('ShowMessageBox',TPropertyValue::ensureBoolean($value),false);
}
public function getShowSummary()
{
return $this->getViewState('ShowSummary',true);
}
public function setShowSummary($value)
{
$this->setViewState('ShowSummary',TPropertyValue::ensureBoolean($value),true);
}
public function getShowAnchor()
{
return $this->getViewState('ShowAnchor',false);
}
public function setShowAnchor($value)
{
$this->setViewState('ShowAnchor',TPropertyValue::ensureBoolean($value),false);
}
public function getAutoUpdate()
{
return $this->getViewState('AutoUpdate', true);
}
public function setAutoUpdate($value)
{
$this->setViewState('AutoUpdate', TPropertyValue::ensureBoolean($value), true);
}
public function getValidationGroup()
{
return $this->getViewState('ValidationGroup','');
}
public function setValidationGroup($value)
{
$this->setViewState('ValidationGroup',$value,'');
}
protected function addAttributesToRender($writer)
{
$writer->addAttribute('id',$this->getClientID());
parent::addAttributesToRender($writer);
}
protected function renderJsSummary()
{
if(!$this->getEnabled(true) || !$this->getEnableClientScript())
return;
$serializer = new TJavascriptSerializer($this->getClientScriptOptions());
$options = $serializer->toJavascript();
$script = "new Prado.Validation.Summary({$options});";
$this->getPage()->getClientScript()->registerEndScript($this->getClientID(), $script);
}
protected function getClientScriptOptions()
{
$options['id'] = $this->ClientID;
$options['form'] = $this->Page->Form->ClientID;
if($this->getShowMessageBox())
$options['showmessagebox']='True';
if(!$this->getShowSummary())
$options['showsummary']='False';
$options['headertext']=$this->getHeaderText();
$options['displaymode']=$this->getDisplayMode();
$options['refresh'] = $this->getAutoUpdate();
$options['validationgroup'] =  $this->getValidationGroup();
return $options;
}
protected function getErrorMessages()
{
$validators=$this->getPage()->getValidators($this->getValidationGroup());
$messages = array();
foreach($validators as $validator)
{
if(!$validator->getIsValid() && ($msg=$validator->getErrorMessage())!=='')
$messages[] = $msg;
}
return $messages;
}
protected function renderContents($writer)
{
$this->renderJsSummary();
if($this->getShowSummary())
{
switch($this->getDisplayMode())
{
case 'List':
$content = $this->renderList($writer);
break;
case 'SingleParagraph':
$content = $this->renderSingleParagraph($writer);
break;
case 'BulletList':
default:
$content = $this->renderBulletList($writer);
}
}
}
protected function renderList($writer)
{
$header=$this->getHeaderText();
$messages=$this->getErrorMessages();
$content = '';
if(strlen($header))
$content.= $header."<br/>\n";
foreach($messages as $message)
$content.="$message<br/>\n";
$writer->write($content);
}
protected function renderSingleParagraph($writer)
{
$header=$this->getHeaderText();
$messages=$this->getErrorMessages();
$content = $header;
foreach($messages as $message)
$content.= ' '.$message;
$writer->write($content);
}
protected function renderBulletList($writer)
{
$header=$this->getHeaderText();
$messages=$this->getErrorMessages();
$content = $header;
if(count($messages)>0)
{
$content .= "<ul>\n";
foreach($messages as $message)
$content.= '<li>'.$message."</li>\n";
$content .= "</ul>\n";
}
$writer->write($content);
}
}

interface IRepeatInfoUser
{
public function getHasFooter();
public function getHasHeader();
public function getHasSeparators();
public function getItemCount();
public function generateItemStyle($itemType,$index);
public function renderItem($writer,$repeatInfo,$itemType,$index);
}
class TRepeatInfo extends TComponent
{
private $_caption='';
private $_captionAlign='NotSet';
private $_repeatColumns=0;
private $_repeatDirection='Vertical';
private $_repeatLayout='Table';
public function getCaption()
{
return $this->_caption;
}
public function setCaption($value)
{
$this->_caption=$value;
}
public function getCaptionAlign()
{
return $this->_captionAlign;
}
public function setCaptionAlign($value)
{
$this->_captionAlign=TPropertyValue::ensureEnum($value,array('NotSet','Top','Bottom','Left','Right'));
}
public function getRepeatColumns()
{
return $this->_repeatColumns;
}
public function setRepeatColumns($value)
{
if(($value=TPropertyValue::ensureInteger($value))<0)
throw new TInvalidDataValueException('repeatinfo_repeatcolumns_invalid');
$this->_repeatColumns=$value;
}
public function getRepeatDirection()
{
return $this->_repeatDirection;
}
public function setRepeatDirection($value)
{
$this->_repeatDirection=TPropertyValue::ensureEnum($value,array('Horizontal','Vertical'));
}
public function getRepeatLayout()
{
return $this->_repeatLayout;
}
public function setRepeatLayout($value)
{
$this->_repeatLayout=TPropertyValue::ensureEnum($value,array('Table','Flow'));
}
public function renderRepeater($writer, IRepeatInfoUser $user)
{
if($this->_repeatLayout==='Table')
{
$control=new TTable;
if($this->_caption!=='')
{
$control->setCaption($this->_caption);
$control->setCaptionAlign($this->_captionAlign);
}
}
else
$control=new TWebControl;
$control->setID($user->getClientID());
$control->copyBaseAttributes($user);
if($user->getHasStyle())
$control->getStyle()->copyFrom($user->getStyle());
$control->renderBeginTag($writer);
$writer->writeLine();
if($this->_repeatDirection==='Vertical')
$this->renderVerticalContents($writer,$user);
else
$this->renderHorizontalContents($writer,$user);
$control->renderEndTag($writer);
}
protected function renderHorizontalContents($writer,$user)
{
$tableLayout=($this->_repeatLayout==='Table');
$hasSeparators=$user->getHasSeparators();
$itemCount=$user->getItemCount();
$columns=$this->_repeatColumns===0?$itemCount:$this->_repeatColumns;
$totalColumns=$hasSeparators?$columns+$columns:$columns;
$needBreak=$columns<$itemCount;
if($user->getHasHeader())
$this->renderHeader($writer,$user,$tableLayout,$totalColumns,$needBreak);
if($tableLayout)
{
$column=0;
for($i=0;$i<$itemCount;++$i)
{
if($column==0)
$writer->renderBeginTag('tr');
if(($style=$user->generateItemStyle('Item',$i))!==null)
$style->addAttributesToRender($writer);
$writer->renderBeginTag('td');
$user->renderItem($writer,$this,'Item',$i);
$writer->renderEndTag();
$writer->writeLine();
if($hasSeparators && $i!=$itemCount-1)
{
if(($style=$user->generateItemStyle('Separator',$i))!==null)
$style->addAttributesToRender($writer);
$writer->renderBeginTag('td');
$user->renderItem($writer,$this,'Separator',$i);
$writer->renderEndTag();
$writer->writeLine();
}
$column++;
if($i==$itemCount-1)
{
$restColumns=$columns-$column;
if($hasSeparators)
$restColumns=$restColumns?$restColumns+$restColumns+1:1;
for($j=0;$j<$restColumns;++$j)
$writer->write("<td></td>\n");
}
if($column==$columns || $i==$itemCount-1)
{
$writer->renderEndTag();
$writer->writeLine();
$column=0;
}
}
}
else
{
$column=0;
for($i=0;$i<$itemCount;++$i)
{
$user->renderItem($writer,$this,'Item',$i);
if($hasSeparators && $i!=$itemCount-1)
$user->renderItem($writer,$this,'Separator',$i);
$column++;
if($column==$columns || $i==$itemCount-1)
{
if($needBreak)
$writer->writeBreak();
$column=0;
}
$writer->writeLine();
}
}
if($user->getHasFooter())
$this->renderFooter($writer,$user,$tableLayout,$totalColumns,$needBreak);
}
protected function renderVerticalContents($writer,$user)
{
$tableLayout=($this->_repeatLayout==='Table');
$hasSeparators=$user->getHasSeparators();
$itemCount=$user->getItemCount();
if($this->_repeatColumns<=1)
{
$rows=$itemCount;
$columns=1;
$lastColumns=1;
}
else
{
$columns=$this->_repeatColumns;
$rows=(int)(($itemCount+$columns-1)/$columns);
if($rows==0 && $itemCount>0)
$rows=1;
if(($lastColumns=$itemCount%$columns)==0)
$lastColumns=$columns;
}
$totalColumns=$hasSeparators?$columns+$columns:$columns;
if($user->getHasHeader())
$this->renderHeader($writer,$user,$tableLayout,$totalColumns,false);
if($tableLayout)
{
$renderedItems=0;
for($row=0;$row<$rows;++$row)
{
$index=$row;
$writer->renderBeginTag('tr');
for($col=0;$col<$columns;++$col)
{
if($renderedItems>=$itemCount)
break;
if($col>0)
{
$index+=$rows;
if($col-1>=$lastColumns)
$index--;
}
if($index>=$itemCount)
continue;
$renderedItems++;
if(($style=$user->generateItemStyle('Item',$index))!==null)
$style->addAttributesToRender($writer);
$writer->renderBeginTag('td');
$user->renderItem($writer,$this,'Item',$index);
$writer->renderEndTag();
$writer->writeLine();
if(!$hasSeparators)
continue;
if($renderedItems<$itemCount-1)
{
if($columns==1)
{
$writer->renderEndTag();
$writer->renderBeginTag('tr');
}
if(($style=$user->generateItemStyle('Separator',$index))!==null)
$style->addAttributesToRender($writer);
$writer->renderBeginTag('td');
$user->renderItem($writer,$this,'Separator',$index);
$writer->renderEndTag();
$writer->writeLine();
}
else if($columns>1)
$writer->write("<td></td>\n");
}
if($row==$rows-1)
{
$restColumns=$columns-$lastColumns;
if($hasSeparators)
$restColumns+=$restColumns;
for($col=0;$col<$restColumns;++$col)
$writer->write("<td></td>\n");
}
$writer->renderEndTag();
$writer->writeLine();
}
}
else
{
$renderedItems=0;
for($row=0;$row<$rows;++$row)
{
$index=$row;
for($col=0;$col<$columns;++$col)
{
if($renderedItems>=$itemCount)
break;
if($col>0)
{
$index+=$rows;
if($col-1>=$lastColumns)
$index--;
}
if($index>=$itemCount)
continue;
$renderedItems++;
$user->renderItem($writer,$this,'Item',$index);
$writer->writeLine();
if(!$hasSeparators)
continue;
if($renderedItems<$itemCount-1)
{
if($columns==1)
$writer->writeBreak();
$user->renderItem($writer,$this,'Separator',$index);
}
$writer->writeLine();
}
if($row<$rows-1 || $user->getHasFooter())
$writer->writeBreak();
}
}
if($user->getHasFooter())
$this->renderFooter($writer,$user,$tableLayout,$totalColumns,false);
}
protected function renderHeader($writer,$user,$tableLayout,$columns,$needBreak)
{
if($tableLayout)
{
$writer->renderBeginTag('tr');
if($columns>1)
$writer->addAttribute('colspan',"$columns");
$writer->addAttribute('scope','col');
if(($style=$user->generateItemStyle('Header',-1))!==null)
$style->addAttributesToRender($writer);
$writer->renderBeginTag('th');
$user->renderItem($writer,$this,'Header',-1);
$writer->renderEndTag();
$writer->renderEndTag();
}
else
{
$user->renderItem($writer,$this,'Header',-1);
if($needBreak)
$writer->writeBreak();
}
$writer->writeLine();
}
protected function renderFooter($writer,$user,$tableLayout,$columns)
{
if($tableLayout)
{
$writer->renderBeginTag('tr');
if($columns>1)
$writer->addAttribute('colspan',"$columns");
if(($style=$user->generateItemStyle('Footer',-1))!==null)
$style->addAttributesToRender($writer);
$writer->renderBeginTag('td');
$user->renderItem($writer,$this,'Footer',-1);
$writer->renderEndTag();
$writer->renderEndTag();
}
else
$user->renderItem($writer,$this,'Footer',-1);
$writer->writeLine();
}
}

class TRepeater extends TDataBoundControl implements INamingContainer
{
private $_itemTemplate=null;
private $_alternatingItemTemplate=null;
private $_headerTemplate=null;
private $_footerTemplate=null;
private $_separatorTemplate=null;
private $_items=null;
private $_header=null;
private $_footer=null;
public function addParsedObject($object)
{
}
public function getItemTemplate()
{
return $this->_itemTemplate;
}
public function setItemTemplate($value)
{
if($value instanceof ITemplate || $value===null)
$this->_itemTemplate=$value;
else
throw new TInvalidDataTypeException('repeater_template_required','ItemTemplate');
}
public function getAlternatingItemTemplate()
{
return $this->_alternatingItemTemplate;
}
public function setAlternatingItemTemplate($value)
{
if($value instanceof ITemplate || $value===null)
$this->_alternatingItemTemplate=$value;
else
throw new TInvalidDataTypeException('repeater_template_required','AlternatingItemTemplate');
}
public function getHeaderTemplate()
{
return $this->_headerTemplate;
}
public function setHeaderTemplate($value)
{
if($value instanceof ITemplate || $value===null)
$this->_headerTemplate=$value;
else
throw new TInvalidDataTypeException('repeater_template_required','HeaderTemplate');
}
public function getFooterTemplate()
{
return $this->_footerTemplate;
}
public function setFooterTemplate($value)
{
if($value instanceof ITemplate || $value===null)
$this->_footerTemplate=$value;
else
throw new TInvalidDataTypeException('repeater_template_required','FooterTemplate');
}
public function getSeparatorTemplate()
{
return $this->_separatorTemplate;
}
public function setSeparatorTemplate($value)
{
if($value instanceof ITemplate || $value===null)
$this->_separatorTemplate=$value;
else
throw new TInvalidDataTypeException('repeater_template_required','SeparatorTemplate');
}
public function getHeader()
{
return $this->_header;
}
public function getFooter()
{
return $this->_footer;
}
public function getItems()
{
if(!$this->_items)
$this->_items=new TRepeaterItemCollection;
return $this->_items;
}
protected function createItem($itemIndex,$itemType)
{
return new TRepeaterItem($itemIndex,$itemType);
}
private function createItemInternal($itemIndex,$itemType,$dataBind,$dataItem)
{
$item=$this->createItem($itemIndex,$itemType);
$this->initializeItem($item);
$param=new TRepeaterItemEventParameter($item);
if($dataBind)
{
$item->setDataItem($dataItem);
$this->onItemCreated($param);
$this->getControls()->add($item);
$item->dataBind();
$this->onItemDataBound($param);
$item->setDataItem(null);
}
else
{
$this->onItemCreated($param);
$this->getControls()->add($item);
}
return $item;
}
protected function initializeItem($item)
{
$template=null;
switch($item->getItemType())
{
case 'Header': $template=$this->_headerTemplate; break;
case 'Footer': $template=$this->_footerTemplate; break;
case 'Item': $template=$this->_itemTemplate; break;
case 'Separator': $template=$this->_separatorTemplate; break;
case 'AlternatingItem': $template=$this->_alternatingItemTemplate===null ? $this->_itemTemplate : $this->_alternatingItemTemplate; break;
case 'SelectedItem':
case 'EditItem':
default:
break;
}
if($template!==null)
$template->instantiateIn($item);
}
protected function render($writer)
{
$this->renderContents($writer);
}
public function saveState()
{
parent::saveState();
if($this->_items)
$this->setViewState('ItemCount',$this->_items->getCount(),0);
else
$this->clearViewState('ItemCount');
}
public function loadState()
{
parent::loadState();
if(!$this->getIsDataBound())
$this->restoreItemsFromViewState();
$this->clearViewState('ItemCount');
}
public function reset()
{
$this->getControls()->clear();
$this->getItems()->clear();
$this->_header=null;
$this->_footer=null;
}
protected function restoreItemsFromViewState()
{
$this->reset();
if(($itemCount=$this->getViewState('ItemCount',0))>0)
{
$items=$this->getItems();
$hasSeparator=$this->_separatorTemplate!==null;
if($this->_headerTemplate!==null)
$this->_header=$this->createItemInternal(-1,'Header',false,null);
for($i=0;$i<$itemCount;++$i)
{
if($hasSeparator && $i>0)
$this->createItemInternal($i-1,'Separator',false,null);
$itemType=$i%2==0?'Item':'AlternatingItem';
$items->add($this->createItemInternal($i,$itemType,false,null));
}
if($this->_footerTemplate!==null)
$this->_footer=$this->createItemInternal(-1,'Footer',false,null);
}
$this->clearChildState();
}
protected function performDataBinding($data)
{
$this->reset();
$itemIndex=0;
$items=$this->getItems();
$hasSeparator=$this->_separatorTemplate!==null;
foreach($data as $dataItem)
{
if($itemIndex===0 && $this->_headerTemplate!==null)
$this->_header=$this->createItemInternal(-1,'Header',true,null);
if($hasSeparator && $itemIndex>0)
$this->createItemInternal($itemIndex-1,'Separator',true,null);
$itemType=$itemIndex%2==0?'Item':'AlternatingItem';
$items->add($this->createItemInternal($itemIndex,$itemType,true,$dataItem));
$itemIndex++;
}
if($itemIndex>0 && $this->_footerTemplate!==null)
$this->_footer=$this->createItemInternal(-1,'Footer',true,null);
$this->setViewState('ItemCount',$itemIndex,0);
}
public function onBubbleEvent($sender,$param)
{
if($param instanceof TRepeaterCommandEventParameter)
{
$this->onItemCommand($param);
return true;
}
else
return false;
}
public function onItemCreated($param)
{
$this->raiseEvent('OnItemCreated',$this,$param);
}
public function onItemDataBound($param)
{
$this->raiseEvent('OnItemDataBound',$this,$param);
}
public function onItemCommand($param)
{
$this->raiseEvent('OnItemCommand',$this,$param);
}
}
class TRepeaterItemEventParameter extends TEventParameter
{
private $_item=null;
public function __construct(TRepeaterItem $item)
{
$this->_item=$item;
}
public function getItem()
{
return $this->_item;
}
}
class TRepeaterCommandEventParameter extends TCommandEventParameter
{
private $_item=null;
private $_source=null;
public function __construct($item,$source,TCommandEventParameter $param)
{
$this->_item=$item;
$this->_source=$source;
parent::__construct($param->getCommandName(),$param->getCommandParameter());
}
public function getItem()
{
return $this->_item;
}
public function getCommandSource()
{
return $this->_source;
}
}
class TRepeaterItem extends TControl implements INamingContainer
{
private $_itemIndex='';
private $_itemType='';
private $_dataItem=null;
public function __construct($itemIndex,$itemType)
{
$this->_itemIndex=$itemIndex;
$this->_itemType=TPropertyValue::ensureEnum($itemType,'Header','Footer','Item','AlternatingItem','SelectedItem','EditItem','Separator','Pager');
}
public function getItemType()
{
return $this->_itemType;
}
public function getItemIndex()
{
return $this->_itemIndex;
}
public function getDataItem()
{
return $this->_dataItem;
}
public function setDataItem($value)
{
$this->_dataItem=$value;
}
public function onBubbleEvent($sender,$param)
{
if($param instanceof TCommandEventParameter)
{
$this->raiseBubbleEvent($this,new TRepeaterCommandEventParameter($this,$sender,$param));
return true;
}
else
return false;
}
}
class TRepeaterItemCollection extends TList
{
public function insertAt($index,$item)
{
if($item instanceof TRepeaterItem)
parent::insertAt($index,$item);
else
throw new TInvalidDataTypeException('repeateritemcollection_repeateritem_required');
}
}

abstract class TBaseDataList extends TDataBoundControl
{
public function addParsedObject($object)
{
}
protected function createStyle()
{
return new TTableStyle;
}
public function getCaption()
{
return $this->getViewState('Caption','');
}
public function setCaption($value)
{
$this->setViewState('Caption','');
}
public function getCaptionAlign()
{
return $this->getViewState('CaptionAlign','NotSet');
}
public function setCaptionAlign($value)
{
$this->setViewState('CaptionAlign',TPropertyValue::ensureEnum($value,'NotSet','Top','Bottom','Left','Right'),'NotSet');
}
public function getCellSpacing()
{
if($this->getHasStyle())
return $this->getStyle()->getCellSpacing();
else
return -1;
}
public function setCellSpacing($value)
{
$this->getStyle()->setCellSpacing($value);
}
public function getCellPadding()
{
if($this->getHasStyle())
return $this->getStyle()->getCellPadding();
else
return -1;
}
public function setCellPadding($value)
{
$this->getStyle()->setCellPadding($value);
}
public function getHorizontalAlign()
{
if($this->getHasStyle())
return $this->getStyle()->getHorizontalAlign();
else
return 'NotSet';
}
public function setHorizontalAlign($value)
{
$this->getStyle()->setHorizontalAlign($value);
}
public function getGridLines()
{
if($this->getHasStyle())
return $this->getStyle()->getGridLines();
else
return 'None';
}
public function setGridLines($value)
{
$this->getStyle()->setGridLines($value);
}
public function getDataKeyField()
{
return $this->getViewState('DataKeyField','');
}
public function setDataKeyField($value)
{
$this->setViewState('DataKeyField',$value,'');
}
public function getDataKeys()
{
if(($dataKeys=$this->getViewState('DataKeys',null))===null)
{
$dataKeys=new TList;
$this->setViewState('DataKeys',$dataKeys,null);
}
return $dataKeys;
}
protected function getDataFieldValue($data,$field)
{
if(is_array($data))
return $data[$field];
else if(($data instanceof TMap) || ($data instanceof TList))
return $data->itemAt($field);
else if(($data instanceof TComponent) && $data->canGetProperty($field))
{
$getter='get'.$field;
return $data->$getter();
}
else
throw new TInvalidDataValueException('basedatalist_datafield_invalid');
}
public function onSelectedIndexChanged($param)
{
$this->raiseEvent('OnSelectedIndexChanged',$this,$param);
}
}

class TDataList extends TBaseDataList implements INamingContainer, IRepeatInfoUser
{
private $_items=null;
private $_itemTemplate=null;
private $_alternatingItemTemplate=null;
private $_selectedItemTemplate=null;
private $_editItemTemplate=null;
private $_headerTemplate=null;
private $_footerTemplate=null;
private $_separatorTemplate=null;
private $_header=null;
private $_footer=null;
public function getItems()
{
if(!$this->_items)
$this->_items=new TDataListItemCollection;
return $this->_items;
}
public function getItemCount()
{
return $this->_items?$this->_items->getCount():0;
}
public function getItemTemplate()
{
return $this->_itemTemplate;
}
public function setItemTemplate($value)
{
if($value instanceof ITemplate || $value===null)
$this->_itemTemplate=$value;
else
throw new TInvalidDataTypeException('datalist_template_required','ItemTemplate');
}
public function getItemStyle()
{
if(($style=$this->getViewState('ItemStyle',null))===null)
{
$style=new TTableItemStyle;
$this->setViewState('ItemStyle',$style,null);
}
return $style;
}
public function getAlternatingItemTemplate()
{
return $this->_alternatingItemTemplate;
}
public function setAlternatingItemTemplate($value)
{
if($value instanceof ITemplate || $value===null)
$this->_alternatingItemTemplate=$value;
else
throw new TInvalidDataTypeException('datalist_template_required','AlternatingItemType');
}
public function getAlternatingItemStyle()
{
if(($style=$this->getViewState('AlternatingItemStyle',null))===null)
{
$style=new TTableItemStyle;
$this->setViewState('AlternatingItemStyle',$style,null);
}
return $style;
}
public function getSelectedItemTemplate()
{
return $this->_selectedItemTemplate;
}
public function setSelectedItemTemplate($value)
{
if($value instanceof ITemplate || $value===null)
$this->_selectedItemTemplate=$value;
else
throw new TInvalidDataTypeException('datalist_template_required','SelectedItemTemplate');
}
public function getSelectedItemStyle()
{
if(($style=$this->getViewState('SelectedItemStyle',null))===null)
{
$style=new TTableItemStyle;
$this->setViewState('SelectedItemStyle',$style,null);
}
return $style;
}
public function getEditItemTemplate()
{
return $this->_editItemTemplate;
}
public function setEditItemTemplate($value)
{
if($value instanceof ITemplate || $value===null)
$this->_editItemTemplate=$value;
else
throw new TInvalidDataTypeException('datalist_template_required','EditItemTemplate');
}
public function getEditItemStyle()
{
if(($style=$this->getViewState('EditItemStyle',null))===null)
{
$style=new TTableItemStyle;
$this->setViewState('EditItemStyle',$style,null);
}
return $style;
}
public function getHeaderTemplate()
{
return $this->_headerTemplate;
}
public function setHeaderTemplate($value)
{
if($value instanceof ITemplate || $value===null)
$this->_headerTemplate=$value;
else
throw new TInvalidDataTypeException('datalist_template_required','HeaderTemplate');
}
public function getHeaderStyle()
{
if(($style=$this->getViewState('HeaderStyle',null))===null)
{
$style=new TTableItemStyle;
$this->setViewState('HeaderStyle',$style,null);
}
return $style;
}
public function getHeader()
{
return $this->_header;
}
public function getFooterTemplate()
{
return $this->_footerTemplate;
}
public function setFooterTemplate($value)
{
if($value instanceof ITemplate || $value===null)
$this->_footerTemplate=$value;
else
throw new TInvalidDataTypeException('datalist_template_required','FooterTemplate');
}
public function getFooterStyle()
{
if(($style=$this->getViewState('FooterStyle',null))===null)
{
$style=new TTableItemStyle;
$this->setViewState('FooterStyle',$style,null);
}
return $style;
}
public function getFooter()
{
return $this->_footer;
}
public function getSeparatorTemplate()
{
return $this->_separatorTemplate;
}
public function setSeparatorTemplate($value)
{
if($value instanceof ITemplate || $value===null)
$this->_separatorTemplate=$value;
else
throw new TInvalidDataTypeException('datalist_template_required','SeparatorTemplate');
}
public function getSeparatorStyle()
{
if(($style=$this->getViewState('SeparatorStyle',null))===null)
{
$style=new TTableItemStyle;
$this->setViewState('SeparatorStyle',$style,null);
}
return $style;
}
public function getSelectedItemIndex()
{
return $this->getViewState('SelectedItemIndex',-1);
}
public function setSelectedItemIndex($value)
{
if(($value=TPropertyValue::ensureInteger($value))<0)
$value=-1;
if(($current=$this->getSelectedItemIndex())!==$value)
{
$this->setViewState('SelectedItemIndex',$value,-1);
$items=$this->getItems();
$itemCount=$items->getCount();
if($current>=0 && $current<$itemCount)
{
$item=$items->itemAt($current);
if($item->getItemType()!=='EditItem')
$item->setItemType($current%2?'AlternatingItem':'Item');
}
if($value>=0 && $value<$itemCount)
{
$item=$items->itemAt($value);
if($item->getItemType()!=='EditItem')
$item->setItemType('SelectedItem');
}
}
}
public function getSelectedItem()
{
$index=$this->getSelectedItemIndex();
$items=$this->getItems();
if($index>=0 && $index<$items->getCount())
return $items->itemAt($index);
else
return null;
}
public function getSelectedDataKey()
{
if($this->getDataKeyField()==='')
throw new TInvalidOperationException('datalist_datakeyfield_required');
$index=$this->getSelectedItemIndex();
$dataKeys=$this->getDataKeys();
if($index>=0 && $index<$dataKeys->getCount())
return $dataKeys->itemAt($index);
else
return null;
}
public function getEditItemIndex()
{
return $this->getViewState('EditItemIndex',-1);
}
public function setEditItemIndex($value)
{
if(($value=TPropertyValue::ensureInteger($value))<0)
$value=-1;
if(($current=$this->getEditItemIndex())!==$value)
{
$this->setViewState('EditItemIndex',$value,-1);
$items=$this->getItems();
$itemCount=$items->getCount();
if($current>=0 && $current<$itemCount)
$items->itemAt($current)->setItemType($current%2?'AlternatingItem':'Item');
if($value>=0 && $value<$itemCount)
$items->itemAt($value)->setItemType('EditItem');
}
}
public function getEditItem()
{
$index=$this->getEditItemIndex();
$items=$this->getItems();
if($index>=0 && $index<$items->getCount())
return $items->itemAt($index);
else
return null;
}
public function getShowHeader()
{
return $this->getViewState('ShowHeader',true);
}
public function setShowHeader($value)
{
$this->setViewState('ShowHeader',TPropertyValue::ensureBoolean($value),true);
}
public function getShowFooter()
{
return $this->getViewState('ShowFooter',true);
}
public function setShowFooter($value)
{
$this->setViewState('ShowFooter',TPropertyValue::ensureBoolean($value),true);
}
protected function getRepeatInfo()
{
if(($repeatInfo=$this->getViewState('RepeatInfo',null))===null)
{
$repeatInfo=new TRepeatInfo;
$this->setViewState('RepeatInfo',$repeatInfo,null);
}
return $repeatInfo;
}
public function getCaption()
{
return $this->getRepeatInfo()->getCaption();
}
public function setCaption($value)
{
$this->getRepeatInfo()->setCaption($value);
}
public function getCaptionAlign()
{
return $this->getRepeatInfo()->getCaptionAlign();
}
public function setCaptionAlign($value)
{
$this->getRepeatInfo()->setCaptionAlign($value);
}
public function getRepeatColumns()
{
return $this->getRepeatInfo()->getRepeatColumns();
}
public function setRepeatColumns($value)
{
$this->getRepeatInfo()->setRepeatColumns($value);
}
public function getRepeatDirection()
{
return $this->getRepeatInfo()->getRepeatDirection();
}
public function setRepeatDirection($value)
{
$this->getRepeatInfo()->setRepeatDirection($value);
}
public function getRepeatLayout()
{
return $this->getRepeatInfo()->getRepeatLayout();
}
public function setRepeatLayout($value)
{
$this->getRepeatInfo()->setRepeatLayout($value);
}
public function onBubbleEvent($sender,$param)
{
if($param instanceof TDataListCommandEventParameter)
{
$this->onItemCommand($param);
$command=$param->getCommandName();
if(strcasecmp($command,'select')===0)
{
$this->setSelectedItemIndex($param->getItem()->getItemIndex());
$this->onSelectedIndexChanged(null);
return true;
}
else if(strcasecmp($command,'edit')===0)
{
$this->onEditCommand($param);
return true;
}
else if(strcasecmp($command,'delete')===0)
{
$this->onDeleteCommand($param);
return true;
}
else if(strcasecmp($command,'update')===0)
{
$this->onUpdateCommand($param);
return true;
}
else if(strcasecmp($command,'cancel')===0)
{
$this->onCancelCommand($param);
return true;
}
}
return false;
}
public function onItemCreated($param)
{
$this->raiseEvent('OnItemCreated',$this,$param);
}
public function onItemDataBound($param)
{
$this->raiseEvent('OnItemDataBound',$this,$param);
}
public function onItemCommand($param)
{
$this->raiseEvent('OnItemCommand',$this,$param);
}
public function onEditCommand($param)
{
$this->raiseEvent('OnEditCommand',$this,$param);
}
public function onDeleteCommand($param)
{
$this->raiseEvent('OnDeleteCommand',$this,$param);
}
public function onUpdateCommand($param)
{
$this->raiseEvent('OnUpdateCommand',$this,$param);
}
public function onCancelCommand($param)
{
$this->raiseEvent('OnCancelCommand',$this,$param);
}
public function getHasHeader()
{
return ($this->getShowHeader() && $this->_headerTemplate!==null);
}
public function getHasFooter()
{
return ($this->getShowFooter() && $this->_footerTemplate!==null);
}
public function getHasSeparators()
{
return $this->_separatorTemplate!==null;
}
public function generateItemStyle($itemType,$index)
{
if(($item=$this->getItem($itemType,$index))!==null && $item->getHasStyle())
return $item->getStyle();
else
return null;
}
public function renderItem($writer,$repeatInfo,$itemType,$index)
{
$item=$this->getItem($itemType,$index);
if($repeatInfo->getRepeatLayout()==='Table')
$item->renderContents($writer);
else
$item->renderControl($writer);
}
private function getItem($itemType,$index)
{
switch($itemType)
{
case 'Header': return $this->getControls()->itemAt(0);
case 'Footer': return $this->getControls()->itemAt($this->getControls()->getCount()-1);
case 'Item':
case 'AlternatingItem':
case 'SelectedItem':
case 'EditItem':
return $this->getItems()->itemAt($index);
case 'Separator':
$i=$index+$index+1;
if($this->_headerTemplate!==null)
$i++;
return $this->getControls()->itemAt($i);
}
return null;
}
private function createItemInternal($itemIndex,$itemType,$dataBind,$dataItem)
{
$item=$this->createItem($itemIndex,$itemType);
$this->initializeItem($item);
$param=new TDataListItemEventParameter($item);
if($dataBind)
{
$item->setDataItem($dataItem);
$this->onItemCreated($param);
$this->getControls()->add($item);
$item->dataBind();
$this->onItemDataBound($param);
$item->setDataItem(null);
}
else
{
$this->onItemCreated($param);
$this->getControls()->add($item);
}
return $item;
}
protected function createItem($itemIndex,$itemType)
{
return new TDataListItem($itemIndex,$itemType);
}
protected function applyItemStyles()
{
$itemStyle=$this->getViewState('ItemStyle',null);
$alternatingItemStyle=new TTableItemStyle($itemStyle);
if(($style=$this->getViewState('AlternatingItemStyle',null))!==null)
$alternatingItemStyle->mergeWith($style);
$selectedItemStyle=$this->getViewState('SelectedItemStyle',null);
$editItemStyle=new TTableItemStyle($selectedItemStyle);
if(($style=$this->getViewState('EditItemStyle',null))!==null)
$editItemStyle->copyFrom($style);
$headerStyle=$this->getViewState('HeaderStyle',null);
$footerStyle=$this->getViewState('FooterStyle',null);
$pagerStyle=$this->getViewState('PagerStyle',null);
$separatorStyle=$this->getViewState('SeparatorStyle',null);
foreach($this->getControls() as $index=>$item)
{
switch($item->getItemType())
{
case 'Header':
if($headerStyle)
$item->getStyle()->mergeWith($headerStyle);
break;
case 'Footer':
if($footerStyle)
$item->getStyle()->mergeWith($footerStyle);
break;
case 'Separator':
if($separatorStyle)
$item->getStyle()->mergeWith($separatorStyle);
break;
case 'Item':
if($itemStyle)
$item->getStyle()->mergeWith($itemStyle);
break;
case 'AlternatingItem':
if($alternatingItemStyle)
$item->getStyle()->mergeWith($alternatingItemStyle);
break;
case 'SelectedItem':
if($index % 2==1)
{
if($itemStyle)
$item->getStyle()->mergeWith($itemStyle);
}
else
{
if($alternatingItemStyle)
$item->getStyle()->mergeWith($alternatingItemStyle);
}
if($selectedItemStyle)
$item->getStyle()->mergeWith($selectedItemStyle);
break;
case 'EditItem':
if($index % 2==1)
{
if($itemStyle)
$item->getStyle()->mergeWith($itemStyle);
}
else
{
if($alternatingItemStyle)
$item->getStyle()->mergeWith($alternatingItemStyle);
}
if($editItemStyle)
$item->getStyle()->mergeWith($editItemStyle);
break;
default:
break;
}
}
}
protected function initializeItem($item)
{
$template=null;
switch($item->getItemType())
{
case 'Header':
$template=$this->_headerTemplate;
break;
case 'Footer':
$template=$this->_footerTemplate;
break;
case 'Item':
$template=$this->_itemTemplate;
break;
case 'AlternatingItem':
if(($template=$this->_alternatingItemTemplate)===null)
$template=$this->_itemTemplate;
break;
case 'Separator':
$template=$this->_separatorTemplate;
break;
case 'SelectedItem':
if(($template=$this->_selectedItemTemplate)===null)
{
if(!($item->getItemIndex()%2) || ($template=$this->_alternatingItemTemplate)===null)
$template=$this->_itemTemplate;
}
break;
case 'EditItem':
if(($template=$this->_editItemTemplate)===null)
{
if($item->getItemIndex()!==$this->getSelectedItemIndex() || ($template=$this->_selectedItemTemplate)===null)
if(!($item->getItemIndex()%2) || ($template=$this->_alternatingItemTemplate)===null)
$template=$this->_itemTemplate;
}
break;
default:
break;
}
if($template!==null)
$template->instantiateIn($item);
}
public function saveState()
{
parent::saveState();
if($this->_items)
$this->setViewState('ItemCount',$this->_items->getCount(),0);
else
$this->clearViewState('ItemCount');
}
public function loadState()
{
parent::loadState();
if(!$this->getIsDataBound())
$this->restoreItemsFromViewState();
$this->clearViewState('ItemCount');
}
public function reset()
{
$this->getControls()->clear();
$this->getItems()->clear();
$this->_header=null;
$this->_footer=null;
}
protected function restoreItemsFromViewState()
{
$this->reset();
if(($itemCount=$this->getViewState('ItemCount',0))>0)
{
$items=$this->getItems();
$selectedIndex=$this->getSelectedItemIndex();
$editIndex=$this->getEditItemIndex();
if($this->_headerTemplate!==null)
$this->_header=$this->createItemInternal(-1,'Header',false,null);
$hasSeparator=$this->_separatorTemplate!==null;
for($i=0;$i<$itemCount;++$i)
{
if($hasSeparator && $i>0)
$this->createItemInternal($i-1,'Separator',false,null);
if($i===$editIndex)
$itemType='EditItem';
else if($i===$selectedIndex)
$itemType='SelectedItem';
else
$itemType=$i%2?'AlternatingItem':'Item';
$items->add($this->createItemInternal($i,$itemType,false,null));
}
if($this->_footerTemplate!==null)
$this->_footer=$this->createItemInternal(-1,'Footer',false,null);
}
$this->clearChildState();
}
protected function performDataBinding($data)
{
$this->reset();
$keys=$this->getDataKeys();
$keys->clear();
$keyField=$this->getDataKeyField();
$itemIndex=0;
$items=$this->getItems();
$hasSeparator=$this->_separatorTemplate!==null;
$selectedIndex=$this->getSelectedItemIndex();
$editIndex=$this->getEditItemIndex();
foreach($data as $dataItem)
{
if($keyField!=='')
$keys->add($this->getDataFieldValue($dataItem,$keyField));
if($itemIndex===0 && $this->_headerTemplate!==null)
$this->_header=$this->createItemInternal(-1,'Header',true,null);
if($hasSeparator && $itemIndex>0)
$this->createItemInternal($itemIndex-1,'Separator',true,null);
if($itemIndex===$editIndex)
$itemType='EditItem';
else if($itemIndex===$selectedIndex)
$itemType='SelectedItem';
else
$itemType=$itemIndex%2?'AlternatingItem':'Item';
$items->add($this->createItemInternal($itemIndex,$itemType,true,$dataItem));
$itemIndex++;
}
if($itemIndex>0 && $this->_footerTemplate!==null)
$this->_footer=$this->createItemInternal(-1,'Footer',true,null);
$this->setViewState('ItemCount',$itemIndex,0);
}
protected function render($writer)
{
if($this->getHasControls())
{
$this->applyItemStyles();
$repeatInfo=$this->getRepeatInfo();
$repeatInfo->renderRepeater($writer,$this);
}
}
}
class TDataListItemEventParameter extends TEventParameter
{
private $_item=null;
public function __construct(TDataListItem $item)
{
$this->_item=$item;
}
public function getItem()
{
return $this->_item;
}
}
class TDataListCommandEventParameter extends TCommandEventParameter
{
private $_item=null;
private $_source=null;
public function __construct($item,$source,TCommandEventParameter $param)
{
$this->_item=$item;
$this->_source=$source;
parent::__construct($param->getCommandName(),$param->getCommandParameter());
}
public function getItem()
{
return $this->_item;
}
public function getCommandSource()
{
return $this->_source;
}
}
class TDataListItem extends TWebControl implements INamingContainer
{
private $_itemIndex='';
private $_itemType='';
private $_dataItem=null;
public function __construct($itemIndex,$itemType)
{
$this->_itemIndex=$itemIndex;
$this->setItemType($itemType);
}
protected function createStyle()
{
return new TTableItemStyle;
}
public function getItemType()
{
return $this->_itemType;
}
public function setItemType($value)
{
$this->_itemType=TPropertyValue::ensureEnum($value,'Header','Footer','Item','AlternatingItem','SelectedItem','EditItem','Separator','Pager');
}
public function getItemIndex()
{
return $this->_itemIndex;
}
public function getDataItem()
{
return $this->_dataItem;
}
public function setDataItem($value)
{
$this->_dataItem=$value;
}
public function onBubbleEvent($sender,$param)
{
if($param instanceof TCommandEventParameter)
{
$this->raiseBubbleEvent($this,new TDataListCommandEventParameter($this,$sender,$param));
return true;
}
else
return false;
}
}
class TDataListItemCollection extends TList
{
public function insertAt($index,$item)
{
if($item instanceof TDataListItem)
parent::insertAt($index,$item);
else
throw new TInvalidDataTypeException('datalistitemcollection_datalistitem_required');
}
}

class TDataGrid extends TBaseDataList
{
private $_columns=null;
private $_autoColumns=null;
private $_items=null;
private $_header=null;
private $_footer=null;
private $_pager=null;
private $_pagedDataSource=null;
protected function getTagName()
{
return 'table';
}
public function addParsedObject($object)
{
if($object instanceof TDataGridColumn)
$this->getColumns()->add($object);
}
public function getColumns()
{
if(!$this->_columns)
$this->_columns=new TDataGridColumnCollection;
return $this->_columns;
}
public function getAutoColumns()
{
if(!$this->_autoColumns)
$this->_autoColumns=new TDataGridColumnCollection;
return $this->_autoColumns;
}
public function getItems()
{
if(!$this->_items)
$this->_items=new TDataGridItemCollection;
return $this->_items;
}
protected function createStyle()
{
$style=new TTableStyle;
$style->setGridLines('Both');
$style->setCellSpacing(0);
return $style;
}
public function getBackImageUrl()
{
return $this->getStyle()->getBackImageUrl();
}
public function setBackImageUrl($value)
{
$this->getStyle()->setBackImageUrl($value);
}
public function getAlternatingItemStyle()
{
if(($style=$this->getViewState('AlternatingItemStyle',null))===null)
{
$style=new TTableItemStyle;
$this->setViewState('AlternatingItemStyle',$style,null);
}
return $style;
}
public function getItemStyle()
{
if(($style=$this->getViewState('ItemStyle',null))===null)
{
$style=new TTableItemStyle;
$this->setViewState('ItemStyle',$style,null);
}
return $style;
}
public function getSelectedItemStyle()
{
if(($style=$this->getViewState('SelectedItemStyle',null))===null)
{
$style=new TTableItemStyle;
$this->setViewState('SelectedItemStyle',$style,null);
}
return $style;
}
public function getEditItemStyle()
{
if(($style=$this->getViewState('EditItemStyle',null))===null)
{
$style=new TTableItemStyle;
$this->setViewState('EditItemStyle',$style,null);
}
return $style;
}
public function getHeaderStyle()
{
if(($style=$this->getViewState('HeaderStyle',null))===null)
{
$style=new TTableItemStyle;
$this->setViewState('HeaderStyle',$style,null);
}
return $style;
}
public function getHeader()
{
return $this->_header;
}
public function getFooterStyle()
{
if(($style=$this->getViewState('FooterStyle',null))===null)
{
$style=new TTableItemStyle;
$this->setViewState('FooterStyle',$style,null);
}
return $style;
}
public function getFooter()
{
return $this->_footer;
}
public function getPagerStyle()
{
if(($style=$this->getViewState('PagerStyle',null))===null)
{
$style=new TDataGridPagerStyle;
$this->setViewState('PagerStyle',$style,null);
}
return $style;
}
public function getPager()
{
return $this->_pager;
}
public function getSelectedItem()
{
$index=$this->getSelectedItemIndex();
$items=$this->getItems();
if($index>=0 && $index<$items->getCount())
return $items->itemAt($index);
else
return null;
}
public function getSelectedItemIndex()
{
return $this->getViewState('SelectedItemIndex',-1);
}
public function setSelectedItemIndex($value)
{
if(($value=TPropertyValue::ensureInteger($value))<0)
$value=-1;
if(($current=$this->getSelectedItemIndex())!==$value)
{
$this->setViewState('SelectedItemIndex',$value,-1);
$items=$this->getItems();
$itemCount=$items->getCount();
if($current>=0 && $current<$itemCount)
{
$item=$items->itemAt($current);
if($item->getItemType()!=='EditItem')
$item->setItemType($current%2?'AlternatingItem':'Item');
}
if($value>=0 && $value<$itemCount)
{
$item=$items->itemAt($value);
if($item->getItemType()!=='EditItem')
$item->setItemType('SelectedItem');
}
}
}
public function getEditItem()
{
$index=$this->getEditItemIndex();
$items=$this->getItems();
if($index>=0 && $index<$items->getCount())
return $items->itemAt($index);
else
return null;
}
public function getEditItemIndex()
{
return $this->getViewState('EditItemIndex',-1);
}
public function setEditItemIndex($value)
{
if(($value=TPropertyValue::ensureInteger($value))<0)
$value=-1;
if(($current=$this->getEditItemIndex())!==$value)
{
$this->setViewState('EditItemIndex',$value,-1);
$items=$this->getItems();
$itemCount=$items->getCount();
if($current>=0 && $current<$itemCount)
$items->itemAt($current)->setItemType($current%2?'AlternatingItem':'Item');
if($value>=0 && $value<$itemCount)
$items->itemAt($value)->setItemType('EditItem');
}
}
public function getAllowCustomPaging()
{
return $this->getViewState('AllowCustomPaging',false);
}
public function setAllowCustomPaging($value)
{
$this->setViewState('AllowCustomPaging',TPropertyValue::ensureBoolean($value),false);
}
public function getAllowPaging()
{
return $this->getViewState('AllowPaging',false);
}
public function setAllowPaging($value)
{
$this->setViewState('AllowPaging',TPropertyValue::ensureBoolean($value),false);
}
public function getAllowSorting()
{
return $this->getViewState('AllowSorting',false);
}
public function setAllowSorting($value)
{
$this->setViewState('AllowSorting',TPropertyValue::ensureBoolean($value),false);
}
public function getAutoGenerateColumns()
{
return $this->getViewState('AutoGenerateColumns',true);
}
public function setAutoGenerateColumns($value)
{
$this->setViewState('AutoGenerateColumns',TPropertyValue::ensureBoolean($value),true);
}
public function getCurrentPageIndex()
{
return $this->getViewState('CurrentPageIndex',0);
}
public function setCurrentPageIndex($value)
{
if(($value=TPropertyValue::ensureInteger($value))<0)
throw new TInvalidDataValueException('datagrid_currentpageindex_invalid');
$this->setViewState('CurrentPageIndex',$value,0);
}
public function getPageSize()
{
return $this->getViewState('PageSize',10);
}
public function setPageSize($value)
{
if(($value=TPropertyValue::ensureInteger($value))<1)
throw new TInvalidDataValueException('datagrid_pagesize_invalid');
$this->setViewState('PageSize',TPropertyValue::ensureInteger($value),10);
}
public function getPageCount()
{
if($this->_pagedDataSource)
return $this->_pagedDataSource->getPageCount();
return $this->getViewState('PageCount',0);
}
public function getVirtualCount()
{
return $this->getViewState('VirtualCount',0);
}
public function setVirtualCount($value)
{
if(($value=TPropertyValue::ensureInteger($value))<0)
throw new TInvalidDataValueException('datagrid_virtualcount_invalid');
$this->setViewState('VirtualCount',$value,0);
}
public function getShowHeader()
{
return $this->getViewState('ShowHeader',true);
}
public function setShowHeader($value)
{
$this->setViewState('ShowHeader',TPropertyValue::ensureBoolean($value),true);
}
public function getShowFooter()
{
return $this->getViewState('ShowFooter',false);
}
public function setShowFooter($value)
{
$this->setViewState('ShowFooter',TPropertyValue::ensureBoolean($value),false);
}
public function onBubbleEvent($sender,$param)
{
if($param instanceof TDataGridCommandEventParameter)
{
$this->onItemCommand($param);
$command=$param->getCommandName();
if(strcasecmp($command,'select')===0)
{
$this->setSelectedIndex($param->getItem()->getItemIndex());
$this->onSelectedIndexChanged(null);
return true;
}
else if(strcasecmp($command,'edit')===0)
{
$this->onEditCommand($param);
return true;
}
else if(strcasecmp($command,'delete')===0)
{
$this->onDeleteCommand($param);
return true;
}
else if(strcasecmp($command,'update')===0)
{
$this->onUpdateCommand($param);
return true;
}
else if(strcasecmp($command,'cancel')===0)
{
$this->onCancelCommand($param);
return true;
}
else if(strcasecmp($command,'sort')===0)
{
$this->onSortCommand(new TDataGridSortCommandEventParameter($sender,$param->getCommandParameter()));
return true;
}
else if(strcasecmp($command,'page')===0)
{
$p=$param->getCommandParameter();
if(strcasecmp($p,'next'))
$pageIndex=$this->getCurrentPageIndex()+1;
else if(strcasecmp($p,'prev'))
$pageIndex=$this->getCurrentPageIndex()-1;
else
$pageIndex=TPropertyValue::ensureInteger($p)-1;
$this->onPageIndexChanged(new TDataGridPageChangedEventParameter($sender,$pageIndex));
return true;
}
}
return false;
}
public function onCancelCommand($param)
{
$this->raiseEvent('OnCancelCommand',$this,$param);
}
public function onDeleteCommand($param)
{
$this->raiseEvent('OnDeleteCommand',$this,$param);
}
public function onEditCommand($param)
{
$this->raiseEvent('OnEditCommand',$this,$param);
}
public function onItemCommand($param)
{
$this->raiseEvent('OnItemCommand',$this,$param);
}
public function onSortCommand($param)
{
$this->raiseEvent('OnSortCommand',$this,$param);
}
public function onUpdateCommand($param)
{
$this->raiseEvent('OnUpdateCommand',$this,$param);
}
public function onItemCreated($param)
{
$this->raiseEvent('OnItemCreated',$this,$param);
}
public function onItemDataBound($param)
{
$this->raiseEvent('OnItemDataBound',$this,$param);
}
public function onPageIndexChanged($param)
{
$this->raiseEvent('OnPageIndexChanged',$this,$param);
}
public function saveState()
{
parent::saveState();
if(!$this->getEnableViewState(true))
return;
if($this->_items)
$this->setViewState('ItemCount',$this->_items->getCount(),0);
else
$this->clearViewState('ItemCount');
if($this->_autoColumns)
{
$state=array();
foreach($this->_autoColumns as $column)
$state[]=$column->saveState();
$this->setViewState('AutoColumns',$state,array());
}
else
$this->clearViewState('AutoColumns');
if($this->_columns)
{
$state=array();
foreach($this->_columns as $column)
$state[]=$column->saveState();
$this->setViewState('Columns',$state,array());
}
else
$this->clearViewState('Columns');
}
public function loadState()
{
parent::loadState();
if(!$this->getEnableViewState(true))
return;
if(!$this->getIsDataBound())
{
$state=$this->getViewState('AutoColumns',array());
if(!empty($state))
{
$this->_autoColumns=new TDataGridColumnCollection;
foreach($state as $st)
{
$column=new TBoundColumn;
$column->loadState($st);
$this->_autoColumns->add($column);
}
}
else
$this->_autoColumns=null;
$state=$this->getViewState('Columns',array());
if($this->_columns)
{
$i=0;
foreach($this->_columns as $column)
{
$column->loadState($state[$i]);
$i++;
}
}
$this->restoreGridFromViewState();
}
$this->clearViewState('ItemCount');
}
private function createPagedDataSource()
{
$ds=new TPagedDataSource;
$ds->setCurrentPageIndex($this->getCurrentPageIndex());
$ds->setPageSize($this->getPageSize());
$ds->setAllowPaging($this->getAllowPaging());
$ds->setAllowCustomPaging($this->getAllowCustomPaging());
$ds->setVirtualCount($this->getVirtualCount());
return $ds;
}
public function reset()
{
$this->getControls()->clear();
$this->getItems()->clear();
$this->_header=null;
$this->_footer=null;
}
protected function restoreGridFromViewState()
{
$this->reset();
if(($itemCount=$this->getViewState('ItemCount',0))<=0)
return;
$this->_pagedDataSource=$ds=$this->createPagedDataSource();
$allowPaging=$ds->getAllowPaging();
if($allowPaging)
$ds->setDataSource(new TDummyDataSource($itemCount));
else
$ds->setDataSource(new TDummyDataSource($this->getViewState('DataSourceCount',0)));
$columns=new TList($this->getColumns());
$columns->mergeWith($this->_autoColumns);
if($columns->getCount()>0)
{
foreach($columns as $column)
$column->initialize();
if($allowPaging)
$this->createPager(-1,-1,$columnCount,$ds);
$this->createItemInternal(-1,-1,'Header',false,null,$columns);
$selectedIndex=$this->getSelectedItemIndex();
$editIndex=$this->getEditItemIndex();
$index=0;
$dsIndex=$ds->getAllowPaging()?$ds->getFirstIndexInPage():0;
foreach($ds as $data)
{
if($index===$editIndex)
$itemType='EditItem';
else if($index===$selectedIndex)
$itemType='SelectedItem';
else if($index % 2)
$itemType='AlternatingItem';
else
$itemType='Item';
$items->add($this->createItemInternal($index,$dsIndex,$itemType,false,null,$columns));
$index++;
$dsIndex++;
}
$this->createItemInternal(-1,-1,'Footer',false,null,$columns);
if($allowPaging)
$this->createPager(-1,-1,$columnCount,$ds);
}
$this->_pagedDataSource=null;
}
protected function performDataBinding($data)
{
$this->reset();
$keys=$this->getDataKeys();
$keys->clear();
$keyField=$this->getDataKeyField();
$this->_pagedDataSource=$ds=$this->createPagedDataSource();
$ds->setDataSource($data);
$allowPaging=$ds->getAllowPaging();
if($allowPaging && $ds->getCurrentPageIndex()>=$ds->getPageCount())
throw new TInvalidDataValueException('datagrid_currentpageindex_invalid');
$columns=new TList($this->getColumns());
if($this->getAutoGenerateColumns())
{
$autoColumns=$this->createAutoColumns($ds);
$columns->mergeWith($autoColumns);
}
$items=$this->getItems();
if(($columnCount=$columns->getCount())>0)
{
foreach($columns as $column)
$column->initialize();
$allowPaging=$ds->getAllowPaging();
if($allowPaging)
$this->createPager(-1,-1,$columnCount,$ds);
$this->createItemInternal(-1,-1,'Header',true,null,$columns);
$selectedIndex=$this->getSelectedItemIndex();
$editIndex=$this->getEditItemIndex();
$index=0;
$dsIndex=$ds->getAllowPaging()?$ds->getFirstIndexInPage():0;
foreach($ds as $data)
{
if($keyField!=='')
$keys->add($this->getDataFieldValue($data,$keyField));
if($index===$editIndex)
$itemType='EditItem';
else if($index===$selectedIndex)
$itemType='SelectedItem';
else if($index % 2)
$itemType='AlternatingItem';
else
$itemType='Item';
$items->add($this->createItemInternal($index,$dsIndex,$itemType,true,$data,$columns));
$index++;
$dsIndex++;
}
$this->createItemInternal(-1,-1,'Footer',true,null,$columns);
if($allowPaging)
$this->createPager(-1,-1,$columnCount,$ds);
$this->setViewState('ItemCount',$index,0);
$this->setViewState('PageCount',$ds->getPageCount(),0);
$this->setViewState('DataSourceCount',$ds->getDataSourceCount(),0);
}
else
{
$this->clearViewState('ItemCount');
$this->clearViewState('PageCount');
$this->clearViewState('DataSourceCount');
}
$this->_pagedDataSource=null;
}
protected function createItem($itemIndex,$dataSourceIndex,$itemType)
{
return new TDataGridItem($itemIndex,$dataSourceIndex,$itemType);
}
private function createItemInternal($itemIndex,$dataSourceIndex,$itemType,$dataBind,$dataItem,$columns)
{
$item=$this->createItem($itemIndex,$dataSourceIndex,$itemType);
$this->initializeItem($item,$columns);
$param=new TDataGridItemEventParameter($item);
if($dataBind)
{
$item->setDataItem($dataItem);
$this->onItemCreated($param);
$this->getControls()->add($item);
$item->dataBind();
$this->onItemDataBound($param);
$item->setDataItem(null);
}
else
{
$this->onItemCreated($param);
$this->getControls()->add($item);
}
return $item;
}
protected function initializeItem($item,$columns)
{
$cells=$item->getCells();
$itemType=$item->getItemType();
$index=0;
foreach($columns as $column)
{
if($itemType==='Header')
$cell=new TTableHeaderCell;
else
$cell=new TTableCell;
$column->initializeCell($cell,$index,$itemType);
$cells->add($cell);
$index++;
}
}
private function createPager($itemIndex,$dataSourceIndex,$columnSpan,$pagedDataSource)
{
$item=$this->createItem($itemIndex,$dataSourceIndex,'Pager');
$this->initializePager($item,$columnSpan,$pagedDataSource);
$this->onItemCreated(new TDataGridItemEventParameter($item));
$this->getControls()->add($item);
return $item;
}
protected function initializePager($pager,$columnSpan,$pagedDataSource)
{
$cell=new TTableCell;
if($columnSpan>1)
$cell->setColumnSpan($columnSpan);
$this->buildPager($cell,$pagedDataSource);
$pager->getCells()->add($cell);
}
protected function buildPager($cell,$dataSource)
{
switch($this->getPagerStyle()->getMode())
{
case 'NextPrev':
$this->buildNextPrevPager($cell,$dataSource);
break;
case 'Numeric':
$this->buildNumericPager($cell,$dataSource);
break;
}
}
protected function buildNextPrevPager($cell,$dataSource)
{
$style=$this->getPagerStyle();
$controls=$cell->getControls();
if($dataSource->getIsFirstPage())
{
$label=new TLabel;
$label->setText($style->getPrevPageText());
$controls->add($label);
}
else
{
$button=new TLinkButton;
$button->setText($style->getPrevPageText());
$button->setCommandName('page');
$button->setCommandParameter('prev');
$button->setCausesValidation(false);
$controls->add($button);
}
$controls->add('&nbsp;');
if($dataSource->getIsLastPage())
{
$label=new TLabel;
$label->setText($style->getNextPageText());
$controls->add($label);
}
else
{
$button=new TLinkButton;
$button->setText($style->getNextPageText());
$button->setCommandName('page');
$button->setCommandParameter('next');
$button->setCausesValidation(false);
$controls->add($button);
}
}
protected function buildNumericPager($cell,$dataSource)
{
$style=$this->getPagerStyle();
$controls=$cell->getControls();
$pageCount=$dataSource->getPageCount();
$pageIndex=$dataSource->getCurrentPageIndex()+1;
$maxButtonCount=$style->getPageButtonCount();
$buttonCount=$maxButtonCount>$pageCount?$pageCount:$maxButtonCount;
$startPageIndex=1;
$endPageIndex=$buttonCount;
if($pageIndex>$endPageIndex)
{
$startPageIndex=((int)($pageIndex/$maxButtonCount))*$maxButtonCount+1;
if(($endPageIndex=$startPageIndex+$maxButtonCount-1)>$pageCount)
$endPageIndex=$pageCount;
if($endPageIndex-$startPageIndex+1<$maxButtonCount)
{
if(($startPageIndex=$endPageIndex-$maxButtonCount+1)<1)
$startPageIndex=1;
}
}
if($startPageIndex>1)
{
$button=new TLinkButton;
$button->setText('...');
$button->setCommandName('page');
$button->setCommandParameter($startPageIndex-1);
$button->setCausesValidation(false);
$controls->add($button);
$controls->add('&nbsp;');
}
for($i=$startPageIndex;$i<=$endPageIndex;++$i)
{
if($i===$pageIndex)
{
$label=new TLabel;
$label->setText("$i");
$controls->add($label);
}
else
{
$button=new TLinkButton;
$button->setText("$i");
$button->setCommandName('page');
$button->setCommandParameter($i);
$button->setCausesValidation(false);
$controls->add($button);
}
if($i<$endPageIndex)
$controls->add('&nbsp;');
}
if($pageCount>$endPageIndex)
{
$controls->add('&nbsp;');
$button=new TLinkButton;
$button->setText('...');
$button->setCommandName('page');
$button->setCommandParameter($endPageIndex+1);
$button->setCausesValidation(false);
$controls->add($button);
}
}
protected function createAutoColumns($dataSource)
{
if(!$dataSource)
return null;
$autoColumns=$this->getAutoColumns();
$autoColumns->clear();
foreach($dataSource as $row)
{
foreach($row as $key=>$value)
{
$column=new TBoundColumn;
if(is_string($key))
{
$column->setHeaderText($key);
$column->setDataField($key);
$column->setSortExpression($key);
$column->setOwner($this);
$autoColumns->add($column);
}
else
{
$column->setHeaderText('Item');
$column->setDataField($key);
$column->setSortExpression('Item');
$column->setOwner($this);
$autoColumns->add($column);
}
}
break;
}
return $autoColumns;
}
protected function applyItemStyles()
{
$itemStyle=$this->getViewState('ItemStyle',null);
$alternatingItemStyle=new TTableItemStyle($itemStyle);
if(($style=$this->getViewState('AlternatingItemStyle',null))!==null)
$alternatingItemStyle->mergeWith($style);
$selectedItemStyle=$this->getViewState('SelectedItemStyle',null);
$editItemStyle=new TTableItemStyle($selectedItemStyle);
if(($style=$this->getViewState('EditItemStyle',null))!==null)
$editItemStyle->copyFrom($style);
$headerStyle=$this->getViewState('HeaderStyle',null);
$footerStyle=$this->getViewState('FooterStyle',null);
$pagerStyle=$this->getViewState('PagerStyle',null);
$separatorStyle=$this->getViewState('SeparatorStyle',null);
foreach($this->getControls() as $index=>$item)
{
$itemType=$item->getItemType();
switch($itemType)
{
case 'Header':
if($headerStyle)
$item->getStyle()->mergeWith($headerStyle);
if(!$this->getShowHeader())
$item->setVisible(false);
break;
case 'Footer':
if($footerStyle)
$item->getStyle()->mergeWith($footerStyle);
if(!$this->getShowFooter())
$item->setVisible(false);
break;
case 'Separator':
if($separatorStyle)
$item->getStyle()->mergeWith($separatorStyle);
break;
case 'Item':
if($itemStyle)
$item->getStyle()->mergeWith($itemStyle);
break;
case 'AlternatingItem':
if($alternatingItemStyle)
$item->getStyle()->mergeWith($alternatingItemStyle);
break;
case 'SelectedItem':
if($index % 2==1)
{
if($itemStyle)
$item->getStyle()->mergeWith($itemStyle);
}
else
{
if($alternatingItemStyle)
$item->getStyle()->mergeWith($alternatingItemStyle);
}
if($selectedItemStyle)
$item->getStyle()->mergeWith($selectedItemStyle);
break;
case 'EditItem':
if($index % 2==1)
{
if($itemStyle)
$item->getStyle()->mergeWith($itemStyle);
}
else
{
if($alternatingItemStyle)
$item->getStyle()->mergeWith($alternatingItemStyle);
}
if($editItemStyle)
$item->getStyle()->mergeWith($editItemStyle);
break;
case 'Pager':
if($pagerStyle)
{
$item->getStyle()->mergeWith($pagerStyle);
$mode=$pagerStyle->getMode();
if($index===0)
{
if($mode==='Bottom')
$item->setVisible(false);
}
else
{
if($mode==='Top')
$item->setVisible(false);
}
}
break;
default:
break;
}
if($this->_columns && $itemType!=='Pager')
{
$n=$this->_columns->getCount();
$cells=$item->getCells();
for($i=0;$i<$n;++$i)
{
$cell=$cells->itemAt($i);
$column=$this->_columns->itemAt($i);
if(!$column->getVisible())
$cell->setVisible(false);
else
{
if($itemType==='Header')
$style=$column->getHeaderStyle(false);
else if($itemType==='Footer')
$style=$column->getFooterStyle(false);
else
$style=$column->getItemStyle(false);
if($style!==null)
$cell->getStyle()->mergeWith($style);
}
}
}
}
}
protected function renderContents($writer)
{
if($this->getHasControls())
{
$this->applyItemStyles();
parent::renderContents($writer);
}
}
}
class TDataGridItemEventParameter extends TEventParameter
{
private $_item=null;
public function __construct(TDataGridItem $item)
{
$this->_item=$item;
}
public function getItem()
{
return $this->_item;
}
}
class TDataGridCommandEventParameter extends TCommandEventParameter
{
private $_item=null;
private $_source=null;
public function __construct($item,$source,TCommandEventParameter $param)
{
$this->_item=$item;
$this->_source=$source;
parent::__construct($param->getCommandName(),$param->getCommandParameter());
}
public function getItem()
{
return $this->_item;
}
public function getCommandSource()
{
return $this->_source;
}
}
class TDataGridSortCommandEventParameter extends TEventParameter
{
private $_sortExpression='';
private $_source=null;
public function __construct($source,TDataGridCommandEventParameter $param)
{
$this->_source=$source;
$this->_sortExpression=$param->getCommandParameter();
}
public function getCommandSource()
{
return $this->_source;
}
public function getSortExpression()
{
return $this->_sortExpression;
}
}
class TDataGridPageChangedEventParameter extends TEventParameter
{
private $_newIndex;
private $_source=null;
public function __construct($source,$newPageIndex)
{
$this->_source=$source;
$this->_newIndex=$newPageIndex;
}
public function getCommandSource()
{
return $this->_source;
}
public function getNewPageIndex()
{
return $this->_newIndex;
}
}
class TDataGridItem extends TTableRow implements INamingContainer
{
private $_itemIndex='';
private $_dataSourceIndex=0;
private $_itemType='';
private $_dataItem=null;
public function __construct($itemIndex,$dataSourceIndex,$itemType)
{
$this->_itemIndex=$itemIndex;
$this->_dataSourceIndex=$dataSourceIndex;
$this->setItemType($itemType);
}
public function getItemType()
{
return $this->_itemType;
}
public function setItemType($value)
{
$this->_itemType=TPropertyValue::ensureEnum($value,'Header','Footer','Item','AlternatingItem','SelectedItem','EditItem','Separator','Pager');
}
public function getItemIndex()
{
return $this->_itemIndex;
}
public function getDataSourceIndex()
{
return $this->_dataSourceIndex;
}
public function getDataItem()
{
return $this->_dataItem;
}
public function setDataItem($value)
{
$this->_dataItem=$value;
}
public function onBubbleEvent($sender,$param)
{
if($param instanceof TCommandEventParameter)
{
$this->raiseBubbleEvent($this,new TDataGridCommandEventParameter($this,$sender,$param));
return true;
}
else
return false;
}
}
class TDataGridItemCollection extends TList
{
public function insertAt($index,$item)
{
if($item instanceof TDataGridItem)
parent::insertAt($index,$item);
else
throw new TInvalidDataTypeException('datagriditemcollection_datagriditem_required');
}
}
class TDataGridColumnCollection extends TList
{
public function insertAt($index,$item)
{
if($item instanceof TDataGridColumn)
parent::insertAt($index,$item);
else
throw new TInvalidDataTypeException('datagridcolumncollection_datagridcolumn_required');
}
}
class TDataGridPagerStyle extends TTableItemStyle
{
private $_mode=null;
private $_nextText=null;
private $_prevText=null;
private $_buttonCount=null;
private $_position=null;
private $_visible=null;
public function getMode()
{
return $this->_mode===null?'NextPrev':$this->_mode;
}
public function setMode($value)
{
$this->_mode=TPropertyValue::ensureEnum($value,'NextPrev','Numeric');
}
public function getNextPageText()
{
return $this->_nextText===null?'>':$this->_nextText;
}
public function setNextPageText($value)
{
$this->_nextText=$value;
}
public function getPrevPageText()
{
return $this->_prevText===null?'<':$this->_prevText;
}
public function setPrevPageText($value)
{
$this->_prevText=$value;
}
public function getPageButtonCount()
{
return $this->_buttonCount===null?10:$this->_buttonCount;
}
public function setPageButtonCount($value)
{
if(($value=TPropertyValue::ensureInteger($value))<1)
throw new TInvalidDataValueException('datagridpagerstyle_pagebuttoncount_invalid');
$this->_buttonCount=$value;
}
public function getPosition()
{
return $this->_position===null?'Bottom':$this->_position;
}
public function setPosition($value)
{
$this->_position=TPropertyValue::ensureEnum($value,'Bottom','Top','TopAndBottom');
}
public function getVisible()
{
return $this->_visible===null?true:$this->_visible;
}
public function setVisible($value)
{
$this->_visible=TPropertyValue::ensureBoolean($value);
}
public function reset()
{
parent::reset();
$this->_visible=null;
$this->_position=null;
$this->_buttonCount=null;
$this->_prevText=null;
$this->_nextText=null;
$this->_mode=null;
}
public function copyFrom($style)
{
parent::copyFrom($style);
if($style instanceof TDataGridPagerStyle)
{
$this->_visible=$style->_visible;
$this->_position=$style->_position;
$this->_buttonCount=$style->_buttonCount;
$this->_prevText=$style->_prevText;
$this->_nextText=$style->_nextText;
$this->_mode=$style->_mode;
}
}
public function mergeWith($style)
{
parent::mergeWith($style);
if($style instanceof TDataGridPagerStyle)
{
if($style->_visible!==null)
$this->_visible=$style->_visible;
if($style->_position!==null)
$this->_position=$style->_position;
if($style->_buttonCount!==null)
$this->_buttonCount=$style->_buttonCount;
if($style->_prevText!==null)
$this->_prevText=$style->_prevText;
if($style->_nextText!==null)
$this->_nextText=$style->_nextText;
if($style->_mode!==null)
$this->_mode=$style->_mode;
}
}
}

abstract class TDataGridColumn extends TComponent
{
private $_owner=null;
private $_viewState=array();
public function getHeaderText()
{
return $this->getViewState('HeaderText','');
}
public function setHeaderText($value)
{
$this->setViewState('HeaderText',$value,'');
$this->onColumnChanged();
}
public function getHeaderImageUrl()
{
return $this->getViewState('HeaderImageUrl','');
}
public function setHeaderImageUrl($value)
{
$this->setViewState('HeaderImageUrl',$value,'');
$this->onColumnChanged();
}
public function getHeaderStyle($createStyle=true)
{
if(($style=$this->getViewState('HeaderStyle',null))===null && $createStyle)
{
$style=new TTableItemStyle;
$this->setViewState('HeaderStyle',$style,null);
}
return $style;
}
public function getFooterText()
{
return $this->getViewState('FooterText','');
}
public function setFooterText($value)
{
$this->setViewState('FooterText',$value,'');
$this->onColumnChanged();
}
public function getFooterStyle($createStyle=true)
{
if(($style=$this->getViewState('FooterStyle',null))===null && $createStyle)
{
$style=new TTableItemStyle;
$this->setViewState('FooterStyle',$style,null);
}
return $style;
}
public function getItemStyle($createStyle=true)
{
if(($style=$this->getViewState('ItemStyle',null))===null && $createStyle)
{
$style=new TTableItemStyle;
$this->setViewState('ItemStyle',$style,null);
}
return $style;
}
public function setSortExpression($value)
{
$this->setViewState('SortExpression',$value,'');
$this->onColumnChanged();
}
public function getSortExpression()
{
return $this->getViewState('SortExpression','');
}
public function getVisible($checkParents=true)
{
return $this->getViewState('Visible',true);
}
public function setVisible($value)
{
$this->setViewState('Visible',TPropertyValue::ensureBoolean($value),true);
$this->onColumnChanged();
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
public function loadState($state)
{
$this->_viewState=$state;
}
public function saveState()
{
return $this->_viewState;
}
public function getOwner()
{
return $this->_owner;
}
public function setOwner(TDataGrid $value)
{
$this->_owner=$value;
}
public function onColumnChanged()
{
if($this->_owner)
$this->_owner->onColumnsChanged();
}
public function initialize()
{
}
protected function getDataFieldValue($data,$field)
{
if(is_array($data))
return $data[$field];
else if($data instanceof TMap)
return $data->itemAt($field);
else if(($data instanceof TComponent) && $data->canGetProperty($field))
{
$getter='get'.$field;
return $data->$getter();
}
else
throw new TInvalidDataValueException('datagridcolumn_data_invalid');
}
public function initializeCell($cell,$columnIndex,$itemType)
{
switch($itemType)
{
case 'Header':
$sortExpression=$this->getSortExpression();
$allowSorting=$sortExpression!=='' && (!$this->_owner || $this->_owner->getAllowSorting());
if($allowSorting)
{
if(($url=$this->getHeaderImageUrl())!=='')
{
$button=Prado::createComponent('System.Web.UI.WebControls.TImageButton');
$button->setImageUrl($url);
$button->setCommandName('Sort');
$button->setCommandParameter($sortExpression);
$button->setCausesValidation(false);
$cell->getControls()->add($button);
}
else if(($text=$this->getHeaderText())!=='')
{
$button=Prado::createComponent('System.Web.UI.WebControls.TLinkButton');
$button->setText($text);
$button->setCommandName('Sort');
$button->setCommandParameter($sortExpression);
$button->setCausesValidation(false);
$cell->getControls()->add($button);
}
else
$cell->setText('&nbsp;');
}
else
{
if(($url=$this->getHeaderImageUrl())!=='')
{
$image=Prado::createComponent('System.Web.UI.WebControls.TImage');
$image->setImageUrl($url);
$cell->getControls()->add($image);
}
else
{
if(($text=$this->getHeaderText())==='')
$text='&nbsp;';
$cell->setText($text);
}
}
break;
case 'Footer':
if(($text=$this->getFooterText())==='')
$text='&nbsp;';
$cell->setText($text);
break;
}
}
protected function formatDataValue($formatString,$value)
{
return $formatString===''?TPropertyValue::ensureString($value):sprintf($formatString,$value);
}
}

class TBoundColumn extends TDataGridColumn
{
public function getDataField()
{
return $this->getViewState('DataField','');
}
public function setDataField($value)
{
$this->setViewState('DataField',$value,'');
$this->onColumnChanged();
}
public function getDataFormatString()
{
return $this->getViewState('DataFormatString','');
}
public function setDataFormatString($value)
{
$this->setViewState('DataFormatString',$value,'');
$this->onColumnChanged();
}
public function getReadOnly()
{
return $this->getViewState('ReadOnly',false);
}
public function setReadOnly($value)
{
$this->setViewState('ReadOnly',TPropertyValue::ensureBoolean($value),false);
$this->onColumnChanged();
}
public function initializeCell($cell,$columnIndex,$itemType)
{
parent::initializeCell($cell,$columnIndex,$itemType);
switch($itemType)
{
case 'EditItem':
$control=$cell;
if(!$this->getReadOnly())
{
$textBox=Prado::createComponent('System.Web.UI.WebControls.TTextBox');
$cell->getControls()->add($textBox);
$control=$textBox;
}
if(($dataField=$this->getDataField())!=='')
$control->attachEventHandler('OnDataBinding',array($this,'dataBindColumn'));
break;
case 'Item':
case 'AlternatingItem':
case 'SelectedItem':
if(($dataField=$this->getDataField())!=='')
$cell->attachEventHandler('OnDataBinding',array($this,'dataBindColumn'));
break;
}
}
public function dataBindColumn($sender,$param)
{
$item=$sender->getNamingContainer();
$data=$item->getDataItem();
$formatString=$this->getDataFormatString();
if(($field=$this->getDataField())!=='')
$value=$this->formatDataValue($formatString,$this->getDataFieldValue($data,$field));
else
$value=$this->formatDataValue($formatString,$data);
if(($sender instanceof TTableCell) || ($sender instanceof TTextBox))
$sender->setText($value);
}
}

class TButtonColumn extends TDataGridColumn
{
public function getText()
{
return $this->getViewState('Text','');
}
public function setText($value)
{
$this->setViewState('Text',$value,'');
$this->onColumnChanged();
}
public function getDataTextField()
{
return $this->getViewState('DataTextField','');
}
public function setDataTextField($value)
{
$this->setViewState('DataTextField',$value,'');
$this->onColumnChanged();
}
public function getDataTextFormatString()
{
return $this->getViewState('DataTextFormatString','');
}
public function setDataTextFormatString($value)
{
$this->setViewState('DataTextFormatString',$value,'');
$this->onColumnChanged();
}
public function getButtonType()
{
return $this->getViewState('ButtonType','LinkButton');
}
public function setButtonType($value)
{
$this->setViewState('ButtonType',TPropertyValue::ensureEnum($value,'LinkButton','PushButton'),'LinkButton');
$this->onColumnChanged();
}
public function getCommandName()
{
return $this->getViewState('CommandName','');
}
public function setCommandName($value)
{
$this->setViewState('CommandName',$value,'');
$this->onColumnChanged();
}
public function getCausesValidation()
{
return $this->getViewState('CausesValidation',true);
}
public function setCausesValidation($value)
{
$this->setViewState('CausesValidation',TPropertyValue::ensureBoolean($value),true);
$this->onColumnChanged();
}
public function getValidationGroup()
{
return $this->getViewState('ValidationGroup','');
}
public function setValidationGroup($value)
{
$this->setViewState('ValidationGroup',$value,'');
$this->onColumnChanged();
}
public function initializeCell($cell,$columnIndex,$itemType)
{
parent::initializeCell($cell,$columnIndex,$itemType);
if($itemType==='Item' || $itemType==='AlternatingItem' || $itemType==='SelectedItem' || $itemType==='EditItem')
{
if($this->getButtonType()==='LinkButton')
$button=Prado::createComponent('System.Web.UI.WebControls.TLinkButton');
else
$button=Prado::createComponent('System.Web.UI.WebControls.TButton');
$button->setText($this->getText());
$button->setCommandName($this->getCommandName());
$button->setCausesValidation($this->getCausesValidation());
$button->setValidationGroup($this->getValidationGroup());
if($this->getDataTextField()!=='')
$button->attachEventHandler('OnDataBinding',array($this,'dataBindColumn'));
$cell->getControls()->add($button);
}
}
public function dataBindColumn($sender,$param)
{
if(($field=$this->getDataTextField())!=='')
{
$item=$sender->getNamingContainer();
$data=$item->getDataItem();
$value=$this->getDataFieldValue($data,$field);
$text=$this->formatDataValue($this->getDataTextFormatString(),$value);
if(($sender instanceof TLinkButton) || ($sender instanceof TButton))
$sender->setText($text);
}
}
}

class TEditCommandColumn extends TDataGridColumn
{
public function getButtonType()
{
return $this->getViewState('ButtonType','LinkButton');
}
public function setButtonType($value)
{
$this->setViewState('ButtonType',TPropertyValue::ensureEnum($value,'LinkButton','PushButton'),'LinkButton');
$this->onColumnChanged();
}
public function getEditText()
{
return $this->getViewState('EditText','Edit');
}
public function setEditText($value)
{
$this->setViewState('EditText',$value,'Edit');
$this->onColumnChanged();
}
public function getUpdateText()
{
return $this->getViewState('UpdateText','Update');
}
public function setUpdateText($value)
{
$this->setViewState('UpdateText',$value,'Update');
$this->onColumnChanged();
}
public function getCancelText()
{
return $this->getViewState('CancelText','Cancel');
}
public function setCancelText($value)
{
$this->setViewState('CancelText',$value,'Cancel');
$this->onColumnChanged();
}
public function getCausesValidation()
{
return $this->getViewState('CausesValidation',true);
}
public function setCausesValidation($value)
{
$this->setViewState('CausesValidation',TPropertyValue::ensureBoolean($value),true);
$this->onColumnChanged();
}
public function getValidationGroup()
{
return $this->getViewState('ValidationGroup','');
}
public function setValidationGroup($value)
{
$this->setViewState('ValidationGroup',$value,'');
$this->onColumnChanged();
}
public function initializeCell($cell,$columnIndex,$itemType)
{
parent::initializeCell($cell,$columnIndex,$itemType);
$buttonType=$this->getButtonType()=='LinkButton'?'TLinkButton':'TButton';
if($itemType==='Item' || $itemType==='AlternatingItem' || $itemType==='SelectedItem')
$this->addButtonToCell($cell,'Edit',$this->getUpdateText(),false,'');
else if($itemType==='EditItem')
{
$this->addButtonToCell($cell,'Update',$this->getUpdateText(),$this->getCausesValidation(),$this->getValidationGroup());
$cell->getControls()->add('&nbsp;');
$this->addButtonToCell($cell,'Cancel',$this->getUpdateText(),false,'');
}
}
private function addButtonToCell($cell,$commandName,$text,$causesValidation,$validationGroup)
{
if($this->getButtonType()==='LinkButton')
$button=Prado::createComponent('System.Web.UI.WebControls.TLinkButton');
else
$button=Prado::createComponent('System.Web.UI.WebControls.TButton');
$button->setText($text);
$button->setCommandName($commandName);
$button->setCausesValidation($causesValidation);
$button->setValidationGroup($validationGroup);
$cell->getControls()->add($button);
}
}

class THyperLinkColumn extends TDataGridColumn
{
public function getText()
{
return $this->getViewState('Text','');
}
public function setText($value)
{
$this->setViewState('Text',$value,'');
$this->onColumnChanged();
}
public function getDataTextField()
{
return $this->getViewState('DataTextField','');
}
public function setDataTextField($value)
{
$this->setViewState('DataTextField',$value,'');
$this->onColumnChanged();
}
public function getDataTextFormatString()
{
return $this->getViewState('DataTextFormatString','');
}
public function setDataTextFormatString($value)
{
$this->setViewState('DataTextFormatString',$value,'');
$this->onColumnChanged();
}
public function getNavigateUrl()
{
return $this->getViewState('NavigateUrl','');
}
public function setNavigateUrl($value)
{
$this->setViewState('NavigateUrl',$value,'');
$this->onColumnChanged();
}
public function getDataNavigateUrlField()
{
return $this->getViewState('DataNavigateUrlField','');
}
public function setDataNavigateUrlField($value)
{
$this->setViewState('DataNavigateUrlField',$value,'');
$this->onColumnChanged();
}
public function getDataNavigateUrlFormatString()
{
return $this->getViewState('DataNavigateUrlFormatString','');
}
public function setDataNavigateUrlFormatString($value)
{
$this->setViewState('DataNavigateUrlFormatString',$value,'');
$this->onColumnChanged();
}
public function getTarget()
{
return $this->getViewState('Target','');
}
public function setTarget($value)
{
$this->setViewState('Target',$value,'');
$this->onColumnChanged();
}
public function initializeCell($cell,$columnIndex,$itemType)
{
parent::initializeCell($cell,$columnIndex,$itemType);
if($itemType==='Item' || $itemType==='AlternatingItem' || $itemType==='SelectedItem' || $itemType==='EditItem')
{
$link=Prado::createComponent('System.Web.UI.WebControls.THyperLink');
$link->setText($this->getText());
$link->setNavigateUrl($this->getNavigateUrl());
$link->setTarget($this->getTarget());
if($this->getDataTextField()!=='' || $this->getDataNavigateUrlField()!=='')
$link->attachEventHandler('OnDataBinding',array($this,'dataBindColumn'));
$cell->getControls()->add($link);
}
}
public function dataBindColumn($sender,$param)
{
$item=$sender->getNamingContainer();
$data=$item->getDataItem();
if(($field=$this->getDataTextField())!=='')
{
$value=$this->getDataFieldValue($data,$field);
$text=$this->formatDataValue($this->getDataTextFormatString(),$value);
$sender->setText($text);
}
if(($field=$this->getDataNavigateUrlField())!=='')
{
$value=$this->getDataFieldValue($data,$field);
$url=$this->formatDataValue($this->getDataNavigateUrlFormatString(),$value);
$sender->setNavigateUrl($url);
}
}
}

class TTemplateColumn extends TDataGridColumn
{
private $_itemTemplate=null;
private $_editItemTemplate=null;
private $_headerTemplate=null;
private $_footerTemplate=null;
public function getEditItemTemplate()
{
return $this->_editItemTemplate;
}
public function setEditItemTemplate($value)
{
if($value instanceof ITemplate || $value===null)
$this->_editItemTemplate=$value;
else
throw new TInvalidDataTypeException('templatecolumn_template_required','EditItemTemplate');
}
public function getItemTemplate()
{
return $this->_itemTemplate;
}
public function setItemTemplate($value)
{
if($value instanceof ITemplate || $value===null)
$this->_itemTemplate=$value;
else
throw new TInvalidDataTypeException('templatecolumn_template_required','ItemTemplate');
}
public function getHeaderTemplate()
{
return $this->_headerTemplate;
}
public function setHeaderTemplate($value)
{
if($value instanceof ITemplate || $value===null)
$this->_headerTemplate=$value;
else
throw new TInvalidDataTypeException('templatecolumn_template_required','HeaderTemplate');
}
public function getFooterTemplate()
{
return $this->_footerTemplate;
}
public function setFooterTemplate($value)
{
if($value instanceof ITemplate || $value===null)
$this->_footerTemplate=$value;
else
throw new TInvalidDataTypeException('templatecolumn_template_required','FooterTemplate');
}
public function initializeCell($cell,$columnIndex,$itemType)
{
parent::initializeCell($cell,$columnIndex,$itemType);
$template=null;
switch($itemType)
{
case 'Header':
$template=$this->_headerTemplate;
break;
case 'Footer':
$template=$this->_footerTemplate;
break;
case 'Item':
case 'AlternatingItem':
case 'SelectedItem':
$template=$this->_itemTemplate;
break;
case 'EditItem':
$template=$this->_editItemTemplate===null?$this->_itemTemplate:$this->_editItemTemplate;
break;
}
if($template!==null)
{
$cell->setText('');
$cell->getControls()->clear();
$template->instantiateIn($cell);
}
else
$cell->setText('&nbsp;');
}
}
?>