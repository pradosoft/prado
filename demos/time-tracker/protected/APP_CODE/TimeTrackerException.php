<?php

class TimeTrackerException extends TException
{
	/**
	 * @return string path to the error message file
	 */
	protected function getErrorMessageFile()
	{
		return dirname(__FILE__).'/exceptions.txt';
	}	
}

?>