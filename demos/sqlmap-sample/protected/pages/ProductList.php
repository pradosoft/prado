<?php

class ProductList extends TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		if(!$this->IsPostBack)
		{
			$sqlmap = $this->Application->Modules['petshop-sqlmap'];
			$products = $sqlmap->queryForList('SelectAllProducts');
			$this->productList->setDataSource($products);
			$this->productList->dataBind();
		}
	} 	
}

?>