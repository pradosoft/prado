<?php

class BlogErrors
{
	const ERROR_UKNOWN=0;
	const ERROR_POST_NOT_FOUND=1;
	const ERROR_USER_NOT_FOUND=2;
	const ERROR_PERMISSION_DENIED=3;

	private static $_errorMessages=array(
		self::ERROR_UKNOWN=>'Unknown error.',
		self::ERROR_POST_NOT_FOUND=>'The specified post cannot be found.',
		self::ERROR_USER_NOT_FOUND=>'The specified user account cannot be found.',
		self::ERROR_PERMISSION_DENIED=>'Sorry, you do not have permission to perform this action.',
	);

	public static function getMessage($errorCode)
	{
		return isset(self::$_errorMessages[$errorCode])?self::$_errorMessages[$errorCode]:self::$_errorMessages[0];
	}
}

?>