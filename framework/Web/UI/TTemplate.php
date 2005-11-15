<?php
/**
 * TTemplate class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 */

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
 * It is in the format of &lt;% properyt name-value pairs %&gt;
 * - expressions: expressions are shorthand of {@link TExpression} and {@link TStatements}
 * controls. They are in the formate of &lt;= PHP expression &gt; and &lt; PHP statements &gt;
 * - comments: There are two kinds of comments, regular HTML comments and special template comments.
 * The former is in the format of &lt;!-- comments --&gt;, which will be treated as text strings.
 * The latter is in the format of &lt;%* comments %&gt;, which will be stripped out.
 *
 * Tags are not required to be well-formed.
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
	 * Constructor.
	 * The template will be parsed after construction.
	 * @param string the template string
	 */
	public function __construct($template,$contextPath)
	{
		$this->_contextPath=$contextPath;
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
								throw new TTemplateRuntimeException('template_event_invalid',$name);
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
									throw new TTemplateRuntimeException('property_read_only',get_class($component).'.'.$name);
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
										throw new TTemplateRuntimeException('template_attribute_unbindable',$name);
								}
								$component->getAttributes()->add($name,$value);
							}
							else
								throw new TTemplateRuntimeException('property_not_defined',get_class($component).'.'.$name);
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
									throw new TTemplateRuntimeException('template_component_property_unbindable',get_class($component),$name);
							}
							else
								throw new TTemplateRuntimeException('property_read_only',get_class($component).'.'.$name);
						}
						else
							throw new TTemplateRuntimeException('property_not_defined',get_class($component).'.'.$name);
					}
					$parent=isset($controls[$object[0]])?$controls[$object[0]]:$tplControl;
					$parent->addParsedObject($component);
				}
				else
					throw new TTemplateRuntimeException('must_be_component',$object[1]);
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
	 * NOTE, this method is currently not used!!!
	 * Processes an attribute set in a component tag.
	 * The attribute will be checked to see if it represents a property or an event.
	 * If so, the value will be set to the property, or the value will be treated
	 * as an event handler and attached to the event.
	 * Otherwise, it will be added as regualr attribute if the control allows so.
	 * @param TComponent the component represented by the tag
	 * @param string attribute name
	 * @param string attribute value
	 * @throws TTemplateRuntimeException
	 */
	public static function applyAttribute($component,$name,$value)
	{
		$target=$component;
		if(strpos($name,'.')===false)
			$property=$name;
		else
		{
			$names=explode('.',$name);
			$property=array_pop($names);
			foreach($names as $p)
			{
				if(($target instanceof TComponent) && $target->canGetProperty($p))
				{
					$getter='get'.$p;
					$target=$target->$getter();
				}
				else
					throw new TTemplateRuntimeException('invalid_subproperty',$name);
			}
		}
		if($target instanceof TControl)
		{
			if($target->hasProperty($property))
			{
				$setter='set'.$property;
				if(is_string($value))
					$target->$setter($value);
				else if($value[0]===0)
					$target->bindProperty($property,$value[1]);
				else
				{
					$target->$setter($target->evaluateExpression($value[1]));
				}
			}
			else if($target->hasEvent($property))
			{
				if(strpos($value,'.')===false)
					$target->attachEventHandler($property,'TemplateControl.'.$value);
				else
					$target->attachEventHandler($property,$value);
			}
			else if($target->getAllowCustomAttributes())
				$target->getAttributes()->add($property,$value);
			else
				throw new TTemplateRuntimeException('property_not_defined',get_class($target).'.'.$property);
		}
		else if($target instanceof TComponent)
		{
			$setter='set'.$property;
			$target->$setter($value);
		}
		else
			throw new TTemplateRuntimeException('must_extend_TComponent',get_class($target));
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
	 * @return array the parsed result
	 * @throws TTemplateParsingException if a parsing error is encountered
	 */
	protected function &parse($input)
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
					throw new TTemplateParsingException('unexpected_closing_tag',$line,"</com:$type>");
				}

				$name=array_pop($stack);
				if($name!==$type)
				{
					if($name[0]==='@')
						$tag='</prop:'.substr($name,1).'>';
					else
						$tag='</com:'.$name.'>';
					$line=count(explode("\n",substr($input,0,$matchEnd+1)));
					throw new TTemplateParsingException('expecting_closing_tag',$line,$tag);
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
					throw new TTemplateParsingException('nonunique_template_directive',$line);
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
					throw new TTemplateParsingException('unexpected_closing_tag',$line,"</prop:$prop>");
				}
				$name=array_pop($stack);
				if($name!=='@'.$prop)
				{
					if($name[0]==='@')
						$tag='</prop:'.substr($name,1).'>';
					else
						$tag='</com:'.$name.'>';
					$line=count(explode("\n",substr($input,0,$matchEnd+1)));
					throw new TTemplateParsingException('expecting_closing_tag',$line,$tag);
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
					throw new TTemplateParsingException('no_comments_in_property',$line);
				}
				if($matchStart>$textStart)
					$tpl[$c++]=array($container,substr($input,$textStart,$matchStart-$textStart));
				$textStart=$matchEnd+1;
			}
			else
				throw new TTemplateParsingException('unexpected_matching',$match);
		}
		if(!empty($stack))
		{
			$name=array_pop($stack);
			if($name[0]==='@')
				$tag='</prop:'.substr($name,1).'>';
			else
				$tag='</com:'.$name.'>';
			$line=count(explode("\n",substr($input,0,$matchEnd+1)));
			throw new TTemplateParsingException('expecting_closing_tag',$line,$tag);
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
			else
				$attributes[$name]=substr($value,1,strlen($value)-2);
		}
		return $attributes;
	}
}

?>