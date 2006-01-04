<?php
/**
 * File containing the ezcLogMessage class.
 *
 * @package EventLog
 * @version //autogentag//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */

/**
 * Holds a log message and provides convenience methods to read the information.
 *
 * The ezclogMessage class is used for subtracting the information from the message
 * parameter from {@link trigger_error()}. See the {@link ezcLog::logHandler} for 
 * more information. 
 *
 * The message formats that can be parsed are: 
 * <pre>
 * [ source, category ] Message 
 * </pre>
 *
 * When one name is given between the brackets, the category will be set and the message has a default source:
 * <pre>
 * [ category ] Message
 * </pre>
 *
 * Without any names between the brackets, the default category and source are used:
 * <pre>
 * Message
 * </pre>
 * 
 * The following properties are set after construction or after calling {@link parseMessage()}:
 * - message, contains the message without extra the additional information.
 * - source, contains either the default source or the source set in the incoming message.
 * - category, contains either the default category or the category set in the incoming message.
 * - severity, severity of the error. Which is ezcLog::NOTICE, ezcLog::WARNING, or ezcLog::ERROR. 
 *
 * @package EventLog
 * @version //autogentag//
 * @access private
 */
class ezcLogMessage
{
    /**
     * Holds the properties of this class.
     *
     * @var array(string=>mixed)
     */
     protected $properties = array( "message" => "", "source" => "", "category" => "", "severity" => "" );


    /**
     * Sets the property $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set( $name, $value )
    {
        switch ( $name )
        {
            case 'message':
                $this->properties['message'] = $value;
                break;
            case 'source':
                $this->properties['source'] = $value;
                break;
            case 'category':
                $this->properties['category'] = $value;
                break;
            case 'severity':
                $this->properties['severity'] = $value;
                break;

            default: 
                throw new ezcBasePropertyNotFoundException( $name );
        }

    }

    /**
     * Returns the property $name.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @param string $name
     * @return mixed
     */
    public function __get( $name )
    {
        switch ( $name )
        {
            case 'message':
                return $this->properties['message'];
                break;
            case 'source':
                return $this->properties['source'];
                break;
            case 'category':
                return $this->properties['category'];
                break;
            case 'severity':
                return $this->properties['severity'];
                break;
        }

        throw new ezcBasePropertyNotFoundException( $name );
    }

    /**
     * Constructs the ezcLogMessage from the $message, $severity, $defaultSource and $defaultCategory.
     *
     * $message is parsed by parseMessage() and properties are set.
     *
     * @param string $message
     * @param int $severity
     * @param string $defaultSource Use this source when not given in the message itself.
     * @param string $defaultCategory Use this category when not give in the message itself.
     */
    public function __construct( $message, $severity, $defaultSource, $defaultCategory )
    {
        $this->parseMessage( $message, $severity, $defaultSource, $defaultCategory );
    }

    /**
     * Parses the message $message and sets the properties. 
     *
     * See the general class documentation for message format. 
     * The severity $severity is a E_USER_* PHP constant. The values will be translated accordingly:
     * - E_USER_NOTICE -> ezcLog::NOTICE
     * - E_USER_WARNING -> ezcLog::WARNING
     * - E_USER_ERROR -> ezcLog::ERROR
     *
     * @param string $message
     * @param int $severity
     * @param string $defaultSource 
     * @param string $defaultCategory
     * @return void
     */
    public function parseMessage( $message, $severity, $defaultSource, $defaultCategory )
    {
        preg_match( "/^\s*(?:\[([^,\]]*)(?:,\s(.*))?\])?\s*(.*)$/", $message, $matches );

        $this->message = ( strcmp( $matches[3], "" ) == 0 ? false : $matches[3] );

        if ( strlen( $matches[2] ) == 0 )
        {
            $this->category = ( strcmp( $matches[1], "" ) == 0 ? $defaultCategory : $matches[1] );
            $this->source = $defaultSource;
        }
        else
        {
            $this->category = $matches[2];
            $this->source = $matches[1];
        }

        switch ( $severity )
        {
            case E_USER_NOTICE:  $this->severity = ezcLog::NOTICE; break;
            case E_USER_WARNING: $this->severity = ezcLog::WARNING; break;
            case E_USER_ERROR:  $this->severity = ezcLog::ERROR; break;
            default: $this->severity = false;
        }
    }
}
?>
