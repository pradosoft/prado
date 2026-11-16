<?php

/**
 * TShellEmailerAction class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\Shell;

use Prado\Shell\TShellAction;
use Prado\Shell\TShellWriter;
use Prado\Util\Email\TDbEmailModule;

/**
 * TShellEmailerAction class.
 *
 * TShellEmailerAction manages a {@see TDbEmailModule} from the command line.
 *
 * | Command | Result |
 * |---|---|
 * | `email/send` | Sends the pending emails. |
 * | `email/log [@user] [count]` | Lists logged emails, newest first. |
 * | `email/show <tracking-id>` | Shows one logged email. |
 * | `email/flush <days>` | Deletes logged emails older than the days, except pending emails. |
 * | `email/bounce [file]` | Processes a bounce message from the file, or from standard input. |
 *
 * `email/bounce` reads one raw message, so a mail server can pipe bounces to it, e.g.
 * an alias of `"|php /path/to/prado-cli.php email/bounce"`.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TShellEmailerAction extends TShellAction
{
	protected $action = 'email';
	protected $methods = ['send', 'log', 'show', 'flush', 'bounce'];
	protected $parameters = [null, null, ['tracking-id'], ['days'], null];
	protected $optional = [null, ['@user', 'count'], null, null, ['file']];
	protected $description = [
		'Manages the database email module.',
		'Sends the pending emails.',
		'Lists logged emails, newest first: \'@user\' for one user, and the count (default 20).',
		'Shows one logged email by tracking ID.',
		'Deletes logged emails older than the days, except pending emails.',
		'Processes a bounce message from the file, or from standard input.',
	];

	/** @var null|false|TDbEmailModule the module; false until resolved. */
	private $_emailer = false;

	/**
	 * Returns the module, set by the module or found by type.
	 * @return ?TDbEmailModule the module, null when the application has none.
	 */
	public function getEmailerModule()
	{
		if ($this->_emailer === false) {
			$this->_emailer = null;
			$app = $this->getApplication();
			foreach (array_keys($app->getModulesByType(TDbEmailModule::class, false)) as $id) {
				if (($module = $app->getModule($id)) instanceof TDbEmailModule) {
					$this->_emailer = $module;
					break;
				}
			}
		}
		if (!$this->_emailer) {
			$this->getWriter()->writeError('A ' . TDbEmailModule::class . ' is not found');
			return null;
		}
		return $this->_emailer;
	}

	/**
	 * @param ?TDbEmailModule $emailer the module.
	 */
	public function setEmailerModule($emailer)
	{
		$this->_emailer = $emailer;
	}

	/**
	 * Sends the pending emails.
	 * @param string[] $args the command line arguments.
	 * @return bool true.
	 */
	public function actionSend($args)
	{
		if ($module = $this->getEmailerModule()) {
			$count = $module->sendPendingEmails();
			$this->getWriter()->writeLine("Sent {$count} pending emails.", TShellWriter::GREEN);
		}
		return true;
	}

	/**
	 * Lists logged emails, newest first.
	 * @param string[] $args the command line arguments.
	 * @return bool true.
	 */
	public function actionLog($args)
	{
		if (!($module = $this->getEmailerModule())) {
			return true;
		}
		$username = null;
		$count = 20;
		foreach (array_slice($args, 1) as $arg) {
			if (str_starts_with($arg, '@')) {
				$username = substr($arg, 1);
			} elseif (is_numeric($arg)) {
				$count = (int) $arg;
			}
		}
		$rows = $module->getEmailLog($username, $count);
		if ($rows === false) {
			$this->getWriter()->writeError('Reading the email log is not permitted.');
			return true;
		}
		$writer = $this->getWriter();
		$writer->writeLine();
		foreach ($rows as $row) {
			$writer->write(date('Y-m-d H:i', (int) $row['created_time']) . '  ');
			$writer->write(str_pad($row['status'], 8) . '  ', $row['status'] === TDbEmailModule::STATUS_SENT ? TShellWriter::GREEN : TShellWriter::RED);
			$writer->writeLine($row['tracking_id'] . '  ' . $row['recipients'] . '  "' . $row['subject'] . '"');
		}
		if (!$rows) {
			$writer->writeLine('No logged emails.');
		}
		$writer->writeLine();
		return true;
	}

	/**
	 * Shows one logged email.
	 * @param string[] $args the command line arguments.
	 * @return bool true.
	 */
	public function actionShow($args)
	{
		if (!($module = $this->getEmailerModule())) {
			return true;
		}
		if (!($row = $module->getEmailByTrackingId($args[1] ?? ''))) {
			$this->getWriter()->writeError('No email has the tracking ID ' . ($args[1] ?? ''));
			return true;
		}
		$email = $module->createEmailFromRow($row);
		$writer = $this->getWriter();
		$writer->writeLine();
		foreach (['To' => $row['recipients'], 'Subject' => $row['subject'], 'Status' => $row['status'], 'Opens' => $row['open_count'], 'Clicks' => $row['click_count'], 'Bounce' => $row['bounce_status']] as $name => $value) {
			$writer->write(str_pad($name . ':', 10), TShellWriter::BOLD);
			$writer->writeLine((string) $value);
		}
		$writer->writeLine();
		$writer->writeLine((string) ($email->getTextBody() ?: $email->getBody()));
		$writer->writeLine();
		return true;
	}

	/**
	 * Deletes logged emails older than a number of days.
	 * @param string[] $args the command line arguments.
	 * @return bool true.
	 */
	public function actionFlush($args)
	{
		if (!($module = $this->getEmailerModule())) {
			return true;
		}
		if (!is_numeric($args[1] ?? null)) {
			$this->getWriter()->writeError('The days must be a number.');
			return true;
		}
		$count = $module->clearEmailLog((int) ($args[1] * 86400));
		if ($count === false) {
			$this->getWriter()->writeError('Deleting the email log is not permitted.');
		} else {
			$this->getWriter()->writeLine("Deleted {$count} logged emails.", TShellWriter::GREEN);
		}
		return true;
	}

	/**
	 * Processes one bounce message from a file or standard input.
	 * @param string[] $args the command line arguments.
	 * @return bool true.
	 */
	public function actionBounce($args)
	{
		if (!($module = $this->getEmailerModule())) {
			return true;
		}
		$file = $args[1] ?? null;
		if ($file !== null && !is_file($file)) {
			$this->getWriter()->writeError("The file {$file} is not found.");
			return true;
		}
		$message = (string) ($file !== null ? file_get_contents($file) : stream_get_contents($this->getInputStream()));
		$bounces = $module->processBounceMessage($message);
		foreach ($bounces as $bounce) {
			$this->getWriter()->writeLine($bounce->getRecipient() . ' ' . $bounce->getStatus() . ' ' . $bounce->getAction(), $bounce->getIsPermanent() ? TShellWriter::RED : TShellWriter::YELLOW);
		}
		$this->getWriter()->writeLine(count($bounces) . ' bounced recipients.');
		return true;
	}

	/**
	 * @return resource the stream a bounce message is read from when no file is given.
	 */
	protected function getInputStream()
	{
		return STDIN;
	}
}
