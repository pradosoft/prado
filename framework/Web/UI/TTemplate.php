<?php

/**
 * TTemplateManager and TTemplate class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TException;
use Prado\Exceptions\TTemplateException;
use Prado\Prado;
use Prado\TComponent;
use Prado\TComponentReflection;
use Prado\Web\Javascripts\TJavaScriptLiteral;
use Prado\Web\Services\TPageService;

/**
 * TTemplate class
 *
 * TTemplate implements PRADO template parsing logic.
 * A TTemplate object represents a parsed PRADO control template.
 * It can instantiate the template as child controls of a specified control.
 * The template format is like HTML, with the following special tags introduced,
 * - component tags: a component tag represents the configuration of a component.
 *   The tag name is in the format of com:ComponentType, where ComponentType is
 *   the component class name. Component tags must be well-formed. Attributes of
 *   the component tag are treated as either property initial values, event handler
 *   attachment, or regular tag attributes.
 * - property tags: property tags are used to set large block of attribute values.
 *   The property tag name is in the format of <prop:AttributeName> where AttributeName
 *   can be a property name, an event name or a regular tag attribute name.
 * - group subproperty tags: subproperties of a common property can be configured using
 *   <prop:MainProperty SubProperty1="Value1" SubProperty2="Value2" .../>
 * - directive: directive specifies the property values for the template owner.
 *   It is in the format of <%@ property name-value pairs %>;
 * - expressions: They are in the format of <%= PHP expression %> and
 *   <%% PHP statements %>
 * - Template comments are formatted as  <!--- comments --->, which will be entirely
 *     stripped from the output.
 *
 * Tags other than the above are not required to be well-formed.
 *
 * A TTemplate object represents a parsed PRADO template. To instantiate the
 * template for a particular control, call {@see instantiateIn($control)}, which
 * will create and initialize all components specified in the template and
 * set their parent as $control.
 *
 * Attribute Name Transformations:
 * - Hyphens (dashes) in attribute names are converted to underscores for PHP method lookup.
 *   For example, `--webkit-toggle="modal"` becomes `set__webkit_toggle("modal")` or sets
 *   the `data_toggle` property via `__set()`. This allows HTML5 data attributes to map
 *   to PHP setter methods with underscores.
 * - Property and attribute names are matched case-insensitively for lookup, but the
 *   original case is preserved when calling setters or magic `__set()`. For subproperties
 *   like `Style.ForeColor`, the dot notation accesses the subproperty directly.
 *
 * @note AGENTS: For visibility on screen, the code blocks below must remain dense.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Brad Anderson <belisoful@icloud.com> dash-andCaseSupport.
 * @since 3.0
 * @ method \Prado\Web\Services\TPageService getService()
 * @phpstan-consistent-constructor
 */
class TTemplate extends \Prado\TApplicationComponent implements ITemplate
{
	private const PARSE_COMMENTS = '<!---.*?---!?>';

	private const PARSE_SINGLE_QUOTED_VALUE = '\'.*?\'';
	private const PARSE_DOUBLE_QUOTED_VALUE = '".*?"';
	private const PARSE_EXPRESSION_VALUE = '.*?';

	private const PARSE_CONTROL_NAME = '[\w\.\\\]+';
	private const PARSE_PROP_NAME = '[\w\.\-]+';
	private const PARSE_EQUALS = '\s*=\s*';

	/**
	 *  '<!---.*?---!?>' - template comments (stripped during parse)
	 *	'<\/?com:([\w\.\\\]+)((?:\s*[\w\.\-]+\s*=\s*\'.*?\'|\s*[\w\.\-]+\s*=\s*".*?"|\s*[\w\.\-]+\s*=\s*<%.*?%>)*)\s*\/?>' - component tags & attributes
	 *	'<%@\s*((?:\s*[\w\.]+\s*=\s*\'.*?\'|\s*[\w\.]+\s*=\s*".*?")*)\s*%>'  - directives
	 *	'<%[%#~\/\\$=\\[](.*?)%>'  - expressions
	 *  '<prop:([\w\.\-]+)((?:\s*[\w\.\-]+=\'.*?\'|\s*[\w\.\-]+=".*?"|\s*[\w\.\-]+=<%.*?%>)*)\s*\/>' - closed group subproperty tag
	 *	'<\/?prop:([\w\.\-]+)\s*>'  - open (and end) group subproperty tag
	 */
	public const REGEX_RULES = '/<!---.*?---!?>|<\/?com:([\w\.\\\]+)((?:\s*[\w\.\-]+\s*=\s*\'.*?\'|\s*[\w\.\-]+\s*=\s*".*?"|\s*[\w\.\-]+\s*=\s*<%.*?%>)*)\s*\/?>|<\/?prop:([\w\.\-]+)\s*>|<%@\s*((?:\s*[\w\.]+\s*=\s*\'.*?\'|\s*[\w\.]+\s*=\s*".*?")*)\s*%>|<%[%#~\/\\$=\\[](.*?)%>|<prop:([\w\.\-]+)((?:\s*[\w\.\-]+\s*=\s*\'.*?\'|\s*[\w\.\-]+\s*=\s*".*?"|\s*[\w\.\-]+\s*=\s*<%.*?%>)*)\s*\/>/msS';

