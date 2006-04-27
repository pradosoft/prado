<?php
require_once 'phing/tasks/system/MatchingTask.php';
include_once 'phing/types/FileSet.php';
include_once 'phing/tasks/ext/pearpackage/Fileset.php';
require_once 'PEAR/PackageFileManager.php';
require_once 'PEAR/PackageFileManager/File.php';

/**
 * Task for creating a PEAR package definition file package.xml to be used with
 * the PEAR distribution of PRADO.
 * 
 * @author   Knut Urdalen <knut.urdalen@gmail.com>
 * @package  phing.tasks.ext
 */
class BuildPradoPEARPackageTask extends MatchingTask {
    
    /* Base directory for reading files. */
    private $dir;

    /* PRADO version */
	private $version;

    /* PRADO state */
	private $state = 'stable';
	private $notes;
	private $filesets = array();
	
    /* Package file */
    private $packageFile;

    /**
     * Intitialize the task and throw a BuildException if something is missing.
     */
    public function init() {
        include_once 'PEAR/PackageFileManager2.php';
        if(!class_exists('PEAR_PackageFileManager2')) {
            throw new BuildException("You must have installed PEAR_PackageFileManager2 (PEAR_PackageFileManager >= 1.6.0) in order to create a PEAR package.xml file.");
        }
    }

    /**
     * Helper function to set PEAR package options.
     *
     * @param PEAR_PackageFileManager2 $pkg
     */
    private function setOptions($pkg) {        
		$options['baseinstalldir'] = 'prado';
        $options['packagedirectory'] = $this->dir->getAbsolutePath();
        
        if(empty($this->filesets)) {
			throw new BuildException("You must use a <fileset> tag to specify the files to include in the package.xml");
		}
        
		$options['filelistgenerator'] = 'Fileset';
        
		// Some Phing-specific options needed by our Fileset reader
		$options['phing_project'] = $this->getProject();
		$options['phing_filesets'] = $this->filesets;
		
		if($this->packageFile !== null) {
            // Create one with full path
            $f = new PhingFile($this->packageFile->getAbsolutePath());
            $options['packagefile'] = $f->getName();
            // Must end in trailing slash
            $options['outputdirectory'] = $f->getParent().DIRECTORY_SEPARATOR;
            $this->log("Creating package file: ".$f->getPath(), PROJECT_MSG_INFO);
        } else {
            $this->log("Creating [default] package.xml file in base directory.", PROJECT_MSG_INFO);
        }
        $pkg->setOptions($options);
    }

    /**
     * Main entry point.
     * @return void
     */
    public function main() {

        if($this->dir === null) {
            throw new BuildException("You must specify the \"dir\" attribute for PEAR package task.");
        }
        
		if($this->version === null) {
            throw new BuildException("You must specify the \"version\" attribute for PEAR package task.");
        }

		$package = new PEAR_PackageFileManager2();
		$this->setOptions($package);

		// the hard-coded stuff
		$package->setPackage('prado');
		$package->setSummary('PRADO is a component-based and event-driven framework for rapid Web programming in PHP 5.');
		$package->setDescription('PRADO reconceptualizes Web application development in terms of components, events and properties instead of procedures, URLs and query parameters.

A PRADO component is a combination of a specification file (in XML), an HTML template and a PHP class. PRADO components are combined together to form larger components or complete PRADO pages.

Developing PRADO Web applications mainly involves instantiating prebuilt and application-specific component types, configuring them by setting their properties, responding to their events by writing handler functions, and composing them into application tasks. Event-driven programming

PRADO provides the following benefits for Web application developers:

o Reusability - Codes following the PRADO component protocol are highly reusable. Everything in PRADO is a reusable component.
o Ease of Use - Creating and using components are extremely easy. Usually they simply involve configuring component properties.
o Robustness - PRADO frees developers from writing boring, buggy code. They code in terms of objects, methods and properties, instead of URLs and query parameters. The latest PHP5 exception mechanism is exploited that enables line-precise error reporting.
o Performance - PRADO uses a cache technique to ensure the performance of applications based on it. The performance is in fact comparable to those based on commonly used template engines.
o Team Integration - PRADO enables separation of content and presentation. Components, typically pages, have their content (logic) and presentation stored in different files.');
		$package->setChannel('pear.pradosoft.com');
		$package->setPackageType('php');

		$package->setReleaseVersion($this->version);
		$package->setAPIVersion($this->version);
		
		$package->setReleaseStability($this->state);
		$package->setAPIStability($this->state);
		
		$package->setNotes($this->notes);
		
		$package->setLicense('BSD', 'http://www.opensource.org/licenses/bsd-license.php');
		
		// Add package maintainers
		$package->addMaintainer('lead', 'xue', 'Qiang Xue', 'qiang.xue@gmail.com');

		// "core" dependencies
		$package->setPhpDep('5.0.0');
		$package->setPearinstallerDep('1.4.0');
				
		$package->generateContents();

        $e = $package->writePackageFile();

        if(PEAR::isError($e)) {
            throw new BuildException("Unable to write package file.", new Exception($e->getMessage()));
        }
    }

    /**
     * Used by the PEAR_PackageFileManager_PhingFileSet lister.
     * @return array FileSet[]
     */
    public function getFileSets() {
        return $this->filesets;
    }

    // -------------------------------
    // Set properties from XML
    // -------------------------------

    /**
     * Nested creator, creates a FileSet for this task
     *
     * @return FileSet The created fileset object
     */
    function createFileSet() {
        $num = array_push($this->filesets, new FileSet());
        return $this->filesets[$num-1];
    }

	/**
     * Set the version we are building.
     * @param string $v
     * @return void
     */
	public function setVersion($v){
		$this->version = $v;
	}

	/**
     * Set the state we are building.
     * @param string $v
     * @return void
     */
	public function setState($v) {
		$this->state = $v;
	}
	
	/**
	 * Sets release notes field.
	 * @param string $v
	 * @return void
	 */
	public function setNotes($v) {
		$this->notes = $v;
	}
    /**
     * Sets "dir" property from XML.
     * @param PhingFile $f
     * @return void
     */
    public function setDir(PhingFile $f) {
        $this->dir = $f;
    }

    /**
     * Sets the file to use for generated package.xml
     */
    public function setDestFile(PhingFile $f) {
        $this->packageFile = $f;
    }

}


