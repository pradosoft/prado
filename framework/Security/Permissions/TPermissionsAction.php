<?php
/**
 * TPermissionsAction class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Security\Permissions;

use Prado\Prado;
use Prado\Security\TAuthorizationRule;
use Prado\Shell\TShellAction;
use Prado\Shell\TShellWriter;
use Prado\TPropertyValue;

/**
 * TPermissionsAction class.
 *
 * The indexes, displays rolls with children and permissions rules, and can edit
 * Db roles, children and rules.
 *
 * @author Brad Anderson <belisoful[at]icloud[dot]com>
 * @since 4.2.0
 */
class TPermissionsAction extends TShellAction
{
	protected $action = 'perm';
	protected $methods = ['index', 'role', 'add-rule', 'remove-rule'];
	protected $parameters = [null, 'role-name', ['permission-name', 'action'], ['permission-name', 'db-rule-index']];
	protected $optional = [null, ['[+-]child', '...'], ['users', 'roles', 'verb', 'ips', 'priority', 'class'], null];
	protected $description = [
		'Provides information about Permissions.',
		'Displays DB permission information. \'-a\' for all.',
		'Add and remove children from roles in the DB.',
		'Add a rule to the DB for a specific permission.',
		'Remove a rule from the DB for a specific permission.'];

	private $_allPerms = false;

	private $_manager = false;

	/**
	 *
	 */
	public function getAll()
	{
		return $this->_allPerms;
	}

	/**
	 * @param bool $value If this is called, set the property to true
	 */
	public function setAll($value)
	{
		$this->_allPerms = TPropertyValue::ensureBoolean($value === '' ? true : $value);
	}

	/**
	 * Properties for the action set by parameter
	 * @param string $actionID the action being executed
	 * @return array properties for the $actionID
	 */
	public function options($actionID): array
	{
		if ($actionID === 'index') {
			return ['all'];
		}
		return [];
	}

	/**
	 * Aliases for the properties to be set by parameter
	 * @return array<string, string> aliaas => property for the $actionID
	 */
	public function optionAliases(): array
	{
		return ['a' => 'all'];
	}

	/**
	 *
	 * @param mixed $rule
	 * @param null|mixed $writer
	 */
	protected function ruleToString($rule, $writer = null)
	{
		if (!$writer) {
			$writer = $this->getWriter();
		}
		$users = $rule->getUsers();
		if ($rule->getEveryoneApplied()) {
			$users[] = '*';
		} else {
			if ($rule->getAuthenticatedApplied()) {
				$users[] = '@';
			}
			if ($rule->getGuestApplied()) {
				$users[] = '?';
			}
		}

		return ($writer->format($a = $rule->getAction(), [TShellWriter::BOLD, $a === 'allow' ? TShellWriter::GREEN : TShellWriter::RED]) . ': ') .
			($rule::class === TUserOwnerRule::class ? 'User Owner- ' : '') .
			(($p = $rule->getPriority()) ? '∆' . $p . ' ' : '') .
			(($users[0] !== '*') ? 'users="' . implode(', ', $users) . '" ' : '') .
			((($r = $rule->getRoles()) && (count($r) !== 1 || $r[0] !== '*')) ? 'roles="' . implode(', ', $r) . '" ' : '') .
			((($v = $rule->getVerb()) && $v !== '*') ? 'verb="' . $v . '" ' : '') .
			((($ip = $rule->getIPRules()) && (count($ip) !== 1 || $ip[0] !== '*')) ? 'ip="' . implode(', ', $ip) . '"' : '');
	}

