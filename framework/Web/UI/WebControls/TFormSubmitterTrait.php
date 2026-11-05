<?php

/**
 * TFormSubmitterTrait trait file.
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TFormSubmitterTrait trait.
 * TFormSubmitterTrait backs the form overrides of a submit button: {@see TButton}
 * and {@see TImageButton}. A set override replaces the form's attribute for a
 * submission by that button.
 * | Property | Attribute | Replaces |
 * |---|---|---|
 * | {@see setFormAction FormAction} | `formaction` | the form `action`: the URL that receives the postback |
 * | {@see setFormEncType FormEncType} | `formenctype` | the form `enctype` |
 * | {@see setFormTarget FormTarget} | `formtarget` | the form `target`, such as `_blank` |
 * | CausesValidation false | `formnovalidate` | skips the browser constraint check of the form |
 *
 * {@see addFormSubmitterAttributesToRender()} renders them on a submit button.
 * The script postback of `Prado.PostBack` applies them too. A callback ignores
 * them. The using class has `getViewState()`, `setViewState()`, and
 * `getCausesValidation()`.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TFormSubmitterTrait
{
	/**
	 * @return string the URL that receives the postback of this button, or '' for the form's action
	 */
	public function getFormAction()
	{
		return $this->getViewState('FormAction', '');
	}

	/**
	 * Sets the URL that receives the postback of this button (`formaction`). A page
	 * other than the current one does not raise the button's events.
	 * @param string $value the URL, or '' for the form's action
	 */
	public function setFormAction($value)
	{
		$this->setViewState('FormAction', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * @return string the encoding of the postback of this button, or '' for the form's
	 */
	public function getFormEncType()
	{
		return $this->getViewState('FormEncType', '');
	}

	/**
	 * Sets the encoding of the postback of this button (`formenctype`):
	 * `application/x-www-form-urlencoded`, `multipart/form-data`, or `text/plain`.
	 * @param string $value the encoding, or '' for the form's
	 */
	public function setFormEncType($value)
	{
		$this->setViewState('FormEncType', strtolower(trim(TPropertyValue::ensureString($value))), '');
	}

	/**
	 * @return string the browsing context of the postback of this button, or '' for the form's
	 */
	public function getFormTarget()
	{
		return $this->getViewState('FormTarget', '');
	}

	/**
	 * Sets the browsing context that shows the response of this button's postback
	 * (`formtarget`), such as `_blank` or a frame name.
	 * @param string $value the target, or '' for the form's
	 */
	public function setFormTarget($value)
	{
		$this->setViewState('FormTarget', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * Adds the set form overrides, and `formnovalidate` when the button does not
	 * cause validation.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addFormSubmitterAttributesToRender($writer)
	{
		foreach (['formaction' => $this->getFormAction(), 'formenctype' => $this->getFormEncType(),
			'formtarget' => $this->getFormTarget()] as $name => $value) {
			if ($value !== '') {
				$writer->addAttribute($name, $value);
			}
		}
		if (!$this->getCausesValidation()) {
			$writer->addAttribute('formnovalidate', 'formnovalidate');
		}
	}
}
