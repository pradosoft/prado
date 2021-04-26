<?php
/**
 * TTemplateManager and TTemplate class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI
 */

namespace Prado\Web\UI;

use Prado\Prado;
use Prado\TComponent;
use Prado\Web\Javascripts\TJavaScriptLiteral;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TException;
use Prado\Exceptions\TTemplateException;

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
 * The property tag name is in the format of <prop:AttributeName> where AttributeName
 * can be a property name, an event name or a regular tag attribute name.
 * - group subproperty tags: subproperties of a common property can be configured using
 * <prop:MainProperty SubProperty1="Value1" SubProperty2="Value2" .../>
 * - directive: directive specifies the property values for the template owner.
 * It is in the format of <%@ property name-value pairs %>;
 * - expressions: They are in the format of <%= PHP expression %> and <%% PHP statements %>
 * - comments: There are two kinds of comments, regular HTML comments and special template comments.
 * The former is in the format of <!-- comments -->, which will be treated as text strings.
 * The latter is in the format of <!-- comments --!>, which will be stripped out.
 *
 * Tags other than the above are not required to be well-formed.
 *
 * A TTemplate object represents a parsed PRADO template. To instantiate the template
 * for a particular control, call {@link instantiateIn($control)}, which
 * will create and intialize all components specified in the template and
 * set their parent as $control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI
 * @since 3.0
 */
class TTemplate extends \Prado\TApplicationComponent implements ITemplate
{
	/**
	 *  '<!--.*?--!>' - template comments
	 *  '<!--.*?-->'  - HTML comments
	 *	'<\/?com:([\w\.\\\]+)((?:\s*[\w\.]+\s*=\s*\'.*?\'|\s*[\w\.]+\s*=\s*".*?"|\s*[\w\.]+\s*=\s*<%.*?%>)*)\s*\/?>' - component tags
	 *	'<\/?prop:([\w\.\-]+)\s*>'  - property tags
	 *	'<%@\s*((?:\s*[\w\.]+\s*=\s*\'.*?\'|\s*[\w\.]+\s*=\s*".*?")*)\s*%>'  - directives
	 *	'<%[%#~\/\\$=\\[](.*?)%>'  - expressions
	 *  '<prop:([\w\.\-]+)((?:\s*[\w\.\-]+=\'.*?\'|\s*[\w\.\-]+=".*?"|\s*[\w\.\-]+=<%.*?%>)*)\s*\/>' - group subproperty tags
	 */
	const REGEX_RULES = '/<!--.*?--!>|<!---.*?--->|<\/?com:([\w\.\\\]+)((?:\s*[\w\.]+\s*=\s*\'.*?\'|\s*[\w\.]+\s*=\s*".*?"|\s*[\w\.]+\s*=\s*<%.*?%>)*)\s*\/?>|<\/?prop:([\w\.\-]+)\s*>|<%@\s*((?:\s*[\w\.]+\s*=\s*\'.*?\'|\s*[\w\.]+\s*=\s*".*?")*)\s*%>|<%[%#~\/\\$=\\[](.*?)%>|<prop:([\w\.\-]+)((?:\s*[\w\.\-]+\s*=\s*\'.*?\'|\s*[\w\.\-]+\s*=\s*".*?"|\s*[\w\.\-]+\s*=\s*<%.*?%>)*)\s*\/>/msS';

	/**
	 * Different configurations of component property/event/attribute
	 */
	const CONFIG_DATABIND = 0;
	const CONFIG_EXPRESSION = 1;
	const CONFIG_ASSET = 2;
	const CONFIG_PARAMETER = 3;
	const CONFIG_LOCALIZATION = 4;
	const CONFIG_TEMPLATE = 5;

	/**
	 * @var array list of component tags and strings
	 */
	private $_tpl = [];
	/**
	 * @var array list of directive settings
	 */
	private $_directive = [];
	/**
	 * @var string context path
	 */
	private $_contextPath;
	/**
	 * @var string template file path (if available)
	 */
	private $_tplFile;
	/**
	 * @var int the line number that parsing starts from (internal use)
	 */
	private $_startingLine = 0;
	/**
	 * @var string template content to be parsed
	 */
	private $_content;
	/**
	 * @var bool whether this template is a source template
	 */
	private $_sourceTemplate = true;
	/**
	 * @var string hash code of the template
	 */
	private $_hashCode = '';
	private $_tplControl;
	private $_includedFiles = [];
	private $_includeAtLine = [];
	private $_includeLines = [];


