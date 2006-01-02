<?php

interface IRepeatInfoUser
{
	public function getHasFooter();
	public function getHasHeader();
	public function getHasSeparators();
	public function getRepeatedItemCount();
	public function getItemStyle($itemType,$index);
	public function renderItem($writer,$repeatInfo,$itemType,$index);
}

class TRepeatInfo extends TComponent
{
	private $_caption='';
	private $_captionAlign='NotSet';
	private $_repeatColumns=0;
	private $_repeatDirection='Vertical';
	private $_repeatLayout='Table';

	public function getCaption()
	{
		return $this->_caption;
	}

	public function setCaption($value)
	{
		$this->_caption=$value;
	}

	public function getCaptionAlign()
	{
		return $this->_captionAlign;
	}

	public function setCaptionAlign($value)
	{
		$this->_captionAlign=TPropertyValue::ensureEnum($value,array('NotSet','Top','Bottom','Left','Right'));
	}

	public function getRepeatColumns()
	{
		return $this->_repeatColumns;
	}

	public function setRepeatColumns($value)
	{
		$this->_repeatColumns=TPropertyValue::ensureInteger($value);
	}

	public function getRepeatDirection()
	{
		return $this->_repeatDirection;
	}

	public function setRepeatDirection($value)
	{
		$this->_repeatDirection=TPropertyValue::ensureEnum($value,array('Horizontal','Vertical'));
	}

	public function getRepeatLayout()
	{
		return $this->_repeatLayout;
	}

	public function setRepeatLayout($value)
	{
		$this->_repeatLayout=TPropertyValue::ensureEnum($value,array('Table','Flow'));
	}

	public function renderRepeater($writer, IRepeatInfoUser $user)
	{
		if($this->_repeatLayout==='Table')
		{
			$control=new TTable;
			if($this->_caption!=='')
			{
				$control->setCaption($this->_caption);
				$control->setCaptionAlign($this->_captionAlign);
			}
		}
		else
			$control=new TWebControl;
		$control->setID($user->getClientID());
		$control->copyBaseAttributes($user);
		if($user->getHasStyle())
			$control->getStyle()->copyFrom($user->getStyle());
		$control->renderBeginTag($writer);
		$writer->writeLine();

		if($this->_repeatDirection==='Vertical')
			$this->renderVerticalContents($writer,$user);
		else
			$this->renderHorizontalContents($writer,$user);

		$control->renderEndTag($writer);
	}

	protected function renderHorizontalContents($writer,$user)
	{
		$tableLayout=($this->_repeatLayout==='Table');
		$hasSeparators=$user->getHasSeparators();
		$itemCount=$user->getRepeatedItemCount();
		$columns=$this->_repeatColumns===0?$itemCount:$this->_repeatColumns;
		$totalColumns=$hasSeparators?$columns+$columns:$columns;
		$needBreak=$columns<$itemCount;

		if($user->getHasHeader())
			$this->renderHeader($writer,$user,$tableLayout,$totalColumns,$needBreak);

		// render items
		if($tableLayout)
		{
			$column=0;
			for($i=0;$i<$itemCount;++$i)
			{
				if($column==0)
					$writer->renderBeginTag('tr');
				if(($style=$user->getItemStyle('Item',$i))!==null)
					$style->addAttributesToRender($writer);
				$writer->renderBeginTag('td');
				$user->renderItem($writer,$this,'Item',$i);
				$writer->renderEndTag();
				$writer->writeLine();
				if($hasSeparators && $i!=$itemCount-1)
				{
					if(($style=$user->getItemStyle('Separator',$i))!==null)
						$style->addAttributesToRender($writer);
					$writer->renderBeginTag('td');
					$user->renderItem($writer,$this,'Separator',$i);
					$writer->renderEndTag();
					$writer->writeLine();
				}
				$column++;
				if($i==$itemCount-1)
				{
					$restColumns=$columns-$column;
					if($hasSeparators)
						$restColumns=$restColumns?$restColumns+$restColumns+1:1;
					for($j=0;$j<$restColumns;++$j)
						$writer->write("<td></td>\n");
				}
				if($column==$columns || $i==$itemCount-1)
				{
					$writer->renderEndTag();
					$writer->writeLine();
					$column=0;
				}
			}
		}
		else
		{
			$column=0;
			for($i=0;$i<$itemCount;++$i)
			{
				$user->renderItem($writer,$this,'Item',$i);
				if($hasSeparators && $i!=$itemCount-1)
					$user->renderItem($writer,$this,'Separator',$i);
				$column++;
				if($column==$columns || $i==$itemCount-1)
				{
					if($needBreak)
						$writer->writeBreak();
					$column=0;
				}
				$writer->writeLine();
			}
		}

		if($user->getHasFooter())
			$this->renderFooter($writer,$user,$tableLayout,$totalColumns,$needBreak);
	}

