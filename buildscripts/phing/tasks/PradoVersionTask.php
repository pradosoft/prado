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
		$svnPath=dirname(__FILE__).'/../../../.svn';
		if(is_file($svnPath.'/all-wcprops'))
			$propFile=$svnPath.'/all-wcprops';
		else if(is_file($svnPath.'/dir-wcprops'))
			$propFile=$svnPath.'/dir-wcprops';
		else
			return 'unknown';
		$contents=file_get_contents($propFile);
		if(preg_match('/\\/repos\\/prado\\/\\!svn\\/ver\\/(\d+)\\//ms',$contents,$matches)>0)
			return $matches[1];
		else
			return 'unknown';
	}
}

?>