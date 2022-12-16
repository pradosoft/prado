<?php


class ClearCronTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}

	public function testLog()
	{
		// Initialize the Cron DB by deleting any remnants of prior tests
		// Remnants of prior tests (and errors [by not cleaning up]) can screw up the current round of tests.
		// So we delete the prior test cron.jobs db to start in a clean place.
		@unlink(Prado::getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . 'cron.jobs');
		self::assertTrue(true);
	}
}
