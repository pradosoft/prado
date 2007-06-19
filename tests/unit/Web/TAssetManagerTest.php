<?php
require_once dirname(__FILE__).'/../phpunit.php';

Prado::using('System.Web.TAssetManager');

/**
 * @package System.Web
 */
class TAssetManagerTest extends PHPUnit_Framework_TestCase {

	public static $app = null;
	public static $assetDir = null;

	public function setUp () {
		// Fake environment variables needed to determine path
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_SERVER['SERVER_PORT'] = '80';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/demos/personal/index.php?page=Links';
		$_SERVER['SCRIPT_NAME'] = '/demos/personal/index.php';
		$_SERVER['PHP_SELF'] = '/demos/personal/index.php';
		$_SERVER['QUERY_STRING'] = 'page=Links';
		$_SERVER['SCRIPT_FILENAME'] = __FILE__;
		$_SERVER['PATH_INFO'] = __FILE__;
		$_SERVER['HTTP_REFERER'] = 'http://www.pradosoft.com';
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3';
		$_SERVER['REMOTE_HOST'] = 'localhost';
		
		if (self::$app===null) {
			self::$app=new TApplication(dirname(__FILE__).'/app');
		}
		
		if (self::$assetDir===null) self::$assetDir= dirname(__FILE__).'/assets';
		// Make asset directory if not exists 
	    if (!file_exists (self::$assetDir)) {
    		if (is_writable(dirname(self::$assetDir))) 
    		  mkdir (self::$assetDir) ;
    		else 
    		  throw new Exception ('Directory '.dirname(self::$assetDir).' is not writable');
	    } elseif (!is_dir (self::$assetDir)) {
	       throw new Exception (self::$assetDir.' exists and is not a directory');
	    }
		// Define an alias to asset directory
		prado::setPathofAlias('AssetAlias', self::$assetDir);
		
	}
	
	private function removeDirectory ($dir) {
	  // Let's be sure $dir is a directory to avoir any error. Clear the cache !
	  clearstatcache();
	  if (is_dir($dir)) {
	    foreach (scandir($dir) as $content) {
	      if ($content==='.' or $content==='..') continue; // skip . and ..
	      $content=$dir.'/'.$content;
	      if (is_dir($content)) 
	        $this->removeDirectory ($content); // Recursivly remove directories
	      else
	        unlink ($content); // Remove file
	    }
	    // Now, directory should be empty, remove it
	    rmdir ($dir);
	  }
	}
	
	public function tearDown () {
		// Make some cleaning :)
	    $this->removeDirectory(self::$assetDir); 
	}
	
	public function testInit() {
		
		$manager=new TAssetManager ();

		$manager->init (null);
		
		self::assertEquals(self::$assetDir, $manager->getBasePath());
		self::assertEquals($manager, self::$app->getAssetManager());
		
		// No, remove asset directory, and catch the exception
		if (is_dir(self::$assetDir)) $this->removeDirectory (self::$assetDir);
		try {
			$manager->init (null);
			self::fail ('Expected TConfigurationException not thrown');
		} catch (TConfigurationException $e) {}
	}

	public function testSetBasePath() {
		$manager = new TAssetManager ();
		// First try, invalid directory
		try {
			$manager->setBasePath('invalid');
			self::fail('Expected TInvalidDataValueException not thrown');
		} catch (TInvalidDataValueException $e) {}
		
		// Next, standard asset directory, should work
		
		$manager->setBasePath ('AssetAlias');
		self::assertEquals(self::$assetDir, $manager->getBasePath());
		
		// Finally, test to change after init
		$manager->init (null);
		try {
			$manager->setBasePath ('test');
			self::fail ('Expected TInvalidOperationException not thrown');
		} catch (TInvalidOperationException $e) {}

	}

	public function testSetBaseUrl() {
		$manager=new TAssetManager ();
		$manager->setBaseUrl ('/assets/');
		self::assertEquals("/assets", $manager->getBaseUrl());
	
		$manager->init (null);
		try {
			$manager->setBaseUrl ('/test');
			self::fail ('Expected TInvalidOperationException not thrown');
		} catch (TInvalidOperationException $e) {}
		
	}

	public function testPublishFilePath() {
		$manager=new TAssetManager();
		$manager->setBaseUrl('/');
		$manager->init (null);
		
		// Try to publish a single file
	    $fileToPublish=dirname(__FILE__).'/data/pradoheader.gif';
		$publishedUrl = $manager->publishFilePath($fileToPublish);
		$publishedFile=self::$assetDir.$publishedUrl;
		self::assertEquals($publishedFile, $manager->getPublishedPath($fileToPublish));
		self::assertEquals($publishedUrl, $manager->getPublishedUrl($fileToPublish));
		self::assertTrue(is_file($publishedFile));
		
	    //  try to publish invalid file
	    try {
	      $manager->publishFilePath('invalid_file');
	      self::fail('Expected TInvalidDataValueException not thrown');
	    } catch (TInvalidDataValueException $e) {}
	}
	
	public function testPublishFilePathWithDirectory () {
	    $manager=new TAssetManager();
		$manager->setBaseUrl('/');
		$manager->init (null);
		
		// Try to publish a directory
	    $dirToPublish=dirname(__FILE__).'/data';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir=self::$assetDir.$publishedUrl;
		self::assertEquals($publishedDir, $manager->getPublishedPath($dirToPublish));
		self::assertEquals($publishedUrl, $manager->getPublishedUrl($dirToPublish));
		self::assertTrue(is_dir($publishedDir));
		self::assertTrue(is_file($publishedDir.'/pradoheader.gif'));
		
	}

	public function testPublishTarFile() {
		$manager=new TAssetManager();
		$manager->setBaseUrl('/');
		$manager->init (null);
		
		$tarFile=dirname(__FILE__).'/data/aTarFile.tar';
		$md5File=dirname(__FILE__).'/data/aTarFile.md5';
		
		// First, try with bad md5
	    try {
	      $manager->publishTarFile($tarFile, 'badMd5File');
	      self::fail('Expected TInvalidDataValueException not thrown');
	    } catch (TInvalidDataValueException $e) {}
	    
	    // Then, try with real md5 file
	    $publishedUrl=$manager->publishTarFile($tarFile, $md5File);
	    $publishedDir=self::$assetDir.$publishedUrl;
	    self::assertTrue(is_dir($publishedDir));
	    self::assertTrue(is_file($publishedDir.'/pradoheader.gif'));
	    self::assertTrue(is_file($publishedDir.'/aTarFile.md5'));

	}
}
?>