<?php

class ViewSource extends TPage
{
	private $_path=null;
	private $_fullPath=null;
	private $_fileType=null;

	protected function isFileTypeAllowed($extension)
	{
		return in_array($extension,array('tpl','page','php'));
	}

	protected function getFileExtension($fileName)
	{
		if(($pos=strrpos($fileName,'.'))===false)
			return '';
		else
			return substr($fileName,$pos+1);
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		$path=$this->Request->Items['path'];
		$fullPath=realpath($this->Service->BasePath.'/'.$path);
		if($fullPath!==false && is_file($fullPath) && strpos($fullPath,$this->Service->BasePath)!==false)
		{
 			if($this->isFileTypeAllowed($this->getFileExtension($fullPath)))
 			{
				$this->_fullPath=strtr($fullPath,'\\','/');
				$this->_path=strtr(substr($fullPath,strlen($this->Service->BasePath)),'\\','/');
 			}
		}
		if($this->_fullPath===null)
			throw new THttpException(500,'File Not Found: %s',$path);
		$basePath=dirname($this->_fullPath);
		if($dh=opendir($basePath))
		{
			$str="<h4>{$this->_path}</h4>\n";
			while(($file=readdir($dh))!==false)
			{
				if(is_file($basePath.'/'.$file))
				{
					$fileType=$this->getFileExtension($basePath.'/'.$file);
					if($this->isFileTypeAllowed($fileType))
					{
						if($fileType==='tpl' || $fileType==='page')
							$type='Template file';
						else
							$type='Class file';
						$path='/'.ltrim(strtr(dirname($this->_path),'\\','/').'/'.$file,'/');
						$str.="$type: <a href=\"?page=ViewSource&amp;path=$path\">$file</a><br/>";
					}
				}

			}
			closedir($dh);
			$this->SourceList->Text=$str;
		}

		$this->SourceView->Text=highlight_string(file_get_contents($this->_fullPath),true);
	}
}

?>