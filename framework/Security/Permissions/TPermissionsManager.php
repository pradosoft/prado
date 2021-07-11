<?php
/**
 * TPermissionsManager Class File
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Util
 */

namespace Prado\Security\Permissions;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Security\TAuthorizationRule;
use Prado\Security\TAuthorizationRuleCollection;
use Prado\TApplication;
use Prado\TComponent;
use Prado\TPropertyValue;
use Prado\Util\TDbParameterModule;
use Prado\Xml\TXmlDocument;
use Prado\Xml\TXmlElement;

/**
 * TPermissionsManager class.
 *
 * TPermissionsManager has two parts: Permission Authentication and Roll Based
 * Access Control (RBAC).  Each registered Permission is given a set of
 * {@link \Prado\Security\TAuthorizationRule}s.  The RBAC is based on roles
 * having children roles and permissions being thought of as special roles
 * themselves.
 *
 * TPermissionsManager attaches {@link TPermissionsBehavior} to all classes
 * that implement {@link IPermissions}.  This is the main mechanism
 * by which application permissions are registered.
 *
 * The role hierarchy and permission rules are unique to each application.  The
 * permissions configuration is defined in the TPermissionsManager application
 * configuration or in a separate {@link PermissionsFile}. {@link TPermissionsConfigurationBehavior}
 * enables a page configuration to have Permission Configurations as well.
 * A {@link TDbParameterModule} can be specified for loading dynamic roles and
 * permissions.
 *
 * Module XML configurations (and similarly PermissionFile) follows the format, eg:
 * <code>
 * <module id="permissions" class="Prado\Security\Permissions\TPermissionsManager" DefaultRoles="Default" SuperRoles="Administrator">
 *	<role name="Developer" children="all, param_shell_permission, cron" />
 *	<role name="Manager" children="editor, change_user_role_permission, cron_shell" />
 *	<role name="cron_shell" children="cron_add_task, cron_update_task, cron_remove_task" />
 *	<role name="cron" children="cron_shell, cron_manage_log, cron_add_task, cron_update_task, cron_remove_task" />
 *  <role name="Default" children="register_user, blog_read_posts, blog_comment">
 *	<permissionRule name="param_shell_permission" action="deny" users="*" roles="" verb="*" IPs="" />
 *	<permissionRule name="cron_shell" action="allow" users="*" roles="Developer,cron_shell,cron_manage_log" verb="*" IPs="" />
 *	<permissionRule name="register_user" action="allow" users="?" />
 *	<permissionRule name="register_user" action="allow" roles="Manager" />
 *	<permissionRule name="change_profile" action="deny" users="?" priority="0" />
 *	<permissionRule name="blog_update_posts" class="Prado\Security\Permissions\TUserOwnerRule" Priority="5" />
 *	<permissionRule name="cron" action="allow" users="admin, user1, user2" roles="*" verb="*" IPs="*"  />
 *	<permissionRule name="blog_*" action="allow" users="admin, user1, user2" roles="*" verb="*" IPs="*"  />
 *	<permissionRule name="*" action="deny" priority="1000" />
 * </module>
 * </code>
 *
 * and in PHP the same file would follow the following format, eg:
 * 'modules' => [
 * 'permissions' =>[class => 'Prado\Security\Permissions\TPermissionsManager',
 * 		'properties' => ['DefaultRoles' => 'Default', 'SuperRoles' => "Administrator"],
 *		'roles' => [
 *			'Developer' => ['all', 'param_shell_permission', 'cron'],
 *			'Manager' => ['editor', 'change_user_role_permission', 'cron_shell'],
 *			'cron_shell' => ['cron_add_task', 'cron_update_task', 'cron_remove_task'],
 *			'cron' => ['cron_shell', 'cron_manage_log', 'cron_add_task', 'cron_update_task', 'cron_remove_task'],
 *			'Default' => ['register_user', 'blog_read_posts', 'blog_comment'],
 *		],
 * 		'permissionRules' => [
 *			[name => 'param_shell_permission', 'action' => 'deny', 'users' => '*', roles => '*', 'verb' => '*', 'IPs' =>''],
 *			[name => 'cron_shell', 'action' => 'allow', 'users' => 'Developer,cron_shell,cron_manage_log', roles => 'cron_shell', 'verb' => '*', 'IPs' =>''],
 *			[name => 'register_user', 'action' => 'allow', 'users' => '?'],
 *			[name => 'register_user', 'action' => 'allow', 'roles' => 'Manager'],
 *			[name => 'change_profile', 'action' => 'deny', 'users' => '?', 'priority' => '0'],
 *			[name => 'blog_update_posts', 'class' => 'Prado\Security\Permissions\TUserOwnerRule', 'priority' => '5'],
 *			[name => 'cron', 'action' => 'allow', 'users' => 'admin, user1, user2'],
 *			[name => 'blog_*', 'action' => 'allow', 'users' => 'admin, user1, user2'],
 *			[name => '*', 'action' => 'deny', 'priority' => 1000]
 * </module>
 *
 * In this example, "cron" is not a permission, but when used as a permission,
 * all children permissions will receive the rule.  Permissions with children,
 * such as cron_shell (above), will give all its children the rule as
 * well.
 *
 * A special role "All" is automatically created to contain all the permissions.
 * this is the same as specifying a role as a super role via {@link setSuperRoles}.
 *
 * All users get the roles specified by {@link getDefaultRoles}.  This changes
 * the default Prado behavior for guest users having no roles.
 *
 * Intermediate roles, that are not defined in the user system, may be defined in
 * the hierarchy, in the above example the "cron" role is not defined by the system,
 * but is defined in the hierarchy.
 *
 * Permissions can have multiple permission rules with the same name. they are
 * ordered by natural specified order unless the rule property
 * {@link TAuthorizationRule::Priority} is set.
 *
 * Permissions Authentication rules may use the '*' or 'perm_*' to add the rules to all
 * matching permission names.  Anything before the * is matched as a permission.
 * This does not traverse the hierarchy roles matching the name, just the permissions
 * are matched for the TAuthorizationRule.
 *
 * A permission must list itself as a TAuthorizationRule role for the user to be
 * validated for that permission for authorization.  This is handled automatically
 * by TPermissionManager with the {@link getAutoAllowWithPermission} property.
 * By default getAutoAllowWithPermission is true, and allows any user with
 * that permission in their hierarchy to allow access to the functionality.
 * This rule priority can be set with {@link getAutoRulePriority},
 * where the default is 5 before user defined rules.
 *
 * The second automatic rules includes Modules have their own data rules that can
 * be automatically added via {@link getAutoRuleModuleRules}.  By default this
 * is true. These rules allow owners of the data to be permitted without having
 * a permission-role.  Modules roles can define their own priorities but those
 * without set priorities receive the priority from {@link getAutoRulePriority}.
 *
 * The third, and last, autoRule is the final {@link getAutoDenyAll DenyAll}
 * rule. This is the last rule that denies all by default.  The AutoDenyAll
 * gets its rule priority from {@link getAutoDenyAllPriority}
 *
 * Recursive hierarchy is gracefully handled, in case of any loop structures.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Security\Permissions
 * @method bool dyAddRoleChildren(bool $return, string $role, string[] $children)
 * @method bool dyRemoveRoleChildren(bool $return, string $role, string[] $children)
 * @method bool dyAddPermissionRule(bool $return, string $permission, \Prado\Security\TAuthorizationRule $rule)
 * @method bool dyRemovePermissionRule(bool $return, string $permission, \Prado\Security\TAuthorizationRule $rule)
 * @since 4.2.0
 */
