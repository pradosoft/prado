<?php
/**
 * BlogDataModule class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 */

/**
 * BlogDataModule class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 */
class BlogDataModule extends TModule
{
	const DB_FILE_EXT='.db';
	const DEFAULT_DB_FILE='Application.Data.Blog';
	private $_db=null;
	private $_dbFile=null;

	public function init($config)
	{
		$this->connectDatabase();
	}

	public function getDbFile()
	{
		if($this->_dbFile===null)
			$this->_dbFile=Prado::getPathOfNamespace(self::DEFAULT_DB_FILE,self::DB_FILE_EXT);
		return $this->_dbFile;
	}

	public function setDbFile($value)
	{
		if(($this->_dbFile=Prado::getPathOfNamespace($value,self::DB_FILE_EXT))===null)
			throw new BlogException(500,'blogdatamodule_dbfile_invalid',$value);
	}

	protected function createDatabase()
	{
		$schemaFile=dirname(__FILE__).'/schema.sql';
		$statements=explode(';',file_get_contents($schemaFile));
		foreach($statements as $statement)
		{
			if(trim($statement)!=='')
			{
				try {
					$command=$this->_db->createCommand($statement);
					$command->execute();
				}
				catch(TDbException $e)
				{
					throw new BlogException(500,'blogdatamodule_createdatabase_failed',$e->getErrorMessage(),$statement);
				}
			}
		}
	}

	protected function connectDatabase()
	{
		$dbFile=$this->getDbFile();
		$newDb=!is_file($dbFile);

		try {
			$this->_db=new TDbConnection("sqlite:".$dbFile);
			$this->_db->Active=true;
		}
		catch(TDbException $e)
		{
			throw new BlogException(500,'blogdatamodule_dbconnect_failed',$e->getErrorMessage());
		}

		if($newDb)
			$this->createDatabase();
	}

	protected function generateModifier($filter,$orderBy,$limit)
	{
		$modifier='';
		if($filter!=='')
			$modifier=' WHERE '.$filter;
		if($orderBy!=='')
			$modifier.=' ORDER BY '.$orderBy;
		if($limit!=='')
			$modifier.=' LIMIT '.$limit;
		return $modifier;
	}

	public function query($sql)
	{
		try {
			$command=$this->_db->createCommand($sql);
			return $command->query();
		}
		catch(TDbException $e)
		{
			throw new BlogException(500,'blogdatamodule_query_failed',$e->getErrorMessage(),$sql);
		}
	}

	protected function populateUserRecord($row)
	{
		$userRecord=new UserRecord;
		$userRecord->ID=(integer)$row['id'];
		$userRecord->Name=$row['name'];
		$userRecord->FullName=$row['full_name'];
		$userRecord->Role=(integer)$row['role'];
		$userRecord->Password=$row['passwd'];
		$userRecord->VerifyCode=$row['vcode'];
		$userRecord->Email=$row['email'];
		$userRecord->CreateTime=(integer)$row['reg_time'];
		$userRecord->Status=(integer)$row['status'];
		$userRecord->Website=$row['website'];
		return $userRecord;
	}

	public function queryUsers($filter='',$orderBy='',$limit='')
	{
		if($filter!=='')
			$filter='WHERE '.$filter;
		$sql="SELECT * FROM tblUsers $filter $orderBy $limit";
		$rows=$this->query($sql);
		$users=array();
		foreach($rows as $row)
			$users[]=$this->populateUserRecord($row);
		return $users;
	}

	public function queryUserCount($filter)
	{
		if($filter!=='')
			$filter='WHERE '.$filter;
		$sql="SELECT COUNT(id) AS user_count FROM tblUsers $filter";
		$result=$this->query($sql);
		if(($row=$result->read())!==false)
			return $row['user_count'];
		else
			return 0;
	}

	public function queryUserByID($id)
	{
		$sql="SELECT * FROM tblUsers WHERE id=$id";
		$result=$this->query($sql);
		if(($row=$result->read())!==false)
			return $this->populateUserRecord($row);
		else
			return null;
	}

	public function queryUserByName($name)
	{
		$command=$this->_db->createCommand("SELECT * FROM tblUsers WHERE name=?");
		$command->bindValue(1, $name);

		$result=$command->query();

		if(($row=$result->read())!==false)
			return $this->populateUserRecord($row);
		else
			return null;
	}

