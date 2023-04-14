<?php
/**
 * TPermissionsConfigurationBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Security\Permissions;

use Prado\Util\TBehavior;

/**
 * TPermissionsConfigurationBehavior class.
 *
 * TPermissionsConfigurationBehavior is designed specifically to attach to the
 * {@link TPageConfiguration} class objects.  It reads and parses the
 * permissions role hierarchy and permissions rules from a page configuration
 * file.  Within the config.xml for a page, for example, add the following:
 * <code>
 * 		<permissions>
 *			<role name="pageRole" children="otherRole, permission_name" />
 *			<permissionrule name="permission_name" action="allow" roles="manager"/>
 *		</permissions>
 * </code>
 *
 * See <@link TPermissionsManager> for information on php configurations.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TPermissionsConfigurationBehavior extends TBehavior
{
	/** @var \Prado\Security\Permissions\TPermissionsManager manager object for the behavior */
	private $_manager;

	/** @var array|\Prado\Xml\TXmlElement permissions data to parse */
	private $_permissions = [];

	/**
	 * @param null|\Prado\Security\Permissions\TPermissionsManager $manager
	 */
	public function __construct($manager = null)
	{
		if ($manager) {
			$this->setPermissionsManager($manager);
		}
		parent::__construct();
	}

	/**
	 * Loads the configuration specific for page service. This may be called multiple
	 * times.
	 * @param array $config config array
	 * @param string $configPath base path corresponding to this php element
	 * @param string $configPagePath the page path that the config php is associated with. The page path doesn't include the page name.
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
		$manager = $this->getPermissionsManager();
		foreach ($this->_permissions as $permission) {
			$manager->loadPermissionsData($permission);
		}
		return $callchain->dyApplyConfiguration();
	}

	/**
	 * @return \Prado\Security\Permissions\TPermissionsManager manages application permissions
	 */
	public function getPermissionsManager()
	{
		return $this->_manager;
	}

	/**
	 * @param \Prado\Security\Permissions\TPermissionsManager|\WeakReference $manager manages application permissions
	 */
	public function setPermissionsManager($manager)
	{
		if (class_exists('\WeakReference', false) && $manager instanceof \WeakReference) {
			$manager = $manager->get();
		}
		$this->_manager = $manager;
	}
}
