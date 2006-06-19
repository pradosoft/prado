<?php

require_once 'phing/Task.php';
require_once('PEAR/PackageFileManager2.php');

/**
 * Task to run phpDocumentor for PRADO API docs.
 */
class PradoPearTask extends Task
{
	private $pkgdir;
	private $channel;
	private $version;
	private $state;
	private $category;
	private $package;
	private $summary;
	private $pkgdescription;
	private $notes;
	private $license;

	function setPkgdir($value)
	{
		$this->pkgdir=$value;
	}

	function setChannel($value)
	{
		$this->channel=$value;
	}

	function setVersion($value)
	{
		$this->version=$value;
	}

	function setState($value)
	{
		$this->state=$value;
	}

	function setCategory($value)
	{
		$this->category=$value;
	}

	function setPackage($value)
	{
		$this->package=$value;
	}

	function setSummary($value)
	{
		$this->summary=$value;
	}

	function setPkgdescription($value)
	{
		$this->pkgdescription=$value;
	}

	function setNotes($value)
	{
		$this->notes=$value;
	}

	function setLicense($value)
	{
		$this->license=$value;
	}

	/**
	 * Main entrypoint of the task
	 */
	function main()
	{
		$pkg = new PEAR_PackageFileManager2();

		$e = $pkg->setOptions(
			array(
				'baseinstalldir'    => 'prado',
				'packagedirectory'  => $this->pkgdir,
				'pathtopackagefile' => $this->pkgdir,
				'filelistgenerator' => 'file',
				'simpleoutput'      => true,
				'ignore'            => array(),
				'dir_roles'         =>
					array(
						'docs'          => 'doc',
						'examples'      => 'doc',
						'framework'     => 'php',
						'framework/js'  => 'doc',
						'framework/3rdParty' => 'doc',
					),
				'exceptions' =>
					array(
						'requirements.php' => 'doc',
					),
			)
		);

		// PEAR error checking
		if (PEAR::isError($e))
			die($e->getMessage());
		$pkg->setPackage($this->package);
		$pkg->setSummary($this->summary);
		$pkg->setDescription($this->pkgdescription);
		$pkg->setChannel($this->channel);

		$pkg->setReleaseStability($this->state);
		$pkg->setAPIStability($this->state);
		$pkg->setReleaseVersion($this->version);
		$pkg->setAPIVersion($this->version);

		$pkg->setLicense($this->license);
		$pkg->setNotes($this->notes);
		$pkg->setPackageType('php');
		$pkg->setPhpDep('5.0.0');
		$pkg->setPearinstallerDep('1.4.2');

		$pkg->addRelease();
		$pkg->addMaintainer('lead','qxue','Qiang (Charlie) Xue','qiang.xue@gmail.com');

		$test = $pkg->generateContents();

		$e = $pkg->writePackageFile();

		if (PEAR::isError($e))
			echo $e->getMessage();
	}
}

?>