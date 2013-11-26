<?php
require_once 'phing/Task.php';
include_once 'phing/tasks/system/PropertyTask.php';

class PradoVersionTask extends PropertyTask
{
	/**
	* Execute lint check against PhingFile or a FileSet
	*/
	public function main()
	{
		$this->addProperty('prado.version',$this->getPradoVersion());
		$this->addProperty('prado.revision',$this->getPradoRevision());
		if(substr(PHP_OS, 0, 3) == 'WIN')
			$this->addProperty('prado.winbuild','true');
		else
			$this->addProperty('prado.winbuild','false');
	}

	/**
	 * @return string Prado version
	 */
	private function getPradoVersion()
	{
		$coreFile=dirname(__FILE__).'/../../../framework/PradoBase.php';
		if(is_file($coreFile))
		{
			$contents=file_get_contents($coreFile);
			$matches=array();
			if(preg_match('/public static function getVersion.*?return \'(.*?)\'/ms',$contents,$matches)>0)
				return $matches[1];
		}
		return 'unknown';
	}

	/**
	 * @return string Prado SVN revision
	 */
	private function getPradoRevision()
	{
		$rev=shell_exec("git log -1 --pretty=format:'%h'");
		if($rev===null) $rev='unknown';
		return $rev;
	}
}
