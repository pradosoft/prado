<?php


namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Web\UI\WebControls\TFileUpload;
use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;

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

	public function testDirectoryDefaultsToFalse()
	{
		$upload = new TFileUpload();
		$this->assertFalse($upload->getDirectory());
	}

	public function testRenderDirectoryAttribute()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$upload->setDirectory('true');

		$html = $this->renderBeginTag($upload);

		$this->assertStringContainsString('webkitdirectory="webkitdirectory"', $html);
		$this->assertStringContainsString('multiple="multiple"', $html);
		$this->assertStringContainsString('name="upload1[]"', $html);
	}

	public function testDirectoryUploadKeepsFullPaths()
	{
		$saved = $_FILES;
		try {
			$_FILES['folder'] = [
				'name' => ['a.jpg', 'b.jpg'],
				'full_path' => ['photos/a.jpg', 'photos/2026/b.jpg'],
				'type' => ['image/jpeg', 'image/jpeg'],
				'size' => [10, 20],
				'tmp_name' => ['/tmp/a', '/tmp/b'],
				'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
			];
			$upload = new TFileUpload();
			$upload->setDirectory(true);
			$this->assertTrue($upload->loadPostData('folder', []));
			$files = $upload->getFiles();
			$this->assertCount(2, $files);
			$this->assertSame('photos/2026/b.jpg', $files[1]->getFullPath());
			$this->assertSame('b.jpg', $files[1]->getFileName());
			$this->assertSame('photos/2026/b.jpg', $files[1]->toArray()['fullPath']);
		} finally {
			$_FILES = $saved;
		}
	}

	public function testFullPathDefaultsToFileName()
	{
		$saved = $_FILES;
		try {
			$_FILES['one'] = ['name' => 'a.txt', 'type' => 'text/plain', 'size' => 3, 'tmp_name' => '/tmp/a', 'error' => UPLOAD_ERR_OK];
			$upload = new TFileUpload();
			$upload->loadPostData('one', []);
			$this->assertSame('a.txt', $upload->getFiles()[0]->getFullPath());
		} finally {
			$_FILES = $saved;
		}
	}
}
