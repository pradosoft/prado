<?php
/**
 * TShellCronLogBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */
 
namespace Prado\Util\Cron;

use Prado\Prado;
use Prado\Shell\TShellAppAction;
use Prado\Util\TBehavior;

/**
 * TShellCronLogBehavior class.
 *
 * Enables cron logging to the shell.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Cron
 * @since 4.2.0
 */
class TShellCronLogBehavior extends TBehavior
{
	private $_outWriter;
	
	/**
	 * creates the TShellCronLogBehavior with a writer
	 * @param ITextWriter $writer where to write cron task info to
	 */
	public function __construct($writer = null)
	{
		$this->_outWriter = $writer;
	}
	
	/**
	 * @return ITextWriter the output writer from the shell
	 */
	public function getOutputWriter()
	{
		return $this->_outWriter;
	}
	
	/**
	 * @param ITextWriter $writer the output writer from the shell
	 */
	public function setOutputWriter($writer)
	{
		$this->_outWriter = $writer;
	}
	
	/**
	 * @param int $numtasks number of tasks to run
	 * @param TCallChain $callchain the chain of methods
	 */
	public function dyLogCron($numtasks, $callchain)
	{
		$this->_outWriter->writeLine(" Running {$numtasks} Cron Tasks @ " . date('Y-m-d H:i:s') . " \n");
		$this->_outWriter->flush();
		
		return $callchain->dyLogCron($numtasks);
	}
	
	/**
	 * @param TCronTask $task the task to log
	 * @param string $username the user name running the task
	 * @param TCallChain $callchain the chain of methods
	 */
	public function dyLogCronTask($task, $username, $callchain)
	{
		$this->_outWriter->writeLine("Running Task {$task->getName()} as {$username}");
		$this->_outWriter->flush();
		
		return $callchain->dyLogCronTask($task, $username);
	}
	
	/**
	 * @param TCronTask $task the tasks that was run
	 * @param TCallChain $callchain the chain of methods
	 */
	public function dyUpdateTaskInfo($task, $callchain)
	{
		$this->_outWriter->writeLine("Ending Task {$task->getName()}\n");
		$this->_outWriter->flush();
		
		return $callchain->dyUpdateTaskInfo($task);
	}
}
