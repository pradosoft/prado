<?php
/**
 * TBulletedList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * Includes TListControl class
 */
Prado::using('System.Web.UI.WebControls.TListControl');

/**
 * TBulletedList class
 *
 * TBulletedList displays items in a bullet format.
 * The bullet style is specified by {@link setBulletStyle BulletStyle}. When
 * the style is 'CustomImage', the {@link setBackImageUrl BulletImageUrl}
 * specifies the image used as bullets.
 *
 * TBulletedList displays the item texts in three different modes, specified
 * via {@link setDisplayMode DisplayMode}. When the mode is 'Text', the item texts
 * are displayed as static texts; When the mode is 'HyperLink', each item
 * is displayed as a hyperlink whose URL is given by the item value, and the
 * {@link setTarget Target} property can be used to specify the target browser window;
 * When the mode is 'LinkButton', each item is displayed as a link button which
 * posts back to the page if a user clicks on that and the event {@link onClick OnClick}
 * will be raised under such a circumstance.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TBulletedList extends TListControl implements IPostBackEventHandler
{
	/**
	 * @var boolean cached property value of Enabled
	 */
	private $_isEnabled;
	/**
	 * @var TPostBackOptions postback options
	 */
	private $_postBackOptions;

	private $_currentRenderItemIndex;

	/**
	 * Raises the postback event.
	 * This method is required by {@link IPostBackEventHandler} interface.
	 * If {@link getCausesValidation CausesValidation} is true, it will
	 * invoke the page's {@link TPage::validate validate} method first.
	 * It will raise {@link onClick OnClick} events.
	 * This method is mainly used by framework and control developers.
	 * @param TEventParameter the event parameter
	 */
	public function raisePostBackEvent($param)
	{
		if($this->getCausesValidation())
			$this->getPage()->validate($this->getValidationGroup());
		$this->onClick(new TBulletedListEventParameter((int)$param));
	}

	/**
	 * @return string tag name of the bulleted list
	 */
	protected function getTagName()
	{
		switch($this->getBulletStyle())
		{
			case 'Numbered':
			case 'LowerAlpha':
			case 'UpperAlpha':
			case 'LowerRoman':
			case 'UpperRoman':
				return 'ol';
		}
		return 'ul';
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * This overrides the parent implementation with additional bulleted list specific attributes.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$needStart=false;
		switch($this->getBulletStyle())
		{
			case 'Numbered':
				$writer->addStyleAttribute('list-style-type','decimal');
				$needStart=true;
				break;
			case 'LowerAlpha':
				$writer->addStyleAttribute('list-style-type','lower-alpha');
				$needStart=true;
				break;
			case 'UpperAlpha':
				$writer->addStyleAttribute('list-style-type','upper-alpha');
				$needStart=true;
				break;
			case 'LowerRoman':
				$writer->addStyleAttribute('list-style-type','lower-roman');
				$needStart=true;
				break;
			case 'UpperRoman':
				$writer->addStyleAttribute('list-style-type','upper-roman');
				$needStart=true;
				break;
			case 'Disc':
				$writer->addStyleAttribute('list-style-type','disc');
				break;
			case 'Circle':
				$writer->addStyleAttribute('list-style-type','circle');
				break;
			case 'Square':
				$writer->addStyleAttribute('list-style-type','square');
				break;
			case 'CustomImage':
				$url=$this->getBulletImageUrl();
				$writer->addStyleAttribute('list-style-image',"url($url)");
				break;
		}
		if($needStart && ($start=$this->getFirstBulletNumber())!=1)
			$writer->addAttribute('start',"$start");
		parent::addAttributesToRender($writer);
	}

	/**
	 * @return string image URL used for bullets when {@link getBulletStyle BulletStyle} is 'CustomImage'.
	 */
	public function getBulletImageUrl()
	{
		return $this->getViewState('BulletImageUrl','');
	}

	/**
	 * @param string image URL used for bullets when {@link getBulletStyle BulletStyle} is 'CustomImage'.
	 */
	public function setBulletImageUrl($value)
	{
		$this->setViewState('BulletImageUrl',$value,'');
	}

	/**
	 * @return string style of bullets. Defaults to 'NotSet'.
	 */
	public function getBulletStyle()
	{
		return $this->getViewState('BulletStyle','NotSet');
	}

	/**
	 * @return string style of bullets. Valid values include
	 * 'NotSet','Numbered','LowerAlpha','UpperAlpha','LowerRoman','UpperRoman','Disc','Circle','Square','CustomImage'
	 */
	public function setBulletStyle($value)
	{
		$this->setViewState('BulletStyle',TPropertyValue::ensureEnum($value,'NotSet','Numbered','LowerAlpha','UpperAlpha','LowerRoman','UpperRoman','Disc','Circle','Square','CustomImage'),'NotSet');
	}

	/**
	 * @param string display mode of the list. Defaults to 'Text'.
	 */
	public function getDisplayMode()
	{
		return $this->getViewState('DisplayMode','Text');
	}

	/**
	 * @return string display mode of the list. Valid values include
	 * 'Text', 'HyperLink', 'LinkButton'.
	 */
	public function setDisplayMode($value)
	{
		$this->setViewState('DisplayMode',TPropertyValue::ensureEnum($value,'Text','HyperLink','LinkButton'),'Text');
	}

	/**
	 * @return integer starting index when {@link getBulletStyle BulletStyle} is one of
	 * the following: 'Numbered', 'LowerAlpha', 'UpperAlpha', 'LowerRoman', 'UpperRoman'.
	 * Defaults to 1.
	 */
	public function getFirstBulletNumber()
	{
		return $this->getViewState('FirstBulletNumber',1);
	}

	/**
	 * @param integer starting index when {@link getBulletStyle BulletStyle} is one of
	 * the following: 'Numbered', 'LowerAlpha', 'UpperAlpha', 'LowerRoman', 'UpperRoman'.
	 */
	public function setFirstBulletNumber($value)
	{
		$this->setViewState('FirstBulletNumber',TPropertyValue::ensureInteger($value),1);
	}

	/**
	 * Raises 'OnClick' event.
	 * This method is invoked when the {@link getDisplayMode DisplayMode} is 'LinkButton'
	 * and end-users click on one of the buttons.
	 * @param TBulletedListEventParameter event parameter.
	 */
	public function onClick($param)
	{
		$this->raiseEvent('OnClick',$this,$param);
	}

	/**
	 * @return string the target window or frame to display the Web page content
	 * linked to when {@link getDisplayMode DisplayMode} is 'HyperLink' and one of
	 * the hyperlinks is clicked.
	 */
	public function getTarget()
	{
		return $this->getViewState('Target','');
	}

	/**
	 * @param string the target window or frame to display the Web page content
	 * linked to when {@link getDisplayMode DisplayMode} is 'HyperLink' and one of
	 * the hyperlinks is clicked.
	 */
	public function setTarget($value)
	{
		$this->setViewState('Target',$value,'');
	}

	/**
	 * Renders the control.
	 * @param THtmlWriter the writer for the rendering purpose.
	 */
	public function render($writer)
	{
		if($this->getHasItems())
			parent::render($writer);
	}

	/**
	 * Renders the body contents.
	 * @param THtmlWriter the writer for the rendering purpose.
	 */
	public function renderContents($writer)
	{
		$this->_isEnabled=$this->getEnabled(true);
		$this->_postBackOptions=$this->getPostBackOptions();
		$writer->writeLine();
		foreach($this->getItems() as $index=>$item)
		{
			if($item->getHasAttributes())
			{
				foreach($item->getAttributes() as $name=>$value)
					$writer->addAttribute($name,$value);
			}
			$writer->renderBeginTag('li');
			$this->renderBulletText($writer,$item,$index);
			$writer->renderEndTag();
			$writer->writeLine();
		}
	}

	/**
	 * Renders each item
	 * @param THtmlWriter writer for the rendering purpose
	 * @param TListItem item to be rendered
	 * @param integer index of the item being rendered
	 */
	protected function renderBulletText($writer,$item,$index)
	{
		switch($this->getDisplayMode())
		{
			case 'Text':
				return $this->renderTextItem($writer, $item, $index);
			case 'HyperLink':
				$this->renderHyperLinkItem($writer, $item, $index);
				break;
			case 'LinkButton':
				$this->renderLinkButtonItem($writer, $item, $index);
		}
		if(($accesskey=$this->getAccessKey())!=='')
			$writer->addAttribute('accesskey',$accesskey);
		$writer->renderBeginTag('a');
		$writer->write(THttpUtility::htmlEncode($item->getText()));
		$writer->renderEndTag();
	}

	protected function renderTextItem($writer, $item, $index)
	{
		if($item->getEnabled())
			$writer->write(THttpUtility::htmlEncode($item->getText()));
		else
		{
			$writer->addAttribute('disabled','disabled');
			$writer->renderBeginTag('span');
			$writer->write(THttpUtility::htmlEncode($item->getText()));
			$writer->renderEndTag();
		}
	}

	protected function renderHyperLinkItem($writer, $item, $index)
	{
		if(!$this->_isEnabled || !$item->getEnabled())
			$writer->addAttribute('disabled','disabled');
		else
		{
			$writer->addAttribute('href',$item->getValue());
			if(($target=$this->getTarget())!=='')
				$writer->addAttribute('target',$target);
		}
	}

	protected function renderLinkButtonItem($writer, $item, $index)
	{
		if(!$this->_isEnabled || !$item->getEnabled())
			$writer->addAttribute('disabled','disabled');
		else
		{
			$this->_currentRenderItemIndex = $index;
			$this->getPage()->getClientScript()->registerPostbackControl($this);
			$writer->addAttribute('id', $this->getClientID().$index);
			$writer->addAttribute('href', "javascript:;//".$this->getClientID().$index);
		}
	}

	/**
	 * @return TPostBackOptions postback options used for linkbuttons.
	 */
	public function getPostBackOptions()
	{
		$options['ValidationGroup'] = $this->getValidationGroup();
		$options['CausesValidation'] = $this->getCausesValidation();
		$options['EventTarget'] = $this->getUniqueID();
		$options['EventParameter'] = $this->_currentRenderItemIndex;
		$options['ID'] = $this->getClientID().$this->_currentRenderItemIndex;
		return $options;
	}

	protected function canCauseValidation()
	{
		$group = $this->getValidationGroup();
		$hasValidators = $this->getPage()->getValidators($group)->getCount()>0;
		return $this->getCausesValidation() && $hasValidators;
	}

	/**
	 * @throws TNotSupportedException if this method is invoked
	 */
	public function setAutoPostBack($value)
	{
		throw new TNotSupportedException('bulletedlist_autopostback_unsupported');
	}

	/**
	 * @throws TNotSupportedException if this method is invoked
	 */
	public function setSelectedIndex($index)
	{
		throw new TNotSupportedException('bulletedlist_selectedindex_unsupported');
	}

	/**
	 * @throws TNotSupportedException if this method is invoked
	 */
	public function setSelectedIndices($indices)
	{
		throw new TNotSupportedException('bulletedlist_selectedindices_unsupported');
	}

	/**
	 * @throws TNotSupportedException if this method is invoked
	 */
	public function setSelectedValue($value)
	{
		throw new TNotSupportedException('bulletedlist_selectedvalue_unsupported');
	}
}

/**
 * TBulletedListEventParameter
 * Event parameter for {@link TBulletedList::onClick Click} event of the
 * bulleted list. The {@link getIndex Index} gives the zero-based index
 * of the item that is currently being clicked.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TBulletedListEventParameter extends TEventParameter
{
	/**
	 * @var integer index of the item clicked
	 */
	private $_index;

	/**
	 * Constructor.
	 * @param integer index of the item clicked
	 */
	public function __construct($index)
	{
		$this->_index=$index;
	}

	/**
	 * @return integer zero-based index of the item (rendered as a link button) that is clicked
	 */
	public function getIndex()
	{
		return $this->_index;
	}
}
?>