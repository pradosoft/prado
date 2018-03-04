<?php
/**
 * TTemplateControl class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI
 */

namespace Prado\Web\UI;

use Prado\Prado;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\WebControls\TContent;
use Prado\Web\UI\WebControls\TContentPlaceHolder;

/**
 * TTemplateControl class.
 * TTemplateControl is the base class for all controls that use templates.
 * By default, a control template is assumed to be in a file under the same
 * directory with the control class file. They have the same file name and
 * different extension name. For template file, the extension name is ".tpl".
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI
 * @since 3.0
 */
class TTemplateControl extends TCompositeControl
{
	/**
	 * template file extension.
	 */
	const EXT_TEMPLATE = '.tpl';

	/**
	 * @var ITemplate the parsed template structure shared by the same control class
	 */
	private static $_template = [];
	/**
	 * @var ITemplate the parsed template structure specific for this control instance
	 */
	private $_localTemplate;
	/**
	 * @var TTemplateControl the master control if any
	 */
	private $_master;
	/**
	 * @var string master control class name
	 */
	private $_masterClass = '';
	/**
	 * @var array list of TContent controls
	 */
	private $_contents = [];
	/**
	 * @var array list of TContentPlaceHolder controls
	 */
	private $_placeholders = [];

	/**
	 * Returns the template object associated with this control object.
	 * @return ITemplate|null the parsed template, null if none
	 */
	public function getTemplate()
	{
		if ($this->_localTemplate === null) {
			$class = get_class($this);
			if (!isset(self::$_template[$class])) {
				self::$_template[$class] = $this->loadTemplate();
			}
			return self::$_template[$class];
		} else {
			return $this->_localTemplate;
		}
	}

	/**
	 * Sets the parsed template.
	 * Note, the template will be applied to the whole control class.
	 * This method should only be used by framework and control developers.
	 * @param ITemplate $value the parsed template
	 */
	public function setTemplate($value)
	{
		$this->_localTemplate = $value;
	}

	/**
	 * @return boolean whether this control is a source template control.
	 * A source template control loads its template from external storage,
	 * such as file, db, rather than from within another template.
	 */
	public function getIsSourceTemplateControl()
	{
		if (($template = $this->getTemplate()) !== null) {
			return $template->getIsSourceTemplate();
		} else {
			return false;
		}
	}

	/**
	 * @return string the directory containing the template. Empty if no template available.
	 */
	public function getTemplateDirectory()
	{
		if (($template = $this->getTemplate()) !== null) {
			return $template->getContextPath();
		} else {
			return '';
		}
	}

	/**
	 * Loads the template associated with this control class.
	 * @return ITemplate the parsed template structure
	 */
	protected function loadTemplate()
	{
		Prado::trace("Loading template " . get_class($this), '\Prado\Web\UI\TTemplateControl');
		$template = $this->getService()->getTemplateManager()->getTemplateByClassName(get_class($this));
		return $template;
	}

	/**
	 * Creates child controls.
	 * This method is overridden to load and instantiate control template.
	 * This method should only be used by framework and control developers.
	 */
	public function createChildControls()
	{
		if ($tpl = $this->getTemplate()) {
			foreach ($tpl->getDirective() as $name => $value) {
				if (is_string($value)) {
					$this->setSubProperty($name, $value);
				} else {
					throw new TConfigurationException('templatecontrol_directive_invalid', get_class($this), $name);
				}
			}
			$tpl->instantiateIn($this);
		}
	}

	/**
	 * Registers a content control.
	 * @param string ID of the content
	 * @param TContent
	 */
	public function registerContent($id, TContent $object)
	{
		if (isset($this->_contents[$id])) {
			throw new TConfigurationException('templatecontrol_contentid_duplicated', $id);
		} else {
			$this->_contents[$id] = $object;
		}
	}

	/**
	 * Registers a content placeholder to this template control.
	 * This method should only be used by framework and control developers.
	 * @param string placeholder ID
	 * @param TContentPlaceHolder placeholder control
	 */
	public function registerContentPlaceHolder($id, TContentPlaceHolder $object)
	{
		if (isset($this->_placeholders[$id])) {
			throw new TConfigurationException('templatecontrol_placeholderid_duplicated', $id);
		} else {
			$this->_placeholders[$id] = $object;
		}
	}

	/**
	 * @return string master class name (in namespace form)
	 */
	public function getMasterClass()
	{
		return $this->_masterClass;
	}

