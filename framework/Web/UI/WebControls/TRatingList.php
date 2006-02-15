<?php

Prado::using('System.Web.UI.WebControls.TRadioButtonList');

/**
 * TRatingList
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TRatingList extends TRadioButtonList
{

	public function __construct()
	{
		parent::__construct();
		$this->getRepeatInfo()->setRepeatDirection('Horizontal');
	}

	/**
	 * @param string the direction (Vertical, Horizontal) of traversing the list
	 */
	public function setRepeatDirection($value)
	{
		throw new TNotSupportedException('ratinglits_repeatdirection_unsupported');
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
	   $style = $this->getViewState('RatingStyle', 'default');
	   return is_string($style) ? $this->createRatingStyle($style) : $style;
	}

	protected function createRatingStyle($type)
	{
		return new TRatingListDefaultStyle;
	}

	/**
	 * @return string caption text. Default is "Rate It:".
	 */
	public function getCaptionText()
	{
		return $this->getViewState('Caption', 'Rate It:');
	}

	/**
	 * @param string caption text
	 */
	public function setCaptionText($value)
	{
		$this->setViewState('Caption', $value, 'Rate It:');
	}

	public function getRatingClientOptions()
	{
		$options = $this->getRatingStyle()->getOptions();
		$options['ID'] = $this->getClientID();
		$options['caption'] = $this->getCaptionText();
		$options['field'] = $this->getUniqueID();
		$options['total'] = $this->getItems()->getCount();
		$options['pos'] = $this->getSelectedIndex();
		return $options;
	}

	protected function publishRatingListStyle()
	{
		$cs = $this->getPage()->getClientScript();
		$style = $this->getRatingStyle()->getStyleSheet();
		$url = $this->getService()->getAsset($style);
		if(!$cs->isStyleSheetFileRegistered($style))
			$cs->registerStyleSheetFile($style, $url);	
		return $url;
	}
	
	protected function publishRatingListAssets()
	{
		$cs = $this->getPage()->getClientScript();
		$assets = $this->getRatingStyle()->getAssets();
		$list = array();
		foreach($assets as $file)
			$list[] = $this->getService()->getAsset($file);
		return $list;
	}
	
	/**
	 * @param THtmlWriter writer
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->publishRatingListStyle();
		$this->publishRatingListAssets();
		$id = $this->getClientID();
		$scripts = $this->getPage()->getClientScript();
		$serializer = new TJavascriptSerializer($this->getRatingClientOptions());
		$options = $serializer->toJavascript();
		$code = "new Prado.WebUI.TRatingList($options);";
		$scripts->registerEndScript("prado:$id", $code);
	}
}

abstract class TRatingListStyle
{
	private $_options = array();

	public function __construct()
	{
		$options['pos'] = -1;
		$options['dx'] = 22;
		$options['dy'] = 30;
		$options['ix'] = 4;
		$options['iy'] = 4;
		$options['hx'] = 240;
		$options['total'] = -1;
		$this->_options = $options;
	}

	public function getOptions()
	{
		return $this->_options;
	}

	public function setOptions($options)
	{
		$this->_options = $options;
	}

	abstract function getStyleSheet();

	abstract function getAssets();
}

class TRatingListDefaultStyle extends TRatingListStyle
{	
	public function __construct()
	{
		parent::__construct();
		$options = $this->getOptions();
		$options['cssClass'] = 'TRatingList_default';
		$this->setOptions($options);
	}

	public function getStyleSheet()
	{
		$style = 'System.Web.Javascripts.ratings.default';
		$cssFile=Prado::getPathOfNamespace($style,'.css');
		return $cssFile;
	}

	public function getAssets()
	{
		$assets = array();
		$image = 'System.Web.Javascripts.ratings.10star_white';
		$assets[] =  Prado::getPathOfNamespace($image, '.gif');
		return $assets;
	}
}

?>