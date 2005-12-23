<?php

class DetailPage extends TPage
{
	private $_file;

	public function onLoad($param)
	{
		parent::onLoad($param);
		$isSrc=true;
		if(($id=$this->Request->Items['src'])===null)
			$this->_file=$this->determineFile($this->Request->Items['tpl'],false);
		else
			$this->_file=$this->determineFile($id,true);
	}

	protected function determineFile($id,$isSrcFile)
	{
		$basePath=dirname(__FILE__).'/controls';

		$xml=new TXmlDocument;
		$xml->loadFromFile($basePath.'/config.xml');
		$pages=$xml->getElementByTagName('pages')->getElementsByTagName('page');
		$fileName='';
		foreach($pages as $page)
		{
			if($page->Attributes['id']===$id)
			{
				if($isSrcFile)
					$fileName=$basePath.'/'.$page->Attributes['class'].'.php';
				else if($page->Attributes['TemplateFile']!==null)
				{
					$fileName=$page->Attributes['TemplateFile'];
					if(($pos=strrpos($fileName,'.'))!==false)
						$fileName=substr($fileName,$pos+1);
					$fileName=$basePath.'/'.$fileName.'.tpl';
				}
				else
					$fileName=$basePath.'/'.$page->Attributes['class'].'.tpl';
				break;
			}
		}
		if(empty($fileName) || !is_file($fileName))
			throw new THttpException(500,"File not exists!");
		return $fileName;
	}

	protected function render($writer)
	{
		$contents=file_get_contents($this->_file);
		$writer->write(highlight_string($contents,true));
	}
}

?>