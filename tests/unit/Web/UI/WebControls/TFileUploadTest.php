<?php


use Prado\Web\UI\WebControls\TFileUpload;
use PHPUnit\Framework\TestCase;

class TFileUploadTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createPageWithUpload()
	{
		$page = new \Prado\Web\UI\TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$upload = new TFileUpload();
		$upload->setID('upload1');
		$page->getControls()->add($upload);
		return [$page, $upload];
	}

	// ================================================================================
	// Accept Property Tests
	// ================================================================================

	public function testAcceptDefaultsToEmpty()
	{
		$upload = new TFileUpload();
		$this->assertEquals('', $upload->getAccept());
	}

	public function testSetAccept()
	{
		$upload = new TFileUpload();
		$upload->setAccept('.jpg, image/png, image/*');
		$this->assertEquals('.jpg, image/png, image/*', $upload->getAccept());
	}

	public function testRenderAcceptAttribute()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$upload->setAccept('image/*');

		$html = $this->renderBeginTag($upload);

		$this->assertStringContainsString('type="file"', $html);
		$this->assertStringContainsString('accept="image/*"', $html);
	}

	public function testRenderWithoutAcceptAttribute()
	{
		[$page, $upload] = $this->createPageWithUpload();

		$html = $this->renderBeginTag($upload);

		$this->assertStringNotContainsString('accept=', $html);
	}

	// ================================================================================
	// Capture Property Tests
	// ================================================================================

	public function testCaptureDefaultsToEmpty()
	{
		$upload = new TFileUpload();
		$this->assertEquals('', $upload->getCapture());
	}

	public function testSetCapture()
	{
		$upload = new TFileUpload();
		$upload->setCapture('environment');
		$this->assertEquals('environment', $upload->getCapture());
	}

	public function testRenderCaptureAttribute()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$upload->setAccept('image/*');
		$upload->setCapture('user');

		$html = $this->renderBeginTag($upload);

		$this->assertStringContainsString('capture="user"', $html);
	}

	public function testRenderWithoutCaptureAttribute()
	{
		[$page, $upload] = $this->createPageWithUpload();

		$html = $this->renderBeginTag($upload);

		$this->assertStringNotContainsString('capture=', $html);
	}

	public function testRenderMultipleAttribute()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$upload->setMultiple(true);

		$html = $this->renderBeginTag($upload);

		$this->assertStringContainsString('multiple="multiple"', $html);
		$this->assertStringContainsString('[]', $html);
	}
}
