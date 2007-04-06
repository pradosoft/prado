<?php
/**
 * TActiveRatingList class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Web.UI.ActiveControls
 */

/**
 * TActiveRatingList Class
 *
 * Displays clickable images that represent a TActiveRadioButtonList
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.Web.UI.ActiveControls
 * @since 3.1
 */
class TActiveRatingList extends TActiveRadioButtonList
{
	const SCRIPT_PATH = 'prado/activeratings';

	/**
	 * @var array list of published rating images.
	 */
	private $_ratingImages = array();

	/**
	 * Sets the default repeat direction to horizontal.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setRepeatDirection(TRepeatDirection::Horizontal);
	}

	/**
	 * @return boolean whether the items in the column can be edited. Defaults to false.
	 */
	public function getReadOnly()
	{
		return $this->getViewState('ReadOnly',false);
	}

	/**
	 * @param boolean whether the items in the column can be edited
	 */
	public function setReadOnly($value)
	{
		$this->setViewState('ReadOnly',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * The repeat layout must be Table.
	 * @param string repeat layout type
	 * @throws TInvaliddataValueException when repeat layout is not Table.
	 */
	public function setRepeatLayout($value)
	{
		if($value!==TRepeatLayout::Table)
			throw new TInvalidDataValueException('ratinglist_table_layout_only');
		else
			parent::setRepeatLayout($value);
	}

	/**
	 * @return float rating value.
	 */
	public function getRating()
	{
		return $this->getViewState('Rating',0.0);
	}

	/**
	 * @param float rating value, also sets the selected Index
	 */
	public function setRating($value)
	{
		$rating = TPropertyValue::ensureFloat($value);
		$this->setViewState('Rating', $rating);
		$canUpdate = $this->getActiveControl()->getEnableUpdate();
		$this->getActiveControl()->setEnableUpdate(false);
		parent::setSelectedIndex($this->getRatingIndex($rating));
		$this->getActiveControl()->setEnableUpdate($canUpdate);
		if($this->getActiveControl()->canUpdateClientSide())
			$this->callClientFunction('setRating',$rating);
	}

	/**
	 * @param float rating value
	 * @return int rating as integer
	 */
	protected function getRatingIndex($rating)
	{
		$interval = $this->getHalfRatingInterval();
		$base = intval($rating)-1;
		$remainder = $rating-$base-1;
		return $remainder > $interval[1] ? $base+1 : $base;
	}

	/**
	 * @param int change the rating selection index
	 */
	public function setSelectedIndex($value)
	{
		$value = TPropertyValue::ensureInteger($value);
		$canUpdate = $this->getActiveControl()->getEnableUpdate();
		$this->getActiveControl()->setEnableUpdate(false);
		parent::setSelectedIndex($value);
		$this->getActiveControl()->setEnableUpdate($canUpdate);
		if($this->getActiveControl()->canUpdateClientSide())
			$this->callClientFunction('setRating',$value+1);
	}

	/**
	 * Calls the client-side static method for this control class.
	 * @param string static method name
	 * @param mixed method parmaeter
	 */
	protected function callClientFunction($func,$value)
	{
		$client = $this->getPage()->getCallbackClient();
		$code = $this->getClientClassName().'.'.$func;
		$client->callClientFunction($code,array($this,$value));
	}

	/**
	 * @return string control or html element ID for displaying a caption.
	 */
	public function getCaptionID()
	{
		return $this->getViewState('CaptionID', '');
	}

	/**
	 * @param string control or html element ID for displaying a caption.
	 */
	public function setCaptionID($value)
	{
		$this->setViewState('CaptionID', $value, '');
	}

	protected function getCaptionControl()
	{
		if(($id=$this->getCaptionID())!=='')
		{
			if($control=$this->getParent()->findControl($id))
				return $control;
		}
		throw new TInvalidDataValueException(
			'ratinglist_invalid_caption_id',$id,$this->getID());
	}

	public function setCaption($value)
	{
		$this->getCaptionControl()->setText($value);
		if($this->getActiveControl()->canUpdateClientSide())
			$this->callClientFunction('setCaption',$value);
	}

	public function getCaption()
	{
		return $this->getCaptionControl()->getText();
	}

	/**
	 * @param boolean true to enable the rating to be changed.
	 */
	public function setEnabled($value)
	{
		$value = TPropertyValue::ensureBoolean($value);
		parent::setEnabled($value);
		if($this->getActiveControl()->canUpdateClientSide())
			$this->callClientFunction('setEnabled',$value);
	}

	/**
	 * @param string set the rating style, default is "default"
	 */
	public function setRatingStyle($value)
	{
	   $this->setViewState('RatingStyle', $value, 'default');
	}

	/**
	 * @return TActiveRatingListStyle current rating style
	 */
	public function getRatingStyle()
	{
	   return $this->getViewState('RatingStyle', 'default');
	}

	/**
	 * Sets the interval such that those rating values within the interval
	 * will be considered as a half star rating.
	 * @param array rating display half value interval, default is array(0.3, 0.7);
	 */
	public function setHalfRatingInterval($value)
	{
		$this->setViewState('HalfRating',
				TPropertyValue::ensureArray($value), array(0.3, 0.7));
	}

	/**
	 * @return array rating display half value interval, default is array(0.3, 0.7);
	 */
	public function getHalfRatingInterval()
	{
		return $this->getViewState('HalfRating', array(0.3, 0.7));
	}

	/**
	 * @return string rating style css class name.
	 */
	protected function getRatingStyleCssClass()
	{
		return 'TActiveRatingList_'.$this->getRatingStyle();
	}

	/**
	 * @return array list of post back options.
	 */
	protected function getPostBackOptions()
	{
		$options = parent::getPostBackOptions();
		$options['Style'] = $this->getRatingStyleCssClass();
		$options['CaptionID'] = $this->getCaptionControlID();
		$options['SelectedIndex'] = $this->getSelectedIndex();
		$options['Rating'] = $this->getRating();
		$options['HalfRating'] = $this->getHalfRatingInterval();
		return $options;
	}

	/**
	 * Registers the javascript code for initializing the active control
	 * only if {@link setReadOnly ReadOnly} property is false.
	 */
	protected function renderClientControlScript($writer)
	{
		if($this->getReadOnly()===false)
			parent::renderClientControlScript($writer);
	}

	/**
	 * @return string find the client ID of the caption control.
	 */
	protected function getCaptionControlID()
	{
		if(($id=$this->getCaptionID())!=='')
		{
			if($control=$this->getParent()->findControl($id))
			{
				if($control->getVisible(true))
					return $control->getClientID();
			}
			else
				return $id;
		}
		return '';
	}

	/**
	 * @param string asset file in the self::SCRIPT_PATH directory.
	 * @return string asset file url.
	 */
	protected function getAssetUrl($file='')
	{
		$base = $this->getPage()->getClientScript()->getPradoScriptAssetUrl();
		return $base.'/'.self::SCRIPT_PATH.'/'.$file;
	}

	/**
	 * @param string rating style name
	 * @return string URL of the css style file
	 */
	protected function publishRatingListStyle($style)
	{
		$cs = $this->getPage()->getClientScript();
		$url = $this->getAssetUrl($style.'.css');
		if(!$cs->isStyleSheetFileRegistered($url))
			$cs->registerStyleSheetFile($url, $url);
		return $url;
	}

	/**
	 * @param string rating style name
	 * @param string rating image file extension, default is '.gif'
	 * @return array URL of publish the rating images
	 */
	protected function publishRatingListImages($style, $fileExt='.gif')
	{
		$types = array('blank', 'selected', 'half', 'combined');
		$files = array();
		foreach($types as $type)
			$files[$type] = $this->getAssetUrl("{$style}_{$type}{$fileExt}");
		return $files;
	}

	/**
	 * Add rating style class name to the class attribute
	 * when {@link setReadOnly ReadOnly} property is true and when the
	 * {@link setCssClass CssClass} property is empty.
	 * @param THtmlWriter renderer
	 */
	public function render($writer)
	{
		if($this->getReadOnly())
			$writer->addAttribute('class', $this->getRatingStyleCssClass());
		else
		{
			$writer->addAttribute('id',$this->getClientID());
			$this->getActiveControl()->registerCallbackClientScript(
				$this->getClientClassName(), $this->getPostBackOptions());
		}
		parent::render($writer);
	}

	/**
	 * Publish the the rating style css file and rating image files.
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);

		$this->publishRatingListStyle($this->getRatingStyle());
		$this->_ratingImages = $this->publishRatingListImages($this->getRatingStyle());
	}

	/**
	 * Renders the rating images if {@link setReadOnly ReadOnly} is true
	 * otherwise render the radio buttons.
	 */
	public function renderItem($writer,$repeatInfo,$itemType,$index)
	{
		if($this->getReadOnly())
			$this->renderStaticRating($writer, $repeatInfo, $itemType, $index);
		else
			parent::renderItem($writer, $repeatInfo, $itemType, $index);
	}

	/**
	 * Renders the static rating images.
	 */
	protected function renderStaticRating($writer, $repeatInfo, $itemType, $index)
	{
		$image = new TImage;
		$image->setImageUrl($this->_ratingImages[$this->getRatingImageType($index)]);
		$image->setAlternateText($this->getRating());
		$image->render($writer);
	}

	/**
	 * @param integer rating image index
	 * @return string the rating image corresponding to current index to be rendered.
	 */
	protected function getRatingImageType($index)
	{
		$rating = floatval($this->getRating());
		$int = intval($rating);
		$limit = $this->getHalfRatingInterval();
		if($index < $int || ($rating < $index+1 && $rating > $index+$limit[1]))
			return 'selected';
		if($rating >= $index+$limit[0] && $rating <= $index+$limit[1])
			return 'half';
		return 'blank';
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TActiveRatingList';
	}
}

?>