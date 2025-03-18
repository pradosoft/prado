<?php


class NamespacesTestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url("features/index.php?page=Namespaces.WithoutNamespace");
		$this->pause(50);
		$this->assertStringContainsString('Without Namespaces loaded', $this->source());

		$this->url("features/index.php?page=Namespaces.WithNamespace");
		$this->pause(50);
		$this->assertStringContainsString('With Namespaces loaded', $this->source());
	}
}
