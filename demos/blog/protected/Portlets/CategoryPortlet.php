<?php

Prado::using('Application.Portlets.Portlet');

class CategoryPortlet extends Portlet
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		$this->CategoryList->DataSource=$this->Application->getModule('data')->queryCategories();
		$this->CategoryList->dataBind();
	}
}

?>