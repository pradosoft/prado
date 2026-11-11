<?php

/**
 * TEmailModule class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

/*TODO:
 - Active Controls throw error.  addParsedObject not getting objects properly?
 - TDbEmailModule (log emails, TDbEmailService for viewing emails)
 - TEmailTrackerImage - img tag with url to email service to message ID
 - TEmailViewLink - Link to email service with message Id
 - unit tests
 - php Docs
*/

namespace Prado\Util\Email;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TPropertyValue;
use Prado\TEventResults;
use Prado\Util\Email\UI\TEmailPage;
use Prado\Util\Email\TEmail;
use Prado\Util\Email\Behaviors\TEmailerBehavior;
use Prado\Exceptions\THttpException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TApplicationMode;
use Prado\Web\UI\TTemplateManager;

/**
 * TEmailModule class.
 *
 * TEmailModule provides basic email services for creating and sending emails.
 * Default properties for TEmails can be configured similarly to how TPageService
 * configures TPage properties. The TEmailerBehavior is attached to TApplication
 * for system wide functionality; providing createEmail, sendEmail, and sendMail
 * functionality.
 *
 * The TEmailModule acts like an email service similar to TPageService but
 * without dependency upon Request, Response, and other interactive components.
 *
 * TEmailPage is the subclass of TPage and is a passthrough to a contained
 * TEmail.  The Templates loads the TEmail through the TEmailPage.
 * TEmail can receive default values, like MasterClass, From, and Subject through
 * embedded directive configurations.
 *
 * Emails are in a folder called "Emails" in the application folder and accessed
 * by path.  eg.  "Help.Welcome"  Email templates end with '.email' and are loaded
 * along with its associated PHP class.  A text version of the message is stored in
 * the ".text" file of the same name.  Call function createEmail with the Email
 * and directory Path to load the Email and configurations.
 *
 * TEmail has a built in Send() method that calls this module sendMail to send the email.
 *
 * Alternate Email delivery methods, like Symphony Mailer or PHPMailer, can have PRADO
 * behaviors with handlers to onSendMail to take over from the default handler (the
 * PHP mail() function).
 *
 * Here is an example configuration.
 * <modules>
 *    <module id="emailer" class="Prado\Util\Email\TEmailModule" DefaultBccAddress="debug@website.com, manager@website.com">
 *		<emails From="admin@mycompany.com" MasterClass="Application.Layouts.DefaultEmailLayout" AbsoluteUrl="https://www.absoluteurl.com/">
 *		   <email id="*" ReturnPath="noreply@mycompany.com" />
 *		   <email id="Help/*" From="help@mycompany.com" MasterClass="Application.Layouts.Help'`EmailLayout" />
 *		</emails>
 *    </module>
 *    ...
 * </modules>
 *
 * The default Email sending behavior is the {@link TNativeMailBehavior} that sends
 * emails using the native PHP mail() function.  The included SMTP mailer behavior
 * wrapper for PHPMail is {@link TPhpMalierBehavior}.  It is attached with the
 * TBehaviorsModule, eg:
 *
 * <code>
 *	<module id="behaviors" class="Prado\Util\TBehaviorsModule">
 *	 <behavior name="phpMailer" Class="Prado\Util\Email\Behaviors\TPhpMailerBehavior"
 *		AttachTo="module:emailer" DNS="tls+smtp://username%40google.com:SMTP-password@smtp.mywebsite.org" />
 *  </module>
 * </code>
 *
 * For TPhpMailer to work, the PHPMailer must be added (required) in Composer to
 * the application.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TEmailModule extends \Prado\TModule
{
	/**
	 * the behavior name
	 */
	public const EMAILER_BEHAVIOR_NAME = 'emailer';
	/**
	 * Configuration file name
	 */
	public const CONFIG_FILE_XML = 'config.xml';
	/**
	 * Configuration file name
	 */
	public const CONFIG_FILE_PHP = 'config.php';
	/**
	 * Default base path
	 */
	public const DEFAULT_BASEPATH = 'Emails';
	/**
	 * Prefix of ID used for storing parsed configuration in cache
	 */
	public const CONFIG_CACHE_PREFIX = 'prado:emailservice:';
	/**
	 * Page template file extension
	 */
	public const EMAIL_FILE_EXT = '.email';
	/**
	 * Page Text-Only file extension
	 */
	public const EMAIL_TEXT_FILE_EXT = '.text';
	/**
	 * Prefix of Pages used for instantiating new pages
	 */
	public const EMAIL_NAMESPACE_PREFIX = 'Application\\Emails\\';
	/**
	 * @var string root path of pages
	 */
	private $_basePath;
	/**
	 * @var string base path class in namespace format
	 */
	private $_baseEmailClass = '\\Prado\\Util\\Email\\UI\\TEmailPage';
	/**
	 * @var bool whether the Module is initialized
	 */
	private $_initialized = false;
	/**
	 * @var TTemplateManager template manager
	 */
	private $_templateManager;

	/** @var array the common Email Properties. */
	private $_emailConfig;

	/** @var null|string Emails all have this added to the Cc field  */
	protected $_defaultCcAddress;

	/** @var null|string Emails all have this added to the Bcc field  */
	protected $_defaultBccAddress;

	/** @var string the Additional Parameters for the default PHP Mailer */
	private $_mailAdditionalParameters = '';

	/**
	 * this may be called after being attached
	 * @param array|\Prado\Xml\TXmlElement $config app configuration
	 */
	public function init($config)
	{
		$app = $this->getApplication();
		$this->attachBehavior(self::EMAILER_BEHAVIOR_NAME, ['class' => '\Prado\Util\Email\Behaviors\TNativeMailBehavior']);
		$app->attachBehavior(self::EMAILER_BEHAVIOR_NAME, ['class' => '\Prado\Util\Email\Behaviors\TEmailerBehavior', 'EmailerModule' => $this]);

		$this->_emailConfig = $this->loadEmailConfig($config);
		$this->_initialized = true;
	}

	/**
	 * Collects configuration for a Email.
	 * @param array|\Prado\Xml\TXmlElement $config additional configuration specified in the application configuration
	 * @return TEmailConfiguration
	 */
	protected function loadEmailConfig($config)
	{
		$application = $this->getApplication();
		$pagePath = '';
		$pageConfig = null;
		if (($cache = $application->getCache()) === null) {
			$pageConfig = new TEmailConfiguration($pagePath);
			if ($config !== null) {
				if ($application->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
					$pageConfig->loadPageConfigurationFromPhp($config, $application->getBasePath(), '');
				} else {
					$pageConfig->loadPageConfigurationFromXml($config, $application->getBasePath(), '');
				}
			}
			$pageConfig->loadFromFiles($this->getBasePath());
		} else {
			$configCached = true;
			$currentTimestamp = [];
			$arr = $cache->get(self::CONFIG_CACHE_PREFIX . $this->getID() . $pagePath);
			if (is_array($arr)) {
				[$pageConfig, $timestamps] = $arr;
				if ($application->getMode() !== TApplicationMode::Performance) {
					foreach ($timestamps as $fileName => $timestamp) {
						if ($fileName === 0) { // application config file
							$appConfigFile = $application->getConfigurationFile();
							$currentTimestamp[0] = $appConfigFile === null ? 0 : @filemtime($appConfigFile);
							if ($currentTimestamp[0] > $timestamp || ($timestamp > 0 && !$currentTimestamp[0])) {
								$configCached = false;
							}
						} else {
							$currentTimestamp[$fileName] = @filemtime($fileName);
							if ($currentTimestamp[$fileName] > $timestamp || ($timestamp > 0 && !$currentTimestamp[$fileName])) {
								$configCached = false;
							}
						}
					}
				}
			} else {
				$configCached = false;
				$paths = explode('.', $pagePath);
				$configPath = $this->getBasePath();
				$fileName = $this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP
					? self::CONFIG_FILE_PHP
					: self::CONFIG_FILE_XML;
				foreach ($paths as $path) {
					$configFile = $configPath . DIRECTORY_SEPARATOR . $fileName;
					$currentTimestamp[$configFile] = @filemtime($configFile);
					$configPath .= DIRECTORY_SEPARATOR . $path;
				}
				$appConfigFile = $application->getConfigurationFile();
				$currentTimestamp[0] = $appConfigFile === null ? 0 : @filemtime($appConfigFile);
			}
			if (!$configCached) {
				$pageConfig = new TEmailConfiguration($pagePath);
				if ($config !== null) {
					if ($application->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
						$pageConfig->loadPageConfigurationFromPhp($config, $application->getBasePath(), '');
					} else {
						$pageConfig->loadPageConfigurationFromXml($config, $application->getBasePath(), '');
					}
				}
				$pageConfig->loadFromFiles($this->getBasePath());
				$cache->set(self::CONFIG_CACHE_PREFIX . $this->getID() . $pagePath, [$pageConfig, $currentTimestamp]);
			}
		}
		return $pageConfig;
	}

	/**
	 * Initializes page context.
	 * Page context includes path alias settings, namespace usages,
	 * parameter initialization, module loadings, page initial properties
	 * and authorization rules.
	 * @param string $path the path to the Email. eg "Help.Welcome"
	 * @param TEmail $email the email to apply the configuration upon.
	 * @param TEmailConfiguration $emailConfig
	 */
	protected function initEmailContext($path, $email, $emailConfig)
	{
		$this->applyEmailConfiguration($path, $email, $emailConfig);

		$config = new TEmailConfiguration($path);
		$config->loadFromFiles($this->getBasePath());
		$this->applyEmailConfiguration($path, $email, $config);
	}

	/**
	 * Applies a email configuration.
	 * @param TEmailConfiguration $config the configuration
	 * @param string $pagePath the Path of the page in App format in relation to "Emails" directory.
	 * @param \Prado\Util\Email\TEmail $email the email receiving the configuration
	 */
	protected function applyEmailConfiguration($pagePath, $email, $config)
	{
		foreach ($config->getEmailProperties($pagePath) as $name => $value) {
			$email->setSubProperty($name, $value);
		}

		// external configurations
		foreach ($config->getExternalConfigurations() as $filePath => $params) {
			[$configPagePath, $condition] = $params;
			if ($condition !== true) {
				$condition = $this->evaluateExpression($condition);
			}
			if ($condition) {
				if (($path = Prado::getPathOfNamespace($filePath, Prado::getApplication()->getConfigurationFileExt())) === null || !is_file($path)) {
					throw new TConfigurationException('emailer_includefile_invalid', $filePath);
				}
				$c = new TEmailConfiguration($pagePath);
				$c->loadFromFile($path, $configPagePath);
				$this->applyEmailConfiguration($pagePath, $email, $c);
			}
		}
		$config->dyApplyEmailConfiguration($email);
	}

	/**
	 * Finds a localized template file.
	 * @param string $filename text file.
	 * @param string $culture culture string, null to use current culture
	 * @return null|string a localized template file if found, null otherwise.
	 */
	protected function getLocalizedFile($filename, $culture = null)
	{
		if (($app = $this->getApplication()->getGlobalization(false)) === null) {
			return is_file($filename) ? $filename : null;
		}
		foreach ($app->getLocalizedResource($filename, $culture) as $file) {
			if (($file = realpath($file)) !== false && is_file($file)) {
				return $file;
			}
		}
		return null;
	}

	/**
	 *
	 *
	 * @param string $emailPath path to email in Email directory
	 * @param string $to address the email is going to, default null
	 * @param string $culture culture string, null to use current culture
	 */
	public function createEmail($emailPath, $to = null, $culture = null)
	{
		$path = $this->getBasePath() . DIRECTORY_SEPARATOR . strtr($emailPath, '.', DIRECTORY_SEPARATOR);
		$hasTemplateFile = is_file($path . self::EMAIL_FILE_EXT);
		$hasTextFile = is_file($path . self::EMAIL_TEXT_FILE_EXT);
		$hasClassFile = is_file($path . Prado::CLASS_FILE_EXT);

		if (!$hasTemplateFile && !$hasClassFile) {
			$paths = $this->onAdditionalEmailPaths($emailPath);
			$applicationPath = Prado::getPathOfAlias('Application') ?? '';
			$throwException = true;
			foreach ($paths as $path) {
				if (stripos($path, $applicationPath) !== 0) {
					throw new THttpException(403, 'emailermodule_security_violation', $path);
				}
				$hasTemplateFile = is_file($path . self::EMAIL_FILE_EXT);
				$hasTextFile = is_file($path . EMAIL_TEXT_FILE_EXT);
				$hasClassFile = is_file($path . Prado::CLASS_FILE_EXT);
				if ($hasTemplateFile || $hasClassFile) {
					$throwException = false;
					break;
				}
			}
			if ($throwException) {
				throw new THttpException(404, 'emailermodule_page_unknown', $emailPath);
			}
		}

		if ($hasClassFile) {
			$className = basename($path);
			$namespacedClassName = static::EMAIL_NAMESPACE_PREFIX . str_replace('.', '\\', $emailPath);

			if (!class_exists($className, false) && !class_exists($namespacedClassName, false)) {
				include_once($path . Prado::CLASS_FILE_EXT);
			}

			if (!class_exists($className, false)) {
				$className = $namespacedClassName;
			}
		} else {
			$className = $this->getBaseEmailClass();
			Prado::using($className);
			if (($pos = strrpos($className, '.')) !== false) {
				$className = substr($className, $pos + 1);
			}
		}

		if ($className !== '\Prado\Util\Email\UI\TEmailPage' && !is_subclass_of($className, '\Prado\Util\Email\UI\TEmailPage')) {
			throw new TConfigurationException('emailermodule_page_unknown', $emailPath);
		}

		$email = Prado::createComponent($className);
		$email->setEmailerModule($this);
		$email->setPagePath($emailPath);

		if ($hasTemplateFile) {
			$email->setTemplate($this->getTemplateManager()->getTemplateByFileName($path . self::EMAIL_FILE_EXT, '\Prado\Util\Email\UI\TEmailTemplate', $culture));
		}
		$this->initEmailContext($emailPath, $email, $this->_emailConfig);
		if ($hasTextFile) {
			$fileName = $this->getLocalizedFile($path . self::EMAIL_TEXT_FILE_EXT, $culture);
			$email->setTextBody(file_get_contents($fileName));
		}
		if ($to) {
			$email->setTo($to);
		}

		return $email;
	}

	/**
	 *
	 * @param \Prado\Util\Email\TEmail $email
	 * @param null|array substitution key-values.
	 * @param bool $many
	 * @param mixed $customData
	 */
	public function sendEmail($email, $customData, $many = false)
	{
		if (!is_object($email) || !($email instanceof TEmail)) {
			throw new TConfigurationException("emailermodule_not_an_email");
		}
		$email->setCustomData($customData);
		if ($email instanceof \Prado\Util\Email\UI\TEmailPage) {
			echo("getting Email\n");
			$email = $email->getEmail();
		}

		$html = $email->getBody();
		$message = $email->getTextBody();

		$subject = $email->getSubject() ?? '';
		if (strpos($subject, "\n") !== false || strpos($subject, "\r") !== false) {
			return false;
		}

		// Is the email html?
		$isHTML = ($html != '') && ($message != '');
		if (!$isHTML && preg_match('/(<html\W*?>|<body\W*?>)/', ($html != '') ? $html : $message)) {
			$isHTML = true;
			$html = ($html != '') ? $html : $message;
			$message = null;
		}
		if ($isHTML && $message === null) {
			$email->setTextBody(TEmail::htmlToText($html));
		}
		$email->setIsHtml($isHTML);

		if ($email->getUsername() == null) {
			$user = $this->getApplication()->getUser();
			if ($user && !$user->getIsGuest()) {
				$email->setUsername($user->getName());
			}
		}

		//fancy up the headers if they aren't
		if (!($from = $email->getFrom()) && ($nfrom = ini_get('sendmail_from'))) {
			$email->setFrom($from = $nfrom);
		} elseif (!$from) {
			throw new TConfigurationException('emailermodule_from_required');
		}
		if (!($sender = $email->getSender())) {
			$email->setSender($from);
		}
		if (!$email->getReturnPath() && ($replyto = $email->getReplyTo()) && ($replyto != $email->getFrom())) {
			$email->setReturnPath($replyto);
		}
		if (!$email->getContentType() && $isHTML) {
			$email->getContentType('text/html');
		}
		if ($dcc = $this->getDefaultCcAddress()) {
			$cc = $email->getCc();
			$email->setCc(strlen($cc) ? $cc . ', ' . $dcc : $dcc);
		}
		if ($dbcc = $this->getDefaultBccAddress()) {
			$bcc = $email->getBcc();
			$email->setBcc(strlen($bcc) ? $bcc . ', ' . $dbcc : $dbcc);
		}
		if (!($mid = $email->getMessageId())) {
			$email->setMessageId($mid = sha1((string) microtime(true)));
		}

		$result = $this->onSendEmail($email, $many);

		if (count($result = array_filter($result, function ($a) {
			return $a !== null && !is_object($a);
		}))) {
			return array_reduce($result, function ($a, $b) {
				return $a | $b;
			}, 0);
		}
	}

	/**
	 * Raises the onSendEmail event.  This uses EventResults::EVENT_RESULT_FEED_FORWARD, so
	 * return values that are not null becomes the new parameter.  This can be used to stop
	 * further processing of the email.
	 *
	 * The $param is a TEmailEventParameter with an the TEmail and are many emails being processed.
	 * The parameter can be filtered, logged, sent, or otherwise processed by event handlers.
	 *
	 * If the handlers sends the email, return true to stop further processing. If the handler has
	 * an error, it can return false to stop further processing and return that the email was not
	 * sent.
	 *
	 * @param \Prado\Util\Email\TEmail $email The Email being sent.
	 * @param bool $many
	 * @return array results of raising the onSendMail event.
	 */
	public function onSendEmail($email, $many)
	{
		return $this->raiseEvent('onSendEmail', $this, new TEmailEventParameter($email, $many), TEventResults::EVENT_RESULT_FEED_FORWARD);
	}

	/**
	 * This method places no-cache meta in the head.
	 * @param array<string>|string $to The email To Address(es).
	 * @param string $subject Subject of the Email, no returns
	 * @param array<string>|string $message The string message or an array,
	 *  where [0] is the html string message and [1] is text message without html.
	 * @param array $headers Email Headers, arary keys are Header names and array values are
	 *  the Header Values.
	 * @param array $attachments File paths to the attachment, the data itself, or
	 * array('path'=>'/path/to/file/file.jpg','mime'=>'image/jpeg','data'=>$filedata, 'name'=>'filename.jpg').
	 * Keys are the new "cid" reference email [file] names or can be specified in the array.
	 * 'name' is required. 'data' and 'mime' is optional. either 'path' or 'data' must be provided.
	 * @param bool $many is this one of many emails, default false.
	 */
	public function sendMail($to, $subject, $message, $headers = [], $attachments = [], $many = false)
	{
		$email = new TEmail();

		$email->setTo($to);
		$email->setSubject($subject);

		$headers ??= [];
		$attachments ??= [];

		if (is_string($headers)) {
			$new = [];
			foreach (preg_split("/[\n\r]{1,2}/", $headers) as $header) {
				if (!$header) {
					continue;
				}
				$header = preg_split("/\:\s*/", $header);
				$new[$header[0]] = $header[1];
			}
			$headers = $new;
		}

		// Is the email html?
		if (is_array($message)) {
			if ($isHTML = count($message) > 1) {
				$email->setBody($message[0]);
				$email->setTextBody($message[1]);
			} else {
				$email->setBody($message[0]);
			}
		} else {
			$email->setBody($message);
		}

		$email->setHeaders($headers);

		foreach ($attachments as $name => $file) {
			$email->addAttachment($name, $file);
		}
		return $this->sendEmail($email, null, $many);
	}

	/**
	 *
	 */
	public function closeConnection()
	{
		$this->dyCloseConnection();
	}

	/**
	 * @return string the root directory for storing pages. Defaults to the 'Emails' directory under the application base path.
	 */
	public function getBasePath()
	{
		if ($this->_basePath === null) {
			$basePath = Prado::getApplication()->getBasePath() . DIRECTORY_SEPARATOR . self::DEFAULT_BASEPATH;
			if (($this->_basePath = realpath($basePath)) === false || !is_dir($this->_basePath)) {
				throw new TConfigurationException('emailermodule_basepath_invalid', $basePath);
			}
		}
		return $this->_basePath;
	}

	/**
	 * @param string $value root directory (in namespace form) storing pages
	 * @throws TInvalidOperationException if the service is initialized already or basepath is invalid
	 */
	public function setBasePath($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('emailermodule_basepath_unchangeable');
		} elseif (($path = Prado::getPathOfNamespace($value)) === null || !is_dir($path)) {
			throw new TConfigurationException('emailermodule_basepath_invalid', $value);
		}
		$this->_basePath = realpath($path);
	}

	/**
	 * Sets the base page class name (in namespace format).
	 * If a page only has a template file without email class file,
	 * this base email class will be instantiated.
	 * @param string $value class name
	 */
	public function setBasePageClass($value)
	{
		$this->_baseEmailClass = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string base email class name in namespace format. Defaults to '\Prado\Util\Email\TEmail'.
	 */
	public function getBasePageClass()
	{
		return $this->_baseEmailClass;
	}

	/**
	 * Grabs the Service TemplateManager if available, or creates its own.
	 * @return TTemplateManager template manager
	 */
	public function getTemplateManager()
	{
		if (!$this->_templateManager) {
			$service = $this->getService();
			if (!$service->canGetProperty('TemplateManager') || !($this->_templateManager = $service->getTemplateManager())) {
				$this->_templateManager = new TTemplateManager();
				$this->_templateManager->init(null);
			}
		}
		return $this->_templateManager;
	}

	/**
	 * @param TTemplateManager $value template manager
	 */
	public function setTemplateManager(TTemplateManager $value)
	{
		$this->_templateManager = $value;
	}

	/**
	 * all emails get this CC in addition to the email's CC.  Multiple can be
	 * specified with a comma between email addresses.
	 * @return string Default CC address, for when an email doesn't have its own.
	 */
	public function getDefaultCcAddress()
	{
		return $this->_defaultCcAddress;
	}

	/**
	 * all emails get this CC in addition to the email's CC.  Multiple can be
	 * specified with a comma between email addresses.
	 * @param $address set the Default CC Address.
	 */
	public function setDefaultCcAddress($address)
	{
		$this->_defaultCcAddress = TPropertyValue::ensureString($address);
	}

	/**
	 * all emails get this BCC in addition to the email's BCC.  Multiple can be
	 * specified with a comma between email addresses.
	 * @return string Default BCC address.
	 */
	public function getDefaultBccAddress()
	{
		return $this->_defaultBccAddress;
	}

	/**
	 * all emails get this BCC in addition to the email's BCC.  Multiple can be
	 * specified with a comma between email addresses.
	 * @param $address set the Default Bcc Address.
	 */
	public function setDefaultBccAddress($address)
	{
		$this->_defaultBccAddress = TPropertyValue::ensureString($address);
	}

	/**
	 * @return string Any additional parameters for php mail().
	 */
	public function getMailAdditionalParameters()
	{
		return $this->_mailAdditionalParameters;
	}

	/**
	 * @param string $additionalParam Any additional parameters for php mail().
	 */
	public function setMailAdditionalParameters($additionalParam)
	{
		$this->_mailAdditionalParameters = TPropertyValue::ensureString($additionalParam);
	}

	/**
	 * This event is raised if the page is not found in the BasePath.
	 * This provides additional possible Page Paths to look for the page.
	 * The typical handler would look like:
	 * <code>
	 * public function additionalEmailPaths($service, $emailPath)
	 * {
	 *	 return $this->getPluginEmailPath() . DIRECTORY_SEPARATOR . strtr($emailPath, '.', DIRECTORY_SEPARATOR);
	 * }
	 * </code>
	 * @param mixed $param what is passed as the parameter to the event
	 * @since 4.2.0
	 */
	public function onAdditionalEmailPaths($param)
	{
		return $this->raiseEvent('onAdditionalEmailPaths', $this, $param);
	}
}
