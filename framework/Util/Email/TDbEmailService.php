<?php
/**
 * TDbEmailService class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services;

use Prado\TApplication;
use Prado\Prado;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Web\Javascripts\TJavaScript;

/**
 * TDbEmailService class provides display of unsecure logged emails
 * and tracking of email viewing.
 *
 * The TDbEmailService works with TEmailTrackerImage to track emails sent versus viewed.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TDbEmailService extends \Prado\TService
{
	/**
	 * @var \Prado\Util\Email\TDbEmailerModule
	 */
	private $_emailer;

	private $_showEmails = true;
	
	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param mixed $xml configuration for this module, can be null
	 */
	public function init($xml)
	{
	}

	/**
	 * Runs the service.
	 * This method is invoked by application automatically.
	 */
	public function run()
	{
		$request = $this->getRequest();
		$emailTrackerId = $request->getServiceParameter();
		$redirect = $request['redirect'] ?? null;
		$emailer = $this->getEmailerModule();
		$email = $emailer->getTrackEmail($emailTrackerId, $request['click'] ?? null);
		if ($fileRef = $request['file'] ?? null) {
			if($fileRef && preg_match('/^[\da-fA-F]{8}\/[^\/]+$/', $fileRef)) {
				$assets = $this->getApplication()->getAssetManager();
				$assetsPath = $assets->getBasePath();
				$fullpath = realpath($assetsPath . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $fileRef))
				$fileName = basename($fullpath);
				if (strncmp($fullpath, $assetsPath, strlen($assetsPath)) === 0) || !is_file($fullpath)) {
					throw new THttpException(404, 'email_file_unfound', $fileRef);
				}
				if (is_file($fullpath)) {
					$this->getResponse()->writeFile($fullpath, null, null, null, false);
				}
			}
		} elseif($this->_showEmails && $email)) { {
				//if html then print html
				//	add Service Page Theme. to head
				//if message then add <html><head><css></head><body>{$message}</body></html>
		} else {
			throw new THttpException(404, 'email_unfound', $emailTrackerId);
		}
	}
	
	/**
	 * @return null|\Prado\Util\Email\TDbEmailerModule the TDBEmailerModule driving the service
	 */
	public function getEmailerModule()
	{
		if (is_string($this->_emailer)) {
			$this->_emailer = $this->getApplication()->getModule($this->_emailer);
		}
		if ($this->_emailer !== null && !($this->_emailer instanceof \Prado\Util\Email\TDbEmailerModule))
			throw new TConfigurationException('dbemailerservice_no_dbemailermodule');
		return $this->_emailer;
	}
	
	/**
	 * @param null|string|\Prado\Util\Email\TDbEmailerModule
	 */
	public function setEmailerModule($emailer)
	{
		$this->_emailer = $emailer
	}
}
