<?php
/**
 * File containing the ezcLogWriterFile class.
 *
 * @package EventLog
 * @version //autogentag//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * The ezcLogWriterFile class provides functionality to write log files to the file 
 * system.
 *
 * The main purpose is to keep track of the various log files and support 
 * log rotation. The file format of the log should be implemented in a subclass.
 * 
 * The following example implements a new log writer that writes the output in ({@link print_r()} format) 
 * to a file:
 * <code>
 * class MyLogWriter extends ezcLogWriterFile
 * {
 *    // Call parent constructor. (In this case, it possible to omit the constructor.)
 *    public function __construct($dir, $file = null, $maxSize = 204800, $maxFiles = 3 )
 *    {
 *        parent::__construct($dir, $file, $maxSize, $maxFiles);
 *    }
 *
 *    // Implement the ezcLogWriter interface:
 *    public function writeLogMessage( $message, $type, $source, $category, $extraInfo = array() )
 *    {
 *        // Create a message
 *        $res = print_r( array( "message" => $message, "type" => $type, "source" => $source, "category" => $category ), true );
 *        
 *        // And call the parent class
 *        $this->write( $type, $source, $category, $res );
 *    }
 *}
 * </code>
 *
 * @package EventLog
 * @version //autogentag//
 */
abstract class ezcLogWriterFile implements ezcLogWriter
{
    /**
     * Contains all the open files. The first file in the
     * array is always the default file.
     *
     * @var array(resource)
     */
    protected $openFiles = array();


    /**
     * Keeps track of which group of messages should be stored
     * in what file.
     *
     * @var ezcLogMap
     */
    protected $fileMap;

    /**
     * Directory where the log files should be placed.
     *
     * @var string
     */
    protected $logDirectory;

    /**
     * Maximum file size before rotation.
     *
     * @var int
     */
    protected $maxSize;

    /**
     * Maximum log rotation files with the same name.
     *
     * When rotating and the max limit is reached, the oldest log
     * is discarded.
     *
     * @var int
     */
    protected $maxFiles;


    /**
     * Constructs an ezcLogFileWriter.
     *
     * The log files will be placed in the directory $logDirectory. 
     * 
     * If the file $defaultFile is not null, log messages that are not {@link map() mapped}
     * to any file are written to this $defaultFile. If $defaultFile is null, then
     * log messages are discarded.

     *
     * Set $maxLogRotationSize to specify the maximum size of a logfile. When the
     * maximum size is reached, the log will be rotated. $maxLogFiles sets the maximum 
     * number of rotated log files. The oldest rotated log will be removed when the
     * maxLogFiles exceeds.
     *
     * @param string $logDirectory
     * @param string $defaultFile
     * @param string $maxLogRotationSize
     * @param string $maxLogFiles
     */
    public function __construct( $logDirectory, $defaultFile = null, $maxLogRotationSize = 204800, $maxLogFiles = 3 )
    {
        $this->maxSize = $maxLogRotationSize;
        $this->maxFiles = $maxLogFiles;
        $this->logDirectory = $logDirectory;
        $this->defaultFile = $defaultFile;

        if ( !is_null( $defaultFile ) )
        {
            $this->openFile( $defaultFile );
        }

        $this->fileMap = new ezcLogMap();
    }

    /**
     * Destructs the object and closes all open file handles.
     */
    public function __destruct()
    {
        foreach ( $this->openFiles as $fh )
        {
            fclose( $fh );
        }
    }


    /**
     * This method writes the $string to a file.
     *
     * The file to which the string will be written depends on the $eventType, $eventSource, and
     * $eventCategory.
     *
     * @throws ezcLogWriterException if it was not possible to write to the log file.
     * @param int $eventType
     * @param string $eventSource
     * @param string $eventCategory
     * @param string $string
     * @return void
     */
    protected function write( $eventType, $eventSource, $eventCategory, $string )
    {
        $fileHandles = $this->fileMap->get( $eventType, $eventSource, $eventCategory );

        if ( count( $fileHandles ) > 0 )
        {
            foreach ( $fileHandles as $fh )
            {
                if ( fwrite( $fh, $string ) === false)
                {
                    throw ezcLogWriterException( "Cannot write to the attached log file.", ezcLogWriterException::FILE_NOT_WRITABLE );
                }
            }
        }
        else
        {
            if ( !is_null( $this->defaultFile ) )
            {
                if ( fwrite( $this->openFiles[$this->defaultFile], $string ) === false )
                {
                    throw ezcLogWriterException( "Cannot write to the default log file.", ezcLogWriterException::FILE_NOT_WRITABLE );
                }
            }
        }
    }

    /**
     * Returns the filehandle of the $fileName.
     *
     * If the maximum file size is exceeded, the file will be rotated before opening.
     *
     * @return resource
     */
    protected function openFile( $fileName )
    {
        if ( isset( $this->openFiles[$fileName] ) )
        {
            return $this->openFiles[$fileName];
        }

        clearstatcache();
        if ( file_exists( $this->logDirectory . "/". $fileName ) &&
            ( filesize( $this->logDirectory . "/". $fileName ) >= $this->maxSize ) )
        {
            $this->rotateLog( $fileName );
        }

        $fh = @fopen( $this->logDirectory ."/".  $fileName, "w" );
        if ( $fh === false )
        {
            // throw exception.
            throw new ezcLogFileException( "Cannot open the file <{$fileName}> for writing", ezcLogFileException::FILE_NOT_FOUND );
        }

        $this->openFiles[$fileName] = $fh;
        return $fh;
    }

    /**
     * Rotates a log and returns true upon success.
     *
     * @return bool
     */
    protected function rotateLog( $fileName )
    {
        $file = $this->logDirectory . "/" . $fileName;

        for ( $i = $this->maxFiles; $i > 0; --$i )
        {
            $logRotateName =  $file. '.' . $i;
            if ( file_exists( $logRotateName ) )
            {
                if ( $i == $this->maxFiles )
                {
                    unlink( $logRotateName );
                }
                else
                {
                    $newLogRotateName = $file . '.' . ( $i + 1 );
                    rename( $logRotateName, $newLogRotateName );
                }
            }
        }
        if ( file_exists( $file ) )
        {
            $newLogRotateName =  $file . '.' . 1;
            rename( $file, $newLogRotateName );
            return true;
        }
        return false;
    }


	/**
	 * Maps the filename $fileName to the messages specified by the {@link ezcLogFilter} $logFilter.
     *
     * Log messages that matches with the filter are written to the file $fileName. 
     * This method works the same as {@link ezclog::map()}.
     *
     * @param ezcLogFilter $logFilter 
     * @param string $fileName
     * @return void
	 */
	public function map( ezcLogFilter $logFilter, $fileName )
	{
        $fh = $this->openFile( $fileName );
        $this->fileMap->map( $logFilter->severity, $logFilter->source, $logFilter->category, $fh );
	}

	/**
	 * Unmaps the filename $fileName from the messages specified by the {@link ezcLogFilter} $logFilter.
     *
     * Log messages that matches with the filter are no longer written to the file $fileName. 
     * This method works the same as {@link ezclog::unmap()}.
     *
     * @param ezcLogFilter $logFilter 
     * @param string $fileName
     * @return void
	 */
    public function unmap( $logFilter, $fileName )
    {
        $this->fileMap->unmap( $logFilter->severity, $logFilter->source, $logFilter->category, $this->openFiles[ $fileName ] );
    }

}
?>
