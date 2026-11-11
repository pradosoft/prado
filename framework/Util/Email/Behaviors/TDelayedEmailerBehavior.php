<?php

/**
 * TDelayedEmailerBehavior.php class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\Behaviors;

use Prado\Prado;
use Prado\Exceptions\TConfigurationException;
use Prado\Util\Cron\TDbCronModule;
use Prado\Util\Email\Cron\TSendEmailTask;

/**
 * TDelayedEmailerBehaviorclass.
 *
 * TDelayedEmailerBehavior plugs into TEmailModule to delay sending
 * emails until the Application is ending after the page is rendered
 * and sent to the user.  It makes use of TDbCronModule realtime tasks
 * to ensure emails are sent.
 *
 * This can be used with the basic TEmailModule but should not be
 * used with TDbEmailerModule SendEmailsOffline feature.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TDelayedEmailerBehavior extends \Prado\Util\TBehavior
{
	/** @var \Prado\Util\Cron\TDbCronModule|string */
	private $_cron;

	/**
	 * Adds TEmailModule::onSendMail handler to capture sent emails and delays
	 * them until TApplication::onEndRequest.  This makes use of the TDBCronModule
	 * realtime tasks to ensure emails are sent.  If the user cancels the page before
	 * the email is sent, the next DbCron process will send the email.
	 *
	 * If running in CLI, no delaying emails.
	 * @param \Prado\TComponent $owner
	 */
	public function attach($owner)
	{
		parent::attach($owner);
		if (php_sapi_name() === 'cli') {
			return;
		}
		$owner->attachEventHandler('onSendEmail', [$this, 'delayedEmailHandler'], -20);
		$owner->getApplication()->attachEventHandler('onEndRequest', [$this, 'removeEmailHandler'], -20);
	}

	/**
	 * this captures emails, sends them via DbCron realtime task at onEndRequest.
	 * This blocks all email handlers from further processing the email until
	 * the end of the request.
	 * @param \Prado\Util\Email\TEmailModule $sender
	 * @param \Prado\Util\Email\TEmailEventPramater $param
	 */
	public function delayedEmailHandler($sender, $param)
	{
		if (!is_object($param) || !$this->getEnabled()) {
			return null;
		}
		$cron = $this->getCronModule();
		$sendTask = new TSendEmailTask($param->getEmail());
		$sendTask->setSchedule('@' . (time() + 3)); //wait for page to send until cron kicks in.
		$cron->addTask($sendTask, true);

		return true;
	}

	/**
	 * removes the email delay handler to allow for normal email processing.
	 * @param \Prado\Util\Email\TEmailModule $sender
	 * @param \Prado\Util\Email\TEmailEventPramater $param
	 */
	public function removeEmailHandler($sender, $param)
	{
		$this->getOwner()->detachEventHandler('onSendEmail', [$this, 'delayedEmailHandler']);
	}


	/**
	 * @return null|\Prado\Util\Cron\TDbCronModule returns the Cron Module of the applications
	 */
	public function getCronModule()
	{
		if ($this->_cron === null) {
			$app = Prado::getApplication();
			$modules = $app->getModulesByType('Prado\\Util\\Cron\\TDbCronModule');
			foreach ($modules as $id => $m) {
				if ($this->_cron = $app->getModule($id)) {
					break;
				}
			}
		} elseif (is_string($this->_cron)) {
			$app = Prado::getApplication();
			$this->_cron = $app->getModule($this->_cron);
		}

		if (!$this->_cron) {
			throw new TConfigurationException('delayemailer_no_cron_found');
		}
		if (!($this->_cron instanceof TDbCronModule)) {
			throw new TConfigurationException('delayemailer_not_cron');
		}
		return $this->_cron;
	}

	/**
	 * @param null|\Prado\Util\Cron\TDbCronModule $cron sets the Cron Module
	 */
	public function setCronModule($cron)
	{
		$this->_cron = $cron;
	}
}
