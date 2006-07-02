<?php

class Home extends TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		$list=$this->getPageList(dirname(__FILE__),'');
		$this->List->DataSource=$list;
		$this->List->dataBind();
	}

	protected function getPageList($directory,$basePath)
	{
		$list=array();
		$folder=@opendir($directory);
		while($entry=@readdir($folder))
		{
			if($entry[0]==='.')
				continue;
			else if(is_file($directory.'/'.$entry))
			{
				if(($page=basename($entry,'.page'))!==$entry && strpos($page,'.')===false)
					$list['?page='.$basePath.$page]=$basePath.$page;
			}
			else
				$list=array_merge($list,$this->getPageList($directory.'/'.$entry,$basePath.$entry.'.'));
		}
		closedir($folder);
		return $list;
	}
}

?>