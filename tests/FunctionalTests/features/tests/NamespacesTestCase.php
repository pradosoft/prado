<?php


class NamespacesTestCase extends \PradoGenericSelenium2Test
{
    public function test()
    {
        $this->url("features/index.php?page=Namespaces.WithoutNamespace");
        $this->pause(50);
        $this->assertContains('Without Namespaces loaded', $this->source());

        $this->url("features/index.php?page=Namespaces.WithNamespace");
        $this->pause(50);
        $this->assertContains('With Namespaces loaded', $this->source());
    }

}