class TPermissionsManager extends \Prado\TModule implements IPermissions
{
	public const PERMISSIONS_BEHAVIOR = 'permissions';
	
	public const USER_PERMISSIONS_BEHAVIOR = 'usercan';
	
	public const PERMISSIONS_CONFIG_BEHAVIOR = 'permissionsConfig';
	
	public const PERM_PERMISSIONS_MANAGE_ROLES = 'permissions_manage_roles';
	
	public const PERM_PERMISSIONS_MANAGE_PERMISSION_RULES = 'permissions_manage_permission_rules';
	
	/** @var string[] roles that get all permissions, default [] */
	private $_superRoles;
	
	/** @var string[] Default roles to give all users, default [] */
	private $_defaultRoles;
	
	/** @var \Prado\Security\TAuthorizationRuleCollection[] contains the rules for each permission */
	private $_permissionRules = [];
	
	/** @var string permission descriptions */
	private $_permissionDescriptions = []; //TODO
	
	/** @var array<string, \Prado\Security\TAuthorizationRule[]>  */
	private $_autoRules = [];
	
	/** @var bool contains the hierarchy of roles-permissions */
	private $_hierarchy = [];
	
	/** @var bool is the module initialized */
	private $_initialized = false;
	
	/** @var string user/role information file */
	private $_permissionFile;
	
