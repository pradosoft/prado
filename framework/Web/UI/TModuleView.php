<?php

/**
 * TModuleView class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TModuleView class.
 *
 * TModuleView disables its children controls when a module is not present.  In their
 * place the optional {@see getFallbackTemplate FallbackTemplate} is instantiated.
 *
 * Properties:
 * - <b>ModuleId</b>, string — the application module ID to check the presence of.
 * - <b>Condition</b>, bool — the condition to display module specific children controls.
 * - <b>FallbackTemplate</b>, ITemplate — template rendered when the module is absent.
 *
 * By leaving out the ModuleId, it becomes like a {@see TConditional}, except this
 * does not create the children when the condition is not met.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TModuleView extends TCompositeControl
{
	private $_isActive;
	private $_mod;
	private $_moduleId = '';
	private $_condition = '';

	/** @var ?ITemplate Template rendered when the module is not found. */
	private $_fallbacktemplate;

	/**
	 * Creates child controls.
	 * This method overrides the parent implementation. It evaluates {@see getCondition Condition}
	 * and instantiate the corresponding template.
	 * @return void
	 */
	public function createChildControls()
	{
		if (!$this->getIsActive()) {
			$this->getControls()->clear();
			if ($this->_fallbacktemplate) {
				$this->_fallbacktemplate->instantiateIn($this);
			}
		}
		parent::createChildControls();
	}

	/**
	 * Override parent to always return a control collection not dependent upon
	 * {@see getAllowChildControls}.
	 * @return TControlCollection control collection
	 */
	protected function createControlCollection()
	{
		return new TControlCollection($this);
	}

	/**
	 * Whether or not the control is active with the module and the condition expression
	 * evaluating to true.
	 * @return bool is the Module View active otherwise the FallbackTemplate is showing.
	 */
	public function getIsActive(): bool
	{
		if ($this->_isActive === null) {
			$this->_isActive = $this->getModuleAvailable() && $this->getConditionEvaluation();
		}
		return $this->_isActive;
	}

	/**
	 * Internal reset of the isActive property.
	 */
	protected function resetIsActive(): void
	{
		$this->_isActive = null;
	}

	/**
	 * @return bool whether the module specified by {@see getModuleId ModuleId} is registered
	 */
	public function getModuleAvailable(): bool
	{
		return $this->getModule() !== null;
	}

	/**
	 * @return ?mixed the module instance, or null if not found
	 */
	public function getModule(): mixed
	{
		if ($this->_mod === null) {
			$moduleId = $this->getModuleId();
			if (!empty($moduleId) && ($app = $this->getApplication())) {
				$this->_mod = $app->getModule($moduleId);
			}
		}
		return $this->_mod;
	}

	/**
	 * @return string the application module ID to check for
	 */
	public function getModuleId()
	{
		return $this->_moduleId;
	}

	/**
	 * @param string $value the application module ID to check for
	 */
	public function setModuleId($value)
	{
		$value = TPropertyValue::ensureString($value);
		if ($this->_moduleId !== $value) {
			$this->_mod = null;
			$this->_moduleId = $value;
			$this->resetIsActive();
		}
	}

	/**
	 * @return string the evaluated condition for turning on or off the children
	 */
	public function getCondition()
	{
		return empty($this->_condition) ? 'true' : $this->_condition;
	}

	/**
	 * @param string $value the evaluated condition for turning on or off the children
	 */
	public function setCondition($value)
	{
		$value = TPropertyValue::ensureString($value);
		if ($this->_condition !== $value) {
			$value = TPropertyValue::ensureString($value);
			$value = html_entity_decode($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
			$this->_condition = $value;
			$this->resetIsActive();
		}
	}

	/**
	 * Creates child controls.
	 * This method overrides the parent implementation. It evaluates {@see getCondition Condition}
	 * and instantiate the corresponding template.
	 * @return bool if the expression evaluates to true
	 */
	protected function getConditionEvaluation(): bool
	{
		$result = true;
		$condition = $this->getCondition();
		$tplControl = $this->getTemplateControl();
		if (!$tplControl) {
			return false;
		}
		try {
			$result = $tplControl->evaluateExpression($condition);
		} catch (\Exception $e) {
			throw new TInvalidDataValueException('conditional_condition_invalid', $condition, $e->getMessage());
		}
		return $result;
	}

	/**
	 * @return ?ITemplate the template rendered when the module is not found
	 */
	public function getFallbackTemplate()
	{
		return $this->_fallbacktemplate;
	}

	/**
	 * @param ITemplate $template the template rendered when the module is not found
	 */
	public function setFallbackTemplate($template)
	{
		$this->_fallbacktemplate = $template;
	}

	//  --- TControl Overrides

	/**
	 * This specifically tells the template Whether or not to allow children controls.
	 * @return bool true if child controls should be instantiated
	 */
	public function getAllowChildControls()
	{
		return $this->getModuleAvailable();
	}
}
