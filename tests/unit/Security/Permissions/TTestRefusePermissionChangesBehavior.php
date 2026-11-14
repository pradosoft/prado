<?php

namespace Prado\Test\Unit\Security\Permissions;

use Prado\Util\TBehavior;

/**
 * Handles the runtime-change dynamic events of {@see TPermissionsManager} as refused.
 */
class TTestRefusePermissionChangesBehavior extends TBehavior
{
	public function dyAddRoleChildren($return, $role, $children, $callchain)
	{
		return true;
	}

	public function dyRemoveRoleChildren($return, $role, $children, $callchain)
	{
		return true;
	}

	public function dyAddPermissionRule($return, $permission, $rule, $callchain)
	{
		return true;
	}

	public function dyRemovePermissionRule($return, $permission, $rule, $callchain)
	{
		return true;
	}

	public function dyRegisterShellAction($return, $callchain)
	{
		return true;
	}
}
