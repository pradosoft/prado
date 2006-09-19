<?php
/**
 * TProviderException class.
 * The exception that is thrown when a configuration provider error has occurred. 
 * This exception class is also used by providers to throw exceptions when internal 
 * errors occur within the provider that do not map to other pre-existing exception classes.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TProviderException.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Configuration.Provider
 * @since 3.1
 */

Prado::using('System.Exceptions.TException');
class TProviderException extends TException 
{
	
}
?>