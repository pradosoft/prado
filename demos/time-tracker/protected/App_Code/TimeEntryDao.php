<?php

class TimeEntryDao extends BaseDao
{
	public function addNewTimeEntry($entry)
	{
		$sqlmap = $this->getConnection();
		$sqlmap->insert('AddNewTimeEntry', $entry);	
	}
	
	public function getTimeEntryByID($entryID)
	{
		$sqlmap = $this->getConnection();
		return $sqlmap->queryForObject('GetTimeEntryByID', $entryID);	
	}
	
	public function deleteTimeEntry($entryID)
	{
		$sqlmap = $this->getConnection();
		$sqlmap->delete('DeleteTimeEntry', $entryID);
	}
	
	public function getTimeEntriesInProject($username, $projectID)
	{
		$sqlmap = $this->getConnection();
		$param['username'] = $username;
		$param['project'] = $projectID;
		return $sqlmap->queryForList('GetAllTimeEntriesByProjectIdAndUser', $param);
	}
	
	public function updateTimeEntry($entry)
	{
		$sqlmap = $this->getConnection();
		$sqlmap->update('UpdateTimeEntry', $entry);
	}
	
	public function getTimeEntriesByDate($username, $start, $end)
	{
		$sqlmap = $this->getConnection();
		$param['username'] = $username;
		$param['startDate'] = $start;
		$param['endDate'] = $end;
		return $sqlmap->queryForList('GetTimeEntriesByDate', $param);	
	}
}

?>