	/**
	 * display the database parameter key values.
	 * @param array $args parameters
	 * @return bool is the action handled
	 */
	public function actionIndex($args)
	{
		$writer = $this->getWriter();
		if (!($manager = $this->getPermissionsManager())) {
			$writer->writeError('No TPermissionsManager found to report');
			return true;
		}

		$writer->writeLine();
		$writer->writeLine('Permissions Manager Information', TShellWriter::UNDERLINE);
		$writer->writeLine();
		$writer->writeLine('Super Roles: ' . implode(', ', $manager->getSuperRoles() ?? ['(none)']));
		$writer->writeLine('Default Roles: ' . implode(', ', $manager->getDefaultRoles() ?? ['(none)']));

		$dbConfigRoles = $manager->getDbConfigRoles();
		$dbConfigRules = $manager->getDbConfigPermissionRules();
		$roles = $this->getAll() ? $manager->getHierarchyRoleChildren(null) : $dbConfigRoles;
		$rules = $this->getAll() ? $manager->getPermissionRules(null) : $dbConfigRules;
		$len = 0;
		foreach ($roles as $role => $children) {
			if (($l = strlen($role)) > $len) {
				$len = $l;
			}
		}
		$writer->writeLine();
		$writer->write("    ");
		$writer->writeLine('Roles:', TShellWriter::UNDERLINE);
		foreach ($roles as $role => $children) {
			$writer->write($writer->pad($role, $len + 1));
			$writer->writeLine($writer->wrapText(implode(', ', $children), $len + 1));
		}

		$len = 0;
		foreach ($rules as $permName => $permRules) {
			if (($l = strlen($permName)) > $len) {
				$len = $l;
			}
		}
		$writer->writeLine();
		$writer->write("    ");
		$writer->writeLine('Permission Rules:', TShellWriter::UNDERLINE);
		foreach ($rules as $name => $collection) {
			$writer->write($writer->pad($name, $len + 1));
			$rules[$name] = [];
			$i = 0;
			foreach ($collection as $key => $rule) {
				$rules[$name][] = '#' . ($i++) . ' ' . $this->ruleToString($rule, $writer);
			}
			$writer->writeLine($writer->wrapText(implode("\n", $rules[$name]), $len + 1));
			$writer->writeLine();
		}
		$writer->writeLine();
		return true;
	}

	/**
	 * get children of a role, and adds to and removes children from a db configuration.
	 * @param array $args parameters
	 * @return bool is the action handled
	 */
	public function actionRole($args)
	{
		$writer = $this->getWriter();

		if (!($manager = $this->getPermissionsManager())) {
			$writer->writeError('No TPermissionsManager found to view and edit permissions');
			return true;
		}

		if (!$manager->getDbParameter()) {
			$writer->writeError('TPermissionsManager has no DbParameter to store db permissions configurations');
			return true;
		}

		$writer->writeLine();

		if (!($role = ($args[1] ?? null))) {
			$writer->writeError('Action requires <role-name> to view and edit');
			return true;
		}
		$role = strtolower($role);
		$diff_children = [];
		$merge_children = [];
		for ($i = 2; $i < count($args); $i++) {
			if ($args[$i][0] === '-') {
				$diff_children[] = substr($args[$i], 1);
			} else {
				$merge_children[] = substr($args[$i], $args[$i][0] === '+' ? 1 : 0);
			}
		}
		if ($merge_children) {
			if (!$manager->addRoleChildren($role, $merge_children)) {
				$writer->writeError('Could not add role children');
				return true;
			}
		}
		if ($diff_children) {
			if (!$manager->removeRoleChildren($role, $diff_children)) {
				$writer->writeError('Could not remove role children');
				return true;
			}
		}
		if ($merge_children || $diff_children) {
			$writer->writeLine("   Role Children Change Successful", [TShellWriter::GREEN, TShellWriter::BOLD]);
			$writer->writeLine();
		}
		$roles = $manager->getDbConfigRoles();
		$writer->write("    ");
		$writer->writeLine('Current Db Role and Children', TShellWriter::UNDERLINE);

		$writer->write($role, [TShellWriter::BOLD, TShellWriter::BLUE]);
		$writer->write(' ');
		if (!($roles[$role] ?? null)) {
			$writer->write('(no children)', TShellWriter::DARK_GRAY);
		} else {
			$writer->writeLine($writer->wrapText(implode(", ", $roles[$role] ?? []), strlen($role) + 1));
		}
		$writer->writeLine();
		$writer->writeLine();
		return true;
	}

