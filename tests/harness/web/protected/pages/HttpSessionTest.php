<?php

class HttpSessionTest extends \Prado\Web\UI\TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);

		$action = $this->Request->itemAt('action') ?? 'info';
		$session = $this->Application->getSession();

		switch ($action) {
			case 'start':
				$session->open();
				$session->add('testkey', 'testvalue');
				$session->add('counter', 1);
				break;

			case 'read':
				// Open session to read persisted data from a previous request.
				$session->open();
				break;

			case 'write':
				$session->open();
				$key = $this->Request->itemAt('key') ?? 'writekey';
				$val = $this->Request->itemAt('val') ?? 'writevalue';
				$session->add($key, $val);
				break;

			case 'regenerate':
				$session->open();
				$oldId = $session->regenerate(false);
				$session->add('oldId', $oldId);
				break;

			case 'destroy':
				$session->open();
				$session->destroy();
				break;

			// case 'info': fall through — no session started, just report state
		}
	}

	public function getSessionJson(): string
	{
		$session = $this->Application->getSession();
		$started = $session->getIsStarted();
		return htmlspecialchars(json_encode([
			'isStarted'   => $started,
			'sessionId'   => $started ? $session->getSessionID() : null,
			'sessionName' => $session->getSessionName(),
			'timeout'     => $session->getTimeout(),
			'count'       => $started ? $session->getCount() : 0,
			'testkey'     => $started ? $session->itemAt('testkey') : null,
			'counter'     => $started ? $session->itemAt('counter') : null,
			'oldId'       => $started ? $session->itemAt('oldId') : null,
		]), ENT_QUOTES);
	}
}
