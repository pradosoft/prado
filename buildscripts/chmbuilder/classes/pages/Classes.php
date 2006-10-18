<?php

class Classes extends TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);
			$dataFile=Prado::getPathOfNamespace('Application.Data.classes','.data');
			$classes=unserialize(file_get_contents($dataFile));
			$data=array();
			$baseUrl=$this->Request->ApplicationUrl;
			foreach(array_keys($classes) as $className)
				$data["$className.html"]=$className;
			$this->ClassList->DataSource=$data;
			$this->ClassList->dataBind();
	}
}

?>
