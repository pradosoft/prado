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
 * There are two ways of loading a template, either by the associated template
 * control class name, or the template file name.
 * The former is via calling {@link getTemplateByClassName}, which tries to
 * locate the corresponding template file under the directory containing
 * the class file. The name of the template file is the class name with
 * the extension '.tpl'. To load a template from a template file path,
 * call {@link getTemplateByFileName}.
 *
 * By default, TTemplateManager is registered with {@link TPageService} as the
 * template manager module that can be accessed via {@link TPageService::getTemplateManager()}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
class TTemplateManager extends TModule
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
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * It starts output buffer if it is enabled.
	 * @param TXmlElement module configuration
	 */
	public function init($config)
	{
		$this->getApplication()->getPageService()->setTemplateManager($this);
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
		if(($fileName=$this->getLocalizedTemplate($fileName))!==null)
		{
			Prado::trace("Loading template $fileName",'System.Web.UI.TTemplateManager');
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

	/**
	 * Finds a localized template file.
	 * @param string template file.
	 * @return string|null a localized template file if found, null otherwise.
	 */
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
 * set their parent as $control.
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
	 *	'<%@\s*((?:\s*[\w\.]+=\'.*?\'|\s*[\w\.]+=".*?")*)\s*%>'  - directives
	 *	'<%[%#~\\$=\\[](.*?)%>'  - expressions
	 */
	const REGEX_RULES='/<!.*?!>|<!--.*?-->|<\/?com:([\w\.]+)((?:\s*[\w\.]+=\'.*?\'|\s*[\w\.]+=".*?"|\s*[\w\.]+=<%.*?%>)*)\s*\/?>|<\/?prop:([\w\.]+)\s*>|<%@\s*((?:\s*[\w\.]+=\'.*?\'|\s*[\w\.]+=".*?")*)\s*%>|<%[%#~\\$=\\[](.*?)%>/msS';

	/**
	 * Different configurations of component property/event/attribute
	 */
	const CONFIG_DATABIND=0;
	const CONFIG_EXPRESSION=1;
	const CONFIG_ASSET=2;
	const CONFIG_PARAMETER=3;
	const CONFIG_LOCALIZATION=4;
	const CONFIG_TEMPLATE=5;

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
	 * @var integer the line number that parsing starts from (internal use)
	 */
	private $_startingLine=0;
	/**
	 * @var string template content to be parsed
	 */
	private $_content;
	/**
	 * @var boolean whether this template is a source template
	 */
	private $_sourceTemplate=true;


	/**
	 * Constructor.
	 * The template will be parsed after construction.
	 * @param string the template string
	 * @param string the template context directory
	 * @param string the template file, null if no file
	 * @param integer the line number that parsing starts from (internal use)
	 * @param boolean whether this template is a source template, i.e., this template is loaded from
	 * some external storage rather than from within another template.
	 */
	public function __construct($template,$contextPath,$tplFile=null,$startingLine=0,$sourceTemplate=true)
	{
		$this->_sourceTemplate=$sourceTemplate;
		$this->_contextPath=$contextPath;
		$this->_tplFile=$tplFile;
		$this->_startingLine=$startingLine;
		$this->_content=$template;
		$this->parse($template);
		$this->_content=null; // reset to save memory
	}

	/**
	 * @return boolean whether this template is a source template, i.e., this template is loaded from
	 * some external storage rather than from within another template.
	 */
	public function getIsSourceTemplate()
	{
		return $this->_sourceTemplate;
	}

	/**
	 * @return string context directory path
	 */
	public function getContextPath()
	{
		return $this->_contextPath;
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
	 */
	public function instantiateIn($tplControl)
	{
		if(($page=$tplControl->getPage())===null)
			$page=Prado::getApplication()->getPageService()->getRequestedPage();
		$controls=array();
		foreach($this->_tpl as $key=>$object)
		{
			$parent=isset($controls[$object[0]])?$controls[$object[0]]:$tplControl;
			if(!$parent->getAllowChildControls())
				continue;
			if(isset($object[2]))	// component
			{
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
						$this->configureControl($component,$name,$value);
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
					$parent->addParsedObject($component);
				}
			}
			else	// string
				$parent->addParsedObject($object[1]);
		}
	}

	/**
	 * Configures a property/event of a control.
	 * @param TControl control to be configured
	 * @param string property name
	 * @param mixed property initial value
	 */
	protected function configureControl($control,$name,$value)
	{
		if(strncasecmp($name,'on',2)===0)		// is an event
			$this->configureEvent($control,$name,$value);
		else if(strpos($name,'.')===false)	// is a simple property or custom attribute
			$this->configureProperty($control,$name,$value);
		else	// is a subproperty
			$this->configureSubProperty($control,$name,$value);
	}

	/**
	 * Configures a property of a non-control component.
	 * @param TComponent component to be configured
	 * @param string property name
	 * @param mixed property initial value
	 */
	protected function configureComponent($component,$name,$value)
	{
		if(strpos($name,'.')===false)	// is a simple property or custom attribute
			$this->configureProperty($component,$name,$value);
		else	// is a subproperty
			$this->configureSubProperty($component,$name,$value);
	}

	/**
	 * Configures an event for a control.
	 * @param TControl control to be configured
	 * @param string event name
	 * @param string event handler
	 */
	protected function configureEvent($component,$name,$value)
	{
		if(strpos($value,'.')===false)
			$component->attachEventHandler($name,array($component,'TemplateControl.'.$value));
		else
			$component->attachEventHandler($name,array($component,$value));
	}

	/**
	 * Configures a simple property for a component.
	 * @param TComponent component to be configured
	 * @param string property name
	 * @param mixed property initial value
	 */
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
				case self::CONFIG_ASSET:		// asset URL
					$url=Prado::getApplication()->getAssetManager()->publishFilePath($this->_contextPath.'/'.$value[1]);
					$component->$setter($url);
					break;
				case self::CONFIG_PARAMETER:		// application parameter
					$component->$setter(Prado::getApplication()->getParameters()->itemAt($value[1]));
					break;
				case self::CONFIG_LOCALIZATION:
					Prado::using('System.I18N.Translation');
					$component->$setter(localize(trim($value[1])));
					break;
				default:	// an error if reaching here
					break;
			}
		}
		else
			$component->$setter($value);
	}

	/**
	 * Configures a subproperty for a component.
	 * @param TComponent component to be configured
	 * @param string subproperty name
	 * @param mixed subproperty initial value
	 */
	protected function configureSubProperty($component,$name,$value)
	{
		if(is_array($value))
		{
			switch($value[0])
			{
				case self::CONFIG_DATABIND:		// databinding
					$component->bindProperty($name,$value[1]);
					break;
				case self::CONFIG_EXPRESSION:		// expression
					$component->setSubProperty($name,$component->evaluateExpression($value[1]));
					break;
				case self::CONFIG_TEMPLATE:
					$component->setSubProperty($name,$value[1]);
					break;
				case self::CONFIG_ASSET:		// asset URL
					$url=Prado::getApplication()->getAssetManager()->publishFilePath($this->_contextPath.'/'.$value[1]);
					$component->setSubProperty($name,$url);
					break;
				case self::CONFIG_PARAMETER:		// application parameter
					$component->setSubProperty($name,Prado::getApplication()->getParameters()->itemAt($value[1]));
					break;
				case self::CONFIG_LOCALIZATION:
					$component->setSubProperty($name,localize($value[1]));
					break;
				default:	// an error if reaching here
					break;
			}
		}
		else
			$component->setSubProperty($name,$value);
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
	 * @throws TConfigurationException if a parsing error is encountered
	 */
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
				if(strpos($str,'<com:')===0)	// opening component tag
				{
					if($expectPropEnd)
						continue;
					if($matchStart>$textStart)
						$tpl[$c++]=array($container,substr($input,$textStart,$matchStart-$textStart));
					$textStart=$matchEnd+1;
					$type=$match[1][0];
					$attributes=$this->parseAttributes($match[2][0],$match[2][1]);
					$this->validateAttributes($type,$attributes);
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
						throw new TConfigurationException('template_closingtag_unexpected',"</com:$type>");

					$name=array_pop($stack);
					if($name!==$type)
					{
						$tag=$name[0]==='@' ? '</prop:'.substr($name,1).'>' : "</com:$name>";
						throw new TConfigurationException('template_closingtag_expected',$tag);
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
						throw new TConfigurationException('template_directive_nonunique');
					$this->_directive=$this->parseAttributes($match[4][0],$match[4][1]);
				}
				else if(strpos($str,'<%')===0)	// expression
				{
					if($expectPropEnd)
						continue;
					if($matchStart>$textStart)
						$tpl[$c++]=array($container,substr($input,$textStart,$matchStart-$textStart));
					$textStart=$matchEnd+1;
					if($str[2]==='=')	// expression
						$tpl[$c++]=array($container,'TExpression',array('Expression'=>THttpUtility::htmlDecode($match[5][0])));
					else if($str[2]==='%')  // statements
						$tpl[$c++]=array($container,'TStatements',array('Statements'=>THttpUtility::htmlDecode($match[5][0])));
					else
						$tpl[$c++]=array($container,'TLiteral',array('Text'=>$this->parseAttribute($str)));
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
							if(isset($tpl[$container][2][$prop]))
								throw new TConfigurationException('template_property_duplicated',$prop);
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

	/**
	 * Parses the attributes of a tag from a string.
	 * @param string the string to be parsed.
	 * @return array attribute values indexed by names.
	 */
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
			if(isset($attributes[$name]))
				throw new TConfigurationException('template_property_duplicated',$name);
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
		return array(self::CONFIG_TEMPLATE,new TTemplate($content,$this->_contextPath,$this->_tplFile,$line,false));
	}

	/**
	 * Parses a single attribute.
	 * @param string the string to be parsed.
	 * @return array attribute initialization
	 */
	protected function parseAttribute($value)
	{
		$matches=array();
		if(!preg_match('/\\s*(<%#.*?%>|<%=.*?%>|<%~.*?%>|<%\\$.*?%>|<%\\[.*?\\]%>)\\s*/msS',$value,$matches) || $matches[0]!==$value)
			return THttpUtility::htmlDecode($value);
		$value=THttpUtility::htmlDecode($matches[1]);
		if($value[2]==='#') // databind
			return array(self::CONFIG_DATABIND,substr($value,3,strlen($value)-5));
		else if($value[2]==='=') // a dynamic initialization
			return array(self::CONFIG_EXPRESSION,substr($value,3,strlen($value)-5));
		else if($value[2]==='~') // a URL
			return array(self::CONFIG_ASSET,trim(substr($value,3,strlen($value)-5)));
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
					// a subproperty, so the first segment must be readable
					$subname=substr($name,0,$pos);
					if(!is_callable(array($className,'get'.$subname)))
						throw new TConfigurationException('template_property_unknown',$type,$subname);
				}
				else if(strncasecmp($name,'on',2)===0)
				{
					// an event
					if(!is_callable(array($className,$name)))
						throw new TConfigurationException('template_event_unknown',$type,$name);
					else if(!is_string($att))
						throw new TConfigurationException('template_eventhandler_invalid',$type,$name);
				}
				else
				{
					// a simple property
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
					// a subproperty, so the first segment must be readable
					$subname=substr($name,0,$pos);
					if(!is_callable(array($className,'get'.$subname)))
						throw new TConfigurationException('template_property_unknown',$type,$subname);
				}
				else if(strncasecmp($name,'on',2)===0)
					throw new TConfigurationException('template_event_forbidden',$type,$name);
				else
				{
					// id is still alowed for TComponent, even if id property doesn't exist
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

?>