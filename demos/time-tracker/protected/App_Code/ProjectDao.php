<?php
/**
 * Project DAO class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $16/07/2006: $
 * @package Demos
 */

/**
 * Project DAO class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $16/07/2006: $
 * @package Demos
 * @since 3.1
 */
class ProjectDao extends BaseDao
{
	public function projectNameExists($projectName)
	{
		$sqlmap = $this->getConnection();
		return $sqlmap->queryForObject('ProjectNameExists', $projectName);
	}
	
	public function addNewProject($project)
	{
		$sqlmap = $this->getConnection();
		$sqlmap->insert('CreateNewProject', $project);
	}
	
	public function getProjectByID($projectID)
	{
		$sqlmap = $this->getConnection();
		return $sqlmap->queryForObject('GetProjectByID', $projectID);
	}
	
	public function deleteProject($projectID)
	{
		$sqlmap = $this->getConnection();
		$sqlmap->update('DeleteProject',$projectID);
	}
	
	public function addUserToProject($projectID, $username)
	{
		$sqlmap = $this->getConnection();
		$members = $this->getProjectMembers($projectID);
		if(!in_array($username, $members))
		{
			$param['username'] = $username;
			$param['project'] = $projectID;
			$sqlmap->insert('AddUserToProject',$param);
		} 
	}
	
	public function getProjectMembers($projectID)
	{
		$sqlmap = $this->getConnection();
		return $sqlmap->queryForList('GetProjectMembers', $projectID);
	}
	
	public function getAllProjects()
	{
		$sqlmap = $this->getConnection();
		return $sqlmap->queryForList('GetAllProjects');	
	}
	
	public function getProjectsByManagerName($manager)
	{
		$sqlmap = $this->getConnection();
		return $sqlmap->queryForList('GetProjectsByManagerName', $manager);
	}
	
	public function getProjectsByUserName($username)
	{
		$sqlmap = $this->getConnection();
		return $sqlmap->queryForList('GetProjectsByUserName', $username);
	}
	
	public function removeUserFromProject($projectID, $username)
	{
		$sqlmap = $this->getConnection();
		$param['username'] = $username;
		$param['project'] = $projectID;
		$sqlmap->delete('RemoveUserFromProject', $param);
	}
	
	public function updateProject($project)
	{
		$sqlmap = $this->getConnection();
		$sqlmap->update('UpdateProject', $project);
	}
}

?>