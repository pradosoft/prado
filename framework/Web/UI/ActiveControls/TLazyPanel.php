<?php

/**
 * TLazyPanel class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\TPropertyValue;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\ITemplate;
use Prado\Web\UI\TTemplate;

/**
 * TLazyPanel class.
 *
 * TLazyPanel renders a placeholder, then loads its content by callback. The
 * first request does none of the content's work: the {@see getContentTemplate ContentTemplate}
 * is instantiated only in the request that loads the content.
 *
 * ```php
 * <com:TLazyPanel ID="Stats" Trigger="Visible" RootMargin="200px" OnLoadContent="loadStats">
 *   <prop:PlaceholderTemplate>
 *     <p>Loading statistics…</p>
 *   </prop:PlaceholderTemplate>
 *   <prop:ContentTemplate>
 *     <com:TRepeater ID="StatRows">...</com:TRepeater>
 *   </prop:ContentTemplate>
 * </com:TLazyPanel>
 * ```
 * ```php
 * public function loadStats($sender, $param)
 * {
 *   $this->StatRows->setDataSource($this->computeStats());
 *   $this->StatRows->dataBind();
 * }
 * ```
 *
 * The {@see getTrigger Trigger} selects when the client requests the content:
 * - Load → when the page loads.
 * - Visible → when the panel enters the viewport, extended by {@see getRootMargin RootMargin}
 *   (default). Without `IntersectionObserver` the client requests it when the page loads.
 * - Manual → when the client calls `Prado.WebUI.TLazyPanel.load(id)`.
 *
 * The load callback:
 * 1. instantiates the ContentTemplate in the panel,
 * 2. raises {@see onLoadContent OnLoadContent}, where the application populates the content,
 * 3. renders the panel, which replaces the placeholder on the client.
 *
 * {@see loadContent()} loads the content from the server in any request, such as
 * the callback of another control. The loaded flag is kept in control state, so
 * later postbacks and callbacks recreate the content from the page state and keep
 * showing it. OnLoadContent is raised once, in the request that loads.
 *
 * The {@see getPlaceholderTemplate PlaceholderTemplate} is instantiated when the
 * panel renders unloaded; its controls render and raise no events. Body content
 * of the panel renders in both states, before the placeholder or the content.
 * The panel is a naming container, so a content control has the same ID in every request.
 *
 * Accessibility: an unloaded panel renders `aria-busy="true"`. The loaded panel
 * renders without it, so assistive technology reads the region when the content
 * is complete. The panel is no live region: the arrival of the content is not
 * announced. For a short result that should be announced, add
 * `Attributes.aria-live="polite"`; aria-busy holds the announcement until the
 * content is complete.
 *
 * A failed load callback leaves the placeholder, removes `aria-busy`, and sets
 * `data-prado-lazy="failed"`. `Prado.WebUI.TLazyPanel.load(id)` requests it again.
 * The load callback causes no validation by default.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @method TActiveControlAdapter<TBaseActiveCallbackControl> getAdapter()
 */
class TLazyPanel extends TActivePanel implements ICallbackEventHandler, INamingContainer
{
	/**
	 * @var ?ITemplate the template of the loaded content
	 */
	private $_contentTemplate;
	/**
	 * @var ?ITemplate the template shown until the content loads
	 */
	private $_placeholderTemplate;
	/**
	 * @var bool whether the content template is instantiated in this request
	 */
	private $_contentCreated = false;
	/**
	 * @var bool whether the placeholder template is instantiated in this request
	 */
	private $_placeholderCreated = false;

	/**
	 * Creates the panel; its load callback causes no validation by default.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->getActiveControl()->setCausesValidation(false);
	}

	/**
	 * @return TBaseActiveCallbackControl standard callback control options.
	 */
	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	/**
	 * @return TCallbackClientSide client-side request options of the load callback.
	 */
	public function getClientSide()
	{
		return $this->getActiveControl()->getClientSide();
	}

	/**
	 * @return ?ITemplate the template of the content, instantiated when the content loads.
	 */
	public function getContentTemplate()
	{
		return $this->_contentTemplate;
	}

	/**
	 * @param ITemplate $value the template of the content, instantiated when the content loads.
	 */
	public function setContentTemplate(ITemplate $value)
	{
		$this->_contentTemplate = $value;
	}

	/**
	 * @return ?ITemplate the template rendered until the content loads.
	 */
	public function getPlaceholderTemplate()
	{
		return $this->_placeholderTemplate;
	}

	/**
	 * @param ITemplate $value the template rendered until the content loads.
	 */
	public function setPlaceholderTemplate(ITemplate $value)
	{
		$this->_placeholderTemplate = $value;
	}

	/**
	 * @return string when the client requests the content, a {@see TLazyPanelTrigger}. Defaults to Visible.
	 */
	public function getTrigger()
	{
		return $this->getViewState('Trigger', TLazyPanelTrigger::Visible);
	}