	/**
	 * Different configurations of component property/event/attribute
	 */
	public const CONFIG_VALUE = 1;
	public const CONFIG_DATABIND = 2;
	public const CONFIG_EXPRESSION = 3;
	public const CONFIG_ASSET = 4;
	public const CONFIG_PARAMETER = 5;
	public const CONFIG_LOCALIZATION = 6;
	public const CONFIG_TEMPLATE = 7;

	/** Template Info Keys */
	public const TPL_PARENT_INDEX = 0;
	public const TPL_TYPE = 1;
	public const TPL_PROPS = 2;

	/** Property Info Keys */
	public const PROP_TYPE = 0;
	public const PROP_NAME = 1;
	public const PROP_VALUE = 2;

	/** @var array list of component tags and strings */
	private $_tpl = [];
	/** @var array list of directive settings */
	private $_directive = [];
	/** @var string context path */
	private $_contextPath;
	/** @var string template file path (if available) */
	private $_tplFile;
	/** @var int the line number that parsing starts from (internal use) */
	private $_startingLine = 0;
	/** @var string template content to be parsed */
	private $_content;
	/** @var bool tells whether the class and attributes should be validated before moving on	 */
	private $_attributevalidation = true;
	/** @var bool whether this template is a source template */
	private $_sourceTemplate = true;
	/** @var string hash code of the template */
	private $_hashCode = '';

	/** @var \Prado\Web\UI\TControl */
	private $_tplControl;
	/** @var array<string> */
	private $_includedFiles = [];
	/** @var array<int> */
	private $_includeAtLine = [];
	/** @var array<int> */
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
	 * @return bool whether or not validation of the template is active
	 * @since 4.2.0
	 */
	public function getAttributeValidation()
	{
		return $this->_attributevalidation;
	}

	/**
	 * @param bool $value whether or not validation of the template is active
	 * @since 4.2.0
	 */
	public function setAttributeValidation($value)
	{
		$this->_attributevalidation = $value;
	}

