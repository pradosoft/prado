<?php

class HttpStatusCodeTest extends \Prado\Web\UI\TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);

		$status = (int) ($this->Request->itemAt('status') ?? 200);
		$reason = $this->Request->itemAt('reason') ?? null;

		if ($status !== 200) {
			$this->Response->setStatusCode($status, $reason);
		}
	}
}
