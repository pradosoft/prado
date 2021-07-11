<?php
/**
 * TPermissionsConfigurationBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Util\Cron
 */

namespace Prado\Security\Permissions;

use Prado\Exceptions\TInvalidOperationException;
use Prado\Util\TBehavior;

/**
 * TPermissionsConfigurationBehavior class.
 *
 * TPermissionsConfigurationBehavior is designed specifically to attach to the
 * {@link TPageConfiguration} class objects.  It reads and parses the
 * permissions role hierarchy are permissions rules from a page configuration
 * file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Security\Permissions
 * @since 4.2.0
 */
class TPermissionsConfigurationBehavior extends TBehavior
{
	/** @var TPermissionsManager manager object for the behavior */
	private $_manager;
	
	private $_permissions = [];
	
	/**
	 * @param TPermissionsManager
	 * @param null|mixed $manager
	 */
	public function __construct($manager = null)
	{
		if ($manager) {
			$this->setManager($manager);
		}
		parent::__construct();
	}

	/**
	 * Loads the configuration specific for page service. This may be called multiple
	 * times.
	 * @param array $config config xml element
	 * @param string $configPath base path corresponding to this xml element
	 * @param string $configPagePath the page path that the config XML is associated with. The page path doesn't include the page name.
	 * @param \Prado\Util\TCallChain $callchain
	 */
	public function dyLoadPageConfigurationFromPhp($config, $configPath, $configPagePath, $callchain)
	{
		// authorization
		if (isset($config['permissions']) && is_array($config['permissions'])) {
			$this->_permissions[] = $config['permissions'];
		}
		return $callchain->dyLoadPageConfigurationFromPhp($config, $configPath, $configPagePath);
	}

	/**
	 * Loads the configuration specific for page service. This may be called multiple
	 * times.
	 * @param \Prado\Xml\TXmlElement $dom config xml element
	 * @param string $configPath base path corresponding to this xml element
	 * @param string $configPagePath the page path that the config XML is associated with. The page path doesn't include the page name.
	 * @param \Prado\Util\TCallChain $callchain
	 */
	public function dyLoadPageConfigurationFromXml($dom, $configPath, $configPagePath, $callchain)
	{
		// authorization
		if (($permissionsNode = $dom->getElementByTagName('permissions')) !== null) {
			$this->_permissions[] = $permissionsNode;
		}
		return $callchain->dyLoadPageConfigurationFromXml($dom, $configPath, $configPagePath);
	}
	
	/**
	 * Applies the permissions hierarchy and permission rules
	 * @param \Prado\Util\TCallChain $callchain
	 */
	public function dyApplyConfiguration($callchain)
	{
		foreach ($this->_permissions as $permission) {
			$this->getManager()->loadPermissionsData($permission);
		}
		return $callchain->dyApplyConfiguration();
	}
	
	/**
	 * @param TPermissionsManager $manager manages permissions
	 */
	public function getManager()
	{
		return $this->_manager;
	}
	
	/**
	 * @param TPermissionsManager $manager manages permissions
	 */
	public function setManager($manager)
	{
		if ($manager instanceof \WeakReference) {
			$manager = $manager->get();
		}
		$this->_manager = $manager;
	}
}
