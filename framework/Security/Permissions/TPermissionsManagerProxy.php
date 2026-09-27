<?php

/**
 * TPermissionsManagerProxy class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Security\Permissions;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\IModuleDependency;
use Prado\IProxy;
use Prado\Prado;
use Prado\TComponent;
use Prado\TComponentProxyTrait;
use Prado\Util\Log\TLogger;
use Prado\Xml\TXmlElement;

/**
 * TPermissionsManagerProxy class.
 *
 * TPermissionsManagerProxy is a transparent permissions module that delegates
 * every {@see TPermissionsManager} operation to another TPermissionsManager
 * module registered with the application. One logical permissions slot can be
 * swapped at configuration time without changing the consumers that depend on it.
 *
 * ## Configuration
 *
 * {@see getBackingPermissionsManagerId BackingPermissionsManagerId} names the
 * backing module. TPermissionsManagerProxy declares that module as a required
 * {@see IModuleDependency}, so the application initializes it first.
 *
 * The role hierarchy and the permission rules belong to the backing module. A
 * `<role>` or `<permissionrule>` element, a `roles` or `permissionrules` key in
 * a PHP configuration, or a {@see setPermissionFile PermissionFile} on the proxy
 * throws `permissionsmanagerproxy_rules_not_allowed`.
 *
 * ## Transparency
 *
 * Every public TPermissionsManager method and property is overridden to call
 * the backing's public method of the same name; the proxy holds no rules,
 * hierarchy, descriptions, or auto-rule state of its own. Other property reads
 * and writes, method calls, and events reach the backing through
 * {@see TComponentProxyTrait}, which wires the backing's public `on` events on
 * first resolution. `dy` and `fx` names are never forwarded.
 *
 * The backing's {@see TPermissionsManager::init()} attaches the three
 * permissions class behaviors and registers the built-in permissions. The
 * proxy's {@see init()} does neither, and {@see getPermissions()} returns an
 * empty list so the {@see TPermissionsBehavior} the backing attaches to the
 * proxy (an {@see IPermissions} instance) registers nothing a second time.
 * {@see __destruct()} leaves the class behaviors in place; they belong to the
 * backing. {@see TPermissionsManager::getManager()} resolves to the backing
 * because {@see \Prado\TApplication::getModulesByType()} drops a proxy whose
 * backing is listed.
 *
 * Replacing a {@see setBackingPermissionsManagerId BackingPermissionsManagerId}
 * logs a {@see TLogger::WARNING} so a runtime swap is visible in the application log.
 *
 * Configure in `application.xml`:
 * ```xml
 * <module id="permissions" class="Prado\Security\Permissions\TPermissionsManagerProxy" BackingPermissionsManagerId="realPermissions" />
 * <module id="realPermissions" class="Prado\Security\Permissions\TPermissionsManager" SuperRoles="Administrator">
 *     <role name="Default" children="register_user" />
 *     <permissionrule name="*" action="deny" priority="1000" />
 * </module>
 * ```
 *
 * Or instantiate directly:
 * ```php
 * $proxy = new TPermissionsManagerProxy();
 * $proxy->setBackingPermissionsManagerId('realPermissions');
 * $proxy->init(null);
 * // All operations now delegate to the 'realPermissions' module.
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPermissionsManagerProxy extends TPermissionsManager implements IModuleDependency, IProxy
{
	use TComponentProxyTrait;

	/** @var string module ID of the backing permissions manager; empty until configured */
	private string $_backingPermissionsManagerId = '';

	// ----------------------------------------------------------------- lifecycle

	/**
	 * Declares the backing permissions manager module as a required dependency so
	 * that {@see \Prado\TApplication} initializes it before this proxy, in every pass.
	 * @param bool $isPreInit `true` for the dyPreInit pass, `false` for init(); not used
	 * @return ?string the backing module ID, or null when none is configured
	 */
	public function getModuleDependencies(bool $isPreInit = false): ?string
	{
		$id = $this->getBackingPermissionsManagerId();
		return $id === '' ? null : $id;
	}

	/**
	 * Initializes the proxy module. The rules, the class behaviors, and the
	 * `onAuthenticationComplete` handler belong to the backing module, so this
	 * method skips {@see TPermissionsManager::init()} and raises `dyInit` through
	 * {@see \Prado\TModule::init()} only.
	 * @param null|array|TXmlElement $config module configuration
	 * @throws TInvalidOperationException when the proxy is already initialized
	 * @throws TConfigurationException when {@see getBackingPermissionsManagerId BackingPermissionsManagerId} is empty
	 * @throws TConfigurationException when the configuration carries a role or permission rule
	 */
	public function init($config)
	{
		if ($this->getIsInitialized()) {
			throw new TInvalidOperationException('permissions_init_once');
		}
		if ($this->getBackingPermissionsManagerId() === '') {
			throw new TConfigurationException('permissionsmanagerproxy_backing_permissions_manager_id_required');
		}
		if ($this->hasRulesConfig($config)) {
			throw new TConfigurationException('permissionsmanagerproxy_rules_not_allowed', $this->getID());
		}
		\Prado\TModule::init($config);
		$this->markInitialized();
	}

	/**
	 * Returns whether a module configuration carries a role or a permission rule.
	 * @param null|array|TXmlElement $config module configuration
	 * @return bool whether a `<role>` or `<permissionrule>` element, or a `roles` or `permissionrules` key, is present
	 */
	protected function hasRulesConfig($config): bool
	{
		if (is_array($config)) {
			return isset($config['roles']) || isset($config['permissionrules']);
		}
		return $config instanceof TXmlElement
			&& ($config->getElementByTagName('role') !== null || $config->getElementByTagName('permissionrule') !== null);
	}

	/**
	 * Declares no permissions. The backing module registers the built-in
	 * permissions with itself; a second registration through the
	 * {@see TPermissionsBehavior} attached to this proxy would be a duplicate.
	 * @param TPermissionsManager $manager the manager the behavior registers with
	 * @return TPermissionEvent[] an empty list
	 */
	public function getPermissions($manager)
	{
		return [];
	}

	/**
	 * Releases the proxy's own behaviors and global listeners. The permissions
	 * class behaviors are the backing's, so {@see TPermissionsManager::__destruct()}
	 * is skipped and they stay attached.
	 */
	public function __destruct()
	{
		TComponent::__destruct();
	}

	// ----------------------------------------------------------------- TComponentProxyTrait implementation

	/**
	 * Returns the backing permissions manager through {@see getPermissionsManager()}.
	 * @throws TConfigurationException when {@see getBackingPermissionsManagerId BackingPermissionsManagerId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TPermissionsManager}
	 * @return ?TComponent the backing permissions manager
	 */
	public function getProxyBacking(): ?TComponent
	{
		return $this->getPermissionsManager();
	}

	/**
	 * Returns whether a {@see getBackingPermissionsManagerId BackingPermissionsManagerId}
	 * is configured, which enables lazy resolution from the module registry.
	 * @return bool whether lazy resolution is possible
	 */
	protected function canResolveProxyBacking(): bool
	{
		return $this->getBackingPermissionsManagerId() !== '';
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return string the stored module ID of the backing permissions manager
	 */
	protected function getBackingPermissionsManagerIdDirect(): string
	{
		return $this->_backingPermissionsManagerId;
	}

	/**
	 * @param string $value the module ID to store
	 */
	protected function setBackingPermissionsManagerIdDirect(string $value): void
	{
		$this->_backingPermissionsManagerId = $value;
	}

	/**
	 * @return string the module ID of the backing permissions manager
	 */
	public function getBackingPermissionsManagerId(): string
	{
		return $this->getBackingPermissionsManagerIdDirect();
	}

	/**
	 * Sets the module ID of the backing permissions manager. Replacing a non-empty
	 * ID detaches the event forwarders, logs a {@see TLogger::WARNING}, and drops
	 * the resolved backing so the next operation resolves the new module.
	 * @param string $value the module ID of the permissions manager to proxy
	 */
	public function setBackingPermissionsManagerId(string $value): void
	{
		$current = $this->getBackingPermissionsManagerIdDirect();
		if ($value === $current) {
			return;
		}
		if ($current !== '') {
			$this->detachProxy();
			Prado::log(
				sprintf("TPermissionsManagerProxy.BackingPermissionsManagerId changed from '%s' to '%s'.", $current, $value),
				TLogger::WARNING,
				'prado.security.permissions'
			);
		}
		$this->setBackingPermissionsManagerIdDirect($value);
		$this->setPermissionsManagerDirect(null);
	}

	/**
	 * Returns the stored backing permissions manager, narrowing the trait's
	 * `?TComponent` to `?TPermissionsManager`.
	 * @return ?TPermissionsManager the backing permissions manager, or null when not yet resolved
	 */
	protected function getPermissionsManagerDirect(): ?TPermissionsManager
	{
		$backing = $this->getProxyBackingDirect();
		return $backing instanceof TPermissionsManager ? $backing : null;
	}

	/**
	 * @param ?TPermissionsManager $value the backing permissions manager to store
	 */
	protected function setPermissionsManagerDirect(?TPermissionsManager $value): void
	{
		$this->setProxyBackingDirect($value);
	}

	/**
	 * Returns the backing {@see TPermissionsManager}, resolving it through
	 * {@see \Prado\TApplication::getModule()} on first call. The first resolution
	 * calls {@see attachProxy()}.
	 * @throws TConfigurationException when {@see getBackingPermissionsManagerId BackingPermissionsManagerId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TPermissionsManager}
	 * @return TPermissionsManager the backing permissions manager module
	 */
	public function getPermissionsManager(): TPermissionsManager
	{
		$manager = $this->getPermissionsManagerDirect();
		if ($manager === null) {
			$id = $this->getBackingPermissionsManagerId();
			if ($id === '') {
				throw new TConfigurationException('permissionsmanagerproxy_backing_permissions_manager_id_required');
			}
			$manager = $this->getApplication()->getModule($id);
			if ($manager === null) {
				throw new TConfigurationException('permissionsmanagerproxy_permissions_manager_not_found', $id);
			}
			if (!($manager instanceof TPermissionsManager)) {
				throw new TConfigurationException('permissionsmanagerproxy_invalid_permissions_manager_type', $id);
			}
			$this->setPermissionsManagerDirect($manager);
			$this->attachProxy();
		}
		return $manager;
	}

	// ----------------------------------------------------------------- permissions delegation

	/**
	 * Registers a permission with the backing permissions manager.
	 * @param string $permissionName name of the permission
	 * @param string $description description of the permission
	 * @param null|\Prado\Security\TAuthorizationRule[] $rules preset rules
	 */
	public function registerPermission($permissionName, $description, $rules = null)
	{
		$this->getPermissionsManager()->registerPermission($permissionName, $description, $rules);
	}

	/**
	 * Returns the short description of a permission from the backing.
	 * @param string $permissionName name of the permission
	 * @return string short description of the permission
	 */
	public function getPermissionDescription($permissionName)
	{
		return $this->getPermissionsManager()->getPermissionDescription($permissionName);
	}

	/**
	 * Loads roles, children, and permission rules into the backing.
	 * @param array|TXmlElement $config configurations to parse
	 */
	public function loadPermissionsData($config)
	{
		$this->getPermissionsManager()->loadPermissionsData($config);
	}

	/**
	 * Registers the permissions shell action through the backing.
	 * @param object $sender sender of this event handler
	 * @param null|mixed $param parameter for the event
	 */
	public function registerShellAction($sender, $param)
	{
		$this->getPermissionsManager()->registerShellAction($sender, $param);
	}

	/**
	 * Checks the backing's role hierarchy for a permission.
	 * @param string|string[] $roles the roles to check the permission
	 * @param string $permission the permission-role being checked for in the hierarchy
	 * @param array<string, bool> &$checked the roles already checked
	 * @return bool whether the permission is in the hierarchy of the roles
	 */
	public function isInHierarchy($roles, $permission, &$checked = [])
	{
		return $this->getPermissionsManager()->isInHierarchy($roles, $permission, $checked);
	}

	/**
	 * Returns the backing's runtime roles from the database.
	 * @return array<string, string[]> roles and children from the database
	 */
	public function getDbConfigRoles()
	{
		return $this->getPermissionsManager()->getDbConfigRoles();
	}

	/**
	 * Returns the backing's runtime permission rules from the database.
	 * @return array<string, \Prado\Security\TAuthorizationRule[]> the runtime rules
	 */
	public function getDbConfigPermissionRules()
	{
		return $this->getPermissionsManager()->getDbConfigPermissionRules();
	}

	/**
	 * Adds children to a role in the backing's runtime context.
	 * @param string $role the role to add children
	 * @param string|string[] $children the children to add to the role
	 * @return bool was the method successful
	 */
	public function addRoleChildren($role, $children)
	{
		return $this->getPermissionsManager()->addRoleChildren($role, $children);
	}

	/**
	 * Removes children from a role in the backing's runtime context.
	 * @param string $role the role to remove children from
	 * @param string|string[] $children the children to remove from the role
	 * @return bool was the method successful
	 */
	public function removeRoleChildren($role, $children)
	{
		return $this->getPermissionsManager()->removeRoleChildren($role, $children);
	}

	/**
	 * Adds a permission rule in the backing's runtime context.
	 * @param string $permission the permission or role to receive the rule
	 * @param \Prado\Security\TAuthorizationRule $rule the rule to add
	 * @return bool was the method successful
	 */
	public function addPermissionRule($permission, $rule)
	{
		return $this->getPermissionsManager()->addPermissionRule($permission, $rule);
	}

	/**
	 * Removes a permission rule in the backing's runtime context.
	 * @param string $permission the permission or role to remove the rule from
	 * @param \Prado\Security\TAuthorizationRule $rule the rule to remove
	 * @return bool was the method successful
	 */
	public function removePermissionRule($permission, $rule)
	{
		return $this->getPermissionsManager()->removePermissionRule($permission, $rule);
	}

	/**
	 * Returns the roles in the backing's hierarchy.
	 * @return string[] the roles in the hierarchy
	 */
	public function getHierarchyRoles()
	{
		return $this->getPermissionsManager()->getHierarchyRoles();
	}

	/**
	 * Returns the children of a role in the backing's hierarchy.
	 * @param string $role the role to return its children
	 * @return null|array<string, string[]>|string[] the children of a specific role
	 */
	public function getHierarchyRoleChildren($role)
	{
		return $this->getPermissionsManager()->getHierarchyRoleChildren($role);
	}

	/**
	 * Returns the backing's rules for a permission, or all rules.
	 * @param null|string $permission the permission name, or null for every permission
	 * @return null|array<string, \Prado\Security\TAuthorizationRuleCollection>|\Prado\Security\TAuthorizationRuleCollection the rules
	 */
	public function getPermissionRules($permission)
	{
		return $this->getPermissionsManager()->getPermissionRules($permission);
	}

	// ----------------------------------------------------------------- property delegation

	/**
	 * @return null|string[] the backing's roles that get all permissions
	 */
	public function getSuperRoles()
	{
		return $this->getPermissionsManager()->getSuperRoles();
	}

	/**
	 * @param string|string[] $roles the roles that get all permissions
	 * @throws TInvalidOperationException when the backing module is initialized
	 */
	public function setSuperRoles($roles)
	{
		$this->getPermissionsManager()->setSuperRoles($roles);
	}

	/**
	 * @return null|string[] the backing's default roles of all users
	 */
	public function getDefaultRoles()
	{
		return $this->getPermissionsManager()->getDefaultRoles();
	}

	/**
	 * @param string|string[] $roles the default roles of all users
	 * @throws TInvalidOperationException when the backing module is initialized
	 */
	public function setDefaultRoles($roles)
	{
		$this->getPermissionsManager()->setDefaultRoles($roles);
	}

	/**
	 * @return string the backing's full path to the file storing role/rule information
	 */
	public function getPermissionFile()
	{
		return $this->getPermissionsManager()->getPermissionFile();
	}

	/**
	 * Rejects a permission file. Rule data is configured on the backing module.
	 * @param string $value role/rule data file path
	 * @throws TConfigurationException always
	 */
	public function setPermissionFile($value)
	{
		throw new TConfigurationException('permissionsmanagerproxy_rules_not_allowed', $this->getID());
	}

	/**
	 * @return numeric the backing's priority of Allow With Permission and Preset Rules
	 */
	public function getAutoRulePriority()
	{
		return $this->getPermissionsManager()->getAutoRulePriority();
	}

	/**
	 * @param numeric $priority the priority of Allow With Permission and Preset Rules
	 * @throws TInvalidOperationException when the backing module is initialized
	 */
	public function setAutoRulePriority($priority)
	{
		$this->getPermissionsManager()->setAutoRulePriority($priority);
	}

	/**
	 * @return bool whether the backing enables the Allow With Permission rule
	 */
	public function getAutoAllowWithPermission()
	{
		return $this->getPermissionsManager()->getAutoAllowWithPermission();
	}

	/**
	 * @param bool $enable enable the Allow With Permission rule
	 * @throws TInvalidOperationException when the backing module is initialized
	 */
	public function setAutoAllowWithPermission($enable)
	{
		$this->getPermissionsManager()->setAutoAllowWithPermission($enable);
	}

	/**
	 * @return bool whether the backing enables module preset rules
	 */
	public function getAutoPresetRules()
	{
		return $this->getPermissionsManager()->getAutoPresetRules();
	}

	/**
	 * @param bool $enable enable module preset rules
	 * @throws TInvalidOperationException when the backing module is initialized
	 */
	public function setAutoPresetRules($enable)
	{
		$this->getPermissionsManager()->setAutoPresetRules($enable);
	}

	/**
	 * @return bool whether the backing adds the Deny All rule
	 */
	public function getAutoDenyAll()
	{
		return $this->getPermissionsManager()->getAutoDenyAll();
	}

	/**
	 * @param bool $enable add the Deny All rule to every permission
	 * @throws TInvalidOperationException when the backing module is initialized
	 */
	public function setAutoDenyAll($enable)
	{
		$this->getPermissionsManager()->setAutoDenyAll($enable);
	}

	/**
	 * @return numeric the backing's priority of the Deny All rule
	 */
	public function getAutoDenyAllPriority()
	{
		return $this->getPermissionsManager()->getAutoDenyAllPriority();
	}

	/**
	 * @param numeric $priority the priority of the Deny All rule
	 * @throws TInvalidOperationException when the backing module is initialized
	 */
	public function setAutoDenyAllPriority($priority)
	{
		$this->getPermissionsManager()->setAutoDenyAllPriority($priority);
	}

	/**
	 * @return null|\Prado\Util\TDbParameterModule|string the backing's DbParameter module or module ID
	 */
	public function getDbParameter()
	{
		return $this->getPermissionsManager()->getDbParameter();
	}

	/**
	 * @param null|\Prado\Util\TDbParameterModule|string $provider the DbParameter module ID or module
	 * @throws TInvalidOperationException when the backing module is initialized
	 * @throws TConfigurationException when `$provider` is not a TDbParameterModule
	 */
	public function setDbParameter($provider)
	{
		$this->getPermissionsManager()->setDbParameter($provider);
	}

	/**
	 * @return string the backing's name of the parameter to load
	 */
	public function getLoadParameter()
	{
		return $this->getPermissionsManager()->getLoadParameter();
	}

	/**
	 * @param string $value name of the parameter to load
	 * @throws TInvalidOperationException when the backing module is initialized
	 */
	public function setLoadParameter($value)
	{
		$this->getPermissionsManager()->setLoadParameter($value);
	}

	// -------------------------------------------------- serialization

	/**
	 * Excludes the transient forwarder list, the resolved backing (re-resolved
	 * from the module registry after unserialization), and an empty backing ID
	 * from serialization.
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$this->_addProxyEventNamesZappable($exprops);
		$this->_addProxyBackingZappable($exprops);
		if ($this->getBackingPermissionsManagerIdDirect() === '') {
			$exprops[] = "\0" . __CLASS__ . "\0_backingPermissionsManagerId";
		}
	}
}
