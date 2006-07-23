<?php

class CategoryDao extends BaseDao
{
	function addNewCategory($category)
	{
		$sqlmap = $this->getConnection();
		$exists = $this->getCategoryByNameInProject(
			$category->Name, $category->ProjectID);
		if(!$exists)
			$sqlmap->insert('AddNewCategory', $category);
	}	
	
	function getCategoryByID($categoryID)
	{
		$sqlmap = $this->getConnection();
		return $sqlmap->queryForObject('GetCategoryByID', $categoryID);
	}
	
	function getAllCategories()
	{
		$sqlmap = $this->getConnection();
		return $sqlmap->queryForList('GetAllCategories');
	}
	
	function deleteCategory($categoryID)
	{
		$sqlmap = $this->getConnection();
		$sqlmap->delete('DeleteCategory', $categoryID);
	}
	
	function getCategoriesByProjectID($projectID)
	{
		$sqlmap = $this->getConnection();
		return $sqlmap->queryForList('GetCategoriesByProjectID', $projectID);
	}
	
	function getCategoryByNameInProject($name, $projectID)
	{
		$sqlmap = $this->getConnection();
		$param['project'] = $projectID;
		$param['category'] = $name;
		return $sqlmap->queryForObject('GetCategoryByNameInProject', $param);
	}
	
	function updateCategory($category)
	{
		$sqlmap = $this->getConnection();
		$sqlmap->update('UpdateCategory', $category);
	}
}

?>