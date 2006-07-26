<?php

class UserReportsDao extends BaseDao
{
	public function getUserTimeReport($username)
	{
		$sqlmap = $this->getConnection();
		return $sqlmap->queryForObject('GetTimeReportByUsername', $username);
	}
	
	public function getTimeReportsByCategoryID($categoryID)
	{
		$sqlmap = $this->getConnection();
		return $sqlmap->queryForList('GetTimeReportByCategoryID', $categoryID);		
	}
	
	public function getTimeReportsByProjectIDs($projects)
	{
		$ids = implode(',', array_map('intval', $projects));
		$sqlmap = $this->getConnection();
		return $sqlmap->queryForList('GetTimeReportByProjectIDs', $ids);				
	}
		
}

?>