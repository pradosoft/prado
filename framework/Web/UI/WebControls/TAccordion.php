<?php
/**
 * TAccordion class file.
 *
 * @author Gabor Berczi, DevWorx Hungary <gabor.berczi@devworx.hu>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TAccordion.php 2915 2011-05-15 16:26:11Z ctrlaltca@gmail.com $
 * @package System.Web.UI.WebControls
 * @since 3.2
 */

/**
 * Class TAccordion.
 *
 * TAccordion displays an accordion control. Users can click on the view headers to switch among
 * different accordion views. Each accordion view is an independent panel that can contain arbitrary content.
 *
 * A TAccordion control consists of one or several {@link TAccordionView} controls representing the possible
 * accordion views. At any time, only one accordion view is visible (active), which is specified by any of
 * the following properties:
 * - {@link setActiveViewIndex ActiveViewIndex} - the zero-based integer index of the view in the view collection.
 * - {@link setActiveViewID ActiveViewID} - the text ID of the visible view.
 * - {@link setActiveView ActiveView} - the visible view instance.
 * If both {@link setActiveViewIndex ActiveViewIndex} and {@link setActiveViewID ActiveViewID}
 * are set, the latter takes precedence.
 *
 * TAccordion uses CSS to specify the appearance of the accordion headers and panel. By default,
 * an embedded CSS file will be published which contains the default CSS for TTabPanel.
 * You may also use your own CSS file by specifying the {@link setCssUrl CssUrl} property.
 * The following properties specify the CSS classes used for elements in a TAccordion:
 * - {@link setCssClass CssClass} - the CSS class name for the outer-most div element (defaults to 'accordion');
 * - {@link setHeaderCssClass HeaderCssClass} - the CSS class name for nonactive accordion div elements (defaults to 'accordion-header');
 * - {@link setActiveHeaderCssClass ActiveHeaderCssClass} - the CSS class name for the active accordion div element (defaults to 'accordion-header-active');
 * - {@link setViewCssClass ViewCssClass} - the CSS class for the div element enclosing view content (defaults to 'accordion-view');
 *
 * When the user clicks on a view header, the switch between the old visible view and the clicked one is animated.
 * You can use the {@link setAnimationDuration AnimationDuration} property to set the animation length in seconds;
 * it defaults to 1 second, and when set to 0 it will produce an immediate switch with no animation.
 *
 * The TAccordion auto-sizes itself to the largest of all views, so it can encompass all of them without scrolling.
 * If you want to specify a fixed height (in pixels), use the {@link setViewHeight ViewHeight} property.
 * When a TAccordion is nested inside another, it's adviced to manually specify a {@link setViewHeight ViewHeight} for the internal TAccordion
 *
 * To use TAccordion, write a template like following:
 * <code>
 * <com:TAccordion>
 *   <com:TAccordionView Caption="View 1">
 *     content for view 1
 *   </com:TAccordionView>
 *   <com:TAccordionView Caption="View 2">
 *     content for view 2
 *   </com:TAccordionView>
 *   <com:TAccordionView Caption="View 3">
 *     content for view 3
 *   </com:TAccordionView>
 * </com:TAccordion>
 * </code>
 *
 * @author Gabor Berczi, DevWorx Hungary <gabor.berczi@devworx.hu>
 * @version $Id: TAccordion.php 2915 2011-05-15 16:26:11Z ctrlaltca@gmail.com $
 * @package System.Web.UI.WebControls
 * @since 3.2
 */

class TAccordion extends TWebControl implements IPostBackDataHandler
{
	private $_dataChanged=false;

	/**
	 * @return string tag name for the control
	 */
	protected function getTagName()
	{
		return 'div';
	}

	/**
	 * Adds object parsed from template to the control.
	 * This method adds only {@link TAccordionView} objects into the {@link getViews Views} collection.
	 * All other objects are ignored.
	 * @param mixed object parsed from template
	 */
	public function addParsedObject($object)
	{
		if($object instanceof TAccordionView)
			$this->getControls()->add($object);
	}

