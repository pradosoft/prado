<?php
/**
 * TTemplateManager and TTemplate class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 */

/**
 * TTemplateManager class
 *
 * TTemplateManager manages the loading and parsing of control templates.
 *
 * Given a class name, TTemplateManager tries to locate the corresponding template
 * file under the directory containing the class file. The name of the template file
 * is the class name with the extension '.tpl'.
 *
 * By default, TTemplateManager is registered with {@link TPageService} as the
 * template manager module that can be accessed via {@link TPageService::getTemplateManager()}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
class TTemplateManager extends TComponent implements IModule
{
	/**
	 * Template file extension
	 */
	const TEMPLATE_FILE_EXT='.tpl';
	/**
	 * Prefix of the cache variable name for storing parsed templates
	 */
	const TEMPLATE_CACHE_PREFIX='prado:template:';
	/**
	 * @var TApplication application instance
	 */
	private $_application;
	/**
	 * @var string module ID
	 */
	private $_id;

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * It starts output buffer if it is enabled.
	 * @param TApplication application
	 * @param TXmlElement module configuration
	 */
	public function init($application,$config)
	{
		$this->_application=$application;
		$application->getService()->setTemplateManager($this);
	}

	/**
	 * @return string id of this module
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string id of this module
	 */
	public function setID($value)
	{
		$this->_id=$value;
	}

	/**
	 * Loads the template corresponding to the specified class name.
	 * @return ITemplate template for the class name, null if template doesn't exist.
	 */
	public function getTemplateByClassName($className)
	{
		$class=new ReflectionClass($className);
		$tplFile=dirname($class->getFileName()).'/'.$className.self::TEMPLATE_FILE_EXT;
		return $this->getTemplateByFileName($tplFile);
	}

	/**
	 * Loads the template from the specified file.
	 * @return ITemplate template parsed from the specified file, null if the file doesn't exist.
	 */
	public function getTemplateByFileName($fileName)
	{
		if(($fileName=realpath($fileName))!==false && is_file($fileName))
		{
			if(($cache=$this->_application->getCache())===null)
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
}

/**
 * TTemplate implements PRADO template parsing logic.
 * A TTemplate object represents a parsed PRADO control template.
 * It can instantiate the template as child controls of a specified control.
 * The template format is like HTML, with the following special tags introduced,
 * - component tags: a component tag represents the configuration of a component.
 * The tag name is in the format of com:ComponentType, where ComponentType is the component
 * class name. Component tags must be well-formed. Attributes of the component tag
 * are treated as either property initial values, event handler attachment, or regular
 * tag attributes.
 * - property tags: property tags are used to set large block of attribute values.
 * The property tag name is in the format of prop:AttributeName, where AttributeName
 * can be a property name, an event name or a regular tag attribute name.
 * - directive: directive specifies the property values for the template owner.
 * It is in the format of &lt;% property name-value pairs %&gt;
 * - expressions: expressions are shorthand of {@link TExpression} and {@link TStatements}
 * controls. They are in the formate of &lt;= PHP expression &gt; and &lt; PHP statements &gt;
 * - comments: There are two kinds of comments, regular HTML comments and special template comments.
 * The former is in the format of &lt;!-- comments --&gt;, which will be treated as text strings.
 * The latter is in the format of &lt;%* comments %&gt;, which will be stripped out.
 *
 * Tags other than the above are not required to be well-formed.
 *
 * A TTemplate object represents a parsed PRADO template. To instantiate the template
 * for a particular control, call {@link instantiateIn($control)}, which
 * will create and intialize all components specified in the template and
 * set their parent as the control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
class TTemplate extends TComponent implements ITemplate
{
	/**
	 *  '<!.*?!>' - template comments
	 *	'<!--.*?-->'  - HTML comments
	 *	'<\/?com:([\w\.]+)((?:\s*[\w\.]+=\'.*?\'|\s*[\w\.]+=".*?"|\s*[\w\.]+=<%.*?%>)*)\s*\/?>' - component tags
	 *	'<\/?prop:([\w\.]+)\s*>'  - property tags
	 *	'<%@\s*(\w+)((?:\s*[\w\.]+=\'.*?\'|\s*[\w\.]+=".*?")*)\s*%>'  - directives
	 *	'<%=?(.*?)%>'  - expressions
	 */
	const REGEX_RULES='/<!.*?!>|<!--.*?-->|<\/?com:([\w\.]+)((?:\s*[\w\.]+=\'.*?\'|\s*[\w\.]+=".*?"|\s*[\w\.]+=<%.*?%>)*)\s*\/?>|<\/?prop:([\w\.]+)\s*>|<%@\s*((?:\s*[\w\.]+=\'.*?\'|\s*[\w\.]+=".*?")*)\s*%>|<%=?(.*?)%>/msS';

	/**
	 * @var array list of component tags and strings
	 */
	private $_tpl=array();
	/**
	 * @var array list of directive settings
	 */
	private $_directive=array();
	/**
	 * @var string context path
	 */
	private $_contextPath;
	/**
	 * @var string template file path (if available)
	 */
	private $_tplFile=null;

	/**
	 * Constructor.
	 * The template will be parsed after construction.
	 * @param string the template string
	 * @param string the template context directory
	 * @param string the template file, null if no file
	 */
	public function __construct($template,$contextPath,$tplFile=null)
	{
		$this->_contextPath=$contextPath;
		$this->_tplFile=$tplFile;
		$this->parse($template);
	}

	/**
	 * @return array name-value pairs declared in the directive
	 */
	public function getDirective()
	{
		return $this->_directive;
	}

	/**
	 * @return array the parsed template
	 */
	public function &getItems()
	{
		return $this->_tpl;
	}

	/**
	 * Instantiates the template.
	 * Content in the template will be instantiated as components and text strings
	 * and passed to the specified parent control.
	 * @param TControl the parent control
	 * @throws TTemplateRuntimeException if an error is encountered during the instantiation.
	 */
	public function instantiateIn($tplControl)
	{
		$page=$tplControl->getPage();
		$assetManager=$page->getService()->getAssetManager();
		$controls=array();
		foreach($this->_tpl as $key=>$object)
		{
			if(isset($object[2]))	// component
			{
				if(strpos($object[1],'.')===false)
				{
					if(class_exists($object[1],false))
						$component=new $object[1];
					else
					{
						include_once($object[1].Prado::CLASS_FILE_EXT);
						if(class_exists($object[1],false))
							$component=new $object[1];
						else
							throw new TTemplateRuntimeException('template_component_unknown',$object[1]);
					}
				}
				else
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
					// apply attributes
					foreach($object[2] as $name=>$value)
					{
						if($component->hasEvent($name))		// is an event
						{
							if(is_string($value))
							{
								if(strpos($value,'.')===false)
									$component->attachEventHandler($name,array($component,'TemplateControl.'.$value));
								else
									$component->attachEventHandler($name,array($component,$value));
							}
							else
								throw new TTemplateRuntimeException('template_event_invalid',get_class($component),$name);
						}
						else if(strpos($name,'.')===false)	// is simple property or custom attribute
						{
							if($component->hasProperty($name))
							{
								if($component->canSetProperty($name))
								{
									$setter='set'.$name;
									if(is_string($value))
										$component->$setter($value);
									else if($value[0]===0)
										$component->bindProperty($name,$value[1]);
									else if($value[0]===1)
										$component->$setter($component->evaluateExpression($value[1]));
									else  // url
									{
										$url=$assetManager->publishFilePath($this->_contextPath.'/'.$value[1]);
										$component->$setter($url);
									}
								}
								else
									throw new TTemplateRuntimeException('template_property_readonly',get_class($component),$name);
							}
							else if($component->getAllowCustomAttributes())
							{
								if(is_array($value))
								{
									if($value[0]===1)
										$value=$component->evaluateExpression($value[1]);
									else if($value[0]===2)
										$value=$assetManager->publishFilePath($this->_contextPath.'/'.$value[1]);
									else
										throw new TTemplateRuntimeException('template_attribute_unbindable',get_class($component),$name);
								}
								$component->getAttributes()->add($name,$value);
							}
							else
								throw new TTemplateRuntimeException('template_property_undefined',get_class($component),$name);
						}
						else	// complex property
						{
							if(is_string($value))
								$component->setSubProperty($name,$value);
							else if($value[0]===0)
								$component->bindProperty($name,$value[1]);
							else if($value[0]===1)
								$component->setSubProperty($component->evaluateExpression($value[1]));
							else
							{
								$url=$assetManager->publishFilePath($this->_contextPath.'/'.$value[1]);
								$component->$setter($url);
							}
						}
					}
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
					{
						if($component->hasProperty($name))
						{
							if($component->canSetProperty($name))
							{
								$setter='set'.$name;
								if(is_string($value))
									$component->$setter($value);
								else if($value[0]===1)
									$component->$setter($component->evaluateExpression($value[1]));
								else if($value[0]===2)
								{
									$url=$assetManager->publishFilePath($this->_contextPath.'/'.$value[1]);
									$component->$setter($url);
								}
								else
									throw new TTemplateRuntimeException('template_property_unbindable',get_class($component),$name);
							}
							else
								throw new TTemplateRuntimeException('template_property_readonly',get_class($component),$name);
						}
						else
							throw new TTemplateRuntimeException('template_property_undefined',get_class($component),$name);
					}
					$parent=isset($controls[$object[0]])?$controls[$object[0]]:$tplControl;
					$parent->addParsedObject($component);
				}
				else
					throw new TTemplateRuntimeException('template_component_required',$object[1]);
			}
			else	// string
			{
				if(isset($controls[$object[0]]))
					$controls[$object[0]]->addParsedObject($object[1]);
				else
					$tplControl->addParsedObject($object[1]);
			}
		}
	}

	/**
	 * Parses a template string.
	 *
	 * This template parser recognizes five types of data:
	 * regular string, well-formed component tags, well-formed property tags, directives, and expressions.
	 *
	 * The parsing result is returned as an array. Each array element can be of three types:
	 * - a string, 0: container index; 1: string content;
	 * - a component tag, 0: container index; 1: component type; 2: attributes (name=>value pairs)
	 * If a directive is found in the template, it will be parsed and can be
	 * retrieved via {@link getDirective}, which returns an array consisting of
	 * name-value pairs in the directive.
	 *
	 * Note, attribute names are treated as case-insensitive and will be turned into lower cases.
	 * Component and directive types are case-sensitive.
	 * Container index is the index to the array element that stores the container object.
	 * If an object has no container, its container index is -1.
	 *
	 * @param string the template string
	 * @throws TTemplateParsingException if a parsing error is encountered
	 */
	protected function parse($input)
	{
		$tpl=&$this->_tpl;
		$n=preg_match_all(self::REGEX_RULES,$input,$matches,PREG_SET_ORDER|PREG_OFFSET_CAPTURE);
		$expectPropEnd=false;
		$textStart=0;
        $stack=array();
		$container=-1;
		$c=0;
		for($i=0;$i<$n;++$i)
		{
			$match=&$matches[$i];
			$str=$match[0][0];
			$matchStart=$match[0][1];
			$matchEnd=$matchStart+strlen($str)-1;
			if(strpos($str,'<com:')===0)	// opening component tag
			{
				if($expectPropEnd)
					continue;
				if($matchStart>$textStart)
					$tpl[$c++]=array($container,substr($input,$textStart,$matchStart-$textStart));
				$textStart=$matchEnd+1;
				$type=$match[1][0];
				$attributes=$this->parseAttributes($match[2][0]);
				$tpl[$c++]=array($container,$type,$attributes);
				if($str[strlen($str)-2]!=='/')  // open tag
				{
					array_push($stack,$type);
					$container=$c-1;
				}
			}
			else if(strpos($str,'</com:')===0)	// closing component tag
			{
				if($expectPropEnd)
					continue;
				if($matchStart>$textStart)
					$tpl[$c++]=array($container,substr($input,$textStart,$matchStart-$textStart));
				$textStart=$matchEnd+1;
				$type=$match[1][0];

				if(empty($stack))
				{
					$line=count(explode("\n",substr($input,0,$matchEnd+1)));
					if($this->_tplFile===null)
						throw new TTemplateParsingException('template_closingtag_unexpected',"Line $line","</com:$type>");
					else
						throw new TTemplateParsingException('template_closingtag_unexpected',"{$this->_tplFile} (Line $line)","</com:$type>");
				}

				$name=array_pop($stack);
				if($name!==$type)
				{
					if($name[0]==='@')
						$tag='</prop:'.substr($name,1).'>';
					else
						$tag='</com:'.$name.'>';
					$line=count(explode("\n",substr($input,0,$matchEnd+1)));
					if($this->_tplFile===null)
						throw new TTemplateParsingException('template_closingtag_expected',"Line $line",$tag);
					else
						throw new TTemplateParsingException('template_closingtag_expected',"{$this->_tplFile} (Line $line)",$tag);
				}
				$container=$tpl[$container][0];
			}
			else if(strpos($str,'<%@')===0)	// directive
			{
				if($expectPropEnd)
					continue;
				if($matchStart>$textStart)
					$tpl[$c++]=array($container,substr($input,$textStart,$matchStart-$textStart));
				$textStart=$matchEnd+1;
				if(isset($tpl[0]))
				{
					$line=count(explode("\n",substr($input,0,$matchEnd+1)));
					if($this->_tplFile===null)
						throw new TTemplateParsingException('template_directive_nonunique',"Line $line");
					else
						throw new TTemplateParsingException('template_directive_nonunique',"{$this->_tplFile} (Line $line)");
				}
				$this->_directive=$this->parseAttributes($match[4][0]);
			}
			else if(strpos($str,'<%')===0)	// expression
			{
				if($expectPropEnd)
					continue;
				if($matchStart>$textStart)
					$tpl[$c++]=array($container,substr($input,$textStart,$matchStart-$textStart));
				$textStart=$matchEnd+1;
				if($str[2]==='=')
					$tpl[$c++]=array($container,'TExpression',array('Expression'=>$match[5][0]));
				else
					$tpl[$c++]=array($container,'TStatements',array('Statements'=>$match[5][0]));
			}
			else if(strpos($str,'<prop:')===0)	// opening property
			{
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
			else if(strpos($str,'</prop:')===0)	// closing property
			{
				$prop=strtolower($match[3][0]);
				if(empty($stack))
				{
					$line=count(explode("\n",substr($input,0,$matchEnd+1)));
					if($this->_tplFile===null)
						throw new TTemplateParsingException('template_closingtag_unexpected',"Line $line","</prop:$prop>");
					else
						throw new TTemplateParsingException('template_closingtag_unexpected',"{$this->_tplFile} (Line $line)","</prop:$prop>");
				}
				$name=array_pop($stack);
				if($name!=='@'.$prop)
				{
					if($name[0]==='@')
						$tag='</prop:'.substr($name,1).'>';
					else
						$tag='</com:'.$name.'>';
					$line=count(explode("\n",substr($input,0,$matchEnd+1)));
					if($this->_tplFile===null)
						throw new TTemplateParsingException('template_closingtag_expected',"Line $line",$tag);
					else
						throw new TTemplateParsingException('template_closingtag_expected',"{$this->_tplFile} (Line $line)",$tag);
				}
				if(($last=count($stack))<1 || $stack[$last-1][0]!=='@')
				{
					if($matchStart>$textStart && $container>=0)
					{
						$value=substr($input,$textStart,$matchStart-$textStart);
						if(preg_match('/^<%.*?%>$/msS',$value))
						{
							if($value[2]==='#') // databind
								$tpl[$container][2][$prop]=array(0,substr($value,3,strlen($value)-5));
							else if($value[2]==='=') // a dynamic initialization
								$tpl[$container][2][$prop]=array(1,substr($value,3,strlen($value)-5));
							else
								$tpl[$container][2][$prop]=$value;
						}
						else
							$tpl[$container][2][$prop]=$value;
						$textStart=$matchEnd+1;
					}
					$expectPropEnd=false;
				}
			}
			else if(strpos($str,'<!--')===0)	// HTML comments
			{
				$state=0;
			}
			else if(strpos($str,'<!')===0)		// template comments
			{
				if($expectPropEnd)
				{
					$line=count(explode("\n",substr($input,0,$matchEnd+1)));
					if($this->_tplFile===null)
						throw new TTemplateParsingException('template_comments_forbidden',"Line $line");
					else
						throw new TTemplateParsingException('template_comments_forbidden',"{$this->_tplFile} (Line $line)");
				}
				if($matchStart>$textStart)
					$tpl[$c++]=array($container,substr($input,$textStart,$matchStart-$textStart));
				$textStart=$matchEnd+1;
			}
			else
			{
				$line=count(explode("\n",substr($input,0,$matchEnd+1)));
				if($this->_tplFile===null)
					throw new TTemplateParsingException('template_matching_unexpected',"Line $line",$match);
				else
					throw new TTemplateParsingException('template_matching_unexpected',"{$this->_tplFile} (Line $line)",$match);
			}
		}
		if(!empty($stack))
		{
			$name=array_pop($stack);
			if($name[0]==='@')
				$tag='</prop:'.substr($name,1).'>';
			else
				$tag='</com:'.$name.'>';
			$line=count(explode("\n",substr($input,0,$matchEnd+1)));
			if($this->_tplFile===null)
				throw new TTemplateParsingException('template_closingtag_expected',"Line $line",$tag);
			else
				throw new TTemplateParsingException('template_closingtag_expected',"{$this->_tplFile} (Line $line)",$tag);
		}
		if($textStart<strlen($input))
			$tpl[$c++]=array($container,substr($input,$textStart));
		return $tpl;
	}

	/**
	 * Parses the attributes of a tag from a string.
	 * @param string the string to be parsed.
	 * @return array attribute values indexed by names.
	 */
	protected function parseAttributes($str)
	{
		if($str==='')
			return array();
		$pattern='/([\w\.]+)=(\'.*?\'|".*?"|<%.*?%>)/msS';
		$attributes=array();
		$n=preg_match_all($pattern,$str,$matches,PREG_SET_ORDER);
		for($i=0;$i<$n;++$i)
		{
			$name=strtolower($matches[$i][1]);
			$value=$matches[$i][2];
			if($value[0]==='\'' || $value[0]==='"')
			{
				$value=substr($value,1,strlen($value)-2);
				if(!preg_match('/(<%#.*?%>|<%=.*?%>|<%~.*?%>)/msS',$value))
				{
					$attributes[$name]=$value;
					continue;
				}
			}
			if($value[0]==='<')
			{
				if($value[2]==='#') // databind
					$attributes[$name]=array(0,substr($value,3,strlen($value)-5));
				else if($value[2]==='=') // a dynamic initialization
					$attributes[$name]=array(1,substr($value,3,strlen($value)-5));
				else if($value[2]==='~') // a URL
					$attributes[$name]=array(2,trim(substr($value,3,strlen($value)-5)));
				else
					$attributes[$name]=substr($value,2,strlen($value)-4);
			}
		}
		return $attributes;
	}
}

?>