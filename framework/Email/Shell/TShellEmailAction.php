<?php

/**
 * TShellEmailAction class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Shell;

use Prado\Email\Store\IEmailStore;
use Prado\Email\TEmail;
use Prado\Email\TEmailAddress;
use Prado\Email\TEmailModule;
use Prado\Email\TEmailStatus;
use Prado\Shell\TShellAction;
use Prado\Shell\TShellWriter;

/**
 * TShellEmailAction class.
 *
 * TShellEmailAction is the "email" action of the PRADO shell, for the email
 * module of the application.
 *
 * | Command | Does |
 * |---|---|
 * | `email/send` | delivers the queued emails |
 * | `email/log [@user] [count]` | lists stored emails, newest first |
 * | `email/show <tracking-id>` | shows one stored email |
 * | `email/flush <days>` | deletes stored emails older than the days, except queued ones |
 * | `email/bounce [file]` | processes a bounce message from the file or standard input |
 * | `email/test <address>` | sends a test email to the address |
 *
 * `email/bounce` reads standard input so a mail server can pipe the bounces of the
 * Return-Path address to it.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TShellEmailAction extends TShellAction
{
	protected $action = 'email';
	protected $methods = ['send', 'log', 'show', 'flush', 'bounce', 'test'];
	protected $parameters = [null, null, ['tracking-id'], ['days'], null, ['address']];
	protected $optional = [null, ['@user', 'count'], null, null, ['file'], null];
	protected $description = [
		'Manages the emails of the email module.',
		'Delivers the queued emails.',
		'Lists stored emails, newest first: \'@user\' for one user, and the count (default 20).',
		'Shows one stored email by tracking ID.',
		'Deletes stored emails older than the days, except queued emails.',
		'Processes a bounce message from the file, or from standard input.',
		'Sends a test email to the address.',
	];

	/** @var null|false|TEmailModule the module; false until it is looked for. */
	private $_module = false;

	/**
	 * Returns the email module, the one set or the first of the application.
	 * @return ?TEmailModule the module, null with an error line when there is none.
	 */
	public function getEmailModule(): ?TEmailModule
	{
		if ($this->_module === false) {
			$this->_module = null;
			$app = $this->getApplication();
			foreach (array_keys($app->getModulesByType(TEmailModule::class)) as $id) {
				if (($module = $app->getModule($id)) instanceof TEmailModule) {
					$this->_module = $module;
					break;
				}
			}
		}
		if ($this->_module === null) {
			$this->getWriter()->writeError('A ' . TEmailModule::class . ' is not found.');
		}
		return $this->_module;
	}

	/**
	 * @param ?TEmailModule $module the email module.
	 */
	public function setEmailModule(?TEmailModule $module)
	{
		$this->_module = $module;
	}

	/**
	 * @return ?IEmailStore the store of the module, null with an error line when there is none.
	 */
	protected function getStore(): ?IEmailStore
	{
		if (($module = $this->getEmailModule()) === null) {
			return null;
		}
		if (($store = $module->getStore()) === null) {
			$this->getWriter()->writeError('The email module has no store.');
		}
		return $store;
	}

	/**
	 * Delivers the queued emails.
	 * @param array $args the command arguments.
	 * @return bool true.
	 */
	public function actionSend($args)
	{
		if ($this->getStore() !== null) {
			$module = $this->getEmailModule();
			try {
				$count = $module->sendQueued();
			} finally {
				$module->close();
			}
			$this->getWriter()->writeLine("Sent {$count} queued emails.", TShellWriter::GREEN);
		}
		return true;
	}

	/**
	 * Lists stored emails, newest first.
	 * @param array $args the command arguments: "@user" and a count.
	 * @return bool true.
	 */
	public function actionLog($args)
	{
		if (($store = $this->getStore()) === null) {
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
		$writer = $this->getWriter();
		$emails = $store->findEmails($username, null, $count);
		if ($emails === false) {
			$writer->writeError('Reading the email log is not permitted.');
			return true;
		}
		$writer->writeLine();
		foreach ($emails as $info) {
			$writer->write(date('Y-m-d H:i', $info['created_time']) . '  ');
			$writer->write(str_pad($info['status'], 10) . '  ', $info['status'] === TEmailStatus::Sent ? TShellWriter::GREEN : TShellWriter::RED);
			$writer->writeLine($info['tracking_id'] . '  "' . $info['subject'] . '"');
		}
		if (!$emails) {
			$writer->writeLine('No stored emails.');
		}
		$writer->writeLine();
		return true;
	}

	/**
	 * Shows one stored email.
	 * @param array $args the command arguments: the tracking ID.
	 * @return bool true.
	 */
	public function actionShow($args)
	{
		if (($store = $this->getStore()) === null) {
			return true;
		}
		$id = (string) ($args[1] ?? '');
		if (($info = $store->getEmailInfo($id)) === null || ($email = $store->getEmail($id)) === null) {
			$this->getWriter()->writeError('No email has the tracking ID ' . $id);
			return true;
		}
		$writer = $this->getWriter();
		$fields = [
			'To' => TEmailAddress::formatList($email->getTo()),
			'Subject' => $info['subject'],
			'Status' => $info['status'] . ($info['error_text'] ? ' (' . $info['error_text'] . ')' : ''),
			'Attempts' => $info['attempts'],
			'Opens' => $info['open_count'],
			'Clicks' => $info['click_count'],
		];
		foreach ($info['recipients'] as $recipient) {
			if ($recipient['bounce_status'] !== null) {
				$fields['Bounce'] = $recipient['address'] . ' ' . $recipient['bounce_status'] . ' ' . $recipient['bounce_diagnostic'];
			}
		}
		$writer->writeLine();
		foreach ($fields as $name => $value) {
			$writer->write(str_pad($name . ':', 10), TShellWriter::BOLD);
			$writer->writeLine((string) $value);
		}
		$writer->writeLine();
		$writer->writeLine((string) ($email->getText() ?? $email->getHtml()));
		$writer->writeLine();
		return true;
	}

	/**
	 * Deletes stored emails older than a number of days.
	 * @param array $args the command arguments: the days.
	 * @return bool true.
	 */
	public function actionFlush($args)
	{
		if (($store = $this->getStore()) === null) {
			return true;
		}
		if (!is_numeric($args[1] ?? null)) {
			$this->getWriter()->writeError('The days must be a number.');
			return true;
		}
		$count = $store->deleteEmails((int) ($args[1] * 86400));
		if ($count === false) {
			$this->getWriter()->writeError('Deleting from the email log is not permitted.');
		} else {
			$this->getWriter()->writeLine("Deleted {$count} stored emails.", TShellWriter::GREEN);
		}
		return true;
	}

	/**
	 * Processes one bounce message from a file or standard input.
	 * @param array $args the command arguments: an optional file.
	 * @return bool true.
	 */
	public function actionBounce($args)
	{
		if (($module = $this->getEmailModule()) === null) {
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
	 * Sends a test email, which shows that the module, its From, and its transport work.
	 * @param array $args the command arguments: the address.
	 * @return bool true.
	 */
	public function actionTest($args)
	{
		if (($module = $this->getEmailModule()) === null) {
			return true;
		}
		$writer = $this->getWriter();
		try {
			$email = new TEmail();
			$email->setTo((string) ($args[1] ?? ''));
			$email->setSubject('PRADO email test');
			$email->setText('This is a test email from the PRADO email module.');
			$status = $module->sendEmail($email);
			$module->close();
		} catch (\Throwable $e) {
			$writer->writeError($e->getMessage());
			return true;
		}
		if ($status === TEmailStatus::Failed) {
			$writer->writeError('The test email failed: ' . $email->getError());
		} else {
			$writer->writeLine('The test email is ' . strtolower($status) . '.', TShellWriter::GREEN);
		}
		return true;
	}

	/**
	 * @return resource the stream that a piped bounce message is read from.
	 */
	protected function getInputStream()
	{
		return STDIN;
	}
}
