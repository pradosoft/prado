<?php
/**
 * TTabPanel class file.
 *
 * @author Tomasz Wolny <tomasz.wolny@polecam.to.pl> and Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 * @since 3.1.1
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidOperationException;
use Prado\TPropertyValue;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\Javascripts\TJavaScript;

/**
 * Class TTabPanel.
 *
 * TTabPanel displays a tabbed panel. Users can click on the tab bar to switching among
 * different tab views. Each tab view is an independent panel that can contain arbitrary content.
 *
 * If the {@link setAutoSwitch AutoSwitch} property is enabled, the user will be able to switch the active view
 * to another one just hovering its corresponding tab caption.
 *
 * A TTabPanel control consists of one or several {@link TTabView} controls representing the possible
 * tab views. At any time, only one tab view is visible (active), which is specified by any of
 * the following properties:
 * - {@link setActiveViewIndex ActiveViewIndex} - the zero-based integer index of the view in the view collection.
 * - {@link setActiveViewID ActiveViewID} - the text ID of the visible view.
 * - {@link setActiveView ActiveView} - the visible view instance.
 * If both {@link setActiveViewIndex ActiveViewIndex} and {@link setActiveViewID ActiveViewID}
 * are set, the latter takes precedence.
 *
 * TTabPanel uses CSS to specify the appearance of the tab bar and panel. By default,
 * an embedded CSS file will be published which contains the default CSS for TTabPanel.
 * You may also use your own CSS file by specifying the {@link setCssUrl CssUrl} property.
 * The following properties specify the CSS classes used for elements in a TTabPanel:
 * - {@link setCssClass CssClass} - the CSS class name for the outer-most div element (defaults to 'tab-panel');
 * - {@link setTabCssClass TabCssClass} - the CSS class name for nonactive tab div elements (defaults to 'tab-normal');
 * - {@link setActiveTabCssClass ActiveTabCssClass} - the CSS class name for the active tab div element (defaults to 'tab-active');
 * - {@link setViewCssClass ViewCssClass} - the CSS class for the div element enclosing view content (defaults to 'tab-view');
 *
 * To use TTabPanel, write a template like following:
 * <code>
 * <com:TTabPanel>
 *   <com:TTabView Caption="View 1">
 *     content for view 1
 *   </com:TTabView>
 *   <com:TTabView Caption="View 2">
 *     content for view 2
 *   </com:TTabView>
 *   <com:TTabView Caption="View 3">
 *     content for view 3
 *   </com:TTabView>
 * </com:TTabPanel>
 * </code>
 *
 * @author Tomasz Wolny <tomasz.wolny@polecam.to.pl> and Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.1.1
 */
class TTabPanel extends \Prado\Web\UI\WebControls\TWebControl implements \Prado\Web\UI\IPostBackDataHandler
{
	private $_dataChanged = false;

	/**
	 * @return string tag name for the control
	 */
	protected function getTagName()
	{
		return 'div';
	}

	/**
	 * Adds object parsed from template to the control.
	 * This method adds only {@link TTabView} objects into the {@link getViews Views} collection.
	 * All other objects are ignored.
	 * @param mixed $object object parsed from template
	 */
	public function addParsedObject($object)
	{
		if ($object instanceof TTabView) {
			$this->getControls()->add($object);
		}
	}

	/**
	 * Returns the index of the active tab view.
	 * Note, this property may not return the correct index.
	 * To ensure the correctness, call {@link getActiveView()} first.
	 * @return int the zero-based index of the active tab view. If -1, it means no active tab view. Default is 0 (the first view is active).
	 */
	public function getActiveViewIndex()
	{
		return $this->getViewState('ActiveViewIndex', 0);
	}

