<?php
/**
 * File containing the ezcLogWriterException class.
 *
 * @package EventLog
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * The ezcLogWriterException will be thrown when an {@class ezcLogWriter} or
 * a subclass encounters an exceptional state.
 *
 * @package EventLog
 * @version //autogen//
 */
class ezcLogWriterException extends Exception
{
    /**
     * Constructs a new ezcLogWriterException with the message $message.
     *
     * @param string $message
     */
    public function __construct( $message )
    {
        parent::__construct( $message, 0 );
    }
}
?>
