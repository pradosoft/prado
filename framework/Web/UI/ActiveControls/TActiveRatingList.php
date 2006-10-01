<?php

class TActiveRatingList extends TActiveRadioButtonList
{
	private $_ratingImages = array();

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

	public function setRepeatLayout($value)
	{
		if($value!==TRepeatLayout::Table)
			throw new TInvalidDataValueException('ratinglist_table_layout_only');
		else
			parent::setRepeatLayout($value);
	}

	/**
	 * @param boolean whether the items in the column can be edited
	 */
	public function setReadOnly($value)
	{
		$this->setViewState('ReadOnly',TPropertyValue::ensureBoolean($value),false);
	}

	public function getRating()
	{
		return $this->getViewState('Rating',0);
	}

	public function setRating($value)
	{
		$this->setViewState('Rating', TPropertyValue::ensureFloat($value),0);
	}

	public function setSelectedIndex($value)
	{
		$canUpdate = $this->getActiveControl()->getEnableUpdate();
		$this->getActiveControl()->setEnableUpdate(false);
		parent::setSelectedIndex($value);
		$this->getActiveControl()->setEnableUpdate($canUpdate);
		if($this->getActiveControl()->canUpdateClientSide())
			$this->callClientFunction('setRating',$value);
	}

	protected function callClientFunction($func,$value)
	{
		$client = $this->getPage()->getCallbackClient();
		$code = $this->getClientClassName().'.'.$func;
		$client->callClientFunction($code,array($this,$value));
	}

	/**
	 * @return string caption text.
	 */
	public function getCaptionID()
	{
		return $this->getViewState('CaptionID', '');
	}

	/**
	 * @param string caption text
	 */
	public function setCaptionID($value)
	{
		$this->setViewState('CaptionID', $value, '');
	}

	public function setEnabled($value)
	{
		parent::setEnabled($value);
		if($this->getActiveControl()->canUpdateClientSide())
			$this->callClientFunction('setEnabled',$value);
	}

	/**
	 * @param string set the rating style
	 */
	public function setRatingStyle($value)
	{
	   $this->setViewState('RatingStyle', $value, 'default');
	}

	/**
	 * @return TRatingListStyle current rating style
	 */
	public function getRatingStyle()
	{
	   return $this->getViewState('RatingStyle', 'default');
	}

	public function setHalfRatingLimit($value)
	{
		$this->setViewState('HalfRating',
				TPropertyValue::ensureArray($value), array(0.3, 0.7));
	}

	public function getHalfRatingLimit()
	{
		return $this->getViewState('HalfRating', array(0.3, 0.7));
	}

	protected function getRatingStyleCssClass()
	{
		return 'TRatingList_'.$this->getRatingStyle();
	}

	protected function getPostBackOptions()
	{
		$options = parent::getPostBackOptions();
		$options['Style'] = $this->getRatingStyleCssClass();
		$options['CaptionID'] = $this->getCaptionControl();
		$options['SelectedIndex'] = $this->getSelectedIndex();
		return $options;
	}

	/**
	 * Registers the javascript code for initializing the active control.
	 */
	protected function renderClientControlScript($writer)
	{
		if($this->getReadOnly()===false)
			parent::renderClientControlScript($writer);
	}

	protected function getCaptionControl()
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

	protected function publishRatingListStyle($style)
	{
		$cs = $this->getPage()->getClientScript();
		$stylesheet = 'System.Web.Javascripts.ratings.'.$style;
		if(($cssFile=Prado::getPathOfNamespace($stylesheet,'.css'))===null)
			throw new TConfigurationException('ratinglist_stylesheet_not_found',$style);
		$url = $this->publishFilePath($cssFile);
		if(!$cs->isStyleSheetFileRegistered($style))
			$cs->registerStyleSheetFile($style, $url);
		return $url;
	}

	protected function publishRatingListImages($style, $fileExt='.png')
	{
		$images['blank'] = "System.Web.Javascripts.ratings.{$style}_blank";
		$images['selected'] = "System.Web.Javascripts.ratings.{$style}_selected";
		$images['half'] = "System.Web.Javascripts.ratings.{$style}_half";
		$images['combined'] = "System.Web.Javascripts.ratings.{$style}_combined";
		$files = array();
		foreach($images as $type => $image)
		{
			if(($file=Prado::getPathOfNamespace($image, $fileExt))===null)
				throw TConfigurationException('ratinglist_image_not_found',$image);
			$files[$type] = $this->publishFilePath($file);
		}
		return $files;
	}

	public function render($writer)
	{
		if($this->getReadOnly())
		{
			$writer->addAttribute('class', $this->getRatingStyleCssClass());
			$writer->addAttribute('title', $this->getRating());
		}
		parent::render($writer);
	}

	/**
	 * @param THtmlWriter writer
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);

		$this->publishRatingListStyle($this->getRatingStyle());
		$this->_ratingImages = $this->publishRatingListImages($this->getRatingStyle());
	}

	public function renderItem($writer,$repeatInfo,$itemType,$index)
	{
		if($this->getReadOnly())
			$this->renderStaticRating($writer, $repeatInfo, $itemType, $index);
		else
			parent::renderItem($writer, $repeatInfo, $itemType, $index);
	}

	protected function renderStaticRating($writer, $repeatInfo, $itemType, $index)
	{
		$image = new TImage;
		$image->setImageUrl($this->_ratingImages[$this->getRatingImageType($index)]);
		$image->setAlternateText($this->getRating());
		$image->render($writer);
	}

	protected function getRatingImageType($index)
	{
		$rating = floatval($this->getRating());
		$int = intval($rating);
		$limit = $this->getHalfRatingLimit();
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
		return 'Prado.WebUI.TRatingList';
	}
}

?>