<?php

/**
 * TWebTemplate class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\IPostBackDataHandler;
use Prado\Web\UI\IPostBackEventHandler;
use Prado\Web\UI\TControl;

/**
 * TWebTemplate class
 *
 * TWebTemplate represents the HTML5 `<template>` element. The `<template>`
 * element holds markup that the browser parses but does not render. The content
 * lives in a separate `DocumentFragment` reachable as `element.content`, from
 * which script stamps out copies. Scripts inside the content do not execute and
 * images inside it do not load until the content is cloned into the document.
 *
 * The class is named TWebTemplate because {@see \Prado\Web\UI\TTemplate} is the
 * Prado server-side template engine, which is unrelated to this HTML element.
 *
 * Properties:
 * - <b>ShadowRootMode</b>, {@see TShadowRootMode} — declares a shadow root on
 *   the parent element: `NotSet` (default, an ordinary template), `Open`, or
 *   `Closed`.
 * - <b>ShadowRootDelegatesFocus</b>, bool — whether the declared shadow root
 *   delegates focus. Defaults to `false`.
 * - <b>ShadowRootClonable</b>, bool — whether the declared shadow root is
 *   cloned with its host. Defaults to `false`.
 * - <b>ShadowRootSerializable</b>, bool — whether the declared shadow root is
 *   serializable. Defaults to `false`.
 * - <b>TrackInstances</b>, bool — whether stamped copies are recorded as
 *   updatable instances. Defaults to `true`.
 * - <b>PersistInstances</b>, bool — whether stamped instances round-trip a
 *   postback through a hidden field and are restored afterwards. Defaults to
 *   `false`.
 * - <b>ValidateContent</b>, bool — whether child controls are checked for
 *   compatibility with template content during OnPreRender. Defaults to `true`.
 * - <b>EnableClientScript</b>, bool — whether the client-side
 *   `Prado.WebUI.TWebTemplate` wrapper is registered for this element, which
 *   also renders the `id` attribute. Defaults to `true`.
 *
 * The three `ShadowRoot*` boolean properties render only when
 * {@see getShadowRootMode ShadowRootMode} is not `NotSet`; the HTML
 * specification ignores them otherwise.
 *
 * ## Client-side usage
 *
 * With {@see getEnableClientScript EnableClientScript} enabled, the element is
 * registered in `Prado.Registry` under its ClientID and offers stamping methods:
 *
 * ```javascript
 * const tpl = Prado.WebUI.TWebTemplate.get('<%= $this->Row->ClientID %>');
 * tpl.appendTo('listBody', {name: 'Ada', role: 'Engineer'});
 * tpl.repeatInto('listBody', [{name: 'Ada'}, {name: 'Grace'}]);
 * ```
 *
 * Placeholders written as `{{name}}` in text and attribute values are replaced
 * during stamping. Values are assigned as text, never parsed as HTML, so data
 * cannot inject markup.
 *
 * ## Declarative shadow DOM
 *
 * When {@see getShadowRootMode ShadowRootMode} is `Open` or `Closed`, the HTML
 * parser attaches a shadow root to the parent element and removes the
 * `<template>` element from the document tree. No element remains to wrap, so
 * the client-side wrapper is not registered in that mode.
 *
 * ## Prado controls inside the content
 *
 * Template content is inert and lives outside the document. A child control
 * that registers a client-side wrapper initializes while its element is still
 * inside the fragment, so the wrapper binds to `null` and never re-binds to a
 * stamped copy. An active button stamped this way keeps its submit name and
 * performs a full-page postback instead of an AJAX callback, and every stamped
 * copy repeats the same ClientID. Use plain markup with `{{...}}` placeholders
 * for repeated content, and keep active controls outside the template.
 *
 * With {@see getValidateContent ValidateContent} enabled (the default), a
 * {@see TConfigurationException} is thrown during OnPreRender for child
 * controls that cannot function inside template content: active controls,
 * postback data and postback event controls, and validators. Controls that emit
 * plain markup, such as {@see TLabel}, {@see TImage}, and the semantic
 * controls, pass the check. Controls registering head content or scripts, such
 * as {@see TTextHighlighter}, are not detected; their page-level registrations
 * run once against the inert content and do not reach stamped copies.
 *
 * This boundary is by design. A server control is one object with one viewstate,
 * so stamped copies cannot carry per-copy server state. For repeated content
 * whose rows need server-side controls, use {@see TRepeater} or {@see TDataList},
 * which create a real control per row.
 *
 * ## Persisting instances across postbacks
 *
 * Stamped instances live only in the browser, so a full-page postback discards
 * them. With {@see getPersistInstances PersistInstances} enabled, the wrapper
 * serializes each instance's UID, target element, and data into a hidden field
 * on every change; the control reads the field from the post data and renders
 * it back, and the wrapper restores the instances after the page loads. The
 * boundaries: a cold GET request has no post data to restore from, instances
 * are restorable only when stamped into an element with an `id`, and
 * restoration replays the instance data, so user-typed input inside a copy is
 * not preserved.
 *
 * ## Updating the content from the server
 *
 * Placing the template inside a {@see \Prado\Web\UI\ActiveControls\TActivePanel}
 * and re-rendering that panel during a callback replaces the element with new
 * markup. The callback re-registers the wrapper against the replacement, so
 * later stamping uses the updated content. Copies stamped before the update
 * keep the markup they were stamped from.
 *
 * Template usage:
 * ```html
 * <com:TWebTemplate ID="RowTemplate">
 *     <tr><td class="name">{{name}}</td><td>{{role}}</td></tr>
 * </com:TWebTemplate>
 *
 * <div>
 *     <com:TWebTemplate ShadowRootMode="Open">
 *         <style>p { color: rebeccapurple; }</style>
 *         <p>Encapsulated content.</p>
 *     </com:TWebTemplate>
 * </div>
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @see https://html.spec.whatwg.org/multipage/scripting.html#the-template-element
 * @since 4.4.0
 */
