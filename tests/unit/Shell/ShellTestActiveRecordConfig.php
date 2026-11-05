<?php

namespace Prado\Test\Unit\Shell;

use Prado\Data\ActiveRecord\TActiveRecordConfig;

/**
 * A minimal TActiveRecordConfig stub used to verify that
 * {@see TShellApplication::installShellActions()} registers {@see TActiveRecordAction}
 * only when at least one {@see TActiveRecordConfig} module is present.
 */
class ShellTestActiveRecordConfig extends TActiveRecordConfig
{
	public function init($config) {}
}
