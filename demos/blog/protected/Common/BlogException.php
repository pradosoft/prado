<?php

class BlogException extends TApplicationException
{
	/**
	 * @return string path to the error message file
	 */
	protected function getErrorMessageFile()
	{
		return dirname(__FILE__).'/messages.txt';
	}
}

?>