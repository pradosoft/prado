<?php

namespace Prado\Test\Unit\Email;

use Prado\TComponent;

/**
 * A component that listens to the global email events and records them, as a
 * plugin that reacts to emails would.
 */
class TEmailGlobalListener extends TComponent
{
	/** @var array<array{0: string, 1: object, 2: mixed}> the events received: name, sender, parameter. */
	public array $events = [];

	/** @var bool whether fxEmailSend cancels the email. */
	public bool $cancel = false;

	/** @var ?string the path that fxEmailAdditionalPaths returns. */
	public ?string $path = null;

	public function getAutoGlobalListen()
	{
		return true;
	}

	public function names(): array
	{
		return array_column($this->events, 0);
	}

	public function fxEmailSend($sender, $param)
	{
		$this->events[] = ['fxEmailSend', $sender, $param];
		if ($this->cancel) {
			$param->cancel();
		}
	}

	public function fxEmailSent($sender, $param)
	{
		$this->events[] = ['fxEmailSent', $sender, $param];
	}

	public function fxEmailFailed($sender, $param)
	{
		$this->events[] = ['fxEmailFailed', $sender, $param];
	}

	public function fxEmailBounce($sender, $param)
	{
		$this->events[] = ['fxEmailBounce', $sender, $param];
	}

	public function fxEmailComplaint($sender, $param)
	{
		$this->events[] = ['fxEmailComplaint', $sender, $param];
	}

	public function fxEmailOpen($sender, $param)
	{
		$this->events[] = ['fxEmailOpen', $sender, $param];
	}

	public function fxEmailClick($sender, $param)
	{
		$this->events[] = ['fxEmailClick', $sender, $param];
	}

	public function fxEmailUnsubscribe($sender, $param)
	{
		$this->events[] = ['fxEmailUnsubscribe', $sender, $param];
	}

	public function fxEmailAdditionalPaths($sender, $param)
	{
		$this->events[] = ['fxEmailAdditionalPaths', $sender, $param];
		return $this->path;
	}
}