	/**
	 * adds a DB Configuration Permission Rule.  Here is the format of the function
	 * arguments.
	 * 'perm/add-rule' permission_name action users roles verb ips priority
	 * and can be use like this:
	 * ```sh
	 * 	prado-cli perm/add-rule '*' deny '*' 'Default' '*' '192.168.*' 1000
	 * ```
	 * @param array $args parameters
	 * @return bool is the action handled
	 */
	public function actionAddRule($args)
	{
		$writer = $this->getWriter();

		if (!($manager = $this->getPermissionsManager())) {
			$writer->writeError('No TPermissionsManager found to view and edit permissions');
			return true;
		}
		if (!$manager->getDbParameter()) {
			$writer->writeError('TPermissionsManager has no DbParameter to store db permissions configurations');
			return true;
		}

		if (!($name = ($args[1] ?? null))) {
			$writer->writeError('Permissions needs a name to add a rule');
			return true;
		}
		$name = strtolower($name);
		if (!($action = strtolower($args[2] ?? ''))) {
			$writer->writeError('Permissions needs an action [allow, deny] to add the rule');
			return true;
		}
		if (!in_array($action, ['allow', 'deny'])) {
			$writer->writeError("Permissions action '{$action}' is not [allow, deny]");
			return true;
		}
		$users = $args[3] ?? null;
		$roles = $args[4] ?? null;
		$verb = $args[5] ?? null;
		$ips = $args[6] ?? null;
		$priority = (!is_numeric($args[7] ?? null)) ? null : $args[7];
		$class = $args[8] ?? TAuthorizationRule::class;

		if (!$users) {
			$users = '*';
		}
		if (!$roles) {
			$roles = '*';
		}
		if (!$verb) {
			$verb = '*';
		}
		if (!in_array($verb, ['*', 'get', 'post'])) {
			$writer->writeError("Permissions verb '{$verb}' is not [*, get, post]");
			return true;
		}
		if (!$ips) {
			$ips = '*';
		}

		$rule = Prado::createComponent($class, $action, $users, $roles, $verb, $ips, $priority);

		if (!$manager->addPermissionRule($name, $rule)) {
			$writer->writeError('Could not add permission rule');
			return true;
		}
		$writer->writeLine();
		$writer->writeLine("   Added Permission Rule Successful", [TShellWriter::GREEN, TShellWriter::BOLD]);

		$dbConfigRules = $manager->getDbConfigPermissionRules();

		$writer->writeLine();
		$writer->write("    ");
		$writer->writeLine("Permission Rules for '{$name}':", TShellWriter::UNDERLINE);
		foreach ($dbConfigRules[$name] as $key => $rule) {
			$writer->writeLine($writer->wrapText('#' . ($key) . ' ' . $this->ruleToString($rule, $writer), 10));
		}
		$writer->writeLine();

		return true;
	}

	/**
	 * removes a DB Configuration Permission Rule
	 * @param array $args parameters
	 * @return bool is the action handled
	 */
	public function actionRemoveRule($args)
	{
		$writer = $this->getWriter();

		if (!($manager = $this->getPermissionsManager())) {
			$writer->writeError('No TPermissionsManager found to view and edit permissions');
			return true;
		}

		if (!$manager->getDbParameter()) {
			$writer->writeError('TPermissionsManager has no DbParameter to store db permissions configurations');
			return true;
		}

		if (!($name = ($args[1] ?? null))) {
			$writer->writeError('Permissions needs a name to remove a rule');
			return true;
		}
		$name = strtolower($name);
		if (!is_numeric($index = ($args[2] ?? null))) {
			$writer->writeError("Permission rule index '{$index}' is not valid");
			return true;
		}

		$dbConfigRules = $manager->getDbConfigPermissionRules();

		if (!isset($dbConfigRules[$name])) {
			$writer->writeError('No rules for specified permission');
			return true;
		}
		if (!isset($dbConfigRules[$name][$index])) {
			$writer->writeError("No rule at index '{$index}' for specified permission '{$name}'");
			return true;
		}

		if (!$manager->removePermissionRule($name, $dbConfigRules[$name][$index])) {
			$writer->writeError('Could not add permission rule');
			return true;
		}
		$writer->writeLine();
		$writer->writeLine("Remove Permission Rule Successful", [TShellWriter::GREEN, TShellWriter::BOLD]);

		$dbConfigRules = $manager->getDbConfigPermissionRules();

		$writer->writeLine();
		$writer->write("    ");
		$writer->writeLine("Permission Rules for '{$name}':", TShellWriter::UNDERLINE);
		if (isset($dbConfigRules[$name])) {
			foreach ($dbConfigRules[$name] as $key => $rule) {
				$writer->writeLine($writer->wrapText('#' . ($key) . ' ' . $this->ruleToString($rule, $writer), 10));
			}
		} else {
			$writer->writeLine("(No Rules for Permission)", TShellWriter::DARK_GRAY);
		}

		$writer->writeLine();

		return true;
	}

	/**
	 * get the TPermissionsManager
	 * @return \Prado\Security\Permissions\TPermissionsManager
	 */
	public function getPermissionsManager()
	{
		if ($this->_manager === false) {
			$this->_manager = null;
			$app = Prado::getApplication();
			foreach ($app->getModulesByType(\Prado\Security\Permissions\TPermissionsManager::class) as $id => $module) {
				if ($this->_manager = $app->getModule($id)) {
					break;
				}
			}
		}
		return $this->_manager;
	}

	/**
	 * get the TPermissionsManager from the Application
	 * @param \Prado\Security\Permissions\TPermissionsManager $manager
	 */
	public function setPermissionsManager($manager)
	{
		$this->_manager = $manager;
	}
}
