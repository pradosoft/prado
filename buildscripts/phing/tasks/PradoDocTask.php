<?php
require_once 'phing/Task.php';

/**
 * Task to run phpDocumentor for PRADO API docs.
 */	
class PradoDocTask extends Task
{
	private $phpdoc = 'phpdoc';
	
	private $title = "Default Title";
			
	private $destdir = ".";
			
	private $sourcepath = NULL;
	
	private $ignorelist = '';
	
	private $template = "";
			
	private $linksource = false;
	
	private $parseprivate = false;

	private $quite = false;

	function setPhpdoc($phpdoc)
	{
		$this->phpdoc=$phpdoc;
	}
	
	function setQuite($quite)
	{
		$this->quite=$quite;
	}

	/**
	 * Set the title for the generated documentation
	 */
	function setTitle($title)
	{
		$this->title = $title;
	}
	
	/**
	 * Set the destination directory for the generated documentation
	 */
	function setDestdir($destdir)
	{
		$this->destdir = $destdir;
	}
	
	/**
	 * Set the source path
	 */
	function setSourcepath(Path $sourcepath)
	{
		if ($this->sourcepath === NULL)
		{
			$this->sourcepath = $sourcepath;
		}
		else
		{
			$this->sourcepath->append($sourcepath);
		}
	}
	
	/**
	 * Set the template
	 */		
	function setTemplate($template)
	{
		$this->template = $template;
	}
	
	/**
	 * Should sources be linked in the generated documentation
	 */
	function setLinksource($linksource)
	{
		$this->linksource = $linksource;
	}

	function setIgnorelist($ignorelist)
	{
		$this->ignorelist=$ignorelist;
	}	
	
	/**
	 * Main entrypoint of the task
	 */
	function main()
	{
		$arguments = $this->constructArguments();
		echo $this->phpdoc . " " . $arguments."\n";
		passthru($this->phpdoc . " " . $arguments, $retval);
	}
	
	/**
	 * Constructs an argument string for phpDocumentor
	 */
	private function constructArguments()
	{
		$arguments = " ";

		if($this->quite)
		{
			$arguments .= '-q "on" ';
		}
		
		if ($this->title)
		{
			$arguments.= "--title \"" . $this->title . "\" ";
		}
		
		if ($this->destdir)
		{
			$arguments.= "-t \"" . $this->destdir . "\" ";
		}
		
		if ($this->sourcepath !== NULL)
		{
			$arguments.= "-d \"" . $this->sourcepath->__toString() . "\" ";
		}
		
		if ($this->template)
		{
			$arguments.= "--template \"" . $this->template . "\" ";
		}
		
		if ($this->linksource)
		{
			$arguments.= "-s ";
		}
		
		if ($this->parseprivate)
		{
			$arguments.= "-pp ";
		}

		if ($this->ignorelist)
		{
			$arguments.='-i "'.$this->ignorelist.'" ';
		}

		return $arguments;
	}
}