	public function insertUser($user)
	{
		$command=$this->_db->createCommand("INSERT INTO tblUsers ".
				"(name,full_name,role,passwd,email,reg_time,status,website) ".
				"VALUES (?,?,?,?,?,?,?,?)");
		$command->bindValue(1, $user->Name);
		$command->bindValue(2, $user->FullName);
		$command->bindValue(3, $user->Role);
		$command->bindValue(4, $user->Password);
		$command->bindValue(5, $user->Email);
		$command->bindValue(6, time());
		$command->bindValue(7, $user->Status);
		$command->bindValue(8, $user->Website);
		$command->execute();

		$user->ID=$this->_db->getLastInsertID();
	}

	public function updateUser($user)
	{
		$command=$this->_db->createCommand("UPDATE tblUsers SET
				name=?,
				full_name=?,
				role=?,
				passwd=?,
				vcode=?,
				email=?,
				status=?,
				website=?
				WHERE id=?");
		$command->bindValue(1, $user->Name);
		$command->bindValue(2, $user->FullName);
		$command->bindValue(3, $user->Role);
		$command->bindValue(4, $user->Password);
		$command->bindValue(5, $user->VerifyCode);
		$command->bindValue(6, $user->Email);
		$command->bindValue(7, $user->Status);
		$command->bindValue(8, $user->Website);
		$command->bindValue(9, $user->ID);
		$command->execute();
	}

	public function deleteUser($id)
	{
		$command=$this->_db->createCommand("DELETE FROM tblUsers WHERE id=?");
		$command->bindValue(1, $id);
		$command->execute();
	}

	protected function populatePostRecord($row)
	{
		$postRecord=new PostRecord;
		$postRecord->ID=(integer)$row['id'];
		$postRecord->AuthorID=(integer)$row['author_id'];
		if($row['author_full_name']!=='')
			$postRecord->AuthorName=$row['author_full_name'];
		else
			$postRecord->AuthorName=$row['author_name'];
		$postRecord->CreateTime=(integer)$row['create_time'];
		$postRecord->ModifyTime=(integer)$row['modify_time'];
		$postRecord->Title=$row['title'];
		$postRecord->Content=$row['content'];
		$postRecord->Status=(integer)$row['status'];
		$postRecord->CommentCount=(integer)$row['comment_count'];
		return $postRecord;
	}

	public function queryPosts($postFilter,$categoryFilter,$orderBy,$limit)
	{
		//FIXME this is insecure by design since it misses proper escaping
		$filter='';
		if($postFilter!=='')
			$filter.=" AND $postFilter";
		if($categoryFilter!=='')
			$filter.=" AND a.id IN (SELECT post_id AS id FROM tblPost2Category WHERE $categoryFilter)";
		$sql="SELECT a.id AS id,
					a.author_id AS author_id,
					b.name AS author_name,
					b.full_name AS author_full_name,
					a.create_time AS create_time,
					a.modify_time AS modify_time,
					a.title AS title,
					a.content AS content,
					a.status AS status,
					a.comment_count AS comment_count
				FROM tblPosts a, tblUsers b
				WHERE a.author_id=b.id $filter $orderBy $limit";
		$rows=$this->query($sql);
		$posts=array();
		foreach($rows as $row)
			$posts[]=$this->populatePostRecord($row);
		return $posts;
	}

	public function queryPostsSearch($keywords,$orderBy,$limit)
	{
		$sql="SELECT a.id AS id,
					a.author_id AS author_id,
					b.name AS author_name,
					b.full_name AS author_full_name,
					a.create_time AS create_time,
					a.modify_time AS modify_time,
					a.title AS title,
					a.content AS content,
					a.status AS status,
					a.comment_count AS comment_count
				FROM tblPosts a, tblUsers b
				WHERE a.author_id=b.id AND a.status=0";

		foreach($keywords as $keyword)
			$sql.=" AND (content LIKE ? OR title LIKE ?)";

		$sql.=" $orderBy $limit";

		$command=$this->_db->createCommand($sql);

		$i=1;
		foreach($keywords as $keyword)
		{
			$command->bindValue($i, "%".$keyword."%");
			$i++;
		}

		$rows=$command->query();

		$posts=array();
		foreach($rows as $row)
			$posts[]=$this->populatePostRecord($row);
		return $posts;

	}

	public function queryPostCount($postFilter,$categoryFilter)
	{
		//FIXME this is insecure by design since it misses proper escaping
		$filter='';
		if($postFilter!=='')
			$filter.=" AND $postFilter";
		if($categoryFilter!=='')
			$filter.=" AND a.id IN (SELECT post_id AS id FROM tblPost2Category WHERE $categoryFilter)";
		$sql="SELECT COUNT(a.id) AS post_count
				FROM tblPosts a, tblUsers b
				WHERE a.author_id=b.id $filter";
		$result=$this->query($sql);
		if(($row=$result->read())!==false)
			return $row['post_count'];
		else
			return 0;
	}

	public function queryPostByID($id)
	{
		$sql="SELECT a.id AS id,
		             a.author_id AS author_id,
		             b.name AS author_name,
		             b.full_name AS author_full_name,
		             a.create_time AS create_time,
		             a.modify_time AS modify_time,
		             a.title AS title,
		             a.content AS content,
		             a.status AS status,
		             a.comment_count AS comment_count
		      FROM tblPosts a, tblUsers b
		      WHERE a.id=? AND a.author_id=b.id";

		$command=$this->_db->createCommand($sql);
		$command->bindValue(1, $id);

		$result=$command->query();

		if(($row=$result->read())!==false)
			return $this->populatePostRecord($row);
		else
			return null;
	}

	public function insertPost($post,$catIDs)
	{
		$command=$this->_db->createCommand("INSERT INTO tblPosts
				(author_id,create_time,modify_time,title,content,status)
				VALUES (?,?,?,?,?,?)");
		$command->bindValue(1, $post->AuthorID);
		$command->bindValue(2, $post->CreateTime);
		$command->bindValue(3, $post->ModifyTime);
		$command->bindValue(4, $post->Title);
		$command->bindValue(5, $post->Content);
		$command->bindValue(6, $post->Status);

		$command->execute();
		$post->ID=$this->_db->getLastInsertID();
		foreach($catIDs as $catID)
			$this->insertPostCategory($post->ID,$catID);
	}

	public function updatePost($post,$newCatIDs=null)
	{
		if($newCatIDs!==null)
		{
			$cats=$this->queryCategoriesByPostID($post->ID);
			$catIDs=array();
			foreach($cats as $cat)
				$catIDs[]=$cat->ID;
			$deleteIDs=array_diff($catIDs,$newCatIDs);
			foreach($deleteIDs as $id)
				$this->deletePostCategory($post->ID,$id);
			$insertIDs=array_diff($newCatIDs,$catIDs);
			foreach($insertIDs as $id)
				$this->insertPostCategory($post->ID,$id);
		}

		$command=$this->_db->createCommand("UPDATE tblPosts SET
				modify_time=?,
				title=?,
				content=?,
				status=?
				WHERE id=?");
		$command->bindValue(1, $post->ModifyTime);
		$command->bindValue(2, $post->Title);
		$command->bindValue(3, $post->Content);
		$command->bindValue(4, $post->Status);
		$command->bindValue(5, $post->ID);

		$command->execute();
	}

	public function deletePost($id)
	{
		$cats=$this->queryCategoriesByPostID($id);
		foreach($cats as $cat)
			$this->deletePostCategory($id,$cat->ID);

		$command=$this->_db->createCommand("DELETE FROM tblComments WHERE post_id=?");
		$command->bindValue(1, $id);
		$command->execute();

		$command=$this->_db->createCommand("DELETE FROM tblPosts WHERE id=?");
		$command->bindValue(1, $id);
		$command->execute();
	}

	protected function populateCommentRecord($row)
	{
		$commentRecord=new CommentRecord;
		$commentRecord->ID=(integer)$row['id'];
		$commentRecord->PostID=(integer)$row['post_id'];
		$commentRecord->AuthorName=$row['author_name'];
		$commentRecord->AuthorEmail=$row['author_email'];
		$commentRecord->AuthorWebsite=$row['author_website'];
		$commentRecord->AuthorIP=$row['author_ip'];
		$commentRecord->CreateTime=(integer)$row['create_time'];
		$commentRecord->Content=$row['content'];
		$commentRecord->Status=(integer)$row['status'];
		return $commentRecord;
	}

	public function queryComments($filter,$orderBy,$limit)
	{
		//FIXME this is insecure by design since it misses proper escaping
		if($filter!=='')
			$filter='WHERE '.$filter;
		$sql="SELECT * FROM tblComments $filter $orderBy $limit";
		$rows=$this->query($sql);
		$comments=array();
		foreach($rows as $row)
			$comments[]=$this->populateCommentRecord($row);
		return $comments;
	}

	public function queryCommentsByPostID($id)
	{
		$sql="SELECT * FROM tblComments WHERE post_id=? ORDER BY create_time DESC";
		$command=$this->_db->createCommand($sql);
		$command->bindValue(1, $id);

		$rows=$command->query();

		$comments=array();
		foreach($rows as $row)
			$comments[]=$this->populateCommentRecord($row);
		return $comments;
	}

	public function insertComment($comment)
	{
		$sql="INSERT INTO tblComments
				(post_id,author_name,author_email,author_website,author_ip,create_time,status,content)
				VALUES (?,?,?,?,?,?,?,?)";
		$command=$this->_db->createCommand($sql);
		$command->bindValue(1, $comment->PostID);
		$command->bindValue(2, $comment->AuthorName);
		$command->bindValue(3, $comment->AuthorEmail);
		$command->bindValue(4, $comment->AuthorWebsite);
		$command->bindValue(5, $comment->AuthorIP);
		$command->bindValue(6, $comment->CreateTime);
		$command->bindValue(7, $comment->Status);
		$command->bindValue(8, $comment->Content);

		$command->execute();
		$comment->ID=$this->_db->getLastInsertID();
		$this->query("UPDATE tblPosts SET comment_count=comment_count+1 WHERE id={$comment->PostID}");
	}

	public function updateComment($comment)
	{
		$sql="UPDATE tblComments SET status=? WHERE id=?";
		$command=$this->_db->createCommand($sql);
		$command->bindValue(1, $comment->Status);
		$command->bindValue(2, $comment->ID);

		$command->execute();
	}

	public function deleteComment($id)
	{
		$sql="SELECT post_id FROM tblComments WHERE id=?";
		$command=$this->_db->createCommand($sql);
		$command->bindValue(1, $id);
		$result=$command->query();

		if(($row=$result->read())!==false)
		{
			$command=$this->_db->createCommand("DELETE FROM tblComments WHERE id=?");
			$command->bindValue(1, $id);
			$command->execute();

			$command=$this->_db->createCommand("UPDATE tblPosts SET comment_count=comment_count-1 WHERE id=?");
			$command->bindValue(1, $row['post_id']);
			$command->execute();
		}
	}

	protected function populateCategoryRecord($row)
	{
		$catRecord=new CategoryRecord;
		$catRecord->ID=(integer)$row['id'];
		$catRecord->Name=$row['name'];
		$catRecord->Description=$row['description'];
		$catRecord->PostCount=$row['post_count'];
		return $catRecord;
	}

	public function queryCategories()
	{
		$sql="SELECT * FROM tblCategories ORDER BY name ASC";
		$rows=$this->query($sql);
		$cats=array();
		foreach($rows as $row)
			$cats[]=$this->populateCategoryRecord($row);
		return $cats;
	}

	public function queryCategoriesByPostID($postID)
	{
		$sql="SELECT a.id AS id,
				a.name AS name,
				a.description AS description,
				a.post_count AS post_count
				FROM tblCategories a, tblPost2Category b
				WHERE a.id=b.category_id AND b.post_id=? ORDER BY a.name";

		$command=$this->_db->createCommand($sql);
		$command->bindValue(1, $postID);
		$rows=$command->query();

		$cats=array();
		foreach($rows as $row)
			$cats[]=$this->populateCategoryRecord($row);
		return $cats;
	}

	public function queryCategoryByID($id)
	{
		$sql="SELECT * FROM tblCategories WHERE id=?";

		$command=$this->_db->createCommand($sql);
		$command->bindValue(1, $id);
		$result=$command->query();

		if(($row=$result->read())!==false)
			return $this->populateCategoryRecord($row);
		else
			return null;
	}

	public function queryCategoryByName($name)
	{
		$sql="SELECT * FROM tblCategories WHERE name=?";

		$command=$this->_db->createCommand($sql);
		$command->bindValue(1, $name);
		$result=$command->query();

		if(($row=$result->read())!==false)
			return $this->populateCategoryRecord($row);
		else
			return null;
	}

	public function insertCategory($category)
	{
		$sql="INSERT INTO tblCategories
				(name,description)
				VALUES (?,?)";

		$command=$this->_db->createCommand($sql);
		$command->bindValue(1, $category->Name);
		$command->bindValue(2, $category->Description);
		$command->execute();

		$category->ID=$this->_db->getLastInsertID();
	}

	public function updateCategory($category)
	{
		$sql="UPDATE tblCategories SET name=?, description=?, post_count=? WHERE id=?";

		$command=$this->_db->createCommand($sql);
		$command->bindValue(1, $category->Name);
		$command->bindValue(2, $category->Description);
		$command->bindValue(3, $category->PostCount);
		$command->bindValue(4, $category->ID);

		$command->execute();
	}

	public function deleteCategory($id)
	{
		$sql="DELETE FROM tblPost2Category WHERE category_id=?";
		$command=$this->_db->createCommand($sql);
		$command->bindValue(1, $id);
		$command->execute();

		$sql="DELETE FROM tblCategories WHERE id=?";
		$command=$this->_db->createCommand($sql);
		$command->bindValue(1, $id);
		$command->execute();
	}

	public function insertPostCategory($postID,$categoryID)
	{
		$sql="INSERT INTO tblPost2Category (post_id, category_id) VALUES (?, ?)";
		$command=$this->_db->createCommand($sql);
		$command->bindValue(1, $postID);
		$command->bindValue(2, $categoryID);
		$command->execute();

		$sql="UPDATE tblCategories SET post_count=post_count+1 WHERE id=?";
		$command=$this->_db->createCommand($sql);
		$command->bindValue(1, $categoryID);
		$command->execute();

	}

	public function deletePostCategory($postID,$categoryID)
	{
		$sql="DELETE FROM tblPost2Category WHERE post_id=? AND category_id=?";
		$command=$this->_db->createCommand($sql);
		$command->bindValue(1, $postID);
		$command->bindValue(2, $categoryID);
		$result=$command->query();

		if($result->getRowCount()>0)
		{
			$sql="UPDATE tblCategories SET post_count=post_count-1 WHERE id=?";
			$command=$this->_db->createCommand($sql);
			$command->bindValue(1, $categoryID);
			$command->execute();
		}
	}

	public function queryEarliestPostTime()
	{
		$sql="SELECT MIN(create_time) AS create_time FROM tblPosts";
		$result=$this->query($sql);
		if(($row=$result->read())!==false)
			return $row['create_time'];
		else
			return time();
	}
}

class UserRecord
{
	const ROLE_USER=0;
	const ROLE_ADMIN=1;
	const STATUS_NORMAL=0;
	const STATUS_DISABLED=1;
	const STATUS_PENDING=2;
	public $ID;
	public $Name;
	public $FullName;
	public $Role;
	public $Password;
	public $VerifyCode;
	public $Email;
	public $CreateTime;
	public $Status;
	public $Website;
}

class PostRecord
{
	const STATUS_PUBLISHED=0;
	const STATUS_DRAFT=1;
	const STATUS_PENDING=2;
	const STATUS_STICKY=3;
	public $ID;
	public $AuthorID;
	public $AuthorName;
	public $CreateTime;
	public $ModifyTime;
	public $Title;
	public $Content;
	public $Status;
	public $CommentCount;
}

class CommentRecord
{
	public $ID;
	public $PostID;
	public $AuthorName;
	public $AuthorEmail;
	public $AuthorWebsite;
	public $AuthorIP;
	public $CreateTime;
	public $Status;
	public $Content;
}

class CategoryRecord
{
	public $ID;
	public $Name;
	public $Description;
	public $PostCount;
}

