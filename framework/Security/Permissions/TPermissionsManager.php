<?php
/**
 * TPermissionsManager class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
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
 * TPermissionsManager handles Permissions authorization and Roll Based
 * Access Control (RBAC).  Each registered Permission is given a set of
 * {@link \Prado\Security\TAuthorizationRule}s.  The RBAC is based on roles
 * having children roles and permissions, with permissions being thought of
 * as special roles themselves.
 *
 * TPermissionsManager attaches {@link TPermissionsBehavior} to all classes
 * that implement {@link IPermissions}.  This is the main mechanism
 * by which application permissions are registered.
 *
 * The role hierarchy and permission rules are unique to each application.  The
 * permissions configuration is defined in the TPermissionsManager application
 * configuration or in a separate {@link PermissionsFile}. {@link TPermissionsConfigurationBehavior}
 * enables a page configuration to have Permission Configurations as well.
 * A {@link TDbParameterModule} can be specified with {@link getDbParameter} for
 * loading dynamic roles and permissions.
 *
 * Module XML configurations (and similarly PermissionFile) follows the format, eg:
 * <code>
 * <module id="permissions" class="Prado\Security\Permissions\TPermissionsManager" DefaultRoles="Default" SuperRoles="Administrator">
 *	<role name="Developer" children="all, param_shell_permission, cron" />
 *	<role name="Manager" children="editor, change_user_role_permission, cron_shell" />
 *	<role name="cron_shell" children="cron_add_task, cron_update_task, cron_remove_task" />
 *	<role name="cron" children="cron_shell, cron_manage_log, cron_add_task, cron_update_task, cron_remove_task" />
 *  <role name="Default" children="register_user, blog_read_posts, blog_comment">
 *	<permissionrule name="param_shell_permission" action="deny" users="*" roles="" verb="*" IPs="" />
 *	<permissionrule name="cron_shell" action="allow" users="*" roles="Developer,cron_shell,cron_manage_log" verb="*" IPs="" />
 *	<permissionrule name="register_user" action="allow" users="?" />
 *	<permissionrule name="register_user" action="allow" roles="Manager" />
 *	<permissionrule name="change_profile" action="deny" users="?" priority="0" />
 *	<permissionrule name="blog_update_posts" class="Prado\Security\Permissions\TUserOwnerRule" Priority="5" />
 *	<permissionrule name="cron" action="allow" users="admin, user1, user2" roles="*" verb="*" IPs="*"  />
 *	<permissionrule name="blog_*" action="allow" users="admin, user1, user2" roles="*" verb="*" IPs="*"  />
 *	<permissionrule name="*" action="deny" priority="1000" />
 * </module>
 * </code>
 *
 * and in PHP the same file would follow the following format, eg:
 * <code>
 * 'modules' => [
 * 'permissions' => ['class' => 'Prado\Security\Permissions\TPermissionsManager',
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
 *		]
 * ]
 * </code>
 *
 * In this example, "cron" is not a permission, but when used as a permission,
 * all children roles/permissions will receive the rule.  Permissions with children,
 * such as 'cron_shell' (above), will give all its children the rule as
 * well.
 *
 * A special role "All" is automatically created to contain all the permissions.
 * Specifying "all" as a child, is the same as specifying a role as a super role
 * via {@link setSuperRoles}.
 *
 * All users get the roles specified by {@link getDefaultRoles}.  This changes
 * the default Prado behavior for guest users having no roles.
 *
 * Intermediate roles, that are not defined in the user system, may be defined in
 * the hierarchy, in the above example the "cron" role is not defined by the system,
 * but is defined in the role hierarchy.
 *
 * Permission Rules can have multiple rules. they are
 * ordered by natural specified configuration order unless the rule property
 * {@link TAuthorizationRule::setPriority} is set.
 *
 * Permissions authorization rules may use the '*' or 'perm_*' to add the rules to all
 * matching permission names.  Anything before the * is matched as a permission.
 * This does not traverse the hierarchy roles matching the name, just the permissions
 * are matched for the TAuthorizationRule.
 *
 * A permission name must list itself as a role in TAuthorizationRule for the user to be
 * validated for that permission name for authorization.  This is handled automatically
 * by TPermissionManager with the {@link getAutoAllowWithPermission} property.
 * By default getAutoAllowWithPermission is true, and allows any user with
 * that permission in their hierarchy to allow access to the functionality.
 * This rule priority can be set with {@link getAutoRulePriority},
 * where the default is 5, and -thus- before user defined rules.
 *
 * The second automatic rules includes Modules have their own preset rules that can
 * be automatically added via {@link getAutoPresetRules}.  By default this
 * is true. These rules typically allow owners of the data to be permitted without having
 * a permission-role.  Preset rules can define their own priorities but those
 * without set priorities receive the priority from {@link getAutoRulePriority}.
 *
 * The third, and last, auto-Rule is the final {@link getAutoDenyAll DenyAll}
 * rule. This is the last rule that denies all by default.  The AutoDenyAll
 * gets its rule priority from {@link getAutoDenyAllPriority}.  By default,
 * deny all to all permissions is enabled and thus blocking all permissions.
 *
 * Recursive hierarchy is gracefully handled, in case of any loop structures.
 *
 * When TPermissionsManager is a module in your app, there are three permissions
 * to control user access to its function:
 *  - TPermissionsManager::PERM_PERMISSIONS_SHELL 'permissions_shell' enables the shell commands.
 *  - TPermissionsManager::PERM_PERMISSIONS_MANAGE_ROLES 'permissions_manage_roles' enables adding and removing roles and children.
 *  - TPermissionsManager::PERM_PERMISSIONS_MANAGE_RULES 'permissions_manage_rules' enables adding and removing rules for permissions and roles.
 *
 * The role and rule management functions only work when the TDbParameter Module is specified.
 * The following gives user "admin" and all users with "Administrators" role the
 * permission to access permissions shell and its full functionality:
 * <code>
 *	 <role name="permissions_shell" children="permissions_manage_roles, permissions_manage_rules" />
 *   <permissionrule name="permissions_shell" action="allow" users="admin" />
 *   <permissionrule name="permissions_shell" action="allow" roles="Administrators" />
 * <code>
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @method bool dyRegisterShellAction($returnValue)
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

	public const PERM_PERMISSIONS_SHELL = 'permissions_shell';

	public const PERM_PERMISSIONS_MANAGE_ROLES = 'permissions_manage_roles';

	public const PERM_PERMISSIONS_MANAGE_RULES = 'permissions_manage_rules';

	/** @var string[] roles that get all permissions, default [] */
	private $_superRoles;

	/** @var string[] Default roles to give all users, default [] */
	private $_defaultRoles;

	/** @var array<string, \Prado\Security\TAuthorizationRuleCollection> contains the rules for each permission */
	private $_permissionRules = [];

	/** @var array<string, string> contains the short descriptions for each permission */
	private $_descriptions = [];

	/** @var array<string, \Prado\Security\TAuthorizationRule[]> the rules to apply to newly registered Permissions */
	private $_autoRules = [];

	/** @var array<string, string[]> contains the hierarchy of roles and children roles/permissions */
	private $_hierarchy = [];

	/** @var bool is the module initialized */
	private $_initialized = false;

	/** @var string role hierarchy and permission rules information file */
	private $_permissionFile;

	/** @var numeric the priority of the Allow With Permission Rule, default 5 */
	private $_autoRulePriority = 5;

	/** @var bool add allow users with permission-role, default true  */
	private $_autoAllowWithPermission = true;

	/** @var bool add module rules, allows User's data, default true */
	private $_autoRulePresetRules = true;

	/** @var bool add Deny All rule to every permissions as the last rule, default true */
	private $_autoDenyAll = true;

	/** @var numeric the priority of the module Rule, usually these are Allow User As Owner, default 1000000 */
	private $_autoDenyAllPriority = 1000000;

	/** @var \Prado\Util\TDbParameterModule the database module providing runtime roles and rules */
	private $_dbParameter;

	/** @var numeric the priority of the module Rule, usually these are Allow User As Owner */
	private $_parameter = 'configuration:TPermissionsManager:runtime';

	// hierarchy from parameter

	/**
	 * @param \Prado\Security\Permissions\TPermissionsManager $manager
	 * @return TPermissionEvent[] the dynamic events to have authorization
	 */
	public function getPermissions($manager)
	{
		return [
			new TPermissionEvent(static::PERM_PERMISSIONS_SHELL, 'Activates permissions shell commands.', 'dyRegisterShellAction'),
			new TPermissionEvent(static::PERM_PERMISSIONS_MANAGE_ROLES, 'Manages Db Permissions Role Children.', ['dyAddRoleChildren', 'dyRemoveRoleChildren']),
			new TPermissionEvent(static::PERM_PERMISSIONS_MANAGE_RULES, 'Manages Db Permissions Rules.', ['dyAddPermissionRule', 'dyRemovePermissionRule']),
		];
	}

	/**
	 * @param array|TXmlElement $config the application configuration
	 */
	public function init($config)
	{
		$app = $this->getApplication();
		if (is_string($this->_dbParameter)) {
			if (($dbParameter = $app->getModule($this->_dbParameter)) === null) {
				throw new TConfigurationException('permissions_dbparameter_nonexistent', $this->_dbParameter);
			}
			if (!($dbParameter instanceof TDbParameterModule)) {
				throw new TConfigurationException('permissions_dbparameter_invalid', $this->_dbParameter);
			}
			$this->_dbParameter = $dbParameter;
		}

		if ($this->_initialized) {
			throw new TInvalidOperationException('permissions_init_once');
		}
		$this->_initialized = true;

		$manager = \WeakReference::create($this);
		TComponent::attachClassBehavior(static::PERMISSIONS_BEHAVIOR, ['class' => TPermissionsBehavior::class, 'permissionsmanager' => $manager], IPermissions::class, -10);
		TComponent::attachClassBehavior(static::USER_PERMISSIONS_BEHAVIOR, ['class' => TUserPermissionsBehavior::class, 'permissionsmanager' => $manager], \Prado\Security\IUser::class, -10);
		TComponent::attachClassBehavior(static::PERMISSIONS_CONFIG_BEHAVIOR, ['class' => TPermissionsConfigurationBehavior::class, 'permissionsmanager' => $manager], \Prado\Web\Services\TPageConfiguration::class, -10);

		$this->loadPermissionsData($config);
		if ($this->_permissionFile !== null) {
			if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
				$userFile = include $this->_permissionFile;
				$this->loadPermissionsData($userFile);
			} else {
				$dom = new TXmlDocument();
				$dom->loadFromFile($this->_permissionFile);
				$this->loadPermissionsData($dom);
			}
		}
		if ($this->_dbParameter) {
			$this->loadPermissionsData($this->_dbParameter->get($this->_parameter));
		}

		foreach (array_map('strtolower', $this->getSuperRoles() ?? []) as $role) {
			$this->_hierarchy[$role] = array_merge(['all'], $this->_hierarchy[$role] ?? []);
		}

		$app->attachEventHandler('onAuthenticationComplete', [$this, 'registerShellAction']);

		parent::init($config);
	}

	/**
	 * Registers a permission name with description and preset rules.
	 * @param string $permissionName name of the permission
	 * @param string $description description of the permission
	 * @param null|\Prado\Security\TAuthorizationRule[] $rules
	 */
	public function registerPermission($permissionName, $description, $rules = null)
	{
		$permission = strtolower($permissionName);
		$this->_descriptions[$permission] = TPropertyValue::ensureString($description);

		if ($this->_autoDenyAll === true) {
			$this->_autoDenyAll = 2;
			$this->addPermissionRuleInternal('*', new TAuthorizationRule('deny', '*', '*', '*', '*', $this->_autoDenyAllPriority));
		}

		$this->_hierarchy['all'][] = $permission;

		if (!isset($this->_permissionRules[$permission])) {
			$this->_permissionRules[$permission] = new TAuthorizationRuleCollection();
		} else {
			throw new TInvalidOperationException('permissions_duplicate_permission', $permissionName);
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
	 * gets the short description of the permission
	 * @param string $permissionName name of the permission
	 * @return string short description of the permission
	 */
	public function getPermissionDescription($permissionName)
	{
		return $this->_descriptions[strtolower($permissionName)];
	}

	/**
	 * Loads the roles, children, and permission rules.
	 * @param array|\Prado\Xml\TXmlElement $config configurations to parse
	 */
	public function loadPermissionsData($config)
	{
		$isXml = false;
		if (!$config) {
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
					throw new TConfigurationException('permissions_role_children_invalid', $role, is_object($children) ? $children::class : $children);
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
					throw new TConfigurationException('permissions_rule_invalid', $name);
				}
			}
			if (is_numeric($name) && (!isset($properties[0]) || !$properties[0] instanceof TAuthorizationRule)) {
				$name = strtolower($properties['name'] ?? '');
				if (!$name) {
					throw new TConfigurationException('permissions_rules_require_name');
				}
				$class = $properties['class'] ?? TAuthorizationRule::class;
				$action = $properties['action'] ?? '';
				$users = $properties['users'] ?? '';
				$roles = $properties['roles'] ?? '';
				$verb = $properties['verb'] ?? '';
				$ips = $properties['ips'] ?? '';
				$priority = $properties['priority'] ?? '';

				$rule = new $class($action, $users, $roles, $verb, $ips, $priority);
			} else {
				$rule = $properties;
			}
			$this->addPermissionRuleInternal($name, $rule);
		}
	}

	/**
	 * Adds a permission rule to a permission name. Names can contain the '*' character
	 * and every permission with a matching name before the '*' will get the rule
	 * @param string $name Permission name
	 * @param \Prado\Security\TAuthorizationRule|\Prado\Security\TAuthorizationRule[] $rule
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
	 * Removes a permission rule from a permission name.
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
	 * @param object $sender sender of this event handler
	 * @param null|mixed $param parameter for the event
	 */
	public function registerShellAction($sender, $param)
	{
		if ($this->dyRegisterShellAction(false) !== true && ($app = $this->getApplication()) instanceof \Prado\Shell\TShellApplication) {
			$app->addShellActionClass(['class' => TPermissionsAction::class, 'PermissionsManager' => $this]);
		}
	}

	/**
	 * checks if the $permission is in the $roles hierarchy.
	 * @param string[] $roles the roles to check the permission
	 * @param string $permission the permission-role being checked for in the hierarchy
	 * @param array<string, bool> &$checked the roles already checked
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
	 * Get the roles that are runtime from the database
	 * @return array<string, string[]> roles and children from the database
	 */
	public function getDbConfigRoles()
	{
		if (!$this->_dbParameter || !$this->_parameter) {
			return [];
		}
		$runtimeData = $this->_dbParameter->get($this->_parameter) ?? [];
		return $runtimeData['roles'] ?? [];
	}

	/**
	 * Get the permission rules that are runtime from the database
	 * @return array<string, \Prado\Security\TAuthorizationRule[]>
	 */
	public function getDbConfigPermissionRules()
	{
		if (!$this->_dbParameter || !$this->_parameter) {
			return [];
		}
		$runtimeData = $this->_dbParameter->get($this->_parameter) ?? [];
		return $runtimeData['permissionrules'] ?? [];
	}

	/**
	 * This adds children to a role within the runtime context.  The children
	 * can be a single comma separated string.
	 * @param string $role the role to add children
	 * @param string|string[] $children the children to add to the role
	 * @throws TInvalidDataValueException when children is not an array
	 * @return bool was the method successful
	 */
	public function addRoleChildren($role, $children)
	{
		if ($this->dyAddRoleChildren(false, $role, $children) === true || !$this->_dbParameter) {
			return false;
		}
		if (is_string($children)) {
			$children = array_map('trim', explode(',', $children));
		} elseif (!is_array($children)) {
			throw new TInvalidDataValueException('permissions_children_invalid', is_object($children) ? $children::class : $children);
		}
		$role = strtolower($role);
		$children = array_map('strtolower', array_filter($children));
		$this->_hierarchy[$role] = array_merge($this->_hierarchy[$role] ?? [], $children);

		$runtimeData = $this->_dbParameter->get($this->_parameter) ?? [];
		$runtimeData['roles'] ??= [];
		$runtimeData['roles'][$role] = array_unique(array_merge($runtimeData['roles'][$role] ?? [], $children));
		$this->_dbParameter->set($this->_parameter, $runtimeData);

		return true;
	}

	/**
	 * This removes children from a role within the runtime context.  The children
	 * can be a single comma separated string.
	 * @param string $role the role to add children
	 * @param string|string[] $children the children to add to the role
	 * @throws TInvalidDataValueException when children is not an array
	 * @return bool was the method successful
	 */
	public function removeRoleChildren($role, $children)
	{
		if ($this->dyRemoveRoleChildren(false, $role, $children) === true || !$this->_dbParameter) {
			return false;
		}
		if (is_string($children)) {
			$children = array_map('trim', explode(',', $children));
		} elseif (!is_array($children)) {
			throw new TInvalidDataValueException('permissions_children_invalid', is_object($children) ? $children::class : $children);
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
	 * This method adds permission rules with in the runtime context.
	 * @param string $permission
	 * @param \Prado\Security\TAuthorizationRule $rule
	 * @return bool was the method successful
	 */
	public function addPermissionRule($permission, $rule)
	{
		$permission = strtolower($permission);

		if ($this->dyAddPermissionRule(false, $permission, $rule) === true || !$this->_dbParameter) {
			return false;
		}
		$this->addPermissionRuleInternal($permission, $rule);

		$runtimeData = $this->_dbParameter->get($this->_parameter) ?? [];
		$runtimeData['permissionrules'] ??= [];
		$runtimeData['permissionrules'][$permission][] = $rule;
		$this->_dbParameter->set($this->_parameter, $runtimeData);

		return true;
	}

	/**
	 * This method removes permission rules with in the runtime context.
	 * @param string $permission a permission or role to remove the rule from
	 * @param \Prado\Security\TAuthorizationRule $rule
	 * @return bool was the method successful
	 */
	public function removePermissionRule($permission, $rule)
	{
		$permission = strtolower($permission);

		if ($this->dyRemovePermissionRule(false, $permission, $rule) === true || !$this->_dbParameter) {
			return false;
		}

		$this->removePermissionRuleInternal($permission, $rule);

		$runtimeData = $this->_dbParameter->get($this->_parameter) ?? [];
		$runtimeData['permissionrules'] ??= [];

		if (($index = array_search($rule, $runtimeData['permissionrules'][$permission] ?? [], true)) === false) {
			return false;
		}
		unset($runtimeData['permissionrules'][$permission][$index]);
		if (!$runtimeData['permissionrules'][$permission]) {
			unset($runtimeData['permissionrules'][$permission]);
		} else {
			$runtimeData['permissionrules'][$permission] = array_values($runtimeData['permissionrules'][$permission]);
		}
		$this->_dbParameter->set($this->_parameter, $runtimeData);

		return true;
	}

	/**
	 * Gets all the roles in the hierarchy, though may not be valid roles in the application.
	 * @return string[] the roles in the hierarchy.
	 */
	public function getHierarchyRoles()
	{
		return array_keys($this->_hierarchy);
	}

	/**
	 * Gets the children for a specific role in the hierarchy.
	 * @param string $role the role to return its children
	 * @return null|string[] the children of a specific role.
	 */
	public function getHierarchyRoleChildren($role)
	{
		if (!$role) {
			return $this->_hierarchy;
		}
		return $this->_hierarchy[strtolower(TPropertyValue::ensureString($role))] ?? null;
	}

	/**
	 * @param null|string $permission
	 * @return null|array<string, TAuthorizationRuleCollection>|TAuthorizationRuleCollection
	 */
	public function getPermissionRules($permission)
	{
		if (is_string($permission)) {
			return $this->_permissionRules[strtolower($permission)] ?? null;
		} else {
			return $this->_permissionRules;
		}
	}

	/**
	 * All super roles will get "all" roles and thus all permissions on module init.
	 * @return null|string[] array of rolls that get all permissions
	 */
	public function getSuperRoles()
	{
		return $this->_superRoles;
	}

	/**
	 * sets the super roles to get all permissions.
	 * @param string|string[] $roles  of rolls that get all permissions
	 * @throws \Prado\Exceptions\TInvalidOperationException when the module is initialized
	 */
	public function setSuperRoles($roles)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('permissions_property_unchangeable', 'SuperRoles');
		}
		if (!is_array($roles)) {
			$roles = array_map('trim', explode(',', $roles));
		}
		$this->_superRoles = array_filter($roles);
		;
	}

	/**
	 * Gets the default roles of all users.
	 * @return null|string[] the default roles of all users
	 */
	public function getDefaultRoles()
	{
		return $this->_defaultRoles;
	}

	/**
	 * @param string|string[] $roles the default roles of all users
	 * @throws \Prado\Exceptions\TInvalidOperationException when the module is initialized
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
	 * @return string the full path to the file storing role/rule information
	 */
	public function getPermissionFile()
	{
		return $this->_permissionFile;
	}

	/**
	 * @param string $value role/rule data file path (in namespace form). The file format is configuration format
	 * whose content is similar to that role/rule block in the module configuration.
	 * @throws \Prado\Exceptions\TInvalidOperationException if the module is already initialized
	 * @throws \Prado\Exceptions\TConfigurationException if the file is not in proper namespace format
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
	 * @return numeric the priority of Allow With Permission and Preset Rules, default 5
	 */
	public function getAutoRulePriority()
	{
		return $this->_autoRulePriority;
	}

	/**
	 * @param numeric $priority the priority of Allow With Permission and Preset Rules
	 * @throws \Prado\Exceptions\TInvalidOperationException if the module is already initialized
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
	 * @throws \Prado\Exceptions\TInvalidOperationException if the module is already initialized
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
	 * @throws \Prado\Exceptions\TInvalidOperationException if the module is already initialized
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
	 * @throws \Prado\Exceptions\TInvalidOperationException if the module is already initialized
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
	 * @throws \Prado\Exceptions\TInvalidOperationException if the module is already initialized
	 */
	public function setAutoDenyAllPriority($priority)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('permissions_property_unchangeable', 'AutoDenyAllPriority');
		}
		$this->_autoDenyAllPriority = is_numeric($priority) ? $priority : (float) $priority;
	}

	/**
	 * @return \Prado\Util\TDbParameterModule DbParameter instance
	 */
	public function getDbParameter()
	{
		return $this->_dbParameter;
	}

	/**
	 * @param \Prado\Security\IUserManager|string $provider the user manager module ID or the DbParameter object
	 * @throws \Prado\Exceptions\TInvalidOperationException if the module is already initialized
	 * @throws \Prado\Exceptions\TConfigurationException if the $provider is not a TDbParameterModule
	 */
	public function setDbParameter($provider)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('permissions_property_unchangeable', 'DbParameter');
		}
		if ($provider !== null && !is_string($provider) && !($provider instanceof TDbParameterModule)) {
			throw new TConfigurationException('permissions_dbparameter_invalid', is_object($provider) ? $provider::class : $provider);
		}
		$this->_dbParameter = $provider;
	}

	/**
	 * @return string name of the parameter to load
	 */
	public function getLoadParameter()
	{
		return $this->_parameter;
	}

	/**
	 * @param string $value name of the parameter to load
	 * @throws \Prado\Exceptions\TInvalidOperationException if the module is already initialized
	 */
	public function setLoadParameter($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('permissions_property_unchangeable', 'LoadParameter');
		}
		$this->_parameter = $value;
	}

	/**
	 * detaches the automatic class behaviors
	 */
	public function __destruct()
	{
		TComponent::detachClassBehavior(static::PERMISSIONS_BEHAVIOR, IPermissions::class);
		TComponent::detachClassBehavior(static::USER_PERMISSIONS_BEHAVIOR, \Prado\Security\IUser::class);
		TComponent::detachClassBehavior(static::PERMISSIONS_CONFIG_BEHAVIOR, \Prado\Web\Services\TPageConfiguration::class);
		parent::__destruct();
	}
}
