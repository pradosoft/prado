<?php

/**
 * TTemplateControl class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

use Prado\Prado;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\WebControls\TContent;
use Prado\Web\UI\WebControls\TContentPlaceHolder;
use Prado\Web\UI\WebControls\TCheckBox;
use Prado\Web\UI\WebControls\TDatePicker;
use Prado\Web\UI\WebControls\TListControl;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Data\ActiveRecord\TActiveRecord;

/**
 * TTemplateControl class.
 * TTemplateControl is the base class for all controls that use templates.
 * By default, a control template is assumed to be in a file under the same
 * directory with the control class file. They have the same file name and
 * different extension name. For template file, the extension name is ".tpl".
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 * @method \Prado\Web\Services\TPageService getService()
 */
class TTemplateControl extends TCompositeControl
{
	/**
	 * template file extension.
	 */
	public const EXT_TEMPLATE = '.tpl';

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
	 * @return null|TTemplate the parsed template, null if none
	 */
	public function getTemplate()
	{
		if ($this->_localTemplate === null) {
			$class = $this::class;
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
	 * @param \Prado\Web\UI\ITemplate $value the parsed template
	 */
	public function setTemplate($value)
	{
		$this->_localTemplate = $value;
	}

	/**
	 * @return bool whether this control is a source template control.
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
	 * @return \Prado\Web\UI\ITemplate the parsed template structure
	 */
	protected function loadTemplate()
	{
		Prado::trace("Loading template " . $this::class, TTemplateControl::class);
		$template = $this->getService()->getTemplateManager()->getTemplateByClassName($this::class);
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
					throw new TConfigurationException('templatecontrol_directive_invalid', $this::class, $name);
				}
			}
			$tpl->instantiateIn($this);
		}
	}

	/**
	 * Registers a content control.
	 * @param string $id ID of the content
	 * @param TContent $object
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
	 * @param string $id placeholder ID
	 * @param TContentPlaceHolder $object placeholder control
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
	 * @return null|TTemplateControl master control associated with this control, null if none
	 */
	public function getMaster()
	{
		return $this->_master;
	}

	/**
	 * Injects all content controls (and their children) to the corresponding content placeholders.
	 * This method should only be used by framework and control developers.
	 * @param string $id ID of the content control
	 * @param TContent $content the content to be injected
	 */
	public function injectContent($id, $content)
	{
		if (isset($this->_placeholders[$id])) {
			$placeholder = $this->_placeholders[$id];
			$controls = $placeholder->getParent()->getControls();
			$loc = $controls->remove($placeholder);
			$controls->insertAt($loc, $content);
		} else {
			if ($this->_masterClass !== '') {
				$this->_contents[$id] = $content;
			} else {
				throw new TConfigurationException('templatecontrol_placeholder_inexistent', $id);
			}
		}
	}

	/**
	 * Performs the OnInit step for the control and all its child controls.
	 * This method overrides the parent implementation
	 * by ensuring child controls are created first,
	 * and if master class is set, master will be applied.
	 * Only framework developers should use this method.
	 * @param \Prado\Web\UI\TControl $namingContainer the naming container control
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
			throw new TConfigurationException('templatecontrol_mastercontrol_required', $this::class);
		}
		parent::initRecursive($namingContainer);
	}

	/**
	 * Function to update view controls with data in a given AR object.
	 * View controls and AR object need to have the same name in IDs and Attrs respectively.
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $arObj
	 * @param bool $throwExceptions Wheter or not to throw exceptions
	 * @author Daniel Sampedro <darthdaniel85@gmail.com>
	 */
	public function tryToUpdateView($arObj, $throwExceptions = false)
	{
		$objAttrs = get_class_vars($arObj::class);
		foreach (array_keys($objAttrs) as $key) {
			try {
				if ($key != "RELATIONS") {
					$control = $this->{$key};
					if ($control instanceof TTextBox) {
						$control->setText($arObj->{$key});
					} elseif ($control instanceof TCheckBox) {
						$control->setChecked((bool) $arObj->{$key});
					} elseif ($control instanceof TDatePicker) {
						$control->setDate($arObj->{$key});
					}
				} else {
					foreach ($objAttrs["RELATIONS"] as $relKey => $relValues) {
						$relControl = $this->{$relKey};
						switch ($relValues[0]) {
							case TActiveRecord::BELONGS_TO:
							case TActiveRecord::HAS_ONE:
								$relControl->setText($arObj->{$relKey});
								break;
							case TActiveRecord::HAS_MANY:
								if ($relControl instanceof TListControl) {
									$relControl->setDataSource($arObj->{$relKey});
									$relControl->dataBind();
								}
								break;
						}
					}
					break;
				}
			} catch (\Exception $ex) {
				if ($throwExceptions) {
					throw $ex;
				}
			}
		}
	}

	/**
	 * Function to try to update an AR object with data in view controls.
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $arObj
	 * @param bool $throwExceptions Wheter or not to throw exceptions
	 * @author Daniel Sampedro <darthdaniel85@gmail.com>
	 */
	public function tryToUpdateAR($arObj, $throwExceptions = false)
	{
		$objAttrs = get_class_vars($arObj::class);
		foreach (array_keys($objAttrs) as $key) {
			try {
				if ($key == "RELATIONS") {
					break;
				}
				$control = $this->{$key};
				if ($control instanceof TTextBox) {
					$arObj->{$key} = $control->getText();
				} elseif ($control instanceof TCheckBox) {
					$arObj->{$key} = $control->getChecked();
				} elseif ($control instanceof TDatePicker) {
					$arObj->{$key} = $control->getDate();
				}
			} catch (\Exception $ex) {
				if ($throwExceptions) {
					throw $ex;
				}
			}
		}
	}
}