class TWebTemplate extends TWebControl implements IPostBackDataHandler
{
	/** @var bool whether the persisted instance state changed during the postback */
	private bool $_dataChanged = false;

	/**
	 * @return string tag name
	 */
	protected function getTagName()
	{
		return 'template';
	}

	/**
	 * @return TShadowRootMode the shadow root encapsulation mode declared by this
	 *   template. Defaults to TShadowRootMode::NotSet.
	 */
	public function getShadowRootMode()
	{
		return $this->getViewState('ShadowRootMode', TShadowRootMode::NotSet);
	}

	/**
	 * Sets the shadow root encapsulation mode declared by this template. When set
	 * to `Open` or `Closed`, the parser attaches a shadow root to the parent
	 * element and removes this element from the document tree.
	 * @param TShadowRootMode $value the shadow root encapsulation mode
	 */
	public function setShadowRootMode($value)
	{
		$this->setViewState('ShadowRootMode', TPropertyValue::ensureEnum($value, TShadowRootMode::class), TShadowRootMode::NotSet);
	}

	/**
	 * @return bool whether the declared shadow root delegates focus. Defaults to false.
	 *   Renders only when {@see getShadowRootMode ShadowRootMode} is set.
	 */
	public function getShadowRootDelegatesFocus()
	{
		return $this->getViewState('ShadowRootDelegatesFocus', false);
	}