	/**
	 * @param int $value the zero-based index of the current view in the view collection. -1 if no active view.
	 * @throws TInvalidDataValueException if the view index is invalid
	 */
	public function setActiveViewIndex($value)
	{
		$this->setViewState('ActiveViewIndex', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * Returns the ID of the active tab view.
	 * Note, this property may not return the correct ID.
	 * To ensure the correctness, call {@link getActiveView()} first.
	 * @return string The ID of the active tab view. Defaults to '', meaning not set.
	 */
	public function getActiveViewID()
	{
		return $this->getViewState('ActiveViewID', '');
	}

	/**
	 * @param string $value The ID of the active tab view.
	 */
	public function setActiveViewID($value)
	{
		$this->setViewState('ActiveViewID', $value, '');
	}

	/**
	 * Returns the currently active view.
	 * This method will examin the ActiveViewID, ActiveViewIndex and Views collection to
	 * determine which view is currently active. It will update ActiveViewID and ActiveViewIndex accordingly.
	 * @throws TInvalidDataValueException if the active view ID or index set previously is invalid
	 * @return TTabView the currently active view, null if no active view
	 */
	public function getActiveView()
	{
		$activeView = null;
		$views = $this->getViews();
		if (($id = $this->getActiveViewID()) !== '') {
			if (($index = $views->findIndexByID($id)) >= 0) {
				$activeView = $views->itemAt($index);
			} else {
				throw new TInvalidDataValueException('tabpanel_activeviewid_invalid', $id);
			}
		} elseif (($index = $this->getActiveViewIndex()) >= 0) {
			if ($index < $views->getCount()) {
				$activeView = $views->itemAt($index);
			} else {
				throw new TInvalidDataValueException('tabpanel_activeviewindex_invalid', $index);
			}
		} else {
			foreach ($views as $index => $view) {
				if ($view->getActive()) {
					$activeView = $view;
					break;
				}
			}
		}
		if ($activeView !== null) {
			$this->activateView($activeView);
		}
		return $activeView;
	}

	/**
	 * @param TTabView $view the view to be activated
	 * @throws TInvalidOperationException if the view is not in the view collection
	 */
	public function setActiveView($view)
	{
		if ($this->getViews()->indexOf($view) >= 0) {
			$this->activateView($view);
		} else {
			throw new TInvalidOperationException('tabpanel_view_inexistent');
		}
	}

	/**
	 * @return bool status of automatic tab switch on hover
	 */
	public function getAutoSwitch()
	{
		return TPropertyValue::ensureBoolean($this->getViewState('AutoSwitch'));
	}

	/**
	 * @param bool $value whether to enable automatic tab switch on hover
	 */
	public function setAutoSwitch($value)
	{
		$this->setViewState('AutoSwitch', TPropertyValue::ensureBoolean($value));
	}


	/**
	 * @return string URL for the CSS file including all relevant CSS class definitions. Defaults to ''.
	 */
	public function getCssUrl()
	{
		return $this->getViewState('CssUrl', 'default');
	}

	/**
	 * @param string $value URL for the CSS file including all relevant CSS class definitions.
	 */
	public function setCssUrl($value)
	{
		$this->setViewState('CssUrl', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string CSS class for the whole tab control div. Defaults to 'tab-panel'.
	 */
	public function getCssClass()
	{
		$cssClass = parent::getCssClass();
		return $cssClass === '' ? 'tab-panel' : $cssClass;
	}

	/**
	 * @return string CSS class for the currently displayed view div. Defaults to 'tab-view'.
	 */
	public function getViewCssClass()
	{
		return $this->getViewStyle()->getCssClass();
	}

	/**
	 * @param string $value CSS class for the currently displayed view div.
	 */
	public function setViewCssClass($value)
	{
		$this->getViewStyle()->setCssClass($value);
	}

	/**
	 * @return TStyle the style for all the view div
	 */
	public function getViewStyle()
	{
		if (($style = $this->getViewState('ViewStyle', null)) === null) {
			$style = new TStyle;
			$style->setCssClass('tab-view');
			$this->setViewState('ViewStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return string CSS class for non-active tabs. Defaults to 'tab-normal'.
	 */
	public function getTabCssClass()
	{
		return $this->getTabStyle()->getCssClass();
	}

	/**
	 * @param string $value CSS class for non-active tabs.
	 */
	public function setTabCssClass($value)
	{
		$this->getTabStyle()->setCssClass($value);
	}

	/**
	 * @return TStyle the style for all the inactive tab div
	 */
	public function getTabStyle()
	{
		if (($style = $this->getViewState('TabStyle', null)) === null) {
			$style = new TStyle;
			$style->setCssClass('tab-normal');
			$this->setViewState('TabStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return string CSS class for the active tab. Defaults to 'tab-active'.
	 */
	public function getActiveTabCssClass()
	{
		return $this->getActiveTabStyle()->getCssClass();
	}

	/**
	 * @param string $value CSS class for the active tab.
	 */
	public function setActiveTabCssClass($value)
	{
		$this->getActiveTabStyle()->setCssClass($value);
	}

	/**
	 * @return TStyle the style for the active tab div
	 */
	public function getActiveTabStyle()
	{
		if (($style = $this->getViewState('ActiveTabStyle', null)) === null) {
			$style = new TStyle;
			$style->setCssClass('tab-active');
			$this->setViewState('ActiveTabStyle', $style, null);
		}
		return $style;
	}

	/**
	 * Activates the specified view.
	 * If there is any other view currently active, it will be deactivated.
	 * @param TTabView $view the view to be activated. If null, all views will be deactivated.
	 */
	protected function activateView($view)
	{
		$this->setActiveViewIndex(-1);
		$this->setActiveViewID('');
		foreach ($this->getViews() as $index => $v) {
			if ($view === $v) {
				$this->setActiveViewIndex($index);
				$this->setActiveViewID($view->getID(false));
				$view->setActive(true);
			} else {
				$v->setActive(false);
			}
		}
	}

	/**
	 * Loads user input data.
	 * This method is primarly used by framework developers.
	 * @param string $key the key that can be used to retrieve data from the input data collection
	 * @param array $values the input data collection
	 * @return bool whether the data of the control has been changed
	 */
	public function loadPostData($key, $values)
	{
		if (($index = $values[$this->getClientID() . '_1']) !== null) {
			$index = (int) $index;
			$currentIndex = $this->getActiveViewIndex();
			if ($currentIndex !== $index) {
				$this->setActiveViewID(''); // clear up view ID
				$this->setActiveViewIndex($index);
				return $this->_dataChanged = true;
			}
		}
		return false;
	}

	/**
	 * Raises postdata changed event.
	 * This method is required by {@link \Prado\Web\UI\IPostBackDataHandler} interface.
	 * It is invoked by the framework when {@link getActiveViewIndex ActiveViewIndex} property
	 * is changed on postback.
	 * This method is primarly used by framework developers.
	 */
	public function raisePostDataChangedEvent()
	{
		// do nothing
	}

	/**
	 * Returns a value indicating whether postback has caused the control data change.
	 * This method is required by the \Prado\Web\UI\IPostBackDataHandler interface.
	 * @return bool whether postback has caused the control data change. False if the page is not in postback mode.
	 */
	public function getDataChanged()
	{
		return $this->_dataChanged;
	}

	/**
	 * Adds attributes to renderer.
	 * @param THtmlWriter $writer the renderer
	 */
	protected function addAttributesToRender($writer)
	{
		$writer->addAttribute('id', $this->getClientID());
		$this->setCssClass($this->getCssClass());
		parent::addAttributesToRender($writer);
	}

	/**
	 * Registers CSS and JS.
	 * This method is invoked right before the control rendering, if the control is visible.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		// determine the active view
		$this->getActiveView();
		$this->registerStyleSheet();

		$page = $this->getPage();
		$page->registerRequiresPostData($this);
		$page->registerRequiresPostData($this->getClientID() . "_1");
	}

	/**
	 * Registers the CSS relevant to the TTabControl.
	 * It will register the CSS file specified by {@link getCssUrl CssUrl}.
	 * If that is not set, it will use the default CSS.
	 */
	protected function registerStyleSheet()
	{
		$url = $this->getCssUrl();

		if ($url === '') {
			return;
		}

		if ($url === 'default') {
			$url = $this->getApplication()->getAssetManager()->publishFilePath(__DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'tabpanel.css');
		}

		if ($url !== '') {
			$this->getPage()->getClientScript()->registerStyleSheetFile($url, $url);
		}
	}

	/**
	 * Registers the relevant JavaScript.
	 */
	protected function registerClientScript()
	{
		$id = $this->getClientID();
		$options = TJavaScript::encode($this->getClientOptions());
		$className = $this->getClientClassName();
		$cs = $this->getPage()->getClientScript();
		$cs->registerPradoScript('tabpanel');
		$code = "new $className($options);";
		$cs->registerEndScript("prado:$id", $code);
		// ensure an item is always active and visible
		$index = $this->getActiveViewIndex();
		if (!$this->getViews()->itemAt($index)->Visible) {
			$index = 0;
		}
		$cs->registerHiddenField($id . '_1', $index);
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TTabPanel';
	}

	/**
	 * @return array the options for JavaScript
	 */
	protected function getClientOptions()
	{
		$options['ID'] = $this->getClientID();
		$options['ActiveCssClass'] = $this->getActiveTabCssClass();
		$options['NormalCssClass'] = $this->getTabCssClass();
		$viewIDs = [];
		$viewVis = [];
		foreach ($this->getViews() as $view) {
			$viewIDs[] = $view->getClientID();
			$viewVis[] = $view->getVisible();
		}
		$options['Views'] = $viewIDs;
		$options['ViewsVis'] = $viewVis;
		$options['AutoSwitch'] = $this->getAutoSwitch();

		return $options;
	}

	/**
	 * Creates a control collection object that is to be used to hold child controls
	 * @return TTabViewCollection control collection
	 */
	protected function createControlCollection()
	{
		return new TTabViewCollection($this);
	}

	/**
	 * @return TTabViewCollection list of {@link TTabView} controls
	 */
	public function getViews()
	{
		return $this->getControls();
	}

	public function render($writer)
	{
		$this->registerClientScript();
		parent::render($writer);
	}

	/**
	 * Renders body contents of the tab control.
	 * @param THtmlWriter $writer the writer used for the rendering purpose.
	 */
	public function renderContents($writer)
	{
		$views = $this->getViews();
		if ($views->getCount() > 0) {
			$writer->writeLine();
			// render tab bar
			foreach ($views as $view) {
				$view->renderTab($writer);
				$writer->writeLine();
			}
			// render tab views
			foreach ($views as $view) {
				$view->renderControl($writer);
				$writer->writeLine();
			}
		}
	}
}