	/**
	 * Constructor.
	 * The template will be parsed after construction.
	 * @param string $template the template string
	 * @param string $contextPath the template context directory
	 * @param null|string $tplFile the template file, null if no file
	 * @param int $startingLine the line number that parsing starts from (internal use)
	 * @param bool $sourceTemplate whether this template is a source template, i.e., this template is loaded from
	 * some external storage rather than from within another template.
	 */
	public function __construct($template, $contextPath, $tplFile = null, $startingLine = 0, $sourceTemplate = true)
	{
		$this->_sourceTemplate = $sourceTemplate;
		$this->_contextPath = $contextPath;
		$this->_tplFile = $tplFile;
		$this->_startingLine = $startingLine;
		$this->_content = $template;
		$this->_hashCode = md5($template);
		parent::__construct();
		$this->parse($template);
		$this->_content = null; // reset to save memory
	}

	/**
	 * @return string  template file path if available, null otherwise.
	 */
	public function getTemplateFile()
	{
		return $this->_tplFile;
	}

	/**
	 * @return bool whether this template is a source template, i.e., this template is loaded from
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
	 * @return string hash code that can be used to identify the template
	 */
	public function getHashCode()
	{
		return $this->_hashCode;
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
	 * @param \Prado\Web\UI\TControl $tplControl the control who owns the template
	 * @param null|TControl $parentControl the control who will become the root parent of the controls on the template. If null, it uses the template control.
	 */
	public function instantiateIn($tplControl, $parentControl = null)
	{
		$this->_tplControl = $tplControl;
		if ($parentControl === null) {
			$parentControl = $tplControl;
		}
		if (($page = $tplControl->getPage()) === null) {
			$page = $this->getService()->getRequestedPage();
		}
		$controls = [];
		$directChildren = [];
		foreach ($this->_tpl as $key => $object) {
			if ($object[0] === -1) {
				$parent = $parentControl;
			} elseif (isset($controls[$object[0]])) {
				$parent = $controls[$object[0]];
			} else {
				continue;
			}
			if (isset($object[2])) {	// component
				$component = Prado::createComponent($object[1]);
				$properties = &$object[2];
				if ($component instanceof TControl) {
					if ($component instanceof \Prado\Web\UI\WebControls\TOutputCache) {
						$component->setCacheKeyPrefix($this->_hashCode . $key);
					}
					$component->setTemplateControl($tplControl);
					if (isset($properties['id'])) {
						if (is_array($properties['id'])) {
							$properties['id'] = $component->evaluateExpression($properties['id'][1]);
						}
						$tplControl->registerObject($properties['id'], $component);
					}
					if (isset($properties['skinid'])) {
						if (is_array($properties['skinid'])) {
							$component->setSkinID($component->evaluateExpression($properties['skinid'][1]));
						} else {
							$component->setSkinID($properties['skinid']);
						}
						unset($properties['skinid']);
					}

					$component->trackViewState(false);

					$component->applyStyleSheetSkin($page);
					foreach ($properties as $name => $value) {
						$this->configureControl($component, $name, $value);
					}

					$component->trackViewState(true);

					if ($parent === $parentControl) {
						$directChildren[] = $component;
					} else {
						$component->createdOnTemplate($parent);
					}
					if ($component->getAllowChildControls()) {
						$controls[$key] = $component;
					}
				} elseif ($component instanceof TComponent) {
					$controls[$key] = $component;
					if (isset($properties['id'])) {
						if (is_array($properties['id'])) {
							$properties['id'] = $component->evaluateExpression($properties['id'][1]);
						}
						$tplControl->registerObject($properties['id'], $component);
						if (!$component->hasProperty('id')) {
							unset($properties['id']);
						}
					}
					foreach ($properties as $name => $value) {
						$this->configureComponent($component, $name, $value);
					}
					if ($parent === $parentControl) {
						$directChildren[] = $component;
					} else {
						$component->createdOnTemplate($parent);
					}
				}
			} else {
				if ($object[1] instanceof TCompositeLiteral) {
					// need to clone a new object because the one in template is reused
					$o = clone $object[1];
					$o->setContainer($tplControl);
					if ($parent === $parentControl) {
						$directChildren[] = $o;
					} else {
						$parent->addParsedObject($o);
					}
				} else {
					if ($parent === $parentControl) {
						$directChildren[] = $object[1];
					} else {
						$parent->addParsedObject($object[1]);
					}
				}
			}
		}
		// delay setting parent till now because the parent may cause
		// the child to do lifecycle catchup which may cause problem
		// if the child needs its own child controls.
		foreach ($directChildren as $control) {
			if ($control instanceof TComponent) {
				$control->createdOnTemplate($parentControl);
			} else {
				$parentControl->addParsedObject($control);
			}
		}
	}

	/**
	 * Configures a property/event of a control.
	 * @param \Prado\Web\UI\TControl $control control to be configured
	 * @param string $name property name
	 * @param mixed $value property initial value
	 */
	protected function configureControl($control, $name, $value)
	{
		if (strncasecmp($name, 'on', 2) === 0) {		// is an event
			$this->configureEvent($control, $name, $value, $control);
		} elseif (($pos = strrpos($name, '.')) === false) {	// is a simple property or custom attribute
			$this->configureProperty($control, $name, $value);
		} else {	// is a subproperty
			$this->configureSubProperty($control, $name, $value);
		}
	}

	/**
	 * Configures a property of a non-control component.
	 * @param \Prado\TComponent $component component to be configured
	 * @param string $name property name
	 * @param mixed $value property initial value
	 */
	protected function configureComponent($component, $name, $value)
	{
		if (strpos($name, '.') === false) {	// is a simple property or custom attribute
			$this->configureProperty($component, $name, $value);
		} else {	// is a subproperty
			$this->configureSubProperty($component, $name, $value);
		}
	}

	/**
	 * Configures an event for a control.
	 * @param \Prado\Web\UI\TControl $control control to be configured
	 * @param string $name event name
	 * @param string $value event handler
	 * @param \Prado\Web\UI\TControl $contextControl context control
	 */
	protected function configureEvent($control, $name, $value, $contextControl)
	{
		if (strpos($value, '.') === false) {
			$control->attachEventHandler($name, [$contextControl, 'TemplateControl.' . $value]);
		} else {
			$control->attachEventHandler($name, [$contextControl, $value]);
		}
	}

	/**
	 * Configures a simple property for a component.
	 * @param \Prado\TComponent $component component to be configured
	 * @param string $name property name
	 * @param mixed $value property initial value
	 */
	protected function configureProperty($component, $name, $value)
	{
		if (is_array($value)) {
			switch ($value[0]) {
				case self::CONFIG_DATABIND:
					$component->bindProperty($name, $value[1]);
					break;
				case self::CONFIG_EXPRESSION:
					if ($component instanceof TControl) {
						$component->autoBindProperty($name, $value[1]);
					} else {
						$setter = 'set' . $name;
						$component->$setter($this->_tplControl->evaluateExpression($value[1]));
					}
					break;
				case self::CONFIG_TEMPLATE:
					$setter = 'set' . $name;
					$component->$setter($value[1]);
					break;
				case self::CONFIG_ASSET:		// asset URL
					$setter = 'set' . $name;
					$url = $this->publishFilePath($this->_contextPath . DIRECTORY_SEPARATOR . $value[1]);
					$component->$setter($url);
					break;
				case self::CONFIG_PARAMETER:		// application parameter
					$setter = 'set' . $name;
					$component->$setter($this->getApplication()->getParameters()->itemAt($value[1]));
					break;
				case self::CONFIG_LOCALIZATION:
					$setter = 'set' . $name;
					$component->$setter(Prado::localize($value[1]));
					break;
				default:	// an error if reaching here
					throw new TConfigurationException('template_tag_unexpected', $name, $value[1]);
					break;
			}
		} else {
			if (substr($name, 0, 2) == 'js') {
				if ($value && !($value instanceof TJavaScriptLiteral)) {
					$value = new TJavaScriptLiteral($value);
				}
			}
			$setter = 'set' . $name;
			$component->$setter($value);
		}
	}

	/**
	 * Configures a subproperty for a component.
	 * @param \Prado\TComponent $component component to be configured
	 * @param string $name subproperty name
	 * @param mixed $value subproperty initial value
	 */
	protected function configureSubProperty($component, $name, $value)
	{
		if (is_array($value)) {
			switch ($value[0]) {
				case self::CONFIG_DATABIND:		// databinding
					$component->bindProperty($name, $value[1]);
					break;
				case self::CONFIG_EXPRESSION:		// expression
					if ($component instanceof TControl) {
						$component->autoBindProperty($name, $value[1]);
					} else {
						$component->setSubProperty($name, $this->_tplControl->evaluateExpression($value[1]));
					}
					break;
				case self::CONFIG_TEMPLATE:
					$component->setSubProperty($name, $value[1]);
					break;
				case self::CONFIG_ASSET:		// asset URL
					$url = $this->publishFilePath($this->_contextPath . DIRECTORY_SEPARATOR . $value[1]);
					$component->setSubProperty($name, $url);
					break;
				case self::CONFIG_PARAMETER:		// application parameter
					$component->setSubProperty($name, $this->getApplication()->getParameters()->itemAt($value[1]));
					break;
				case self::CONFIG_LOCALIZATION:
					$component->setSubProperty($name, Prado::localize($value[1]));
					break;
				default:	// an error if reaching here
					throw new TConfigurationException('template_tag_unexpected', $name, $value[1]);
					break;
			}
		} else {
			$component->setSubProperty($name, $value);
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
	 * @param string $input the template string
	 * @throws TConfigurationException if a parsing error is encountered
	 */
	protected function parse($input)
	{
		$input = $this->preprocess($input);
		$tpl = &$this->_tpl;
		$n = preg_match_all(self::REGEX_RULES, $input, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
		$expectPropEnd = false;
		$textStart = 0;
		$stack = [];
		$container = -1;
		$matchEnd = 0;
		$c = 0;
		$this->_directive = null;
		try {
			for ($i = 0; $i < $n; ++$i) {
				$match = &$matches[$i];
				$str = $match[0][0];
				$matchStart = $match[0][1];
				$matchEnd = $matchStart + strlen($str) - 1;
				if (strpos($str, '<com:') === 0) {	// opening component tag
					if ($expectPropEnd) {
						continue;
					}
					if ($matchStart > $textStart) {
						$tpl[$c++] = [$container, substr($input, $textStart, $matchStart - $textStart)];
					}
					$textStart = $matchEnd + 1;
					$type = $match[1][0];
					$attributes = $this->parseAttributes($match[2][0], $match[2][1]);
					$class = $this->validateAttributes($type, $attributes);
					$tpl[$c++] = [$container, $class, $attributes];
					if ($str[strlen($str) - 2] !== '/') {  // open tag
						$stack[] = $type;
						$container = $c - 1;
					}
				} elseif (strpos($str, '</com:') === 0) {	// closing component tag
					if ($expectPropEnd) {
						continue;
					}
					if ($matchStart > $textStart) {
						$tpl[$c++] = [$container, substr($input, $textStart, $matchStart - $textStart)];
					}
					$textStart = $matchEnd + 1;
					$type = $match[1][0];

					if (empty($stack)) {
						throw new TConfigurationException('template_closingtag_unexpected', "</com:$type>");
					}

					$name = array_pop($stack);
					if ($name !== $type) {
						$tag = $name[0] === '@' ? '</prop:' . substr($name, 1) . '>' : "</com:$name>";
						throw new TConfigurationException('template_closingtag_expected', $tag, "</com:$type>");
					}
					$container = $tpl[$container][0];
				} elseif (strpos($str, '<%@') === 0) {	// directive
					if ($expectPropEnd) {
						continue;
					}
					if ($matchStart > $textStart) {
						$tpl[$c++] = [$container, substr($input, $textStart, $matchStart - $textStart)];
					}
					$textStart = $matchEnd + 1;
					if (isset($tpl[0]) || $this->_directive !== null) {
						throw new TConfigurationException('template_directive_nonunique');
					}
					$this->_directive = $this->parseAttributes($match[4][0], $match[4][1]);
				} elseif (strpos($str, '<%') === 0) {	// expression
					if ($expectPropEnd) {
						continue;
					}
					if ($matchStart > $textStart) {
						$tpl[$c++] = [$container, substr($input, $textStart, $matchStart - $textStart)];
					}
					$textStart = $matchEnd + 1;
					$literal = trim($match[5][0]);
					if ($str[2] === '=') {	// expression
						$tpl[$c++] = [$container, [TCompositeLiteral::TYPE_EXPRESSION, $literal]];
					} elseif ($str[2] === '%') {  // statements
						$tpl[$c++] = [$container, [TCompositeLiteral::TYPE_STATEMENTS, $literal]];
					} elseif ($str[2] === '#') {
						$tpl[$c++] = [$container, [TCompositeLiteral::TYPE_DATABINDING, $literal]];
					} elseif ($str[2] === '$') {
						$tpl[$c++] = [$container, [TCompositeLiteral::TYPE_EXPRESSION, "\$this->getApplication()->getParameters()->itemAt('$literal')"]];
					} elseif ($str[2] === '~') {
						$tpl[$c++] = [$container, [TCompositeLiteral::TYPE_EXPRESSION, "\$this->publishFilePath('$this->_contextPath/$literal')"]];
					} elseif ($str[2] === '/') {
						$tpl[$c++] = [$container, [TCompositeLiteral::TYPE_EXPRESSION, "rtrim(dirname(\$this->getApplication()->getRequest()->getApplicationUrl()), '\/').'/$literal'"]];
					} elseif ($str[2] === '[') {
						$literal = strtr(trim(substr($literal, 0, strlen($literal) - 1)), ["'" => "\'", "\\" => "\\\\"]);
						$tpl[$c++] = [$container, [TCompositeLiteral::TYPE_EXPRESSION, "Prado::localize('$literal')"]];
					}
				} elseif (strpos($str, '<prop:') === 0) {	// opening property
					if (strrpos($str, '/>') === strlen($str) - 2) {  //subproperties
						if ($expectPropEnd) {
							continue;
						}
						if ($matchStart > $textStart) {
							$tpl[$c++] = [$container, substr($input, $textStart, $matchStart - $textStart)];
						}
						$textStart = $matchEnd + 1;
						$prop = strtolower($match[6][0]);
						$attrs = $this->parseAttributes($match[7][0], $match[7][1]);
						$attributes = [];
						foreach ($attrs as $name => $value) {
							$attributes[$prop . '.' . $name] = $value;
						}
						$type = $tpl[$container][1];
						$this->validateAttributes($type, $attributes);
						foreach ($attributes as $name => $value) {
							if (isset($tpl[$container][2][$name])) {
								throw new TConfigurationException('template_property_duplicated', $name);
							}
							$tpl[$container][2][$name] = $value;
						}
					} else {  // regular property
						$prop = strtolower($match[3][0]);
						$stack[] = '@' . $prop;
						if (!$expectPropEnd) {
							if ($matchStart > $textStart) {
								$tpl[$c++] = [$container, substr($input, $textStart, $matchStart - $textStart)];
							}
							$textStart = $matchEnd + 1;
							$expectPropEnd = true;
						}
					}
				} elseif (strpos($str, '</prop:') === 0) {	// closing property
					$prop = strtolower($match[3][0]);
					if (empty($stack)) {
						throw new TConfigurationException('template_closingtag_unexpected', "</prop:$prop>");
					}
					$name = array_pop($stack);
					if ($name !== '@' . $prop) {
						$tag = $name[0] === '@' ? '</prop:' . substr($name, 1) . '>' : "</com:$name>";
						throw new TConfigurationException('template_closingtag_expected', $tag, "</prop:$prop>");
					}
					if (($last = count($stack)) < 1 || $stack[$last - 1][0] !== '@') {
						if ($matchStart > $textStart) {
							$value = substr($input, $textStart, $matchStart - $textStart);
							if (substr($prop, -8, 8) === 'template') {
								$value = $this->parseTemplateProperty($value, $textStart);
							} else {
								$value = $this->parseAttribute($value);
							}
							if ($container >= 0) {
								$type = $tpl[$container][1];
								$this->validateAttributes($type, [$prop => $value]);
								if (isset($tpl[$container][2][$prop])) {
									throw new TConfigurationException('template_property_duplicated', $prop);
								}
								$tpl[$container][2][$prop] = $value;
							} else {	// a property for the template control
								$this->_directive[$prop] = $value;
							}
							$textStart = $matchEnd + 1;
						}
						$expectPropEnd = false;
					}
				} elseif (strpos($str, '<!--') === 0) {	// comments
					if ($expectPropEnd) {
						throw new TConfigurationException('template_comments_forbidden');
					}
					if ($matchStart > $textStart) {
						$tpl[$c++] = [$container, substr($input, $textStart, $matchStart - $textStart)];
					}
					$textStart = $matchEnd + 1;
				} else {
					throw new TConfigurationException('template_matching_unexpected', $match);
				}
			}
			if (!empty($stack)) {
				$name = array_pop($stack);
				$tag = $name[0] === '@' ? '</prop:' . substr($name, 1) . '>' : "</com:$name>";
				throw new TConfigurationException('template_closingtag_expected', $tag, "nothing");
			}
			if ($textStart < strlen($input)) {
				$tpl[$c++] = [$container, substr($input, $textStart)];
			}
		} catch (\Exception $e) {
			if (($e instanceof TException) && ($e instanceof TTemplateException)) {
				throw $e;
			}
			if ($matchEnd === 0) {
				$line = $this->_startingLine + 1;
			} else {
				$line = $this->_startingLine + count(explode("\n", substr($input, 0, $matchEnd + 1)));
			}
			$this->handleException($e, $line, $input);
		}

		if ($this->_directive === null) {
			$this->_directive = [];
		}

		// optimization by merging consecutive strings, expressions, statements and bindings
		$objects = [];
		$parent = null;
		$id = null;
		$merged = [];
		foreach ($tpl as $id => $object) {
			if (isset($object[2]) || $object[0] !== $parent) {
				if ($parent !== null) {
					if (count($merged[1]) === 1 && is_string($merged[1][0])) {
						$objects[$id - 1] = [$merged[0], $merged[1][0]];
					} else {
						$objects[$id - 1] = [$merged[0], new TCompositeLiteral($merged[1])];
					}
				}
				if (isset($object[2])) {
					$parent = null;
					$objects[$id] = $object;
				} else {
					$parent = $object[0];
					$merged = [$parent, [$object[1]]];
				}
			} else {
				$merged[1][] = $object[1];
			}
		}
		if ($parent !== null && $id !== null) {
			if (count($merged[1]) === 1 && is_string($merged[1][0])) {
				$objects[$id] = [$merged[0], $merged[1][0]];
			} else {
				$objects[$id] = [$merged[0], new TCompositeLiteral($merged[1])];
			}
		}
		$tpl = $objects;
		return $objects;
	}

	/**
	 * Parses the attributes of a tag from a string.
	 * @param string $str the string to be parsed.
	 * @param mixed $offset
	 * @return array attribute values indexed by names.
	 */
	protected function parseAttributes($str, $offset)
	{
		if ($str === '') {
			return [];
		}
		$pattern = '/([\w\.\-]+)\s*=\s*(\'.*?\'|".*?"|<%.*?%>)/msS';
		$attributes = [];
		$n = preg_match_all($pattern, $str, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
		for ($i = 0; $i < $n; ++$i) {
			$match = &$matches[$i];
			$name = strtolower($match[1][0]);
			if (isset($attributes[$name])) {
				throw new TConfigurationException('template_property_duplicated', $name);
			}
			$value = $match[2][0];
			if (substr($name, -8, 8) === 'template') {
				if ($value[0] === '\'' || $value[0] === '"') {
					$attributes[$name] = $this->parseTemplateProperty(substr($value, 1, strlen($value) - 2), $match[2][1] + 1);
				} else {
					$attributes[$name] = $this->parseTemplateProperty($value, $match[2][1]);
				}
			} else {
				if ($value[0] === '\'' || $value[0] === '"') {
					$attributes[$name] = $this->parseAttribute(substr($value, 1, strlen($value) - 2));
				} else {
					$attributes[$name] = $this->parseAttribute($value);
				}
			}
		}
		return $attributes;
	}

	protected function parseTemplateProperty($content, $offset)
	{
		$line = $this->_startingLine + count(explode("\n", substr($this->_content, 0, $offset))) - 1;
		return [self::CONFIG_TEMPLATE, new TTemplate($content, $this->_contextPath, $this->_tplFile, $line, false)];
	}

	/**
	 * Parses a single attribute.
	 * @param string $value the string to be parsed.
	 * @return array attribute initialization
	 */
	protected function parseAttribute($value)
	{
		if (($n = preg_match_all('/<%[#=].*?%>/msS', $value, $matches, PREG_OFFSET_CAPTURE)) > 0) {
			$isDataBind = false;
			$textStart = 0;
			$expr = '';
			for ($i = 0; $i < $n; ++$i) {
				$match = $matches[0][$i];
				$token = $match[0];
				$offset = $match[1];
				$length = strlen($token);
				if ($token[2] === '#') {
					$isDataBind = true;
				}
				if ($offset > $textStart) {
					$expr .= ".'" . strtr(substr($value, $textStart, $offset - $textStart), ["'" => "\\'", "\\" => "\\\\"]) . "'";
				}
				$expr .= '.(' . substr($token, 3, $length - 5) . ')';
				$textStart = $offset + $length;
			}
			$length = strlen($value);
			if ($length > $textStart) {
				$expr .= ".'" . strtr(substr($value, $textStart, $length - $textStart), ["'" => "\\'", "\\" => "\\\\"]) . "'";
			}
			if ($isDataBind) {
				return [self::CONFIG_DATABIND, ltrim($expr, '.')];
			} else {
				return [self::CONFIG_EXPRESSION, ltrim($expr, '.')];
			}
		} elseif (preg_match('/\\s*(<%~.*?%>|<%\\$.*?%>|<%\\[.*?\\]%>|<%\/.*?%>)\\s*/msS', $value, $matches) && $matches[0] === $value) {
			$value = $matches[1];
			if ($value[2] === '~') {
				return [self::CONFIG_ASSET, trim(substr($value, 3, strlen($value) - 5))];
			} elseif ($value[2] === '[') {
				return [self::CONFIG_LOCALIZATION, trim(substr($value, 3, strlen($value) - 6))];
			} elseif ($value[2] === '$') {
				return [self::CONFIG_PARAMETER, trim(substr($value, 3, strlen($value) - 5))];
			} elseif ($value[2] === '/') {
				$literal = trim(substr($value, 3, strlen($value) - 5));
				return [self::CONFIG_EXPRESSION, "rtrim(dirname(\$this->getApplication()->getRequest()->getApplicationUrl()), '\/').'/$literal'"];
			}
		}
		return $value;
	}

	protected function validateAttributes($type, $attributes)
	{
		Prado::using($type);
		if (($pos = strrpos($type, '.')) !== false) {
			$className = substr($type, $pos + 1);
		} else {
			$className = $type;
		}
		$class = new \ReflectionClass($className);
		if (is_subclass_of($className, '\Prado\Web\UI\TControl') || $className === '\Prado\Web\UI\TControl') {
			foreach ($attributes as $name => $att) {
				if (($pos = strpos($name, '.')) !== false) {
					// a subproperty, so the first segment must be readable
					$subname = substr($name, 0, $pos);
					if (!$class->hasMethod('get' . $subname)) {
						throw new TConfigurationException('template_property_unknown', $type, $subname);
					}
				} elseif (strncasecmp($name, 'on', 2) === 0) {
					// an event
					if (!$class->hasMethod($name)) {
						throw new TConfigurationException('template_event_unknown', $type, $name);
					} elseif (!is_string($att)) {
						throw new TConfigurationException('template_eventhandler_invalid', $type, $name);
					}
				} else {
					// a simple property
					if (!($class->hasMethod('set' . $name) || $class->hasMethod('setjs' . $name) || $this->isClassBehaviorMethod($class, 'set' . $name))) {
						if ($class->hasMethod('get' . $name) || $class->hasMethod('getjs' . $name)) {
							throw new TConfigurationException('template_property_readonly', $type, $name);
						} else {
							throw new TConfigurationException('template_property_unknown', $type, $name);
						}
					} elseif (is_array($att) && $att[0] !== self::CONFIG_EXPRESSION) {
						if (strcasecmp($name, 'id') === 0) {
							throw new TConfigurationException('template_controlid_invalid', $type);
						} elseif (strcasecmp($name, 'skinid') === 0) {
							throw new TConfigurationException('template_controlskinid_invalid', $type);
						}
					}
				}
			}
		} elseif (is_subclass_of($className, '\Prado\TComponent') || $className === '\Prado\TComponent') {
			foreach ($attributes as $name => $att) {
				if (is_array($att) && ($att[0] === self::CONFIG_DATABIND)) {
					throw new TConfigurationException('template_databind_forbidden', $type, $name);
				}
				if (($pos = strpos($name, '.')) !== false) {
					// a subproperty, so the first segment must be readable
					$subname = substr($name, 0, $pos);
					if (!$class->hasMethod('get' . $subname)) {
						throw new TConfigurationException('template_property_unknown', $type, $subname);
					}
				} elseif (strncasecmp($name, 'on', 2) === 0) {
					throw new TConfigurationException('template_event_forbidden', $type, $name);
				} else {
					// id is still alowed for TComponent, even if id property doesn't exist
					if (strcasecmp($name, 'id') !== 0 && !($class->hasMethod('set' . $name) || $this->isClassBehaviorMethod($class, 'set' . $name))) {
						if ($class->hasMethod('get' . $name)) {
							throw new TConfigurationException('template_property_readonly', $type, $name);
						} else {
							throw new TConfigurationException('template_property_unknown', $type, $name);
						}
					}
				}
			}
		} else {
			throw new TConfigurationException('template_component_required', $type);
		}
		return $class->getName();
	}

	/**
	 * @return array list of included external template files
	 */
	public function getIncludedFiles()
	{
		return $this->_includedFiles;
	}

	/**
	 * Handles template parsing exception.
	 * This method rethrows the exception caught during template parsing.
	 * It adjusts the error location by giving out correct error line number and source file.
	 * @param \Exception $e template exception
	 * @param int $line line number
	 * @param null|string $input template string if no source file is used
	 */
	protected function handleException($e, $line, $input = null)
	{
		$srcFile = $this->_tplFile;

		if (($n = count($this->_includedFiles)) > 0) { // need to adjust error row number and file name
			for ($i = $n - 1; $i >= 0; --$i) {
				if ($this->_includeAtLine[$i] <= $line) {
					if ($line < $this->_includeAtLine[$i] + $this->_includeLines[$i]) {
						$line = $line - $this->_includeAtLine[$i] + 1;
						$srcFile = $this->_includedFiles[$i];
						break;
					} else {
						$line = $line - $this->_includeLines[$i] + 1;
					}
				}
			}
		}
		$exception = new TTemplateException('template_format_invalid', $e->getMessage());
		$exception->setLineNumber($line);
		if (!empty($srcFile)) {
			$exception->setTemplateFile($srcFile);
		} else {
			$exception->setTemplateSource($input);
		}
		throw $exception;
	}

	/**
	 * Preprocesses the template string by including external templates
	 * @param string $input template string
	 * @return string expanded template string
	 */
	protected function preprocess($input)
	{
		if ($n = preg_match_all('/<%include(.*?)%>/', $input, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
			for ($i = 0; $i < $n; ++$i) {
				$filePath = Prado::getPathOfNamespace(trim($matches[$i][1][0]), TTemplateManager::TEMPLATE_FILE_EXT);
				if ($filePath !== null && is_file($filePath)) {
					$this->_includedFiles[] = $filePath;
				} else {
					$errorLine = count(explode("\n", substr($input, 0, $matches[$i][0][1] + 1)));
					$this->handleException(new TConfigurationException('template_include_invalid', trim($matches[$i][1][0])), $errorLine, $input);
				}
			}
			$base = 0;
			for ($i = 0; $i < $n; ++$i) {
				$ext = file_get_contents($this->_includedFiles[$i]);
				$length = strlen($matches[$i][0][0]);
				$offset = $base + $matches[$i][0][1];
				$this->_includeAtLine[$i] = count(explode("\n", substr($input, 0, $offset)));
				$this->_includeLines[$i] = count(explode("\n", $ext));
				$input = substr_replace($input, $ext, $offset, $length);
				$base += strlen($ext) - $length;
			}
		}

		return $input;
	}

	/**
	 * Checks if the given method belongs to a previously attached class behavior.
	 * @param \ReflectionClass $class
	 * @param string $method
	 * @return bool
	 */
	protected function isClassBehaviorMethod(\ReflectionClass $class, $method)
	{
		$component = new \ReflectionClass('\Prado\TComponent');
		$behaviors = $component->getStaticProperties();
		if (!isset($behaviors['_um'])) {
			return false;
		}
		foreach ($behaviors['_um'] as $name => $list) {
			if (strtolower($class->getShortName()) !== $name && !$class->isSubclassOf($name)) {
				continue;
			}
			foreach ($list as $param) {
				$behavior = $param->getBehavior();
				if (is_array($behavior)) {
					if (method_exists($behavior['class'], $method)) {
						return true;
					}
				} elseif (method_exists($behavior, $method)) {
					return true;
				}
			}
		}
		return false;
	}
}