	/** @var numeric the priority of the Allow With Permission Rule, default 5 */
	private $_autoRulePriority = 5;
	
	/** @var bool add allow users with permission-role, default true  */
	private $_autoAllowWithPermission = true;
	
	/** @var bool add module rules, allows User's data, default true */
	private $_autoRulePresetRules = true;
	
	/** @var bool add Deny All rule to every permissions as the last rule */
	private $_autoDenyAll = true;
	
	/** @var numeric the priority of the module Rule, usually these are Allow User As Owner */
	private $_autoDenyAllPriority = 999999;
	
	/** @var numeric the priority of the module Rule, usually these are Allow User As Owner */
	private $_dbParameter;
	
	/** @var numeric the priority of the module Rule, usually these are Allow User As Owner */
	private $_parameter = 'configuration:TPermissionsManager:runtime';
	
	// hierarchy from parameter
	
	/**
	 * @param mixed $manager
	 * @return TPermissionEvent the dynamic events to have the authorization
	 */
	public function getPermissions($manager)
	{
		return [
			new TPermissionEvent(static::PERM_PERMISSIONS_MANAGE_ROLES, ['dyAddRoleChildren', 'dyRemoveRoleChildren']),
			new TPermissionEvent(static::PERM_PERMISSIONS_MANAGE_PERMISSION_RULES, ['dyAddPermissionRule', 'dyRemovePermissionRule'])
		];
	}
	
	/**
	 * @param array|TXmlElement $config the application configuration
	 */
	public function init($config)
	{
		if (is_string($this->_dbParameter)) {
			$application = $this->getApplication();
			if (($dbParameter = $application->getModule($this->_dbParameter)) === null) {
				throw new TConfigurationException('permissions_dbparameter_nonexistent', $this->_dbParameter);
			}
			if (!($dbParameter instanceof TDbParameterModule)) {
				throw new TConfigurationException('permissions_dbparameter_invalid', $this->_dbParameter);
			}
			$this->_dbParameter = $dbParameter;
		}
		
		if ($this->_initialized) {
			throw new TInvalidOperationException('permissions_init_once', $this->_dbParameter);
		}
		$this->_initialized = true;
		
		$manager = class_exists('\WeakReference') ? \WeakReference::create($this) : $this;
		TComponent::attachClassBehavior(static::PERMISSIONS_BEHAVIOR, ['class' => 'Prado\\Security\\Permissions\\TPermissionsBehavior', 'manager' => $manager], 'Prado\\Security\\Permissions\\IPermissions', -10);
		TComponent::attachClassBehavior(static::USER_PERMISSIONS_BEHAVIOR, ['class' => 'Prado\\Security\\Permissions\\TUserPermissionsBehavior', 'manager' => $manager], 'Prado\\Security\\IUser', -10);
		TComponent::attachClassBehavior(static::PERMISSIONS_CONFIG_BEHAVIOR, ['class' => 'Prado\\Security\\Permissions\\TPermissionsConfigurationBehavior', 'manager' => $manager], 'Prado\\Web\\Services\\TPageConfiguration', -10);
		
		$this->loadPermissionsData($config);
		if ($this->_permissionFile !== null) {
			if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
				$userFile = include $this->_permissionFile;
				$this->loadPermissionsData($userFile);
			} else {
				$dom = new TXmlDocument;
				$dom->loadFromFile($this->_permissionFile);
				$this->loadPermissionsData($dom);
			}
		}
		if ($this->_dbParameter) {
			$this->loadPermissionsData($this->_dbParameter->get($this->_parameter));
		}
		
