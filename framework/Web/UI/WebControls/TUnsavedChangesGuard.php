<?php

/**
 * TUnsavedChangesGuard class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\THttpUtility;

/**
 * TUnsavedChangesGuard class.
 *
 * TUnsavedChangesGuard marks the fields a user changed and asks the browser to confirm
 * leaving the page while changes are unsaved. It watches the form it is placed in, or the
 * element of {@see setControlToWatch ControlToWatch}, such as a TPanel. The client class
 * `Prado.WebUI.TUnsavedChangesGuard` does the work; the control renders a `<span>` for it.
 *
 * A field is changed while it differs from the value the server rendered, so undoing a
 * change clears it. Text inputs, textareas, selects, checkboxes, radios, and file inputs
 * are watched. Hidden and button inputs are not. A field inside an element with the
 * `data-prado-dirty-ignore` attribute is not watched. A template sets it through the
 * {@see TWebControl::getDataset Dataset} subproperty: `Dataset.prado-dirty-ignore="true"`.
 *
 * | State | Client markup |
 * |---|---|
 * | a field is changed | `data-prado-dirty` and {@see setDirtyCssClass DirtyCssClass} on the field |
 * | the scope has unsaved changes | `data-prado-unsaved` on the form or ControlToWatch element |
 * | the state changes | `prado:dirtyChange` event on that element, `detail.dirty` |
 * | {@see setStatusText StatusText} is set and the scope is dirty | the text in the guard's `role="status"` span |
 *
 * With {@see setWarnOnLeave WarnOnLeave}, the client warns through `beforeunload` while the
 * scope is dirty. Submissions of the guard's form, which include Prado postbacks, buttons,
 * and auto-postbacks, and callbacks, a callback redirect included, do not warn.
 *
 * The baseline is the rendered state. A postback renders the posted values, which become
 * the new baseline. A postback whose validation failed did not save, so it renders the
 * guard dirty. A callback that re-renders fields gives them new defaults. A callback that
 * saves without re-rendering calls {@see markClean()}. {@see markDirty()} keeps the scope
 * dirty without a changed field.
 *
 * ```php
 * <com:TUnsavedChangesGuard ID="Guard" ControlToWatch="EditPanel"
 *     StatusText="You have unsaved changes." DirtyCssClass="changed" />
 * <com:TTextBox ID="Search" Dataset.prado-dirty-ignore="true" />
 * ```
 *
 * The server does not learn which fields changed.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TUnsavedChangesGuard extends TWebControl
{
	/** @var ?bool the state set by markClean() or markDirty() in this request; null when unset. */
	private ?bool $_dirty = null;

	/**
	 * @return string the tag name of the guard element, 'span'.
	 */
	protected function getTagName()
	{
		return 'span';
	}

	/**
	 * @return string the ID path of the control whose fields are watched. Defaults to '', the form.
	 */
	public function getControlToWatch()
	{
		return $this->getViewState('ControlToWatch', '');
	}

	/**
	 * Sets the ID path of the control whose fields are watched, from the guard's naming
	 * container. The control renders an element with its ClientID, such as a TPanel.
	 * @param string $value the ID path; '' watches the form
	 */
	public function setControlToWatch($value)
	{
		$this->setViewState('ControlToWatch', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return bool whether the browser warns before leaving while the scope is dirty. Defaults to true.
	 */
	public function getWarnOnLeave()
	{
		return $this->getViewState('WarnOnLeave', true);
	}

	/**
	 * @param bool $value whether the browser warns before leaving while the scope is dirty
	 */
	public function setWarnOnLeave($value)
	{
		$this->setViewState('WarnOnLeave', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return string the CSS class added to a changed field. Defaults to ''.
	 */
	public function getDirtyCssClass()
	{
		return $this->getViewState('DirtyCssClass', '');
	}

	/**
	 * @param string $value the CSS class, or space-separated classes, added to a changed field
	 */
	public function setDirtyCssClass($value)
	{
		$this->setViewState('DirtyCssClass', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the text of the status region while the scope is dirty. Defaults to ''.
	 */
	public function getStatusText()
	{
		return $this->getViewState('StatusText', '');
	}

	/**
	 * Sets the text the guard shows and announces while the scope is dirty. A non-empty
	 * text makes the guard element a `role="status"` live region.
	 * @param string $value the status text; '' renders no status
	 */
	public function setStatusText($value)
	{
		$this->setViewState('StatusText', TPropertyValue::ensureString($value), '');
	}

	/**
	 * Takes the current field values as the baseline. In a callback the client marks its
	 * scope clean; otherwise the page renders the guard clean.
	 */
	public function markClean()
	{
		$this->setDirtyState(false);
	}

	/**
	 * Keeps the scope dirty until it is marked clean. In a callback the client marks its
	 * scope dirty; otherwise the page renders the guard dirty.
	 */
	public function markDirty()
	{
		$this->setDirtyState(true);
	}

	/**
	 * Records the state and, in a callback, sends it to the client.
	 * @param bool $dirty whether the scope is dirty
	 */
	protected function setDirtyState(bool $dirty): void
	{
		$this->_dirty = $dirty;
		if ($this->getPage()?->getIsCallback()) {
			$function = $dirty ? 'markDirty' : 'markClean';
			$this->getPage()->getCallbackClient()->callClientFunction('Prado.WebUI.TUnsavedChangesGuard.' . $function, [$this]);
		}
	}

	/**
	 * A postback with a failed validator did not save, so it renders dirty unless
	 * {@see markClean()} or {@see markDirty()} set the state.
	 * @return bool whether the guard renders its scope dirty.
	 */
	public function getRendersDirty(): bool
	{
		if ($this->_dirty !== null) {
			return $this->_dirty;
		}
		return $this->hasFailedValidation();
	}

	/**
	 * @return bool whether this request is a postback with a failed validator.
	 */
	protected function hasFailedValidation(): bool
	{
		$page = $this->getPage();
		if ($page === null || !$page->getIsPostBack()) {
			return false;
		}
		foreach ($page->getValidators() as $validator) {
			if (!$validator->getIsValid()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Registers the client script when the page supports JavaScript and the guard is enabled.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->getEnabled(true) && $this->getPage()->getClientSupportsJavaScript()) {
			$this->registerClientScript();
		}
	}

	/**
	 * Registers the `unsavedchanges` package and the client control.
	 */
	protected function registerClientScript()
	{
		$options = TJavaScript::encode($this->getClientOptions());
		$className = $this->getClientClassName();
		$cs = $this->getPage()->getClientScript();
		$cs->registerPradoScript('unsavedchanges');
		$cs->registerEndScript('prado:' . $this->getClientID(), "new $className($options);");
	}

	/**
	 * @return string the JavaScript class of the guard.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TUnsavedChangesGuard';
	}

	/**
	 * @throws TConfigurationException if ControlToWatch names no control
	 * @return array the options of the client control.
	 */
	protected function getClientOptions(): array
	{
		$scopeID = '';
		if (($watch = $this->getControlToWatch()) !== '') {
			if (($control = $this->findControl($watch)) === null) {
				throw new TConfigurationException('unsavedchangesguard_controltowatch_invalid', $watch);
			}
			$scopeID = $control->getClientID();
		}
		return [
			'ID' => $this->getClientID(),
			'ScopeID' => $scopeID,
			'WarnOnLeave' => $this->getWarnOnLeave(),
			'DirtyCssClass' => $this->getDirtyCssClass(),
			'StatusText' => $this->getStatusText(),
			'Dirty' => $this->getRendersDirty(),
		];
	}

	/**
	 * Renders the id, and `role="status"` when {@see getStatusText StatusText} is set and no Role is.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('id', $this->getClientID());
		if ($this->getStatusText() !== '' && $this->getRole() === null) {
			$writer->addAttribute('role', 'status');
		}
	}

	/**
	 * Renders the status text when the guard renders dirty.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer
	 */
	public function renderContents($writer)
	{
		if (($text = $this->getStatusText()) !== '' && $this->getRendersDirty()) {
			$writer->write(THttpUtility::htmlEncode($text));
		}
	}
}
