<?php

require_once 'phing/Task.php';

/**
 * Task to index PRADO API docs.
 */	
class ManualIndexTask extends Task
{
	private $docdir;
	private $todir;
	
	/**
	 * @param string the API documentation directory
	 */
	public function setDocdir($value)
	{
		$this->docdir=$value;
	}
	
	public function setTodir($value)
	{
		$this->todir=$value;
	}

	public function main()
	{
		$srcdir=realpath(dirname(__FILE__).'/../../../');
		$zend_path = $srcdir.'/demos/quickstart/protected/index';
		set_include_path(get_include_path().PATH_SEPARATOR.realpath($zend_path));
		require_once ('Zend/Search/Lucene.php');
		require_once($srcdir.'/buildscripts/index/api_index.php');
		$api = new api_index($this->todir, realpath($this->docdir));
		$api->create_index();
	}
}

?>