		foreach ($this->getSuperRoles() ?? [] as $role) {
			$this->_hierarchy[$role] = array_merge(['all'], $this->_hierarchy[$role] ?? []);
		}
		
		parent::init($config);
	}
	
	/**
	 * Registers an object's permissions
	 * @param string $permission
	 * @param null|\Prado\ $rules
	 */
	public function registerPermission($permission, $rules = null)
	{
		$permission = strtolower($permission);
		
		if ($this->_autoDenyAll === true) {
			$this->_autoDenyAll = 2;
			$this->addPermissionRuleInternal('*', new TAuthorizationRule('deny', '*', '*', '*', '*', $this->_autoDenyAllPriority));
		}
		
		$this->_hierarchy['all'][] = $permission;
		
		if (!isset($this->_permissionRules[$permission])) {
			$this->_permissionRules[$permission] = new TAuthorizationRuleCollection();
		} else {
			throw new TInvalidOperationException('permissions_duplicate_permission');
		}
		if ($this->_autoAllowWithPermission) {
			$this->_permissionRules[$permission]->add(new TAuthorizationRule('allow', '*', $permission, '*', '*', $this->_autoRulePriority));
		}
		if ($this->_autoRulePresetRules && $rules) {
			if (!is_array($rules)) {
				$rules = [$rules];
			}
			foreach ($rules as $rule) {
				$this->_permissionRules[$permission]->add($rule, is_numeric($p = $rule->getPriority()) ? $p : $this->_autoRulePriority);
			}
		}
		foreach ($this->_autoRules as $rulePerm => $rules) {
			$pos = strpos($rulePerm, '*');
			if (($pos !== false && strncmp($permission, $rulePerm, $pos) === 0) || $this->isInHierarchy($rulePerm, $permission)) {
				$this->_permissionRules[$permission]->mergeWith($rules);
				if ($rulePerm === $permission) {
					unset($this->_autoRules[$rulePerm]);
				}
			}
		}
	}
	
	/**
	 * @param array|Prado\Xml\TXMLElement configurations to parse
	 * @param mixed $config
	 */
	public function loadPermissionsData($config)
	{
		$isXml = false;
		if (!$config || empty($config)) {
			return;
		}
		$permissions = $roles = [];
		if ($config instanceof TXmlElement) {
			$isXml = true;
			$roles = $config->getElementsByTagName('role');
			$permissions = $config->getElementsByTagName('permissionrule');
		} elseif (is_array($config)) {
			$roles = $config['roles'] ?? [];
			$permissions = $config['permissionrules'] ?? [];
		}
		foreach ($roles as $role => $properties) {
			if ($isXml) {
				$properties = array_change_key_case($properties->getAttributes()->toArray());
				$role = $properties['name'] ?? '';
				$children = array_map('trim', explode(',', $properties['children'] ?? ''));
			} else {
				$children = $properties;
				if (is_string($children)) {
					$children = array_map('trim', explode(',', $children));
				}
				if (!is_array($children)) {
					throw new TConfigurationException('permissions_role_children_invalid');
				}
			}
			
			$role = strtolower($role);
			$children = array_map('strtolower', array_filter($children));
			
			$this->_hierarchy[$role] = array_merge($this->_hierarchy[$role] ?? [], $children);
		}
		foreach ($permissions as $name => $properties) {
			if ($isXml) {
				$properties = array_change_key_case($properties->getAttributes()->toArray());
			} else {
				if (!is_array($properties)) {
					throw new TConfigurationException('permissions_permissions_invalid');
				}
			}
			if (is_numeric($name) && (!isset($properties[0]) || !$properties[0] instanceof TAuthorizationRule)) {
				$name = strtolower($properties['name'] ?? '');
				if (!$name) {
					throw new TConfigurationException('permissions_rules_require_name');
				}
				if (!($properties['action'] ?? null)) {
					throw new TConfigurationException('permissions_rules_require_action');
				}
				unset($properties['name']);
				$properties['class'] = $properties['class'] ?? 'Prado\\Security\\TAuthorizationRule';
				$rule = Prado::createComponent($properties);
			} else {
				$rule = $properties;
			}
			$this->addPermissionRuleInternal($name, $rule);
		}
	}
	
	/**
	 *
	 * @param string $name
	 * @param \Prado\Security\TAuthorizationRule $rule
	 */
	protected function addPermissionRuleInternal($name, $rule)
	{
		if (!is_array($rule)) {
			$rule = [$rule];
		}
		if (($pos = strpos($name, '*')) !== false) {
			foreach ($this->_permissionRules as $perm => $rules) {
				if (strncmp($perm, $name, $pos) === 0) {
					$rules->mergeWith($rule);
				}
			}
			$this->_autoRules[$name] = array_merge($this->_autoRules[$name] ?? [], $rule);
		} elseif (isset($this->_permissionRules[$name])) {
			$this->_permissionRules[$name]->mergeWith($rule);
		} else {
			$this->_autoRules[$name] = array_merge($this->_autoRules[$name] ?? [], $rule);
		}
		if (isset($this->_hierarchy[$name])) {
			//Push the rule down the hierarchy to any children permissions.
			$set = [$name => true];
			$hierarchy = $this->_hierarchy[$name];
			while (count($hierarchy)) {
				$role = array_pop($hierarchy);
				if (!isset($set[$role])) { // stop recursive hierarchy and duplicate permissions
					$set[$role] = true;
					if (isset($this->_permissionRules[$role])) {
						$this->_permissionRules[$role]->mergeWith($rule);
					}
					if (isset($this->_hierarchy[$role])) {
						$hierarchy = array_merge($this->_hierarchy[$role], $hierarchy);
					}
				}
			}
		}
	}
	
	/**
	 *
	 * @param string $name
	 * @param \Prado\Security\TAuthorizationRule $rule
	 */
	protected function removePermissionRuleInternal($name, $rule)
	{
		if (($pos = strpos($name, '*')) !== false) {
			foreach ($this->_permissionRules as $perm => $rules) {
				if (strncmp($perm, $name, $pos) === 0) {
					$rules->remove($rule);
				}
			}
		} elseif (isset($this->_permissionRules[$name])) {
			$this->_permissionRules[$name]->remove($rule);
		}
		if (isset($this->_hierarchy[$name])) {
			//Push the rule down the hierarchy to any children permissions.
			$set = [$name => true];
			$hierarchy = $this->_hierarchy[$name];
			while (count($hierarchy)) {
				$role = array_pop($hierarchy);
				if (!isset($set[$role])) { // stop recursive hierarchy and duplicate permissions
					$set[$role] = true;
					if (isset($this->_permissionRules[$role])) {
						$this->_permissionRules[$role]->remove($rule);
					}
					if (isset($this->_hierarchy[$role])) {
						$hierarchy = array_merge($this->_hierarchy[$role], $hierarchy);
					}
				}
			}
		}
	}
	
	/**
	 * checks if the $permission is in the $roles hierarchy.
	 * @param string[] $roles the roles to check the permission
	 * @param string $permission the permission-role being checked for in the hierarchy
	 * @param &array<string, bool> $checked the rolls already checked
	 */
	public function isInHierarchy($roles, $permission, &$checked = [])
	{
		if (!$roles) {
			return false;
		}
		if (!$checked) {
			if (!is_array($roles)) {
				$roles = array_filter(array_map('trim', explode(',', $roles)));
			}
			$roles = array_map('strtolower', $roles);
			$permission = strtolower($permission);
		}
		if (in_array($permission, $roles)) {
			return true;
		}
		foreach ($roles as $role) {
			if (!isset($checked[$role])) {
				$checked[$role] = true;
				if (isset($this->_hierarchy[$role]) && $this->isInHierarchy($this->_hierarchy[$role], $permission, $checked)) {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * @return array the db configuration
	 */
	public function getDbConfigRoles()
	{
		$runtimeData = $this->_dbParameter->get($this->_parameter) ?? [];
		return $runtimeData['roles'] ?? [];
	}
	
	/**
	 * @param string[] $children the children to add to the role
	 * @return array the db configuration
	 */
	public function getDbConfigPermissionRules()
	{
		$runtimeData = $this->_dbParameter->get($this->_parameter) ?? [];
		return $runtimeData['permissionrules'] ?? [];
	}
	
	/**
	 * @param string $role the role to add children
	 * @param string[] $children the children to add to the role
	 */
	public function addRoleChildren($role, $children)
	{
		if ($this->dyAddRoleChildren(false, $role, $children) || !$this->_dbParameter) {
			return false;
		}
		if (is_string($children)) {
			$children = array_map('trim', explode(',', $children));
		} elseif (!is_array($children)) {
			throw new TInvalidDataValueException('permission_add_children_invalid', $children);
		}
		$role = strtolower($role);
		$children = array_map('strtolower', array_filter($children));
		$this->_hierarchy[$role] = array_merge($this->_hierarchy[$role] ?? [], $children);
		
		$runtimeData = $this->_dbParameter->get($this->_parameter) ?? [];
		$runtimeData['roles'] = $runtimeData['roles'] ?? [];
		$runtimeData['roles'][$role] = array_merge($runtimeData['roles'][$role] ?? [], $children);
		$this->_dbParameter->set($this->_parameter, $runtimeData);
		
		return true;
	}
	
	/**
	 * @param string $role the role to return its .children
	 * @param mixed $children
	 * @return string[] the children of a specific role.
	 */
	public function removeRoleChildren($role, $children)
	{
		if ($this->dyRemoveRoleChildren(false, $role, $children) || !$this->_dbParameter) {
			return false;
		}
		if (is_string($children)) {
			$children = array_map('trim', explode(',', $children));
		} elseif (!is_array($children)) {
			throw new TInvalidDataValueException('permission_remove_children_invalid', $children);
		}
		$role = strtolower($role);
		$children = array_map('strtolower', array_filter($children));
		$this->_hierarchy[$role] = array_values(array_diff($this->_hierarchy[$role] ?? [], $children));
		if (!$this->_hierarchy[$role]) {
			unset($this->_hierarchy[$role]);
		}
		
		$runtimeData = $this->_dbParameter->get($this->_parameter) ?? [];
		$runtimeData['roles'][$role] = array_values(array_diff($runtimeData['roles'][$role] ?? [], $children));
		if (!$runtimeData['roles'][$role]) {
			unset($runtimeData['roles'][$role]);
		}
		$this->_dbParameter->set($this->_parameter, $runtimeData);
		return true;
	}
	
	/**
	 * @param string $permission
	 * @param \Prado\Security\TAuthorizationRule $rule
	 */
	public function addPermissionRule($permission, $rule)
	{
		$permission = strtolower($permission);
		
		if ($this->dyAddPermissionRule(false, $permission, $rule) || !$this->_dbParameter) {
			return false;
		}
		$this->addPermissionRuleInternal($permission, $rule);
		
		$runtimeData = $this->_dbParameter->get($this->_parameter) ?? [];
		$runtimeData['permissionrules'] = $runtimeData['permissionrules'] ?? [];
		$runtimeData['permissionrules'][$permission][] = $rule;
		$this->_dbParameter->set($this->_parameter, $runtimeData);
		
		return true;
	}
	
	/**
	 * @param string $permission a permission or role to remove the rule from
	 * @param \Prado\Security\TAuthorizationRule $rule
	 * @return bool was the removal successful.
	 */
	public function removePermissionRule($permission, $rule)
	{
		$permission = strtolower($permission);
		
		if ($this->dyRemovePermissionRule(false, $permission, $rule) || !$this->_dbParameter) {
			return false;
		}
		
		$this->removePermissionRuleInternal($permission, $rule);
		
		$runtimeData = $this->_dbParameter->get($this->_parameter) ?? [];
		$runtimeData['permissionrules'] = $runtimeData['permissionrules'] ?? [];
		
		if (($index = array_search($rule, $runtimeData['permissionrules'][$permission] ?? [], true)) === false) {
			return false;
		}
		unset($runtimeData['permissionrules'][$permission][$index]);
		if (!$runtimeData['permissionrules'][$permission]) {
			unset($runtimeData['permissionrules'][$permission]);
		}
		$this->_dbParameter->set($this->_parameter, $runtimeData);
		
		return true;
	}
	
	/**
	 * @return string[] the roles of the hierarchy.
	 */
	public function getHierarchyRoles()
	{
		return array_keys($this->_hierarchy);
	}
	
	/**
	 * @param string $role the role to return its .children
	 * @return string[] the children of a specific role.
	 */
	public function getHierarchyRoleChildren($role)
	{
		return $this->_hierarchy[strtolower(TPropertyValue::ensureString($role))] ?? null;
	}
	
	/**
	 * @param null|string $permission
	 * @return array<string, TAuthorizationRuleCollection>|TAuthorizationRuleCollection
	 */
	public function getPermissionRules($permission = null)
	{
		if (is_string($permission)) {
			return $this->_permissionRules[strtolower($permission)] ?? null;
		} else {
			return $this->_permissionRules;
		}
	}
	
	/**
	 * @return string[] array of rolls that get all permissions
	 */
	public function getSuperRoles()
	{
		return $this->_superRoles;
	}
	
	/**
	 * @param string[] $roles  of rolls that get all permissions
	 */
	public function setSuperRoles($roles)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('permissions_property_unchangeable', 'SuperRoles');
		}
		if (!is_array($roles)) {
			$roles = array_filter(array_map('trim', explode(',', $roles)));
		}
		$this->_superRoles = array_map('strtolower', $roles);
		;
	}
	
	/**
	 * @return string[] array of rolls that get all permissions
	 */
	public function getDefaultRoles()
	{
		return $this->_defaultRoles;
	}
	
	/**
	 * @param string[] $roles  of rolls that get all permissions
	 */
	public function setDefaultRoles($roles)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('permissions_property_unchangeable', 'DefaultRoles');
		}
		if (!is_array($roles)) {
			$roles = array_filter(array_map('trim', explode(',', $roles)));
		}
		$this->_defaultRoles = $roles;
	}

	/**
	 * @return string the full path to the file storing user/role information
	 */
	public function getPermissionFile()
	{
		return $this->_permissionFile;
	}

	/**
	 * @param string $value user/role data file path (in namespace form). The file format is XML
	 * whose content is similar to that user/role block in application configuration.
	 * @throws TInvalidOperationException if the module is already initialized
	 * @throws TConfigurationException if the file is not in proper namespace format
	 */
	public function setPermissionFile($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('permissions_property_unchangeable', 'PermissionFile');
		} elseif (($this->_permissionFile = Prado::getPathOfNamespace($value, $this->getApplication()->getConfigurationFileExt())) === null || !is_file($this->_permissionFile)) {
			throw new TConfigurationException('permissions_permissionfile_invalid', $value);
		}
	}
	
	/**
	 * @return numeric the priority of Allow With Permission and Module Rules, default 5
	 */
	public function getAutoRulePriority()
	{
		return $this->_autoRulePriority;
	}
	
	/**
	 * @param numeric $priority the priority of Allow With Permission and Module Rules
	 */
	public function setAutoRulePriority($priority)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('permissions_property_unchangeable', 'AutoRulePriority');
		}
		$this->_autoRulePriority = is_numeric($priority) ? $priority : (float) $priority;
	}
	
	/**
	 * @return bool enable Allow With Permission rule, default true
	 */
	public function getAutoAllowWithPermission()
	{
		return $this->_autoAllowWithPermission;
	}
	
	/**
	 * @param bool $enable enable Allow With Permission rule
	 */
	public function setAutoAllowWithPermission($enable)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('permissions_property_unchangeable', 'AutoAllowWithPermission');
		}
		$this->_autoAllowWithPermission = TPropertyValue::ensureBoolean($enable);
	}
	
	/**
	 * @return bool enable Module Rules, default true
	 */
	public function getAutoPresetRules()
	{
		return $this->_autoRulePresetRules;
	}
	
	/**
	 * @param bool $enable the priority of Allow With Permission
	 */
	public function setAutoPresetRules($enable)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('permissions_property_unchangeable', 'AutoPresetRules');
		}
		$this->_autoRulePresetRules = TPropertyValue::ensureBoolean($enable);
	}
	
	/**
	 * @return bool the priority of Allow With Permission, default true
	 */
	public function getAutoDenyAll()
	{
		return $this->_autoDenyAll > 0;
	}
	
	/**
	 * @param bool $enable the priority of Allow With Permission
	 */
	public function setAutoDenyAll($enable)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('permissions_property_unchangeable', 'AutoDenyAll');
		}
		$this->_autoDenyAll = TPropertyValue::ensureBoolean($enable);
	}
	
	/**
	 * @return numeric the priority of Deny All rule, default 999999
	 */
	public function getAutoDenyAllPriority()
	{
		return $this->_autoDenyAllPriority;
	}
	
	/**
	 * @param numeric $priority the priority of Deny All rule
	 */
	public function setAutoDenyAllPriority($priority)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('permissions_property_unchangeable', 'AutoDenyAllPriority');
		}
		$this->_autoDenyAllPriority = is_numeric($priority) ? $priority : (float) $priority;
	}

	/**
	 * @return Prado\Util\TDbParameterModule user manager instance
	 */
	public function getDbParameter()
	{
		return $this->_dbParameter;
	}

	/**
	 * @param IUserManager|string $provider the user manager module ID or the user manager object
	 * @throws TInvalidOperationException if the module has been initialized or the user manager object is not IUserManager
	 */
	public function setDbParameter($provider)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('permissions_property_unchangeable', 'DbParameter');
		}
		if (!is_string($provider) && !($provider instanceof TDbParameterModule)) {
			throw new TConfigurationException('permissions_dbparameter_invalid', is_object($provider) ? get_class($provider) : $provider);
		}
		$this->_dbParameter = $provider;
	}

	/**
	 * @return Prado\Util\TDbParameterModule user manager instance
	 */
	public function getLoadParameter()
	{
		return $this->_parameter;
	}

	/**
	 * @param string $value the user manager module ID or the user manager object
	 * @throws TInvalidOperationException if the module has been initialized or the user manager object is not IUserManager
	 */
	public function setLoadParameter($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('permissions_property_unchangeable', 'LoadParameter');
		}
		$this->_parameter = $value;
	}
	
	/**
	 * detaches the class behaviors
	 */
	public function __destruct()
	{
		TComponent::detachClassBehavior(static::PERMISSIONS_BEHAVIOR, 'Prado\\Security\\Permissions\\IPermissions');
		TComponent::detachClassBehavior(static::USER_PERMISSIONS_BEHAVIOR, 'Prado\\Security\\IUser');
		TComponent::detachClassBehavior(static::PERMISSIONS_CONFIG_BEHAVIOR, 'Prado\\Web\\Services\\TPageConfiguration');
		parent::__destruct();
	}
}
