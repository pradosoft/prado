<?php

namespace Prado\Test\Unit\Email\UI;

use PHPUnit\Framework\TestCase;
use Prado\Email\UI\TEmailBodyTemplate;

class TEmailBodyTemplateTest extends TestCase
{
	public function testDirectiveIsSplitBetweenBodyAndEmail()
	{
		$template = new TEmailBodyTemplate('<%@ MasterClass="Application.Layout" Email.Subject="Hi" email.From="shop@example.com" InlineStyles="false" %><p>Body</p>', __DIR__, __FILE__);
		$this->assertSame(['MasterClass' => 'Application.Layout', 'InlineStyles' => 'false'], $template->getDirective());
		$this->assertSame(['Subject' => 'Hi', 'From' => 'shop@example.com'], $template->getEmailDirective());
	}

	public function testTemplateWithoutDirective()
	{
		$template = new TEmailBodyTemplate('<p>Body</p>', __DIR__, __FILE__);
		$this->assertSame([], $template->getDirective());
		$this->assertSame([], $template->getEmailDirective());
	}
}
