<?php

class HttpHeaderTest extends \Prado\Web\UI\TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);

		$action = $this->Request->itemAt('action') ?? 'default';

		switch ($action) {
			case 'default':
				// Custom headers visible to Playwright via response.headers()
				$this->Response->appendHeader('X-PRADO-Test: functional-test');
				$this->Response->appendHeader('X-PRADO-Version: 4.3.2');
				break;

			case 'charset':
				// Explicit charset override
				$this->Response->setCharset('ISO-8859-1');
				$this->Response->appendHeader('X-PRADO-Charset-Test: iso');
				break;

			case 'multi':
				// Multiple custom headers
				$this->Response->appendHeader('X-PRADO-First: alpha');
				$this->Response->appendHeader('X-PRADO-Second: beta');
				$this->Response->appendHeader('X-PRADO-Third: gamma');
				break;
		}
	}
}