	protected function renderVerticalContents($writer,$user)
	{
		$tableLayout=($this->_repeatLayout==='Table');
		$hasSeparators=$user->getHasSeparators();
		$itemCount=$user->getRepeatedItemCount();
		if($this->_repeatColumns<=1)
		{
			$rows=$itemCount;
			$columns=1;
			$lastColumns=1;
		}
		else
		{
			$columns=$this->_repeatColumns;
			$rows=(int)(($itemCount+$columns-1)/$columns);
			if($rows==0 && $itemCount>0)
				$rows=1;
			if(($lastColumns=$itemCount%$columns)==0)
				$lastColumns=$columns;
		}
		$totalColumns=$hasSeparators?$columns+$columns:$columns;

		if($user->getHasHeader())
			$this->renderHeader($writer,$user,$tableLayout,$totalColumns,false);

		if($tableLayout)
		{
			$renderedItems=0;
			for($row=0;$row<$rows;++$row)
			{
				$index=$row;
				$writer->renderBeginTag('tr');
				for($col=0;$col<$columns;++$col)
				{
					if($renderedItems>=$itemCount)
						break;
					if($col>0)
					{
						$index+=$rows;
						if($col-1>=$lastColumns)
							$index--;
					}
					if($index>=$itemCount)
						continue;
					$renderedItems++;
					if(($style=$user->getItemStyle('Item',$index))!==null)
						$style->addAttributesToRender($writer);
					$writer->renderBeginTag('td');
					$user->renderItem($writer,$this,'Item',$index);
					$writer->renderEndTag();
					$writer->writeLine();
					if(!$hasSeparators)
						continue;
					if($renderedItems<$itemCount-1)
					{
						if($columns==1)
						{
							$writer->renderEndTag();
							$writer->renderBeginTag('tr');
						}
						if(($style=$user->getItemStyle('Separator',$index))!==null)
							$style->addAttributesToRender($writer);
						$writer->renderBeginTag('td');
						$user->renderItem($writer,$this,'Separator',$index);
						$writer->renderEndTag();
						$writer->writeLine();
					}
					else if($columns>1)
						$writer->write("<td></td>\n");
				}
				if($row==$rows-1)
				{
					$restColumns=$columns-$lastColumns;
					if($hasSeparators)
						$restColumns+=$restColumns;
					for($col=0;$col<$restColumns;++$col)
						$writer->write("<td></td>\n");
				}
				$writer->renderEndTag();
				$writer->writeLine();
			}
		}
		else
		{
			$renderedItems=0;
			for($row=0;$row<$rows;++$row)
			{
				$index=$row;
				for($col=0;$col<$columns;++$col)
				{
					if($renderedItems>=$itemCount)
						break;
					if($col>0)
					{
						$index+=$rows;
						if($col-1>=$lastColumns)
							$index--;
					}
					if($index>=$itemCount)
						continue;
					$renderedItems++;
					$user->renderItem($writer,$this,'Item',$index);
					$writer->writeLine();
					if(!$hasSeparators)
						continue;
					if($renderedItems<$itemCount-1)
					{
						if($columns==1)
							$writer->writeBreak();
						$user->renderItem($writer,$this,'Separator',$index);
					}
					$writer->writeLine();
				}
				if($row<$rows-1 || $user->getHasFooter())
					$writer->writeBreak();
			}
		}

		if($user->getHasFooter())
			$this->renderFooter($writer,$user,$tableLayout,$totalColumns,false);

	}

	protected function renderHeader($writer,$user,$tableLayout,$columns,$needBreak)
	{
		if($tableLayout)
		{
			$writer->renderBeginTag('tr');
			if($columns>1)
				$writer->addAttribute('colspan',"$columns");
			$writer->addAttribute('scope','col');
			if(($style=$user->getItemStyle('Header',-1))!==null)
				$style->addAttributesToRender($writer);
			$writer->renderBeginTag('th');
			$user->renderItem($writer,$this,'Header',-1);
			$writer->renderEndTag();
			$writer->renderEndTag();
		}
		else
		{
			$user->renderItem($writer,$this,'Header',-1);
			if($needBreak)
				$writer->writeBreak();
		}
		$writer->writeLine();
	}

	protected function renderFooter($writer,$user,$tableLayout,$columns)
	{
		if($tableLayout)
		{
			$writer->renderBeginTag('tr');
			if($columns>1)
				$writer->addAttribute('colspan',"$columns");
			if(($style=$user->getItemStyle('Footer',-1))!==null)
				$style->addAttributesToRender($writer);
			$writer->renderBeginTag('td');
			$user->renderItem($writer,$this,'Footer',-1);
			$writer->renderEndTag();
			$writer->renderEndTag();
		}
		else
			$user->renderItem($writer,$this,'Footer',-1);
		$writer->writeLine();
	}
}

?>