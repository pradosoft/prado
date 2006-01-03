<?php

class TBulletedList extends TListControl implements IPostBackEventHandler
{
	private $_isEnabled;
	private $_postBackOptions;

	public function raisePostBackEvent($param)
	{
		if($this->getCausesValidation())
			$this->getPage()->validate($this->getValidationGroup());
		$this->onClick(new TBulletedListEventParameter((int)$param));
	}

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

	public function getBulletImageUrl()
	{
		return $this->getViewState('BulletImageUrl','');
	}

	public function setBulletImageUrl($value)
	{
		$this->setViewState('BulletImageUrl',$value,'');
	}

	public function getBulletStyle()
	{
		return $this->getViewState('BulletStyle','NotSet');
	}

	public function setBulletStyle($value)
	{
		$this->setViewState('BulletStyle',TPropertyValue::ensureEnum($value,'NotSet','Numbered','LowerAlpha','UpperAlpha','LowerRoman','UpperRoman','Disc','Circle','Square','CustomImage'),'NotSet');
	}

	public function getDisplayMode()
	{
		return $this->getViewState('DisplayMode','Text');
	}

	public function setDisplayMode($value)
	{
		$this->setViewState('DisplayMode',TPropertyValue::ensureEnum($value,'Text','HyperLink','LinkButton'),'Text');
	}

	public function getFirstBulletNumber()
	{
		return $this->getViewState('FirstBulletNumber',1);
	}

	public function setFirstBulletNumber($value)
	{
		$this->setViewState('FirstBulletNumber',TPropertyValue::ensureInteger($value),1);
	}

	public function onClick($param)
	{
		$this->raiseEvent('Click',$this,$param);
	}

	/**
	 * @return string the target window or frame to display the Web page content linked to when the THyperLink component is clicked.
	 */
	public function getTarget()
	{
		return $this->getViewState('Target','');
	}

	/**
	 * Sets the target window or frame to display the Web page content linked to when the THyperLink component is clicked.
	 * @param string the target window, valid values include '_blank', '_parent', '_self', '_top' and empty string.
	 */
	public function setTarget($value)
	{
		$this->setViewState('Target',$value,'');
	}

	protected function render($writer)
	{
		if($this->getHasItems())
			parent::render($writer);
	}

	protected function renderContents($writer)
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

	protected function renderBulletText($writer,$item,$index)
	{
		switch($this->getDisplayMode())
		{
			case 'Text':
				if($item->getEnabled())
					$writer->write(THttpUtility::htmlEncode($item->getText()));
				else
				{
					$writer->addAttribute('disabled','disabled');
					$writer->renderBeginTag('span');
					$writer->write(THttpUtility::htmlEncode($item->getText()));
					$writer->renderEndTag();
				}
				return;
			case 'HyperLink':
				if(!$this->_isEnabled || !$item->getEnabled())
					$writer->addAttribute('disabled','disabled');
				else
				{
					$writer->addAttribute('href',$item->getValue());
					if(($target=$this->getTarget())!=='')
						$writer->addAttribute('target',$target);
				}
				break;
			case 'LinkButton':
				if(!$this->_isEnabled || !$item->getEnabled())
					$writer->addAttribute('disabled','disabled');
				else
				{
					$postback=$this->getPage()->getClientScript()->getPostBackEventReference($this,"$index",$this->_postBackOptions);
					$writer->addAttribute('href',$postback);
				}
		}
		if(($accesskey=$this->getAccessKey())!=='')
			$writer->addAttribute('accesskey',$accesskey);
		$writer->renderBeginTag('a');
		$writer->write(THttpUtility::htmlEncode($item->getText()));
		$writer->renderEndTag();
	}

	protected function getPostBackOptions()
	{
		$option=new TPostBackOptions();
		$group = $this->getValidationGroup();
		$hasValidators = $this->getPage()->getValidators($group)->getCount()>0;
		if($this->getCausesValidation() && $hasValidators)
		{
			$options->setPerformValidation(true);
			$options->setValidationGroup($this->getValidationGroup());
			return $options;
		}
		else
			return null;
	}
}

class TBulletedListEventParameter extends TEventParameter
{
	private $_index;
	public function __construct($index)
	{
		$this->_index=$index;
	}

	public function getIndex()
	{
		return $this->_index;
	}
}
?>