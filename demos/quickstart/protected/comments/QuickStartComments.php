<?php

/**
 * QuickStartComments class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version : $  Sat May 27 16:49:19 AZOST 2006 $
 * @package Demos.QuickStart.comments
 * @since 3.0
 */
class QuickStartComments
{
	/**
	 * @var string sqlite database source.
	 */
	private $_database;
	/**
	 * @var sqlite connection.
	 */
	private $_connection;
	
	/**
	 * Sets the sqlite comment database file.
	 */
	public function __construct()
	{
		$this->_database = realpath(dirname(__FILE__).'/comments.db');
	}
	
	/**
	 * Closed the database connection.
	 */
	public function __destruct()
	{
		if(!is_null($this->_connection))
			sqlite_close($this->_connection);
	}
	
	/**
	 * @return resource sqlite database connection.
	 */
	protected function getConnection()
	{
		if(is_null($this->_connection))
			$this->_connection = sqlite_open($this->_database);
		return $this->_connection;
	}
	
	/**
	 * Quote database input data.
	 */
	protected function quote($value)
	{
		return sqlite_escape_string($value);
	}
	
	/**
	 * Executes an sqlite query.
	 * @param string SQL
	 * @return mixed query results.
	 */
	protected function query($sql)
	{
		return sqlite_query($this->getConnection(), $sql);
	}
	
	/**
	 * Returns a row from the sqlite result.
	 * @param resource sqlite result
	 * @return array database record.
	 */
	protected function fetch($resource)
	{
		if($resource !== false)
			return sqlite_fetch_array($resource);
		else
			return false;
	}
	
	/**
	 * Fetch all the records for given SQL query.
	 * @param string SQL query.
	 * @return array result set.
	 */
	protected function fetchAll($sql)
	{
		$rs = $this->query($sql);
		$rows = array();
		while($row = $this->fetch($rs))
			$rows[] = $row;
		return $rows;
	}
	
	/**
	 * Returns all the comments for a given page.
	 * @param string specific page comments
	 * @return array list of comments
	 */
	public function getComments($pageID)
	{
		$page = $this->quote($pageID);
		$sql = "SELECT * FROM comments WHERE page=\"$page\" AND approved = 1 ORDER BY date_added ASC";
		return $this->fetchAll($sql);
	}
	
	/**
	 * Adds a new comment for moderation.
	 * @param string ID of the page to comment belongs
	 * @param string email address of the commenter
	 * @param string comment contents
	 */
	public function addNewComment($pageID, $email, $comment)
	{
		$page = $this->quote($pageID);
		$email = $this->quote($email);
		$comment = $this->quote($comment);
		$date_added = time();
		$sql = <<<EOD
		INSERT INTO comments(page, email, comment, date_added)
				VALUES ("$page", "$email", "$comment", "$date_added")
EOD;
		return $this->query($sql);
	}
	
	/**
	 * Update an existing comment.
	 * @param string comment ID
	 * @param string page ID
	 * @param string email address
	 * @param string updated comment.
	 */
	public function updateComment($commentID, $page, $email, $content)
	{
		$ID = intval($commentID);
		$email = $this->quote($email);
		$comment = $this->quote($content);
		$page = $this->quote($page);
		$sql = <<<EOD
		UPDATE comments SET 
			email = "$email", comment = "$comment", page = "$page"
			WHERE id = $ID;
EOD;
		$this->query($sql);
	}
	
	/**
	 * Delete a comment.
	 * @param string comment ID
	 */
	public function deleteComment($commentID)
	{
		$ID = intval($commentID);
		$this->query("DELETE FROM comments WHERE id=$ID");
	}
		
	/**
	 * @return array all the quequed comments.
	 */
	public function getQuequedComments()
	{
		return $this->fetchAll("SELECT * FROM comments WHERE approved != 1");
	}
	
	/**
	 * Approve a quequed comment.
	 * @param string comment ID.
	 */
	public function approveComment($commentID)
	{
		$ID = intval($commentID);
		$this->query("UPDATE comments SET approved = 1 WHERE id=$ID");
	}
}

?>