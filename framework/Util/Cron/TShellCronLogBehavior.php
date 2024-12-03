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
use Prado\Util\TCallChain;

/**
 * TShellCronLogBehavior class.
 *
 * Enables cron logging to the shell.  It also encapsulates the TShellWriter
 * and basic dyWrite, dyWriteLine, and dyFlush functionality.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TShellCronLogBehavior extends TBehavior
{
	private $_outWriter;

	/**
	 * creates the TShellCronLogBehavior with a writer
	 * @param \Prado\IO\ITextWriter $writer where to write cron task info to
	 */
	public function __construct($writer = null)
	{
		$this->_outWriter = $writer;
	}

	/**
	 * @return \Prado\IO\ITextWriter the output writer from the shell
	 */
	public function getOutputWriter()
	{
		return $this->_outWriter;
	}

	/**
	 * @param \Prado\IO\ITextWriter $writer the output writer from the shell
	 */
	public function setOutputWriter($writer)
	{
		$this->_outWriter = $writer;
	}

	/**
	 * writes, with attributes, to the OutputWriter
	 * @param string $str
	 * @param array|int|\Prado\Util\TCallChain|string $p1
	 * @param null|\Prado\Util\TCallChain $p2
	 * @return mixed
	 */
	public function dyWrite($str, $p1, $p2 = null)
	{
		if ($p1 instanceof TCallChain) {
			$attr = null;
			$callchain = $p1;
		} else {
			$attr = $p1;
			$callchain = $p2;
		}
		$this->_outWriter->write($str, $attr);
		return $callchain->dyWrite($str, $p1, $p2);
	}

	/**
	 * writes Line, with attributes, to the OutputWriter
	 * @param string $str
	 * @param array|int|\Prado\Util\TCallChain|string $p1
	 * @param null|\Prado\Util\TCallChain $p2
	 * @return mixed
	 */
	public function dyWriteLine($str, $p1, $p2 = null)
	{
		if ($p1 instanceof TCallChain) {
			$attr = null;
			$callchain = $p1;
		} else {
			$attr = $p1;
			$callchain = $p2;
		}
		$this->_outWriter->writeLine($str, $attr);
		return $callchain->dyWriteLine($str, $p1, $p2);
	}


	/**
	 * flushes the OutputWriter
	 * @param \Prado\Util\TCallChain $callchain
	 * @return string the accumulated text in the buffer
	 */
	public function dyFlush($callchain)
	{
		$result = $this->_outWriter->flush();
		return $result . $callchain->dyFlush();
	}

	/**
	 * Logs a when cron is run in the shell.
	 * @param int $numtasks number of tasks to run
	 * @param \Prado\Util\TCallChain $callchain the chain of methods
	 * @return mixed
	 */
	public function dyLogCron($numtasks, $callchain)
	{
		$this->_outWriter->writeLine(" Running {$numtasks} Cron Tasks @ " . date('Y-m-d H:i:s') . " \n");

		return $callchain->dyLogCron($numtasks);
	}

	/**
	 * Logs a single cron task when run in the shell.
	 * @param \Prado\Util\Cron\TCronTask $task the task to log
	 * @param string $username the user name running the task
	 * @param \Prado\Util\TCallChain $callchain the chain of methods
	 * @return mixed
	 */
	public function dyLogCronTask($task, $username, $callchain)
	{
		$this->_outWriter->writeLine("Running Task {$task->getName()} as {$username}");
		$this->_outWriter->flush();

		return $callchain->dyLogCronTask($task, $username);
	}

	/**
	 * Logs the end of a single cron task when run in the shell.
	 * @param \Prado\Util\Cron\TCronTask $task the tasks that was run
	 * @param \Prado\Util\TCallChain $callchain the chain of methods
	 * @return mixed
	 */
	public function dyLogCronTaskEnd($task, $callchain)
	{
		$this->_outWriter->writeLine("Ending Task {$task->getName()}\n");

		return $callchain->dyLogCronTaskEnd($task);
	}
}
