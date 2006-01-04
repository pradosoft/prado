<?php
/**
 * File containing the ezcLogWriter interface.
 *
 * @package EventLog
 * @version //autogentag//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * ezcLogWriter defines the common interface for all classes that implement
 * their log writer.
 *
 * See the ezcLogFileWriter for an example of creating your own log writer.
 *  
 * @package EventLog
 * @version //autogentag//
 */
interface ezcLogWriter
{
    /**
     * Writes the message $message to the log.
     *
     * The writer can use the severity, source, and category to filter the
     * incoming messages and determine the location where the messages should
     * be written.
     *
     * The array $optional contains extra information that can be added to the log. For example:
     * line numbers, file names, usernames, etc.
     *
     * @throws ezcLogWriterException when the log writer was unable to write the log message.
     *
     * @param string $message
     * @param int $severity
	 *        ezcLog::DEBUG, ezcLog::SUCCES_AUDIT, ezcLog::FAIL_AUDIT, ezcLog::INFO, ezcLog::NOTICE, 
     *        ezcLog::WARNING, ezcLog::ERROR or ezcLog::FATAL.
     * $param string $source
     * @param string $category
     * @param array(string=>string) $optional
     * @return void
     */
    public function writeLogMessage( $message, $severity, $source, $category, $optional = array() );
}
?>