	/**
	 * @param string $value when the client requests the content, a {@see TLazyPanelTrigger}.
	 * @throws \Prado\Exceptions\TInvalidDataValueException if the value is not a TLazyPanelTrigger.
	 */
	public function setTrigger($value)
	{
		$this->setViewState('Trigger', TPropertyValue::ensureEnum($value, TLazyPanelTrigger::class), TLazyPanelTrigger::Visible);
	}

	/**
	 * @return string the CSS margin that extends the viewport for the Visible trigger. Defaults to '0px'.
	 */
	public function getRootMargin()
	{
		return $this->getViewState('RootMargin', '0px');
	}

	/**
	 * A positive margin, such as '200px', requests the content before the panel scrolls into view.
	 * @param string $value the CSS margin that extends the viewport for the Visible trigger,
	 *   as the `rootMargin` of an `IntersectionObserver`.
	 */
	public function setRootMargin($value)
	{
		$this->setViewState('RootMargin', TPropertyValue::ensureString($value), '0px');
	}

	/**
	 * @return bool whether the content is loaded. It is kept in control state.
	 */
	public function getIsContentLoaded()
	{
		return $this->getControlState('ContentLoaded', false);
	}

	/**
	 * Loads the content: instantiates the {@see getContentTemplate ContentTemplate}
	 * and raises {@see onLoadContent OnLoadContent}. In a callback, the panel renders
	 * and replaces the placeholder on the client. Loaded content is left as it is.
	 * @param ?\Prado\TEventParameter $param the parameter of OnLoadContent, the
	 *   callback parameter in the load callback.
	 */
	public function loadContent($param = null)
	{
		if ($this->getIsContentLoaded()) {
			return;
		}
		$this->setControlState('ContentLoaded', true, false);
		$this->createContent();
		$this->onLoadContent($param);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->render($param instanceof TCallbackEventParameter ? $param->getNewWriter() : $this->getResponse()->createHtmlWriter());
		}
	}

	/**
	 * Raises the load callback event. The content loads; loaded content renders again,
	 * so a repeated load callback also replaces the placeholder.
	 * @param TCallbackEventParameter $param the event parameter
	 */
	public function raiseCallbackEvent($param)
	{
		if ($this->getIsContentLoaded()) {
			$this->render($param->getNewWriter());
		} else {
			$this->loadContent($param);
		}
	}

	/**
	 * Raises the OnLoadContent event, after the content template is instantiated.
	 * The application populates the content in its handler.
	 * @param ?\Prado\TEventParameter $param event parameter
	 */
	public function onLoadContent($param)
	{
		$this->raiseEvent('OnLoadContent', $this, $param);
	}

	/**
	 * Recreates loaded content, whose controls then load their state.
	 */
	public function loadState()
	{
		parent::loadState();
		if ($this->getIsContentLoaded()) {
			$this->createContent();
		}
	}

	/**
	 * Instantiates the content template once per request.
	 */
	protected function createContent()
	{
		if ($this->_contentCreated) {
			return;
		}
		$this->_contentCreated = true;
		if (($template = $this->getContentTemplate()) !== null) {
			$this->instantiateTemplate($template);
		}
	}

	/**
	 * Instantiates the placeholder template once per request.
	 */
	protected function createPlaceholder()
	{
		if ($this->_placeholderCreated) {
			return;
		}
		$this->_placeholderCreated = true;
		if (($template = $this->getPlaceholderTemplate()) !== null) {
			$this->instantiateTemplate($template);
		}
	}

	/**
	 * Instantiates a template in the panel. A {@see TTemplate} takes the template
	 * control of the panel as its context, so its expressions, event handlers, and
	 * control IDs resolve on the page or template control that declares the panel.
	 * @param ITemplate $template the template to instantiate
	 */
	protected function instantiateTemplate(ITemplate $template)
	{
		if ($template instanceof TTemplate) {
			$template->instantiateIn($this->getTemplateControl() ?? $this, $this);
		} else {
			$template->instantiateIn($this);
		}
	}

	/**
	 * Adds `aria-busy` and registers the client script while the content is unloaded.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if (!$this->getIsContentLoaded()) {
			$writer->addAttribute('aria-busy', 'true');
			$this->getActiveControl()->registerCallbackClientScript(
				$this->getClientClassName(),
				$this->getTriggerOptions()
			);
		}
	}

	/**
	 * Renders the body content, then the placeholder while the content is unloaded.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		if (!$this->getIsContentLoaded()) {
			$this->createPlaceholder();
		}
		parent::renderContents($writer);
	}

	/**
	 * @return array the options of the client control.
	 */
	protected function getTriggerOptions()
	{
		return [
			'ID' => $this->getClientID(),
			'EventTarget' => $this->getUniqueID(),
			'Trigger' => $this->getTrigger(),
			'RootMargin' => $this->getRootMargin(),
		];
	}

	/**
	 * @return string the client-side JavaScript class name.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TLazyPanel';
	}
}
