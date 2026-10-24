<?php

/**
 * TTemplateControl class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

use Prado\IDataRenderer;
use Prado\Prado;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\WebControls\TContent;
use Prado\Web\UI\WebControls\TContentPlaceHolder;
use Prado\Web\UI\WebControls\TDataBoundControl;
use Prado\Web\UI\WebControls\TDatePicker;

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
	 * @var array<string, ITemplate> the parsed template structure shared by the same control class
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
	 * @return ?ITemplate the parsed template, null if none
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
			$directive = $tpl->getDirective();
			foreach ($directive as $name => $value) {
				if (!is_string($value)) {
					throw new TConfigurationException('templatecontrol_directive_invalid', $this::class, $name);
				}
			}
			$this->setSubProperties($directive);
			$tpl->instantiateIn($this);
		}
		parent::createChildControls();
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
	 * Updates the view controls with the data of a model.
	 *
	 * Each key of the model data names a control of this template control, read as
	 * `$this->{$key}`. The value goes to that control by its type:
	 *
	 * - {@see TDataBoundControl} and an iterable value → `DataSource`, then `dataBind()`
	 * - {@see TDatePicker} → `Date` (a date string)
	 * - {@see IDataRenderer} → `Data` (`Text`, `Checked`, `SelectedValue`, ...)
	 *
	 * A key without a control, or with a control of another type, is skipped.
	 *
	 * The model data is {@see IViewDataModel::getViewData()}. An object that defines
	 * `getViewData()` without the interface is read the same way, so the prado-activerecord
	 * `TActiveRecord` binds its columns and relations on PRADO 4.3 as well. An array is
	 * the data itself; any other object contributes its public properties.
	 *
	 * @param array|object $model the model
	 * @param bool $throwExceptions true rethrows an exception raised while a control takes
	 *   a value; false (default) skips that key
	 * @author Daniel Sampedro <darthdaniel85@gmail.com>
	 */
	public function tryToUpdateView($model, $throwExceptions = false)
	{
		foreach ($this->getModelViewData($model) as $field => $value) {
			try {
				if (!isset($this->{$field})) {
					continue;
				}
				$control = $this->{$field};
				if ($control instanceof TDataBoundControl && is_iterable($value)) {
					$control->setDataSource($value);
					$control->dataBind();
				} elseif ($control instanceof TDatePicker) {
					$control->setDate($value);
				} elseif ($control instanceof IDataRenderer) {
					$control->setData($value);
				}
			} catch (\Exception $ex) {
				if ($throwExceptions) {
					throw $ex;
				}
			}
		}
	}

	/**
	 * Updates a model with the values of the view's input controls.
	 *
	 * Every object registered with this template control by ID (see
	 * {@see TControl::getRegisteredObjects()}) that accepts user input is read: a
	 * {@see TDatePicker} gives its `Date`, any other {@see IPostBackDataHandler} that is an
	 * {@see IDataRenderer} gives its `Data` (`Text`, `Checked`, `SelectedValue`, ...).
	 * Output-only controls such as {@see \Prado\Web\UI\WebControls\TLabel} are not read.
	 *
	 * The values, keyed by control ID, go to {@see IViewDataModel::setViewData()}; an object
	 * that defines `setViewData()` without the interface receives them the same way. Any
	 * other object receives each value whose key is one of its public properties. The model
	 * decides which keys it takes.
	 *
	 * @param object $model the model
	 * @param bool $throwExceptions true rethrows an exception raised while a control is read
	 *   or the model takes the values; false (default) skips the control, or the model
	 * @since 4.4.0
	 */
	public function tryToUpdateModel($model, $throwExceptions = false)
	{
		$values = [];
		foreach ($this->getRegisteredObjects() as $field => $control) {
			try {
				if ($control instanceof TDatePicker) {
					$values[$field] = $control->getDate();
				} elseif ($control instanceof IPostBackDataHandler && $control instanceof IDataRenderer) {
					$values[$field] = $control->getData();
				}
			} catch (\Exception $ex) {
				if ($throwExceptions) {
					throw $ex;
				}
			}
		}
		try {
			$this->setModelViewData($model, $values);
		} catch (\Exception $ex) {
			if ($throwExceptions) {
				throw $ex;
			}
		}
	}

	/**
	 * Updates a record with the values of the view's input controls.
	 * This is the name from the time Active Record was part of the framework; it calls
	 * {@see tryToUpdateModel()}.
	 * @param object $arObj the record
	 * @param bool $throwExceptions see {@see tryToUpdateModel()}
	 * @author Daniel Sampedro <darthdaniel85@gmail.com>
	 */
	public function tryToUpdateAR($arObj, $throwExceptions = false)
	{
		$this->tryToUpdateModel($arObj, $throwExceptions);
	}

	/**
	 * Returns the data of a model for {@see tryToUpdateView()}.
	 * @param array|object $model the model
	 * @return array<string, mixed> values keyed by field name
	 * @since 4.4.0
	 */
	protected function getModelViewData($model): array
	{
		if (is_array($model)) {
			return $model;
		}
		if ($model instanceof IViewDataModel || method_exists($model, 'getViewData')) {
			return $model->getViewData();
		}
		return get_object_vars($model);
	}

	/**
	 * Passes the view values to a model for {@see tryToUpdateModel()}.
	 * @param object $model the model
	 * @param array<string, mixed> $values values keyed by control ID
	 * @since 4.4.0
	 */
	protected function setModelViewData($model, array $values): void
	{
		if ($model instanceof IViewDataModel || method_exists($model, 'setViewData')) {
			$model->setViewData($values);
			return;
		}
		foreach (array_intersect_key($values, get_object_vars($model)) as $field => $value) {
			$model->{$field} = $value;
		}
	}
}
