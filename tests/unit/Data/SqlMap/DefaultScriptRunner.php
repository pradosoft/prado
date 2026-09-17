<?php

namespace Prado\Test\Unit\Data\SqlMap;


class DefaultScriptRunner
{
	public function runScript($connection, $script)
	{
		$sql = file_get_contents($script);
		$lines = explode(';', $sql);
		foreach ($lines as $line) {
			$line = trim($line);
			if (strlen($line) > 0) {
				$connection->createCommand($line)->execute();
			}
		}
	}
}
