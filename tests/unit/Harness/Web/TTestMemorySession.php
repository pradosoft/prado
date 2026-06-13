<?php

/**
 * TTestMemorySession class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Web\THttpSession;

/**
 * TTestMemorySession — array-backed {@see THttpSession} stand-in.
 *
 * Stores session items in a plain array and records whether the session was
 * opened, destroyed, or regenerated, so session-bound code runs in the CLI
 * test runner without `session_start()` / `session_regenerate_id()`.
 *
 * Auto-loaded by {@see PradoUnitRequires}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */
class TTestMemorySession extends THttpSession
{
	public array $data = [];
	public bool $opened = false;
	public bool $destroyed = false;
	public bool $regenerated = false;

	public function open()
	{
		$this->opened = true;
	}

	public function itemAt($key)
	{
		return $this->data[$key] ?? null;
	}

	public function add($key, $value)
	{
		$this->data[$key] = $value;
	}

	public function remove($key)
	{
		$value = $this->data[$key] ?? null;
		unset($this->data[$key]);
		return $value;
	}

	public function destroy()
	{
		$this->destroyed = true;
		$this->data = [];
	}

	public function regenerate($deleteOld = false)
	{
		$this->regenerated = true;
		return 'regenerated-id';
	}
}
