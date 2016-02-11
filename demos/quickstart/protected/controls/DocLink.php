<?php

class DocLink extends THyperLink
{
	const BASE_URL='http://pradosoft.github.io/docs/manual';

	public function getClassPath()
	{
		return $this->getViewState('ClassPath','');
	}

	public function setClassPath($value)
	{
		$this->setViewState('ClassPath',$value,'');
	}

	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$paths=explode('.',$this->getClassPath());
		if(count($paths)>1)
		{
			$classFile='class-'.array_pop($paths).'.html';
			$this->setNavigateUrl(self::BASE_URL . '/' . $classFile);
			if($this->getText() === '')
				$this->setText('API Manual');
		}
	}
}

