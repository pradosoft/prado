<?php
/**
 * File containing the ezcFileWriterException class.
 *
 * @package EventLog
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Exception thrown when a file error occures.
 *
 * @package EventLog
 * @version //autogen//
 */
class ezcLogFileException extends Exception
{
    /**
     * The  file could not be found on the filesystem.
     */
    const FILE_NOT_FOUND = 1;

    /**
     * The file could not be read from the filesystem.
     */
    const FILE_NOT_READABLE = 2;

    /**
     * The file could not be written to the filesystem.
     */
    const FILE_NOT_WRITABLE = 3;

    /**
     * Constructs a new ezcLogFileExcpetion with the message $message and the error code $code.
     *
     * @param string $message
     * @param int $code
     */
    public function __construct( $message, $code )
    {
        parent::__construct( $message, $code );
    }
}
?>
