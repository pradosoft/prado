<?php
/**
 * TDbEmailerModule class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services;

use Prado\TApplication;
use Prado\TApplicationConfiguration;
use Prado\Prado;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Security\Permissions\IPermissions;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Xml\TXmlElement;

/**
 * TDbEmailModule class.
 *
 * TDbEmailerModule provides email logging, tracking, and sending emails offline.
 *
 * When specified, this module configures the application with an 'email' service,
 * {@link TDbEmailService} for accessing specific emails by the web and for tracking
 * the viewing of an email.
 *
 * <code>
 *   <module id="dbEmailer" class="">
 *		<service id="email" />
 *	 </module>
 *
 * </code>
 * 
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TDbEmailModule extends TEmailModule implements IDbModule, IPermissions
{
	public const EMAIL_SERVICE = 'email';
	
	private $_logging = true;
	
	private $_offlineEmail = false;
	
	private $_serviceID = '';
	
	// Database function
	// log
	// sent field, null is not sent
	// secure (no reading from the web without credentials)
	// $many to $options = ['many' => bool, 'secure' => bool]
	// shell for: sending pending emails, show pending, display email log (w/ name and per user), display specific logged email
	// resend last email of user from email path.
	// db email service: view any non-secure email via trackingId, secure emails requires login, Log viewing of email via trackingID on image url.
	// Prado::log 
	/**
	 * @param Prado\Xml\TXmlElement|array $config
	 */
	public function init($config)
	{
		$properties = [];
		if(is_array($config)) {
			$service =  $config['service'] ?? null;
			$properties = $config['service'] ?? [];
			unset($config['service']);
		} elseif($config instanceof TXmlElement) {
			$service = $config->getElementByTagName('service');
			if($service)
				$properties = array_change_key_case($service->getAttributes()->toArray());
		}

		$this->_serviceID = $properties['id'] ?? ($service ? self::EMAIL_SERVICE : null);
		parent::init($config);

		if($this->_serviceID) {
			$properties['EmailerModule'] = $this;
			$emailServiceConfig = new TApplicationConfiguration();
			$emailServiceConfig->loadFromPhp(['services'=> [$this->_serviceID => ['class' => 'Prado\\Util\\Email\\TDbEmailService', 'properties' => $properties]]]);
			$this->getApplication()->applyConfiguration($emailServiceConfig, true);
		}

		if ($this->dyRegisterShellAction(false) !== true && ($app = $this->getApplication()) instanceof \Prado\Shell\TShellApplication) {
			$app->addShellActionClass(['class' => 'Prado\\Util\\Email\\TShellEmailerAction', 'CronModule' => $this]);
		}
	}
	
	/**
	 * the global event handling requests for cron task info
	 * @param TDbCronModule $cron
	 * @param null $param
	 * @return \Prado\Util\Cron\TCronTask[]
	 */
	public function fxGetCronTaskInfos($cron, $param)
	{
		return [
				new TCronTaskInfo('sendmail', 'Prado\\Util\\Email\\TDbEmailSenderTask', $this->getId(), Prado::localize('DbCron Clean Log Task'), Prado::localize('Clears the database of cron log items before the specified time period.'))
			];
	}
	
	/**
	 * Implements IPermissions for permissions on dyGetCronLogCount, dyClearCronLog, dyRemoveCronLogItem,
	 * dyAddTask, dyUpdateTask, dyRemoveTask
	 * @param \Prado\Security\Permissions\TPermissionsManager $manager
	 * @return \Prado\Security\Permissions\TPermissionEvent[]
	 */
	public function getPermissions($manager)
	{
		$userIsOwnerAllowedRule = new TUserOwnerRule();
		return array_merge([
			new TPermissionEvent(static::PERM_EMAIL_SHELL, 'Activates email shell commands.', 'dyRegisterShellAction')
			new TPermissionEvent(static::PERM_EMAIL_LOG_READ, 'Email read Db log.', ['dyGetEmailLog', 'dyGetEmailLogCount'], $userIsOwnerAllowedRule),
			new TPermissionEvent(static::PERM_EMAIL_LOG_DELETE, 'Email delete Db log.', ['dyClearEmailLog', 'dyRemoveEmailLogItem'], $userIsOwnerAllowedRule)
		], parent::getPermissions($manager));
	}
	
	/**
	 * Raises the onViewEmail event. This is raised when a user views their email with a {@link
	 * TEmailTrackerImage} control in the TEmail.  The Email tracker image URL is loaded from the {@link 
	 * TDbEmailService} which flags the email as viewed and raises this event.
	 *
	 * The event $param is the TEmail.
	 *
	 * @param \Prado\Util\Email\UI\TEmail $email The Email being sent.
	 * @return array results of raising the onViewEmail event.
	 */
	public function onViewEmail($email)
	{
		return $this->raiseEvent('onViewEmail', $this, $email);
	}
	
	public function getEmailByTrackerId()
	{
		
	}
	
	public function getEmailByUID()
	{
		
	}
	
	public function getUserEmailsCount()
	{
		
	}
	
	public function getUserEmails()
	{
		
	}
	
	public function getPendingEmails()
	{
		
	}
	public function sendPendingEmails()
	{
		
	}
	
	/**
	 * Creates the DB connection. If no ConnectionId is provided, then this
	 * creates a sqlite database in runtime named 'cron.jobs'.
	 * @throws TConfigurationException if module ID is invalid or empty
	 * @return \Prado\Data\TDbConnection the created DB connection
	 */
	protected function createDbConnection()
	{
		if ($this->_connID !== '') {
			$config = $this->getApplication()->getModule($this->_connID);
			if ($config instanceof TDataSourceConfig) {
				return $config->getDbConnection();
			} else {
				throw new TConfigurationException('dbcron_connectionid_invalid', $this->_connID);
			}
		} else {
			$db = new TDbConnection();
			// default to SQLite3 database
			$dbFile = $this->getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . 'cron.jobs';
			$db->setConnectionString('sqlite:' . $dbFile);
			return $db;
		}
	}
	
	/**
	 * @return \Prado\Data\TDbConnection the DB connection instance
	 */
	public function getDbConnection()
	{
		if ($this->_conn === null) {
			$this->_conn = $this->createDbConnection();
			$this->_conn->setActive(true);
		}
		return $this->_conn;
	}
	
	/**
	 * @return null|string the ID of a {@link TDataSourceConfig} module. Defaults to empty string, meaning not set.
	 */
	public function getConnectionID()
	{
		return $this->_connID;
	}
	
	/**
	 * Sets the ID of a TDataSourceConfig module.
	 * The datasource module will be used to establish the DB connection for this cron module.
	 * @param string $value ID of the {@link TDataSourceConfig} module
	 * @throws TInvalidOperationException when trying to set this property but the module is already initialized.
	 */
	public function setConnectionID($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('dbcron_property_unchangeable', 'ConnectionID');
		}
		$this->_connID = $value;
	}
	
	/**
	 * @return bool should tasks that run be logged, default true
	 */
	public function getLogCronTasks()
	{
		return $this->_logCronTasks;
	}
	
	/**
	 * @param bool $log should tasks that run be logged
	 */
	public function setLogCronTasks($log)
	{
		$this->_logCronTasks = TPropertyValue::ensureBoolean($log);
	}
	
	/**
	 * @return string table in the database for cron tasks and logs. Defaults to 'crontabs'
	 */
	public function getTableName()
	{
		return $this->_tableName;
	}
	
	/**
	 * @param string $table table in the database for cron tasks and logs
	 * @throws TInvalidOperationException when trying to set this property but the module is already initialized.
	 */
	public function setTableName($table)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('dbcron_property_unchangeable', 'TableName');
		}
		$this->_tableName = TPropertyValue::ensureString($table);
	}
	
	/**
	 * @return bool whether the cron DB table should be automatically created if not exists. Defaults to true.
	 * @see setTableName
	 */
	public function getAutoCreateCronTable()
	{
		return $this->_autoCreate;
	}
	
	/**
	 * @param bool $value whether the cron DB table should be automatically created if not exists.
	 * @throws TInvalidOperationException when trying to set this property but the module is already initialized.
	 * @see setTableName
	 */
	public function setAutoCreateCronTable($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('dbcron_property_unchangeable', 'AutoCreateCronTable');
		}
		$this->_autoCreate = TPropertyValue::ensureBoolean($value);
	}
}
