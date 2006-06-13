<?php
/**
 * TRatingList class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * Includes TRadioButtonList class
 */
Prado::using('System.Web.UI.WebControls.TRadioButtonList');

/**
 * TRatingList class.
 *
 * This class is EXPERIMENTAL.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TRatingList extends TRadioButtonList
{
	private $_ratingImages = array();

	public function __construct()
	{
		parent::__construct();
		$this->getRepeatInfo()->setRepeatDirection('Horizontal');
	}

	public function getAllowInput()
	{
		return $this->getViewState('AllowInput', true);
	}

	public function setAllowInput($value)
	{
		$this->setViewState('AllowInput', TPropertyValue::ensureBoolean($value), true);
	}

	public function getRating()
	{
		if($this->getAllowInput())
			return $this->getSelectedIndex();
		else
			return $this->getViewState('Rating',0);
	}

	public function setRating($value)
	{
		if($this->getAllowInput())
			$this->setSelectedIndex($value);
		else
			$this->setViewState('Rating', TPropertyValue::ensureFloat($value),0);
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

	/**
	 * @return string caption text. Default is "Rate It:".
	 */
	public function getCaption()
	{
		return $this->getViewState('Caption', 'Rate It:');
	}

	/**
	 * @param string caption text
	 */
	public function setCaption($value)
	{
		$this->setViewState('Caption', $value, 'Rate It:');
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

	public function getRatingClientOptions()
	{
		$options['cssClass'] = 'TRatingList_'.$this->getRatingStyle();
		$options['ID'] = $this->getClientID();
		$options['caption'] = $this->getCaption();
		$options['field'] = $this->getUniqueID();
		$options['selectedIndex'] = $this->getSelectedIndex();
		return $options;
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

	protected function publishRatingListImages($style, $fileExt='.gif')
	{
		$images['blank'] = "System.Web.Javascripts.ratings.{$style}_blank";
		$images['hover'] = "System.Web.Javascripts.ratings.{$style}_hover";
		$images['selected'] = "System.Web.Javascripts.ratings.{$style}_selected";
		$images['half'] = "System.Web.Javascripts.ratings.{$style}_half";
		$files = array();
		foreach($images as $type => $image)
		{
			if(($file=Prado::getPathOfNamespace($image, $fileExt))===null)
				throw TConfigurationException('ratinglist_image_not_found',$image);
			$files[$type] = $this->publishFilePath($file);
		}
		return $files;
	}

	/**
	 * @param THtmlWriter writer
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);

		$this->publishRatingListStyle($this->getRatingStyle());
		$this->_ratingImages = $this->publishRatingListImages($this->getRatingStyle());

		if($this->getAllowInput())
			$this->registerRatingListClientScript();
		else
		{
			$this->getRepeatInfo()->setCaption($this->getCaption());
			$this->setAttribute('title', $this->getRating());
		}
	}

	protected function registerRatingListClientScript()
	{
		$id = $this->getClientID();
		$scripts = $this->getPage()->getClientScript();
		$scripts->registerPradoScript('prado');
		$options = TJavaScript::encode($this->getRatingClientOptions());
		$code = "new Prado.WebUI.TRatingList($options);";
		$scripts->registerEndScript("prado:$id", $code);
	}

	public function renderItem($writer,$repeatInfo,$itemType,$index)
	{
		if($this->getAllowInput())
			parent::renderItem($writer, $repeatInfo, $itemType, $index);
		else
			$this->renderRatingListItem($writer, $repeatInfo, $itemType, $index);
	}

	protected function renderRatingListItem($writer, $repeatInfo, $itemType, $index)
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
		if($index < $int || ($rating < $index + 1 && $rating > $index +$limit[1]))
			return 'selected';
		if($rating >= $index+$limit[0] && $rating <= $index+$limit[1])
			return 'half';
		return 'blank';
	}

	public function generateItemStyle($itemType,$index)
	{
		return new TStyle;
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