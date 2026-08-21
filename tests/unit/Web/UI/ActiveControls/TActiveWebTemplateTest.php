<?php

use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\TActiveWebTemplate;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TWebTemplate;
use PHPUnit\Framework\TestCase;

class TActiveWebTemplateTest extends TestCase
{
	public function testExtendsTWebTemplate()
	{
		$this->assertInstanceOf(TWebTemplate::class, new TActiveWebTemplate());
	}

	public function testImplementsIActiveControl()
	{
		$this->assertInstanceOf(IActiveControl::class, new TActiveWebTemplate());
	}

	public function testGetActiveControlReturnsBaseActiveControl()
	{
		$control = new TActiveWebTemplate();
		$this->assertInstanceOf(
			\Prado\Web\UI\ActiveControls\TBaseActiveControl::class,
			$control->getActiveControl()
		);
	}

	public function testRendersTemplateTag()
	{
		$control = new TActiveWebTemplate();
		$this->assertSame('template', PradoUnit::invoke($control, 'getTagName'));
	}

	// --- resolveTarget ---

	public function testResolveTargetFromString()
	{
		$control = new TActiveWebTemplate();
		$this->assertSame('listBody', PradoUnit::invoke($control, 'resolveTarget', 'listBody'));
	}

	public function testResolveTargetFromControl()
	{
		$control = new TActiveWebTemplate();
		$panel = new TPanel();
		$panel->setID('list');
		$this->assertSame(
			$panel->getClientID(),
			PradoUnit::invoke($control, 'resolveTarget', $panel)
		);
	}

	// --- commands without an active page context ---
	//
	// canUpdateClientSide() is false without a page, so each command is a no-op
	// rather than an error. This is the path taken during an ordinary page render.

	public function testStampingCommandsAreNoOpsWithoutPage()
	{
		$control = new TActiveWebTemplate();
		$control->stampInto('listBody', ['name' => 'Ada']);
		$control->prependInto('listBody', ['name' => 'Ada']);
		$control->replaceContentOf('listBody', ['name' => 'Ada']);
		$control->repeatInto('listBody', [['name' => 'Ada'], ['name' => 'Grace']]);
		$this->assertTrue(true);
	}

	public function testInstanceCommandsAreNoOpsWithoutPage()
	{
		$control = new TActiveWebTemplate();
		$control->updateInstance('wt1', ['name' => 'Grace']);
		$control->updateAll(['tag' => 'x']);
		$control->refreshInstance('wt1');
		$control->refreshAll();
		$control->removeInstance('wt1');
		$this->assertTrue(true);
	}

	public function testSetContentIsNoOpWithoutPage()
	{
		$control = new TActiveWebTemplate();
		$control->setContent('<p>{{v}}</p>');
		$control->setContent('<p>{{v}}</p>', true);
		$this->assertTrue(true);
	}

	// --- inherited TWebTemplate behaviour ---

	public function testInheritsTrackInstances()
	{
		$control = new TActiveWebTemplate();
		$this->assertTrue($control->getTrackInstances());
		$control->setTrackInstances(false);
		$this->assertFalse($control->getTrackInstances());
	}

	public function testClientOptionsCarryTrackInstances()
	{
		$control = new TActiveWebTemplate();
		$control->setTrackInstances(false);
		$options = PradoUnit::invoke($control, 'getClientOptions');
		$this->assertFalse($options['TrackInstances']);
	}

	public function testShadowRootModeStillSuppressesClientScript()
	{
		$control = new TActiveWebTemplate();
		$control->setShadowRootMode(\Prado\Web\UI\WebControls\TShadowRootMode::Open);
		$this->assertFalse(PradoUnit::invoke($control, 'getHasClientScript'));
	}
}