	/**
	 * Sets whether the declared shadow root delegates focus. The HTML
	 * specification consults the attribute only alongside `shadowrootmode`, so it
	 * renders only when {@see setShadowRootMode ShadowRootMode} is set.
	 * @param bool $value whether the declared shadow root delegates focus
	 */
	public function setShadowRootDelegatesFocus($value)
	{
		$this->setViewState('ShadowRootDelegatesFocus', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool whether the declared shadow root is cloned with its host. Defaults
	 *   to false. Renders only when {@see getShadowRootMode ShadowRootMode} is set.
	 */
	public function getShadowRootClonable()
	{
		return $this->getViewState('ShadowRootClonable', false);
	}

	/**
	 * Sets whether the declared shadow root is cloned with its host. The HTML
	 * specification consults the attribute only alongside `shadowrootmode`, so it
	 * renders only when {@see setShadowRootMode ShadowRootMode} is set.
	 * @param bool $value whether the declared shadow root is cloned with its host
	 */
	public function setShadowRootClonable($value)
	{
		$this->setViewState('ShadowRootClonable', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool whether the declared shadow root is serializable. Defaults to
	 *   false. Renders only when {@see getShadowRootMode ShadowRootMode} is set.
	 */
	public function getShadowRootSerializable()
	{
		return $this->getViewState('ShadowRootSerializable', false);
	}

	/**
	 * Sets whether the declared shadow root is serializable. The HTML
	 * specification consults the attribute only alongside `shadowrootmode`, so it
	 * renders only when {@see setShadowRootMode ShadowRootMode} is set.
	 * @param bool $value whether the declared shadow root is serializable
	 */
	public function setShadowRootSerializable($value)
	{
		$this->setViewState('ShadowRootSerializable', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool whether stamped copies are recorded as updatable instances.
	 *   Defaults to true.
	 */
	public function getTrackInstances()
	{
		return $this->getViewState('TrackInstances', true);
	}

	/**
	 * Sets whether stamped copies are recorded as updatable instances. Tracking
	 * stores each copy's data and placeholder positions on its root nodes, which
	 * the client-side `updateInstance()` and `refreshInstance()` methods need.
	 * Disable it for copies that are stamped once and never updated.
	 * @param bool $value whether stamped copies are recorded as updatable instances
	 */
	public function setTrackInstances($value)
	{
		$this->setViewState('TrackInstances', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return bool whether stamped instances round-trip a postback through a
	 *   hidden field and are restored afterwards. Defaults to false.
	 */
	public function getPersistInstances()
	{
		return $this->getViewState('PersistInstances', false);
	}

	/**
	 * Sets whether stamped instances survive a postback. The client-side wrapper
	 * serializes each instance's UID, target element id, and data into a hidden
	 * field on every change and restores the instances after the next page load.
	 * Persistence requires {@see getTrackInstances TrackInstances} and targets
	 * with an `id`; restoration replays instance data, not user-typed input.
	 * @param bool $value whether stamped instances survive a postback
	 */
	public function setPersistInstances($value)
	{
		$this->setViewState('PersistInstances', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool whether child controls are checked for compatibility with
	 *   template content during OnPreRender. Defaults to true.
	 */
	public function getValidateContent()
	{
		return $this->getViewState('ValidateContent', true);
	}

	/**
	 * Sets whether child controls are checked for compatibility with template
	 * content. The check throws a {@see TConfigurationException} for active
	 * controls, postback data and postback event controls, and validators, all of
	 * which malfunction inside inert template content. Disable it only when the
	 * limitations are understood, such as a postback button stamped exactly once.
	 * @param bool $value whether child controls are checked during OnPreRender
	 */
	public function setValidateContent($value)
	{
		$this->setViewState('ValidateContent', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return bool whether the client-side wrapper is registered for this element.
	 *   Defaults to true. {@see getShadowRootMode ShadowRootMode} overrides it;
	 *   see {@see getHasClientScript()} for the value in effect.
	 */
	public function getEnableClientScript()
	{
		return $this->getViewState('EnableClientScript', true);
	}

	/**
	 * Sets whether the client-side `Prado.WebUI.TWebTemplate` wrapper is registered
	 * for this element. Registration also renders the `id` attribute, which the
	 * wrapper needs to find the element; disabling the wrapper omits the `id`.
	 * Setting {@see setShadowRootMode ShadowRootMode} suppresses registration
	 * regardless of this property.
	 * @param bool $value whether the client-side wrapper is registered for this element
	 */
	public function setEnableClientScript($value)
	{
		$this->setViewState('EnableClientScript', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * Returns whether the client-side wrapper is registered, combining
	 * {@see getEnableClientScript EnableClientScript} with
	 * {@see getShadowRootMode ShadowRootMode}. The wrapper binds to an element in
	 * the document tree, and declarative shadow DOM removes the element during
	 * parsing, so a declared shadow root suppresses registration.
	 * @return bool whether the client-side wrapper is registered
	 */
	protected function getHasClientScript(): bool
	{
		return $this->getEnableClientScript() && $this->getShadowRootMode() === TShadowRootMode::NotSet;
	}

	/**
	 * Returns whether instances are persisted, combining
	 * {@see getPersistInstances PersistInstances} with
	 * {@see getTrackInstances TrackInstances}. Persistence records each instance's
	 * data and target, which only tracked instances carry, so tracking is required.
	 * @return bool whether instances round-trip a postback
	 */
	protected function getHasPersistence(): bool
	{
		return $this->getPersistInstances() && $this->getTrackInstances();
	}

	/**
	 * @return string the client-side JavaScript class name.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TWebTemplate';
	}

	/**
	 * @return array the options passed to the client-side JavaScript class.
	 */
	protected function getClientOptions(): array
	{
		return [
			'ID' => $this->getClientID(),
			'TrackInstances' => $this->getTrackInstances(),
			'PersistInstances' => $this->getPersistInstances(),
		];
	}

	/**
	 * Validates the content and registers the client-side wrapper script.
	 * @param mixed $param event parameter
	 * @throws TConfigurationException if {@see getValidateContent ValidateContent}
	 *   is enabled and a child control cannot function inside template content
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->getValidateContent()) {
			$this->validateContent($this);
		}
		if ($this->getHasClientScript()) {
			$page = $this->getPage();
			$cs = $page->getClientScript();
			$cs->registerPradoScript('webtemplate');
			$cs->registerPostBackControl($this->getClientClassName(), $this->getClientOptions());
			if ($this->getHasPersistence()) {
				$page->registerRequiresPostData($this);
				$cs->registerHiddenField($this->getPersistFieldID(), $this->getPersistedInstances());
			}
		}
	}

	/**
	 * Throws for descendant controls that cannot function inside template
	 * content. The capability interfaces identify them: active controls bind
	 * their client wrapper to an element that is not in the document, postback
	 * data and postback event controls duplicate their submit names per stamped
	 * copy, and validators register with a page validation cycle the copies are
	 * not part of.
	 * @param TControl $control the control whose children are checked
	 * @throws TConfigurationException if a descendant control is unsupported
	 */
	protected function validateContent(TControl $control)
	{
		foreach ($control->getControls() as $child) {
			if (!($child instanceof TControl)) {
				continue;
			}
			if ($child instanceof IActiveControl
				|| $child instanceof IPostBackDataHandler
				|| $child instanceof IPostBackEventHandler
				|| $child instanceof TBaseValidator
			) {
				throw new TConfigurationException('webtemplate_invalid_child', $this->getID(false) ?: $this->getClientID(), $child::class);
			}
			$this->validateContent($child);
		}
	}

	// -------------------------------------------------- instance persistence

	/**
	 * @return string the id of the hidden field carrying the persisted instances
	 */
	protected function getPersistFieldID(): string
	{
		return $this->getClientID() . '_instances';
	}

	/**
	 * @return string the persisted instance state as the client serialized it;
	 *   empty string when nothing was persisted
	 */
	public function getPersistedInstances()
	{
		return $this->getViewState('PersistedInstances', '');
	}

	/**
	 * Loads the persisted instance state from the post data. The state renders
	 * back into the hidden field, from which the client-side wrapper restores
	 * the instances after the page loads.
	 * This method is required by the {@see IPostBackDataHandler} interface.
	 * @param string $key the key that can be used to retrieve data from the input data collection
	 * @param array $values the input data collection
	 * @return bool whether the persisted state changed
	 */
	public function loadPostData($key, $values)
	{
		$state = TPropertyValue::ensureString($values[$this->getPersistFieldID()] ?? '');
		if ($this->getPersistedInstances() !== $state) {
			$this->setViewState('PersistedInstances', $state, '');
			return $this->_dataChanged = true;
		}
		return false;
	}

	/**
	 * Returns whether postback has caused the persisted instance state to change.
	 * This method is required by the {@see IPostBackDataHandler} interface.
	 * @return bool whether the persisted state changed by the postback
	 */
	public function getDataChanged()
	{
		return $this->_dataChanged;
	}

	/**
	 * Raises the post data changed event. Persisted instance state carries no
	 * server-side event, so this method does nothing.
	 * This method is required by the {@see IPostBackDataHandler} interface.
	 */
	public function raisePostDataChangedEvent()
	{
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		if ($this->getHasClientScript()) {
			$writer->addAttribute('id', $this->getClientID());
		}
		parent::addAttributesToRender($writer);
		if (($mode = $this->getShadowRootMode()) === TShadowRootMode::NotSet) {
			return;
		}
		$writer->addAttribute('shadowrootmode', strtolower($mode));
		if ($this->getShadowRootDelegatesFocus()) {
			$writer->addAttribute('shadowrootdelegatesfocus', 'shadowrootdelegatesfocus');
		}
		if ($this->getShadowRootClonable()) {
			$writer->addAttribute('shadowrootclonable', 'shadowrootclonable');
		}
		if ($this->getShadowRootSerializable()) {
			$writer->addAttribute('shadowrootserializable', 'shadowrootserializable');
		}
	}
}