	/**
	 * @param string $value master control class name (in namespace form)
	 */
	public function setMasterClass($value)
	{
		$this->_masterClass = $value;
	}

	/**
	 * @return TTemplateControl|null master control associated with this control, null if none
	 */
	public function getMaster()
	{
		return $this->_master;
	}

	/**
	 * Injects all content controls (and their children) to the corresponding content placeholders.
	 * This method should only be used by framework and control developers.
	 * @param string ID of the content control
	 * @param TContent the content to be injected
	 */
	public function injectContent($id, $content)
	{
		if (isset($this->_placeholders[$id])) {
			$placeholder = $this->_placeholders[$id];
			$controls = $placeholder->getParent()->getControls();
			$loc = $controls->remove($placeholder);
			$controls->insertAt($loc, $content);
		} else {
			throw new TConfigurationException('templatecontrol_placeholder_inexistent', $id);
		}
	}

	/**
	 * Performs the OnInit step for the control and all its child controls.
	 * This method overrides the parent implementation
	 * by ensuring child controls are created first,
	 * and if master class is set, master will be applied.
	 * Only framework developers should use this method.
	 * @param TControl $namingContainer the naming container control
	 */
	protected function initRecursive($namingContainer = null)
	{
		$this->ensureChildControls();
		if ($this->_masterClass !== '') {
			$master = Prado::createComponent($this->_masterClass);
			if (!($master instanceof TTemplateControl)) {
				throw new TInvalidDataValueException('templatecontrol_mastercontrol_invalid');
			}
			$this->_master = $master;
			$this->getControls()->clear();
			$this->getControls()->add($master);
			$master->ensureChildControls();
			foreach ($this->_contents as $id => $content) {
				$master->injectContent($id, $content);
			}
		} elseif (!empty($this->_contents)) {
			throw new TConfigurationException('templatecontrol_mastercontrol_required', get_class($this));
		}
		parent::initRecursive($namingContainer);
	}

	/**
	 * Function to update view controls with data in a given AR object.
	 * View controls and AR object need to have the same name in IDs and Attrs respectively.
	 * @param TActiveRecord $arObj
	 * @param Boolean $throwExceptions Wheter or not to throw exceptions
	 * @author Daniel Sampedro <darthdaniel85@gmail.com>
	 */
	public function tryToUpdateView($arObj, $throwExceptions = false)
	{
		$objAttrs = get_class_vars(get_class($arObj));
		foreach (array_keys($objAttrs) as $key) {
			try {
				if ($key != "RELATIONS") {
					$control = $this->{$key};
					if ($control instanceof TTextBox) {
						$control->Text = $arObj->{$key};
					} elseif ($control instanceof TCheckBox) {
						$control->Checked = (boolean) $arObj->{$key};
					} elseif ($control instanceof TDatePicker) {
						$control->Date = $arObj->{$key};
					}
				} else {
					foreach ($objAttrs["RELATIONS"] as $relKey => $relValues) {
						$relControl = $this->{$relKey};
						switch ($relValues[0]) {
														case TActiveRecord::BELONGS_TO:
														case TActiveRecord::HAS_ONE:
																$relControl->Text = $arObj->{$relKey};
																break;
														case TActiveRecord::HAS_MANY:
																if ($relControl instanceof TListControl) {
																	$relControl->DataSource = $arObj->{$relKey};
																	$relControl->dataBind();
																}
																break;
												}
					}
					break;
				}
			} catch (Exception $ex) {
				if ($throwExceptions) {
					throw $ex;
				}
			}
		}
	}

	/**
	 * Function to try to update an AR object with data in view controls.
	 * @param TActiveRecord $arObj
	 * @param Boolean $throwExceptions Wheter or not to throw exceptions
	 * @author Daniel Sampedro <darthdaniel85@gmail.com>
	 */
	public function tryToUpdateAR($arObj, $throwExceptions = false)
	{
		$objAttrs = get_class_vars(get_class($arObj));
		foreach (array_keys($objAttrs) as $key) {
			try {
				if ($key == "RELATIONS") {
					break;
				}
				$control = $this->{$key};
				if ($control instanceof TTextBox) {
					$arObj->{$key} = $control->Text;
				} elseif ($control instanceof TCheckBox) {
					$arObj->{$key} = $control->Checked;
				} elseif ($control instanceof TDatePicker) {
					$arObj->{$key} = $control->Date;
				}
			} catch (Exception $ex) {
				if ($throwExceptions) {
					throw $ex;
				}
			}
		}
	}
}
