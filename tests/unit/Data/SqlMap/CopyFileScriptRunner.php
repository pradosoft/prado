<?php

namespace Prado\Test\Unit\Data\SqlMap;


class CopyFileScriptRunner
{
	protected $baseFile;
	protected $targetFile;

	public function __construct($base, $target)
	{
		$this->baseFile = $base;
		$this->targetFile = $target;
	}

	public function runScript($connection, $script)
	{
		copy($this->baseFile, $this->targetFile);
	}
}