	/**
	 * Returns the index of the active accordion view.
	 * Note, this property may not return the correct index.
	 * To ensure the correctness, call {@link getActiveView()} first.
	 * @return integer the zero-based index of the active accordion view. If -1, it means no active accordion view. Default is 0 (the first view is active).
	 */
	public function getActiveViewIndex()
	{
		return $this->getViewState('ActiveViewIndex',0);
	}

	/**
	 * @param integer the zero-based index of the current view in the view collection. -1 if no active view.
	 * @throws TInvalidDataValueException if the view index is invalid
	 */
	public function setActiveViewIndex($value)
	{
		$this->setViewState('ActiveViewIndex',TPropertyValue::ensureInteger($value),0);
	}

	/**
	 * Returns the ID of the active accordion view.
	 * Note, this property may not return the correct ID.
	 * To ensure the correctness, call {@link getActiveView()} first.
	 * @return string The ID of the active accordion view. Defaults to '', meaning not set.
	 */
	public function getActiveViewID()
	{
		return $this->getViewState('ActiveViewID','');
	}

	/**
	 * @param string The ID of the active accordion view.
	 */
	public function setActiveViewID($value)
	{
		$this->setViewState('ActiveViewID',$value,'');
	}

	/**
	 * Returns the currently active view.
	 * This method will examin the ActiveViewID, ActiveViewIndex and Views collection to
	 * determine which view is currently active. It will update ActiveViewID and ActiveViewIndex accordingly.
	 * @return TAccordionView the currently active view, null if no active view
	 * @throws TInvalidDataValueException if the active view ID or index set previously is invalid
	 */
	public function getActiveView()
	{
		$activeView=null;
		$views=$this->getViews();
		if(($id=$this->getActiveViewID())!=='')
		{
			if(($index=$views->findIndexByID($id))>=0)
				$activeView=$views->itemAt($index);
			else
				throw new TInvalidDataValueException('tabpanel_activeviewid_invalid',$id);
		}
		else if(($index=$this->getActiveViewIndex())>=0)
		{
			if($index<$views->getCount())
				$activeView=$views->itemAt($index);
			else
				throw new TInvalidDataValueException('tabpanel_activeviewindex_invalid',$index);
		}
		else
		{
			foreach($views as $index=>$view)
			{
				if($view->getActive())
				{
					$activeView=$view;
					break;
				}
			}
		}
		if($activeView!==null)
			$this->activateView($activeView);
		return $activeView;
	}

	/**
	 * @param TAccordionView the view to be activated
	 * @throws TInvalidOperationException if the view is not in the view collection
	 */
	public function setActiveView($view)
	{
		if($this->getViews()->indexOf($view)>=0)
			$this->activateView($view);
		else
			throw new TInvalidOperationException('tabpanel_view_inexistent');
	}

	/**
	* @return string URL for the CSS file including all relevant CSS class definitions. Defaults to ''.
	*/
	public function getCssUrl()
	{
		return $this->getViewState('CssUrl','default');
	}

	/**
	* @param string URL for the CSS file including all relevant CSS class definitions.
	*/
	public function setCssUrl($value)
	{
		$this->setViewState('CssUrl',TPropertyValue::ensureString($value),'');
	}

	/**
		* @return string CSS class for the whole accordion control div. 
	*/
	public function getCssClass()
	{
		$cssClass=parent::getCssClass();
			return $cssClass===''?'accordion':$cssClass;
	}

	/**
		* @return string CSS class for the currently displayed view div. Defaults to 'accordion-view'.
	*/
	public function getViewCssClass()
	{
		return $this->getViewStyle()->getCssClass();
	}

	/**
	* @param string CSS class for the currently displayed view div.
	*/
	public function setViewCssClass($value)
	{
		$this->getViewStyle()->setCssClass($value);
	}

	/**
		* @return string CSS class for the currently displayed view div. Defaults to 'accordion-view'.
	*/
	public function getAnimationDuration()
	{
		return $this->getViewState('AnimationDuration','1');
	}

	/**
	* @param string CSS class for the currently displayed view div.
	*/
	public function setAnimationDuration($value)
	{
		$this->setViewState('AnimationDuration',$value);
	}

