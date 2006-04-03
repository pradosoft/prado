<?php
/**
 * TLogger class file
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Util
 */

/**
 * TPropelLogRoute class.
 *
 * TPropelLogRoute saves selected log messages into a Propel database.
 * The name of the Propel database object used to represent each message
 * is specified by {@link setPropelObjectName PropelObjectName}, which defaults
 * to 'PradoLog'.
 *
 * The schema of the Propel object must be as follows (the table name can be
 * changed to the value of {@link getPropelObjectName PropelObjectName}.
 * <code>
 * <table name="PradoLog">
 * 	<column
 * 		name="ID"
 * 		required="true"
 * 		primaryKey="true"
 * 		autoIncrement="true"
 * 		type="INTEGER" />
 * 	<column
 * 		name="Category"
 * 		required="true"
 * 		type="VARCHAR"
 * 		size="255" />
 * 	<column
 * 		name="Level"
 * 		required="true"
 * 		type="VARCHAR"
 * 		size="255" />
 * 	<column
 * 		name="Message"
 * 		required="true"
 * 		type="LONGVARCHAR"
 * 		size="2048" />
 * 	<column
 * 		name="Time"
 * 		type="TIMESTAMP"/>
 * </table>
 * </code>
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Revision: $  $Date: $
 * @package System.Util
 * @since 3.0
 */
class TPropelLogRoute extends TLogRoute
{
	private $_className='PradoLog';

	/**
	 * @return string the name of the Prople object used to save each log message. Defaults to 'PradoLog'.
	 */
	public function getPropelObjectName()
	{
		return $this->_className;
	}

	/**
	 * @param string the name of the Prople object used to save each log message. The name can be in namespace format.
	 */
	public function setPropelObjectName($value)
	{
		$this->_className=$value;
	}

	/**
	 * Saves log messages to the Propel database object.
	 *
	 * @param array $logs
	 */
	protected function processLogs($logs)
	{
		foreach($logs as $log)
		{
			$pradoLog=Prado::createComponent($this->_className);
			$pradoLog->setMessage($log[0]);
			$pradoLog->setLevel($this->getLevelName($log[1]));
			$pradoLog->setCategory($log[2]);
			$pradoLog->setTime($log[3]);
			$pradoLog->save();
		}
	}
}
?>