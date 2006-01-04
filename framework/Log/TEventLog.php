<?php

require_once(dirname(__FILE__).'/TLog.php');
require_once(dirname(__FILE__).'/TEzcLogger.php');

/**
 * ${classname}
 *
 * ${description}
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.66 $  $Date: ${DATE} ${TIME} $
 * @package ${package}
 */
class TEventLog extends TEzcLogger implements TLog
{

	public function info($msg, $source='Prado', $category='main')
	{
		ezcLog::getInstance()->log($msg, ezcLog::INFO, 
				array('source'=>$source, 'category'=>$category));
	}

	public function debug($msg, $source='Prado', $category='main')
	{
		ezcLog::getInstance()->log($msg, ezcLog::DEBUG, 
				array('source'=>$source, 'category'=>$category));
	}

	public function notice($msg, $source='Prado', $category='main')
	{
		ezcLog::getInstance()->log($msg, ezcLog::NOTICE, 
				array('source'=>$source, 'category'=>$category));
	}

	public function warn($msg, $source='Prado', $category='main')
	{
		ezcLog::getInstance()->log($msg, ezcLog::WARNING, 
				array('source'=>$source, 'category'=>$category));
	}

	public function error($msg, $source='Prado', $category='main')
	{
		ezcLog::getInstance()->log($msg, ezcLog::NOTICE, 
				array('source'=>$source, 'category'=>$category));
	
	}

	public function fatal($msg, $source='Prado', $category='main')
	{
		ezcLog::getInstance()->log($msg, ezcLog::NOTICE, 
				array('source'=>$source, 'category'=>$category));
	}
}

?>