	/**
	 * Instantiates the template.
	 * Content in the template will be instantiated as components and text strings
	 * and passed to the specified parent control.
	 * @param \Prado\Web\UI\TControl $tplControl the control who owns the template
	 * @param null|TControl $parentControl the control who will become the root parent of the controls on the template. If null, it uses the template control.
	 * @see \Prado\Web\Services\TPageService getService() - when the $tplControl has
	 *			no page, this asks the service for the requested page; for applying style sheets
	 */
	public function instantiateIn($tplControl, $parentControl = null)
	{
		$this->_tplControl = $tplControl;
		if ($parentControl === null) {
			$parentControl = $tplControl;
		}
		if (($page = $tplControl->getPage()) === null && ($service = $this->getService()) !== null && $service->isa(TPageService::class)) {
			$page = $service->getRequestedPage();
		}
		$controls = [];
		$directChildren = [];
		$appParameters = $this->getApplication()->getParameters();
		foreach ($this->_tpl as $tplKey => $tplInfo) {
			if ($tplInfo[self::TPL_PARENT_INDEX] === -1) {
				$parent = $parentControl;
			} elseif (isset($controls[$tplInfo[self::TPL_PARENT_INDEX]])) {
				$parent = $controls[$tplInfo[self::TPL_PARENT_INDEX]];
			} else {
				continue;
			}
			if (isset($tplInfo[self::TPL_PROPS])) {	// is a component or control, b/c it has properties
				$component = Prado::createComponent($tplInfo[self::TPL_TYPE]);
				$properties = &$tplInfo[self::TPL_PROPS];
				if ($component instanceof TControl) {
					if ($component instanceof \Prado\Web\UI\WebControls\TOutputCache) {
						$component->setCacheKeyPrefix($this->_hashCode . $tplKey);
					}
					$component->setTemplateControl($tplControl);
					if (isset($properties['id'])) {
						switch ($properties['id'][self::PROP_TYPE]) {
							case self::CONFIG_VALUE:break;
							case self::CONFIG_PARAMETER:
								$properties['id'][self::PROP_VALUE] = $appParameters->itemAt($properties['id'][self::PROP_VALUE]);
								break;
							default:
								$properties['id'][self::PROP_VALUE] = $component->evaluateExpression($properties['id'][self::PROP_VALUE]);
						}
						$tplControl->registerObject($properties['id'][self::PROP_VALUE], $component);
					}
					if (isset($properties['skinid'])) {
						switch ($properties['skinid'][self::PROP_TYPE]) {
							case self::CONFIG_VALUE:break;
							case self::CONFIG_PARAMETER:
								$properties['skinid'][self::PROP_VALUE] = $appParameters->itemAt($properties['skinid'][self::PROP_VALUE]);
								break;
							default:
								$properties['skinid'][self::PROP_VALUE] = $component->evaluateExpression($properties['skinid'][self::PROP_VALUE]);
						}
						$component->setSkinID($properties['skinid'][self::PROP_VALUE]);
						unset($properties['skinid']);
					}
					$component->trackViewState(false);
					$component->applyStyleSheetSkin($page);
					foreach ($properties as $attrKey => $propInfo) {
						$this->configureControl($component, $attrKey, $propInfo);
					}
					$component->trackViewState(true);
					if ($parent === $parentControl) {
						$directChildren[] = $component;
					} else {
						$component->createdOnTemplate($parent);
					}
					if ($component->getAllowChildControls()) {
						$controls[$tplKey] = $component;
					}
				} elseif ($component instanceof TComponent) {
					$controls[$tplKey] = $component;
					if (isset($properties['id'])) {
						if (!$component->hasProperty('id')) {
							unset($properties['id']);
						} else {
							switch ($properties['id'][self::PROP_TYPE]) {
								case self::CONFIG_VALUE:break;
								case self::CONFIG_PARAMETER:
									$properties['id'][self::PROP_VALUE] = $appParameters->itemAt($properties['id'][self::PROP_VALUE]);
									break;
								default:
									$properties['id'][self::PROP_VALUE] = $component->evaluateExpression($properties['id'][self::PROP_VALUE]);
							}
							$tplControl->registerObject($properties['id'][self::PROP_VALUE], $component);
						}
					}
					foreach ($properties as $attrKey => $propInfo) {
						$this->configureComponent($component, $attrKey, $propInfo);
					}
					if ($parent === $parentControl) {
						$directChildren[] = $component;
					} else {
						$component->createdOnTemplate($parent);
					}
				}
			} else {
				if ($tplInfo[self::TPL_TYPE] instanceof TCompositeLiteral) {
					// need to clone a new object because the one in template is reused
					$o = clone $tplInfo[self::TPL_TYPE];
					$o->setContainer($tplControl);
					if ($parent === $parentControl) {
						$directChildren[] = $o;
					} else {
						$parent->addParsedObject($o);
					}
				} else {
					if ($parent === $parentControl) {
						$directChildren[] = $tplInfo[self::TPL_TYPE];
					} else {
						$parent->addParsedObject($tplInfo[self::TPL_TYPE]);
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
	 * @param string $attrKey property name
	 * @param mixed $propInfo property initial value
	 */
	protected function configureControl($control, $attrKey, $propInfo)
	{
		if (strncasecmp($attrKey, 'on', 2) === 0) {		// is an event
			$this->configureEvent($control, $attrKey, $propInfo, $control);
		} else {
			$this->configureProperty($control, $attrKey, $propInfo);
		}
	}

	/**
	 * Configures a property of a non-control component.
	 * @param \Prado\TComponent $component component to be configured
	 * @param string $attrKey property name
	 * @param mixed $propInfo property initial value
	 */
	protected function configureComponent($component, $attrKey, $propInfo)
	{
		$this->configureProperty($component, $attrKey, $propInfo);
	}

	/**
	 * Configures an event for a control.
	 * @param \Prado\Web\UI\TControl $control control to be configured
	 * @param string $attrKey event name
	 * @param string $propInfo event handler
	 * @param \Prado\Web\UI\TControl $contextControl context control
	 */
	protected function configureEvent($control, $attrKey, $propInfo, $contextControl)
	{
		if (strpos($propInfo[self::PROP_VALUE], '.') === false) {
			$control->attachEventHandler($attrKey, [$contextControl, 'TemplateControl.' . $propInfo[self::PROP_VALUE]]);
		} else {

			$control->attachEventHandler($attrKey, [$contextControl, $propInfo[self::PROP_VALUE]]);
		}
	}

	/**
	 * Configures a simple property for a component.
	 * Note: setSubProperty does set the Property on the component if there is no `.`.
	 * @param \Prado\Web\UI\TControl $component component to be configured
	 * @param string $attrKey property name
	 * @param array $propInfo [type, property value, full name]
	 */
	protected function configureProperty($component, $attrKey, $propInfo)
	{
		$propName = $this->attributeToMethodName($propInfo[self::PROP_NAME]);
		switch ($propInfo[self::PROP_TYPE]) {
			case self::CONFIG_VALUE:
				$pos = strrpos($propName, '.');
				$prop = substr($propName, $pos !== false ? $pos + 1 : 0);
				if (strncasecmp($prop, 'js', 2) === 0) {
					$jsValue = $propInfo[self::PROP_VALUE];
					if ($jsValue && !($jsValue instanceof TJavaScriptLiteral)) {
						$jsValue = new TJavaScriptLiteral($jsValue);
					}
				}
				$component->setSubProperty($propName, $propInfo[self::PROP_VALUE]);
				break;
			case self::CONFIG_DATABIND:		// databinding
				$component->bindProperty($propName, $propInfo[self::PROP_VALUE]);
				break;
			case self::CONFIG_EXPRESSION:		// expression
				if ($component instanceof TControl) {
					$component->autoBindProperty($propName, $propInfo[self::PROP_VALUE]);
				} else {
					$component->setSubProperty($propName, $this->_tplControl->evaluateExpression($propInfo[self::PROP_VALUE]));
				}
				break;
			case self::CONFIG_TEMPLATE:
				$component->setSubProperty($propName, $propInfo[self::PROP_VALUE]);
				break;
			case self::CONFIG_ASSET:		// asset URL
				$url = $this->publishFilePath($this->_contextPath . DIRECTORY_SEPARATOR . $propInfo[self::PROP_VALUE]);
				$component->setSubProperty($propName, $url);
				break;
			case self::CONFIG_PARAMETER:		// application parameter
				$component->setSubProperty($propName, $this->getApplication()->getParameters()->itemAt($propInfo[self::PROP_VALUE]));
				break;
			case self::CONFIG_LOCALIZATION:
				$component->setSubProperty($propName, Prado::localize($propInfo[self::PROP_VALUE]));
				break;
			default:
				$propType = $propInfo[self::PROP_TYPE];
				throw new TConfigurationException('template_tag_unexpected', $propName . " (tag-type: {$propType})", $propInfo[self::PROP_VALUE]);
		}
	}

	/**
	 * Converts attribute names with dashes to method name format.
	 * @param string $propName property name with possible dashes
	 * @return string property name with dashes replaced by underscores
	 * @since 4.3.3
	 */
	protected function attributeToMethodName($propName)
	{
		return str_replace('-', '_', $propName);
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
	 * retrieved via {@see getDirective}, which returns an array consisting of
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
		$tagStack = [];
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
				if (strncasecmp($str, '<com:', 5) === 0) {	// opening component tag
					if ($expectPropEnd) {
						continue;
					}
					if ($matchStart > $textStart) {
						$tpl[$c++] = $this->packTemplate($container, substr($input, $textStart, $matchStart - $textStart));
					}
					$textStart = $matchEnd + 1;
					$tag = $match[1][0];
					$attributes = $this->parseAttributes($match[2][0], $match[2][1]);
					$class = $this->validateAttributes($tag, $attributes);
					$tpl[$c++] = $this->packTemplate($container, $class, $attributes);
					if ($str[strlen($str) - 2] !== '/') {  // open tag, stack the tag w/ case
						$tagStack[] = $tag;
						$container = $c - 1;
					}
				} elseif (strncasecmp($str, '</com:', 6) === 0) {	// closing component tag
					if ($expectPropEnd) {
						continue;
					}
					if ($matchStart > $textStart) {
						$tag = substr($input, $textStart, $matchStart - $textStart);
						$tpl[$c++] = $this->packTemplate($container, $tag);
					}
					$textStart = $matchEnd + 1;
					$tag = $match[1][0];
					if (empty($tagStack)) {
						throw new TConfigurationException('template_closingtag_unexpected', "</com:$tag>");
					}
					$openTag = array_pop($tagStack);
					if ($openTag !== $tag) {
						$expected = $openTag[0] === '@' ? '</prop:' . substr($openTag, 1) . '>' : "</com:$openTag>";
						throw new TConfigurationException('template_closingtag_expected', $expected, "</com:$tag>");
					}
					$container = $tpl[$container][self::TPL_PARENT_INDEX];
				} elseif (strncasecmp($str, '<%@', 3) === 0) {	// directive
					if ($expectPropEnd) {
						continue;
					}
					if ($matchStart > $textStart) {
						$tpl[$c++] = $this->packTemplate($container, substr($input, $textStart, $matchStart - $textStart));
					}
					$textStart = $matchEnd + 1;
					if (isset($tpl[0]) || $this->_directive !== null) { // requires: no parsed template, no directives
						throw new TConfigurationException('template_directive_nonunique');
					}
					$this->_directive = $this->parseAttributes($match[4][0], $match[4][1], true);
				} elseif (strncasecmp($str, '<%', 2) === 0) {	// expression
					if ($expectPropEnd) {
						continue;
					}
					if ($matchStart > $textStart) {
						$tpl[$c++] = $this->packTemplate($container, substr($input, $textStart, $matchStart - $textStart));
					}
					$textStart = $matchEnd + 1;
					$expressionInfo = $this->parseExpression($str[2], trim($match[5][0]));
					$tpl[$c++] = $this->packTemplate($container, $expressionInfo);
				} elseif (strncasecmp($str, '<prop:', 6) === 0) {	// opening property
					if (str_ends_with($str, '/>')) {  //subproperties
						if ($expectPropEnd) {
							continue;
						}
						if ($matchStart > $textStart) {
							$tpl[$c++] = $this->packTemplate($container, substr($input, $textStart, $matchStart - $textStart));
						}
						$textStart = $matchEnd + 1;
						//$propName = strtolower($match[6][0]);
						$propName = ($match[6][0]);
						$propKey = strtolower($propName);
						$attrs = $this->parseAttributes($match[7][0], $match[7][1]);
						$attributes = [];
						foreach ($attrs as $attrKey => $value) {
							$value[self::PROP_NAME] = $propName . '.' . $value[self::PROP_NAME];
							$attributes[$propKey . '.' . $attrKey] = $value;
						}
						$this->validateAttributes($tpl[$container][self::TPL_TYPE], $attributes);
						foreach ($attributes as $attrKey => $propInfo) {
							if (isset($tpl[$container][self::TPL_PROPS][$attrKey])) {
								throw new TConfigurationException('template_property_duplicated', $attrKey);
							}
							$tpl[$container][self::TPL_PROPS][$attrKey] = $propInfo;
						}
					} else {  // regular opening property
						$propName = $match[3][0]; // Preserve case, match on other side
						$tagStack[] = '@' . $propName;
						if (!$expectPropEnd) {
							if ($matchStart > $textStart) {
								$tpl[$c++] = $this->packTemplate($container, substr($input, $textStart, $matchStart - $textStart));
							}
							$textStart = $matchEnd + 1;
							$expectPropEnd = true;
						}
					}
				} elseif (strncasecmp($str, '</prop:', 7) === 0) {	// closing property
					$propName = $match[3][0];
					$propKey = strtolower($propName);
					if (empty($tagStack)) {
						throw new TConfigurationException('template_closingtag_unexpected', "</prop:$propName>");
					}
					$openName = array_pop($tagStack);
					if ($openName !== '@' . $propName) {
						$tag = $openName[0] === '@' ? '</prop:' . substr($openName, 1) . '>' : "</com:$openName>";
						throw new TConfigurationException('template_closingtag_expected', $tag, "</prop:$propName>");
					}
					if (($last = count($tagStack)) < 1 || $tagStack[$last - 1][0] !== '@') {
						if ($matchStart > $textStart) {
							$value = substr($input, $textStart, $matchStart - $textStart);
							if (str_ends_with($propKey, 'template')) {
								//if (strncasecmp(substr($propName, -8, 8), 'template', 8) === 0) {
								$propInfo = $this->parseTemplateProperty($propName, $value, $textStart);
							} else {
								$propInfo = $this->parseAttribute($propName, $value);
							}
							if ($container >= 0) {
								$this->validateAttributes($tpl[$container][self::TPL_TYPE], [$propName => $propInfo]);
								if (isset($tpl[$container][self::TPL_PROPS][$propKey])) {
									throw new TConfigurationException('template_property_duplicated', $propKey);
								}
								$tpl[$container][self::TPL_PROPS][$propKey] = $propInfo;
							} else {	// a property for the template control
								$this->_directive[$propName] = $propInfo[self::PROP_VALUE];
							}
							$textStart = $matchEnd + 1;
						}
						$expectPropEnd = false;
					}
				} elseif (strncmp($str, '<!---', 5) === 0) {	// skip template comments
					if ($matchStart > $textStart) {
						$tpl[$c++] = $this->packTemplate($container, substr($input, $textStart, $matchStart - $textStart));
					}
					$textStart = $matchEnd + 1;
				} else {
					throw new TConfigurationException('template_matching_unexpected', $match);
				}
			}
			if (!empty($tagStack)) {
				$openName = array_pop($tagStack);
				$tag = $openName[0] === '@' ? '</prop:' . substr($openName, 1) . '>' : "</com:$openName>";
				throw new TConfigurationException('template_closingtag_expected', $tag, "nothing");
			}
			if ($textStart < strlen($input)) {
				$tpl[$c++] = $this->packTemplate($container, substr($input, $textStart));
			}
		} catch (\Exception $e) {
			if (($e instanceof TException) && ($e instanceof TTemplateException)) {
				throw $e;
			}
			if ($matchEnd === 0) {
				$line = $this->_startingLine + 1;
			} else {
				$line = $this->_startingLine + substr_count($input, "\n", 0, $matchEnd + 1) + 1;
			}
			$this->handleException($e, $line, $input);
		}

		if ($this->_directive === null) {
			$this->_directive = [];
		}

		return $this->optimizeTemplate();
	}

	/**
	 * Creates a template item array with named keys.
	 * $type can be:
	 *		- a string literal to output (no self::TPL_PROPS).
	 * 		- a class name to instance, (self::TPL_PROPS exists).
	 * 		- an array from {@see parseExpression}.
	 *		- a TTemplate
	 * @param int $parentIndex parent container index
	 * @param array|string|TTemplate $type template data or component type
	 * @param null|array $attributes parsed attributes for components
	 * @return array template item with TPL_PARENT_INDEX, TPL_TYPE, and optionally TPL_PROPS
	 * @since 4.3.3
	 */
	protected function packTemplate(int $parentIndex, string|array|TTemplate $type, ?array $attributes = null)
	{
		if ($attributes === null) {
			return [self::TPL_PARENT_INDEX => $parentIndex, self::TPL_TYPE => $type];
		} else {
			return [self::TPL_PARENT_INDEX => $parentIndex, self::TPL_TYPE => $type, self::TPL_PROPS => $attributes];
		}
	}

	/**
	 * Parses a template property value as a nested TTemplate.
	 * @param string $propName property name being parsed
	 * @param string $content template content between tags
	 * @param int $offset character offset in source for line calculation
	 * @return array packed property with CONFIG_TEMPLATE type
	 */
	protected function parseTemplateProperty(string $propName, string $content, int $offset)
	{
		$line = $this->_startingLine + substr_count($this->_content, "\n", 0, $offset); // -1, unlike other substr_count here
		$template = new static($content, $this->_contextPath, $this->_tplFile, $line, false);
		return $this->packProperty(self::CONFIG_TEMPLATE, $propName, $template);
	}

	/**
	 * Parses attributes from a tag string.
	 * @param string $str attribute string like `attr="value" another='value'`
	 * @param int $offset character offset for line tracking
	 * @param bool $directive if true, preserves case and returns direct values
	 * @return array attribute values indexed by lowercased names
	 */
	protected function parseAttributes(string $str, int $offset, bool $directive = false)
	{
		if ($str === '') {
			return [];
		}
		$pattern = '/([\w\.\-]+)\s*=\s*(' . self::PARSE_SINGLE_QUOTED_VALUE . '|' .
											self::PARSE_DOUBLE_QUOTED_VALUE . '|' .
											'<%' . self::PARSE_EXPRESSION_VALUE . '%>)/msS';
		$attributes = [];
		$n = preg_match_all($pattern, $str, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
		for ($i = 0; $i < $n; ++$i) {
			$match = &$matches[$i];
			$propName = $match[1][0];
			$attrKey = strtolower($propName);
			if (isset($attributes[$attrKey])) {
				throw new TConfigurationException('template_property_duplicated', $attrKey);
			}
			$value = $match[2][0];
			if (str_ends_with($attrKey, 'template')) {
				if ($value[0] === '\'' || $value[0] === '"') {
					$parseValue = $this->parseTemplateProperty($propName, substr($value, 1, strlen($value) - 2), $match[2][1] + 1);
				} else {
					$parseValue = $this->parseTemplateProperty($propName, $value, $match[2][1]);
				}
			} else {
				if ($value[0] === '\'' || $value[0] === '"') {
					$parseValue = $this->parseAttribute($propName, substr($value, 1, strlen($value) - 2));
				} else {
					$parseValue = $this->parseAttribute($propName, $value);
				}
			}
			$attributes[$attrKey] = $parseValue;
		}
		if ($directive) { //directives have cased names and direct values
			$directiveAttr = $attributes;
			$attributes = [];
			foreach ($directiveAttr as $attrKey => &$propInfo) {
				$propName = $this->attributeToMethodName($propInfo[self::PROP_NAME]);
				$attributes[$propName] = $propInfo[self::PROP_VALUE];
			}
		}
		return $attributes;
	}

	/**
	 * Parses a single attribute value to determine its type.
	 * @param string $propName property name from template
	 * @param string $value raw attribute value to parse
	 * @return array [PROP_TYPE, PROP_VALUE, PROP_NAME] packed property info
	 */
	protected function parseAttribute($propName, $value)
	{
		if (($n = preg_match_all('/<%[#=]' . self::PARSE_EXPRESSION_VALUE . '%>/msS', $value, $matches, PREG_OFFSET_CAPTURE)) > 0) {
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
			return $this->packProperty($isDataBind ? self::CONFIG_DATABIND : self::CONFIG_EXPRESSION, $propName, ltrim($expr, '.'));
		} elseif (preg_match('/\\s*(<%~' . self::PARSE_EXPRESSION_VALUE . '%>|' .
								   '<%\\$' . self::PARSE_EXPRESSION_VALUE . '%>|' .
								   '<%\\[' . self::PARSE_EXPRESSION_VALUE . '\\]%>|' .
								   '<%\/' . self::PARSE_EXPRESSION_VALUE . '%>)\\s*/msS', $value, $matches) && $matches[0] === $value) {
			$strValue = $matches[1];
			$propType = $this->propertyExpressionCharToType($strValue, $propName);
			$endOffset = ($propType === self::CONFIG_LOCALIZATION) ? -1 : 0;
			$strValue = trim(substr($strValue, 3, strlen($strValue) - 5 + $endOffset));
			if ($propType === self::CONFIG_EXPRESSION) {
				$strValue = "rtrim(dirname(\$this->getApplication()->getRequest()->getApplicationUrl()), '\/').'/$strValue'";
			}
			return $this->packProperty($propType, $propName, $strValue);
		}
		return $this->packProperty(self::CONFIG_VALUE, $propName, $value);
	}

	/**
	 * Creates a property info array with named keys.
	 * @param int $type CONFIG_* constant for property type
	 * @param string $propName original property name from template
	 * @param mixed $value parsed property value
	 * @return array property info with PROP_TYPE, PROP_NAME, PROP_VALUE keys
	 * @since 4.3.3
	 */
	protected function packProperty($type, $propName, $value)
	{
		return [self::PROP_TYPE => $type, self::PROP_NAME => $propName, self::PROP_VALUE => $value];
	}

	/**
	 * Maps expression character to CONFIG type.
	 * @param string $strValue full expression string like <%~path%>
	 * @param string $propName property name for error messages
	 * @throws TConfigurationException if character is not recognized
	 * @return int CONFIG_* constant
	 * @since 4.3.3
	 */
	protected function propertyExpressionCharToType($strValue, $propName)
	{
		switch ($strValue[2]) {
			case '=': return self::CONFIG_EXPRESSION;
			case '#': return self::CONFIG_DATABIND;
			case '~': return self::CONFIG_ASSET;
			case '[': return self::CONFIG_LOCALIZATION;
			case '$': return self::CONFIG_PARAMETER;
			case '/': return self::CONFIG_EXPRESSION;
			default:
				throw new TConfigurationException('template_tag_unexpected', $propName . " (tag-type: {$strValue[2]})", $strValue);
		}
	}

	/**
	 * Parses template expressions like <%= %>, <%% %>, <%# %> etc.
	 * @param string $tplType expression type character (=, %, #, $, ~, /, [)
	 * @param string $literal expression content
	 * @throws TConfigurationException for invalid expression types
	 * @return array [TCompositeLiteral::TYPE_*, expression string]
	 * @since 4.3.3
	 */
	protected function parseExpression($tplType, $literal)
	{
		switch ($tplType) {
			case '=': return [TCompositeLiteral::TYPE_EXPRESSION, $literal];
			case '%': return [TCompositeLiteral::TYPE_STATEMENTS, $literal];
			case '#': return [TCompositeLiteral::TYPE_DATABINDING, $literal];
			case '$': return [TCompositeLiteral::TYPE_EXPRESSION, "\$this->getApplication()->getParameters()->itemAt('$literal')"];
			case '~': return [TCompositeLiteral::TYPE_EXPRESSION, "\$this->publishFilePath('$this->_contextPath/$literal')"];
			case '/': return [TCompositeLiteral::TYPE_EXPRESSION, "rtrim(dirname(\$this->getApplication()->getRequest()->getApplicationUrl()), '\/').'/$literal'"];
			case '[':
				$literal = strtr(trim(substr($literal, 0, strlen($literal) - 1)), ["'" => "\'", "\\" => "\\\\"]);
				return [TCompositeLiteral::TYPE_EXPRESSION, "Prado::localize('$literal')"];
			default:
				throw new TConfigurationException('template_tag_unexpected', " (expression-type: {$tplType})", $literal);
		}
	}

	/**
	 * Validates component attributes against class definitions using reflection.
	 * @param string $type fully qualified component type name
	 * @param array $attributes attribute key-value pairs to validate
	 * @throws TConfigurationException if validation fails
	 * @return string class name from reflection
	 */
	protected function validateAttributes($type, $attributes)
	{
		$className = Prado::usingClass($type);
		if (!is_string($className)) {
			throw new TConfigurationException('template_component_required', $type);
		}
		$class = TComponentReflection::getReflectionClassForType($className);
		if (!$this->_attributevalidation) {
			return $class->getName();	//	Skins don't validate.
		}
		if (is_subclass_of($className, TControl::class) || $className === TControl::class) {
			foreach ($attributes as $attrKey => $propInfo) {
				if (($pos = strpos($attrKey, '.')) !== false) {	// a subproperty, first segment is readable
					$subname = substr($attrKey, 0, $pos);
					if (!$class->hasMethod('get' . $subname)) {
						throw new TConfigurationException('template_property_unknown', $type, $subname);
					}
				} elseif (strncasecmp($attrKey, 'on', 2) === 0) {	// an event
					if (!$class->hasMethod($attrKey)) {
						throw new TConfigurationException('template_event_unknown', $type, $attrKey);
					} elseif ($propInfo[self::PROP_TYPE] !== self::CONFIG_VALUE || !is_string($propInfo[self::PROP_VALUE])) {
						throw new TConfigurationException('template_eventhandler_invalid', $type, $attrKey);
					}
				} else {	// a simple property
					if (!($class->hasMethod('set' . $attrKey) || $class->hasMethod('setjs' . $attrKey) || $this->isClassBehaviorMethod($class, 'set' . $attrKey))) {
						if ($class->hasMethod('get' . $attrKey) || $class->hasMethod('getjs' . $attrKey)) {
							throw new TConfigurationException('template_property_readonly', $type, $attrKey);
						} else {
							throw new TConfigurationException('template_property_unknown', $type, $attrKey);
						}
					} elseif ($propInfo[self::PROP_TYPE] !== self::CONFIG_EXPRESSION && $propInfo[self::PROP_TYPE] !== self::CONFIG_PARAMETER && $propInfo[self::PROP_TYPE] !== self::CONFIG_VALUE) {
						if (strcasecmp($attrKey, 'id') === 0) {
							throw new TConfigurationException('template_controlid_invalid', $type);
						} elseif (strcasecmp($attrKey, 'skinid') === 0) {
							throw new TConfigurationException('template_controlskinid_invalid', $type);
						}
					}
				}
			}
		} elseif (is_subclass_of($className, TComponent::class) || $className === TComponent::class) {
			foreach ($attributes as $attrKey => $propInfo) {
				if ($propInfo[self::PROP_TYPE] === self::CONFIG_DATABIND) {
					throw new TConfigurationException('template_databind_forbidden', $type, $attrKey);
				}
				if (($pos = strpos($attrKey, '.')) !== false) {	// a subproperty, first segment is readable
					$subname = substr($attrKey, 0, $pos);
					if (!$class->hasMethod('get' . $subname)) {
						throw new TConfigurationException('template_property_unknown', $type, $subname);
					}
				} elseif (strncasecmp($attrKey, 'on', 2) === 0) {
					throw new TConfigurationException('template_event_forbidden', $type, $attrKey);
				} else {	// id is still allowed for TComponent, even if id property doesn't exist
					if (strcasecmp($attrKey, 'id') !== 0 && !($class->hasMethod('set' . $attrKey) || $this->isClassBehaviorMethod($class, 'set' . $attrKey))) {
						if ($class->hasMethod('get' . $attrKey)) {
							throw new TConfigurationException('template_property_readonly', $type, $attrKey);
						} else {
							throw new TConfigurationException('template_property_unknown', $type, $attrKey);
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
	 * @return array<string> list of included external template file paths
	 */
	public function getIncludedFiles()
	{
		return $this->_includedFiles;
	}

	/**
	 * Merges consecutive string and literal items to reduce template size.
	 * Consecutive strings, expressions, statements, and bindings with the same
	 * parent are combined into TCompositeLiteral objects for efficiency.
	 * @return array optimized template array
	 * @since 4.3.3
	 */
	protected function optimizeTemplate()
	{
		$tpl = &$this->_tpl;
		$mergedTemplates = [];
		$parent = null;
		$tplKey = null;
		$merged = [];
		foreach ($tpl as $tplKey => $tplInfo) {
			if (isset($tplInfo[self::TPL_PROPS]) || $tplInfo[self::TPL_PARENT_INDEX] !== $parent) {
				if ($parent !== null) {
					if (count($merged[self::TPL_TYPE]) === 1 && is_string($merged[self::TPL_TYPE][0])) {
						$mergedTemplates[$tplKey - 1] = [self::TPL_PARENT_INDEX => $merged[self::TPL_PARENT_INDEX], self::TPL_TYPE => $merged[self::TPL_TYPE][0]];
					} else {
						$mergedTemplates[$tplKey - 1] = [self::TPL_PARENT_INDEX => $merged[self::TPL_PARENT_INDEX], self::TPL_TYPE => new TCompositeLiteral($merged[self::TPL_TYPE])];
					}
				}
				if (isset($tplInfo[self::TPL_PROPS])) {
					$parent = null;
					$mergedTemplates[$tplKey] = $tplInfo;
				} else {
					$parent = $tplInfo[self::TPL_PARENT_INDEX];
					$merged = [self::TPL_PARENT_INDEX => $parent, self::TPL_TYPE => [$tplInfo[self::TPL_TYPE]]];
				}
			} else {
				$merged[self::TPL_TYPE][] = $tplInfo[self::TPL_TYPE];
			}
		}
		if ($parent !== null && $tplKey !== null) {
			if (count($merged[self::TPL_TYPE]) === 1 && is_string($merged[self::TPL_TYPE][0])) {
				$mergedTemplates[$tplKey] = [self::TPL_PARENT_INDEX => $merged[self::TPL_PARENT_INDEX], self::TPL_TYPE => $merged[self::TPL_TYPE][0]];
			} else {
				$mergedTemplates[$tplKey] = [self::TPL_PARENT_INDEX => $merged[self::TPL_PARENT_INDEX], self::TPL_TYPE => new TCompositeLiteral($merged[self::TPL_TYPE])];
			}
		}
		$tpl = $mergedTemplates;
		return $mergedTemplates;
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
					$errorLine = substr_count($input, "\n", 0, $matches[$i][0][1] + 1) + 1;
					$this->handleException(new TConfigurationException('template_include_invalid', trim($matches[$i][1][0])), $errorLine, $input);
				}
			}
			$base = 0;
			for ($i = 0; $i < $n; ++$i) {
				$ext = file_get_contents($this->_includedFiles[$i]);
				$length = strlen($matches[$i][0][0]);
				$offset = $base + $matches[$i][0][1];
				$this->_includeAtLine[$i] = substr_count($input, "\n", 0, $offset) + 1;
				$this->_includeLines[$i] = substr_count($ext, "\n") + 1;
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
		$component = TComponentReflection::getReflectionClassForType(TComponent::class);
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
