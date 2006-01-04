<?php
/**
 * Autoloader definition for the Translation component.
 *
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version //autogentag//
 * @filesource
 * @package EventLog
 */

return array(
    'ezcLog'               =>  'EventLog/log.php',
    'ezcLogMap'            =>  'EventLog/map.php',
    'ezcLogContext'        =>  'EventLog/context.php',
    'ezcLogMessage'        =>  'EventLog/log_message.php',
    'ezcLogWriter'         =>  'EventLog/interfaces/writer.php',
    'ezcLogWriterFile'     =>  'EventLog/writers/writer_file.php',
    'ezcLogWriterUnixFile' =>  'EventLog/writers/writer_unix_file.php',
    'ezcLogWriterDatabase' =>  'EventLog/writers/writer_database.php',
    'ezcLogFilter'         =>  'EventLog/structs/log_filter.php',
    'ezcLogFileException'  =>  'EventLog/exceptions/file_exception.php',
    'ezcLogWriterException'=>  'EventLog/exceptions/writer_exception.php'
);
?>
