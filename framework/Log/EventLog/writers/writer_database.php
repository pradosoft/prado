<?php
/**
 * File containing the ezcLogWriterDatabase class.
 *
 * @package EventLog
 * @version //autogentag//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Writes log messages to the database.
 *
 * @package EventLog
 * @version //autogentag//
 */
class ezcLogWriterDatabase implements ezcLogWriter
{
    private $db = null;

    private $properties = array();
    private $defaultColumns = array();
    private $additionalColumns = array();

    private $map;
    private $defaultTable = false;

    /**
     * Construct a new database log-writer.
     *
     * You can set the default table to write to with the $defaultTable parameter.
     * If $databaseInstance is given, that instance will be used for writing. If it
     * is ommitted the default database instance will be retrieved.
     *
     * This constructor is a tie-in.
     *
     * @param string $defaultTable
     * @param ezcDbHandler $databaseInstance
     *
     */
    public function __construct( ezcDbHandler $databaseInstance, $defaultTable = false )
    {
        $this->db = $databaseInstance;

        $this->map = new ezcLogMap();
        $this->defaultTable = $defaultTable;

        $this->message = "message";
        $this->datetime = "time";
        $this->severity = "severity";
        $this->source = "source";
        $this->category = "category";
    }

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
            case 'table':    $this->properties['table'] = $value;    break;
            case 'message':
            case 'datetime':
            case 'severity':
            case 'source':
            case 'category': $this->defaultColumns[ $name ] = $value; break;
            default:         $this->additionalColumns[ $name ] = $value; break;
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
            case 'table':    return $this->properties['table'];    break;
            case 'message':
            case 'datetime':
            case 'severity':
            case 'source':
            case 'category': return $this->defaultColumns[ $name ]; break;

            default:         return $this->additionalColumns[ $name ]; break;
        }
    }

    /**
     * Writes the message $message to the log.
     *
     * The writer can use the severity, source, and category to filter the
     * incoming messages and determine the location where the messages should
     * be written.
     *
     * $optional may contain extra information that can be added to the log. For example:
     * line numbers, file names, usernames, etc.
     *
     * @throws ezcLogWriterException when the log writer was unable to write
     *         the log message.
     * @param string $message
     * @param int $severity
	 *        ezcLog:: DEBUG, SUCCES_AUDIT, FAILED_AUDIT, INFO, NOTICE, WARNING, ERROR or FATAL.
     *
     * $param string $source
     * @param string $category
     * @param array(string=>string) $optional
     * @return void
     */
    public function writeLogMessage( $message, $severity, $source, $category, $optional = array() )
    {
        $severityName = ezcLog::translateSeverityName( $severity );

        $colStr = "";
        $valStr = "";

        if ( is_array( $optional ) )
        {
            foreach ( $optional as $key => $val )
            {
                $colStr .= ", " . ( isset( $this->additionalColumns[$key] ) ? $this->additionalColumns[$key] : $key );
                $valStr .= ", " . $this->db->quote( $val );
            }
        }

        $tables = $this->map->get( $severity, $source, $category );

        $query = $this->db->createSelectQuery();
        if ( count( $tables ) > 0)
        {
            foreach ( $tables as $t )
            {
                try
                {
                    $this->db->exec( "INSERT INTO `{$t}` ( {$this->message}, {$this->severity}, {$this->source}, {$this->category}, {$this->datetime} $colStr ) ".
                        "VALUES( ".$this->db->quote( $message ).", ".$this->db->quote( $severityName ).", ".$this->db->quote( $source ).", ".
                        $this->db->quote( $category ).", ".$query->expr->now()." $valStr )" );
                }
                catch ( PDOException $e )
                {
                    throw new ezcLogWriterException( "SQL query failed in ezcLogWriterDatabase.\n". $e->getMessage() );
                }
            }
        }
        else
        {
            if ( $this->defaultTable !== false )
            {
                try
                {
                    $this->db->exec( "INSERT INTO `{$this->defaultTable}` ( {$this->message}, {$this->severity}, {$this->source}, {$this->category}, {$this->datetime} $colStr ) ".
                        "VALUES( ".$this->db->quote( $message ).", ".$this->db->quote( $severityName ).", ".$this->db->quote( $source ).", ".
                        $this->db->quote( $category ).", ".$query->expr->now()." $valStr )" );
                }
                catch ( PDOException $e )
                {
                    throw new ezcLogWriterException( "SQL query failed in ezcLogWriterDatabase.\n". $e->getMessage() );
                }
            }
        }
    }

    /**
     * Returns an array that describes the coupling between the logMessage
     * information and the columns in the database.
     *
     * @return array(string=>string)
     */
    public function getColumnTranslations()
    {
        return array_merge( $this->defaultColumns, $this->additionalColumns );
    }


	/**
	 * Maps the table $tableName to the messages specified by the {@link ezcLogFilter} $logFilter.
     *
     * Log messages that matches with the filter are written to the table $tableName. 
     * This method works the same as {@link ezclog::map()}.
     *
     * @param ezcLogFilter $logFilter 
     * @param string $tableName
     * @return void
	 */
	public function map( ezcLogFilter $logFilter, $tableName )
	{
        $this->map->map( $logFilter->severity, $logFilter->source, $logFilter->category, $tableName );
	}

	/**
	 * Unmaps the table $tableName from the messages specified by the {@link ezcLogFilter} $logFilter.
     *
     * Log messages that matches with the filter are no longer written to the table $tableName. 
     * This method works the same as {@link ezclog::unmap()}.
     *
     * @param ezcLogFilter $logFilter 
     * @param string $fileName
     * @return void
	 */
    public function unmap( ezcLogFilter $logFilter, $tableName )
    {
        $this->map->unmap( $logFilter->severity, $logFilter->source, $logFilter->category, $tableName );
    }
}

?>
