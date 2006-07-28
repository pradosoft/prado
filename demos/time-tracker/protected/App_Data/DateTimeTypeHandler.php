<?php

class DateTimeTypeHandler implements ITypeHandlerCallback
{
	/**
	 * Not implemented.
	 */
	public function getParameter($integer)
	{
		return date('Y-m-d H:i:s', $integer);
	}

	/**
	 * Not implemented.
	 */
	public function getResult($string)
	{
		return strtotime($string);		
	}

	/**
	 * Creates a new instance of TimeTrackerUser
	 * @param array result data
	 * @return TimeTrackerUser new user instance
	 */
	public function createNewInstance($row=null)
	{
		throw new TimeTrackerException('Not implemented');
	}
	
}

?>