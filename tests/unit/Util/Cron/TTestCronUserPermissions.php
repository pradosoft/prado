<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Util\TBehavior;

/**
 * Stands in for the user permissions behavior: the user can only the permissions in {@see $allowed}.
 */
class TTestCronUserPermissions extends TBehavior
{
	/** @var string[] the permissions the user has */
	public array $allowed = [];

	public function can(string $permission): bool
	{
		return in_array($permission, $this->allowed, true);
	}
}