	/**
	 * @return TStyle the style for all the view div
	 */
	public function getViewStyle()
	{
		if(($style=$this->getViewState('ViewStyle',null))===null)
		{
			$style=new TStyle;
			$style->setCssClass('accordion-view');
			$this->setViewState('ViewStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return string CSS class for view headers. Defaults to 'accordion-header'.
	 */
	public function getHeaderCssClass()
	{
		return $this->getHeaderStyle()->getCssClass();
	}

	/**
	 * @param string CSS class for view headers.
	 */
	public function setHeaderCssClass($value)
	{
		$this->getHeaderStyle()->setCssClass($value);
	}

	/**
	 * @return TStyle the style for all the inactive header div
	 */
	public function getHeaderStyle()
	{
		if(($style=$this->getViewState('HeaderStyle',null))===null)
		{
			$style=new TStyle;
			$style->setCssClass('accordion-header');
			$this->setViewState('HeaderStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return string Extra CSS class for the active header. Defaults to 'accordion-header-active'.
	 */
	public function getActiveHeaderCssClass()
	{
		return $this->getActiveHeaderStyle()->getCssClass();
	}

	/**
	 * @param string Extra CSS class for the active header. Will be added to the normal header specified by HeaderCssClass.
	 */
	public function setActiveHeaderCssClass($value)
	{
		$this->getActiveHeaderStyle()->setCssClass($value);
	}

	/**
	 * @return TStyle the style for the active header div
	 */
	public function getActiveHeaderStyle()
	{
		if(($style=$this->getViewState('ActiveHeaderStyle',null))===null)
		{
			$style=new TStyle;
			$style->setCssClass('accordion-header-active');
			$this->setViewState('ActiveHeaderStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return integer Maximum height for the accordion views. If non specified, the accordion will auto-sized to the largest of all views, so it can encompass all of them without scrolling
	 */
	public function getViewHeight()
	{
		return TPropertyValue::ensureInteger($this->getViewState('ViewHeight'));
	}

	/**
	 * @param integer Maximum height for the accordion views. If any of the accordion's views' content is larger, those views will be made scrollable when activated
	 */
	public function setViewHeight($value)
	{
		$this->setViewState('ViewHeight', TPropertyValue::ensureInteger($value));
	}

	/**
	 * Activates the specified view.
	 * If there is any other view currently active, it will be deactivated.
	 * @param TAccordionView the view to be activated. If null, all views will be deactivated.
	 */
	protected function activateView($view)
	{
		$this->setActiveViewIndex(-1);
		$this->setActiveViewID('');
		foreach($this->getViews() as $index=>$v)
		{
			if($view===$v)
			{
				$this->setActiveViewIndex($index);
				$this->setActiveViewID($view->getID(false));
				$view->setActive(true);
			}
			else
				$v->setActive(false);
		}
	}

	/**
	 * Loads user input data.
	 * This method is primarly used by framework developers.
	 * @param string the key that can be used to retrieve data from the input data collection
	 * @param array the input data collection
	 * @return boolean whether the data of the control has been changed
	 */
	public function loadPostData($key,$values)
	{
		if(($index=$values[$this->getClientID().'_1'])!==null)
		{
			$index=(int)$index;
			$currentIndex=$this->getActiveViewIndex();
			if($currentIndex!==$index)
			{
				$this->setActiveViewID(''); // clear up view ID
				$this->setActiveViewIndex($index);
				return $this->_dataChanged=true;
			}
		}
		return false;
	}

	/**
	 * Raises postdata changed event.
	 * This method is required by {@link IPostBackDataHandler} interface.
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
	 * This method is required by the IPostBackDataHandler interface.
	 * @return boolean whether postback has caused the control data change. False if the page is not in postback mode.
	 */
	public function getDataChanged()
	{
		return $this->_dataChanged;
	}

	/**
	 * Adds attributes to renderer.
	 * @param THtmlWriter the renderer
	 */
	protected function addAttributesToRender($writer)
	{
		$writer->addAttribute('id',$this->getClientID());
		$this->setCssClass($this->getCssClass());
		parent::addAttributesToRender($writer);
	}

	/**
	 * Registers CSS and JS.
	 * This method is invoked right before the control rendering, if the control is visible.
	 * @param mixed event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->getActiveView();  // determine the active view
		$this->registerStyleSheet();
	}

	/**
	 * Registers the CSS relevant to the TAccordion.
	 * It will register the CSS file specified by {@link getCssUrl CssUrl}.
	 * If that is not set, it will use the default CSS.
	 */
	protected function registerStyleSheet()
	{
		$url = $this->getCssUrl();
		
		if($url === '') {
			return;
		}
		
		if($url === 'default') {
			$url = $this->getApplication()->getAssetManager()->publishFilePath(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'accordion.css');
		}
		
		if($url !== '') {
			$this->getPage()->getClientScript()->registerStyleSheetFile($url, $url);
		}
	}

	/**
	 * Registers the relevant JavaScript.
	 */
	protected function registerClientScript()
	{
		$id=$this->getClientID();
		$options=TJavaScript::encode($this->getClientOptions());
		$className=$this->getClientClassName();
		$page=$this->getPage();
		$cs=$page->getClientScript();
		$cs->registerPradoScript('accordion');
		$code="new $className($options);";
		$cs->registerEndScript("prado:$id", $code);
		// ensure an item is always active and visible
		$index = $this->getActiveViewIndex();
		if(!$this->getViews()->itemAt($index)->Visible)
			$index=0;
		$cs->registerHiddenField($id.'_1', $index);
		$page->registerRequiresPostData($this);
		$page->registerRequiresPostData($id."_1");
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TAccordion';
	}

	/**
	 * @return array the options for JavaScript
	 */
	protected function getClientOptions()
	{
		$options['ID'] = $this->getClientID();
		$options['ActiveHeaderCssClass'] = $this->getActiveHeaderCssClass();
		$options['HeaderCssClass'] = $this->getHeaderCssClass();
		$options['Duration'] = $this->getAnimationDuration();

		if (($viewheight = $this->getViewHeight())>0)
			$options['maxHeight'] = $viewheight;
		$views = array();
		foreach($this->getViews() as $view)
			$views[$view->getClientID()] = $view->getVisible() ? '1': '0';
		$options['Views'] = $views;

		return $options;
	}

	/**
	 * Creates a control collection object that is to be used to hold child controls
	 * @return TAccordionViewCollection control collection
	 */
	protected function createControlCollection()
	{
		return new TAccordionViewCollection($this);
	}

	/**
	 * @return TAccordionViewCollection list of {@link TAccordionView} controls
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
	 * Renders body contents of the accordion control.
	 * @param THtmlWriter the writer used for the rendering purpose.
	 */
	public function renderContents($writer)
	{
		$views=$this->getViews();
		if($views->getCount()>0)
		{
			$writer->writeLine();
			foreach($views as $view)
			{
				$view->renderHeader($writer);
				$view->renderControl($writer);
				$writer->writeLine();
			}
		}
	}

}

/**
 * Class TAccordionView.
 *
 * TAccordionView represents a single view in a {@link TAccordion}.
 * 
 * TAccordionView is represented inside the {@link TAccordion} with an header label whose text is defined by
 * the {@link setCaption Caption} property; optionally the label can be an hyperlink: use the
 * {@link setNavigateUrl NavigateUrl} property to define the destination url.
 *
 * @author Gabor Berczi, DevWorx Hungary <gabor.berczi@devworx.hu>
 * @version $Id: TAccordion.php 2915 2011-05-15 16:26:11Z ctrlaltca@gmail.com $
 * @package System.Web.UI.WebControls
 * @since 3.2
 */
class TAccordionView extends TWebControl
{
	private $_active=false;

	/**
	 * @return the tag name for the view element
	 */
	protected function getTagName()
	{
		return 'div';
	}

	/**
	 * Adds attributes to renderer.
	 * @param THtmlWriter the renderer
	 */
	protected function addAttributesToRender($writer)
	{
		if(!$this->getActive() && $this->getPage()->getClientSupportsJavaScript())
			$this->getStyle()->setStyleField('display','none');

		$this->getStyle()->mergeWith($this->getParent()->getViewStyle());

		parent::addAttributesToRender($writer);

		$writer->addAttribute('id',$this->getClientID());
	}

	/**
	 * @return string the caption displayed on this header. Defaults to ''.
	 */
	public function getCaption()
	{
		return $this->getViewState('Caption','');
	}

	/**
	 * @param string the caption displayed on this header
	 */
	public function setCaption($value)
	{
		$this->setViewState('Caption',TPropertyValue::ensureString($value),'');
	}

	/**
	 * @return string the URL of the target page. Defaults to ''.
	 */
	public function getNavigateUrl()
	{
		return $this->getViewState('NavigateUrl','');
	}

	/**
	 * Sets the URL of the target page.
	 * If not empty, clicking on this header will redirect the browser to the specified URL.
	 * @param string the URL of the target page.
	 */
	public function setNavigateUrl($value)
	{
		$this->setViewState('NavigateUrl',TPropertyValue::ensureString($value),'');
	}

	/**
	 * @return string the text content displayed on this view. Defaults to ''.
	 */
	public function getText()
	{
		return $this->getViewState('Text','');
	}

	/**
	 * Sets the text content to be displayed on this view.
	 * If this is not empty, the child content of the view will be ignored.
	 * @param string the text content displayed on this view
	 */
	public function setText($value)
	{
		$this->setViewState('Text',TPropertyValue::ensureString($value),'');
	}

	/**
	 * @return boolean whether this accordion view is active. Defaults to false.
	 */
	public function getActive()
	{
		return $this->_active;
	}

	/**
	 * @param boolean whether this accordion view is active.
	 */
	public function setActive($value)
	{
		$this->_active=TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Renders body contents of the accordion view.
	 * @param THtmlWriter the writer used for the rendering purpose.
	 */
	public function renderContents($writer)
	{
		if(($text=$this->getText())!=='')
			$writer->write($text);
		else if($this->getHasControls())
			parent::renderContents($writer);
	}

	/**
	 * Renders the header associated with the accordion view.
	 * @param THtmlWriter the writer for rendering purpose.
	 */
	public function renderHeader($writer)
	{
		if($this->getVisible(false) && $this->getPage()->getClientSupportsJavaScript())
		{
			$writer->addAttribute('id',$this->getClientID().'_0');

			$style=$this->getActive()?$this->getParent()->getActiveHeaderStyle():$this->getParent()->getHeaderStyle();
			
			$style->addAttributesToRender($writer);

			$writer->renderBeginTag($this->getTagName());

			$this->renderHeaderContent($writer);

			$writer->renderEndTag();
		}
	}

	/**
	 * Renders the content in the header.
	 * By default, a hyperlink is displayed.
	 * @param THtmlWriter the HTML writer
	 */
	protected function renderHeaderContent($writer)
	{
		$url = $this->getNavigateUrl();
		if(($caption=$this->getCaption())==='')
			$caption='&nbsp;';
			
		if ($url!='')
			$writer->write("<a href=\"{$url}\">");
		$writer->write("{$caption}");
		if ($url!='')
			$writer->write("</a>");
	}
}

/**
 * Class TAccordionViewCollection.
 *
 * TAccordionViewCollection is a collection of {@link TAccordionView} to be used inside a {@link TAccordion}.
 * 
 * @author Gabor Berczi, DevWorx Hungary <gabor.berczi@devworx.hu>
 * @version $Id: TAccordion.php 2915 2011-05-15 16:26:11Z ctrlaltca@gmail.com $
 * @package System.Web.UI.WebControls
 * @since 3.2
 */
class TAccordionViewCollection extends TControlCollection
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by performing sanity check on the type of new item.
	 * @param integer the speicified position.
	 * @param mixed new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a {@link TAccordionView} object.
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof TAccordionView)
			parent::insertAt($index,$item);
		else
			throw new TInvalidDataTypeException('tabviewcollection_tabview_required');
	}

	/**
	 * Finds the index of the accordion view whose ID is the same as the one being looked for.
	 * @param string the explicit ID of the accordion view to be looked for
	 * @return integer the index of the accordion view found, -1 if not found.
	 */
	public function findIndexByID($id)
	{
		foreach($this as $index=>$view)
		{
			if($view->getID(false)===$id)
				return $index;
		}
		return -1;
	}
}

