<?php


namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Web\TMediaType;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TForm;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TFileUpload;
use Prado\Web\UI\WebControls\TFileUploadItem;
use Prado\Web\UI\WebControls\TPanel;
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

	// ================================================================================
	// Post data
	// ================================================================================

	private function postFiles(array $files, \Closure $test): void
	{
		$saved = $_FILES;
		try {
			$_FILES = $files;
			$test();
		} finally {
			$_FILES = $saved;
		}
	}

	public function testLoadPostDataWithoutTheFileIsNoChange()
	{
		$this->postFiles([], function () {
			$upload = new TFileUpload();
			$this->assertFalse($upload->loadPostData('one', []));
			$this->assertFalse($upload->getDataChanged());
			$this->assertSame([], $upload->getFiles());
		});
	}

	public function testMultipleWithASingleScalarFileLoadsOneFile()
	{
		$this->postFiles(['one' => ['name' => 'a.txt', 'type' => 'text/plain', 'size' => 3, 'tmp_name' => '/tmp/a', 'error' => UPLOAD_ERR_OK]], function () {
			$upload = new TFileUpload();
			$upload->setMultiple(true);
			$this->assertTrue($upload->loadPostData('one', []));
			$this->assertTrue($upload->getDataChanged());
			$this->assertCount(1, $upload->getFiles());
			$this->assertSame('a.txt', $upload->getFileName());
		});
	}

	public function testFileAccessorsReadTheIndexedFile()
	{
		$this->postFiles(['many' => [
			'name' => ['a.txt', 'b.txt'],
			'type' => ['text/plain', 'image/png'],
			'size' => [3, 7],
			'tmp_name' => ['/tmp/a', '/tmp/b'],
			'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_PARTIAL],
		]], function () {
			$upload = new TFileUpload();
			$upload->setMultiple(true);
			$upload->loadPostData('many', []);
			$this->assertSame('b.txt', $upload->getFileName(1));
			$this->assertSame(7, $upload->getFileSize(1));
			$this->assertSame('image/png', $upload->getFileType(1));
			$this->assertSame('/tmp/b', $upload->getLocalName(1));
			$this->assertSame(UPLOAD_ERR_PARTIAL, $upload->getErrorCode(1));
			$this->assertTrue($upload->getHasFile(0));
			$this->assertFalse($upload->getHasFile(1));
			$this->assertFalse($upload->getHasAllFiles());
			$this->assertSame('a.txt,b.txt', $upload->getValidationPropertyValue());
		});
	}

	public function testFileAccessorsWithoutTheIndexReturnDefaults()
	{
		$upload = new TFileUpload();
		$this->assertSame('', $upload->getFileName(2));
		$this->assertSame(0, $upload->getFileSize(2));
		$this->assertSame('', $upload->getFileType(2));
		$this->assertSame('', $upload->getLocalName(2));
		$this->assertSame(UPLOAD_ERR_NO_FILE, $upload->getErrorCode(2));
		$this->assertFalse($upload->getHasFile(2));
		$this->assertTrue($upload->getHasAllFiles());
		$this->assertFalse($upload->saveAs('/tmp/never', true, 2));
		$this->assertSame('', $upload->getValidationPropertyValue());
	}

	public function testSaveAsDelegatesToTheFile()
	{
		$this->postFiles(['one' => ['name' => 'a.txt', 'type' => 'text/plain', 'size' => 3, 'tmp_name' => '/tmp/not-uploaded', 'error' => UPLOAD_ERR_OK]], function () {
			$upload = new TFileUpload();
			$upload->loadPostData('one', []);
			$this->assertFalse($upload->saveAs(sys_get_temp_dir() . '/prado-never-saved', false));
		});
	}

	public function testRaisePostDataChangedEventRaisesOnFileUpload()
	{
		$upload = new TFileUpload();
		$raised = [];
		$upload->attachEventHandler('OnFileUpload', function ($sender, $param) use (&$raised) {
			$raised[] = [$sender, $param];
		});
		$upload->raisePostDataChangedEvent();
		$this->assertSame([[$upload, null]], $raised);
	}

	public function testPropertyDefaultsAndSetters()
	{
		$upload = new TFileUpload();
		$this->assertSame(TFileUpload::MAX_FILE_SIZE, $upload->getMaxFileSize());
		$this->assertFalse($upload->getMultiple());
		$this->assertTrue($upload->getIsValid());
		$this->assertSame('input', PradoUnit::invoke($upload, 'getTagName'));
		$upload->setMaxFileSize('2048');
		$upload->setIsValid('false');
		$this->assertSame(2048, $upload->getMaxFileSize());
		$this->assertFalse($upload->getIsValid());
	}

	public function testFileUploadItemClassIsConfigurable()
	{
		$saved = PradoUnit::getStaticProp(TFileUpload::class, 'fileUploadItemClass');
		try {
			PradoUnit::setStaticProp(TFileUpload::class, 'fileUploadItemClass', TFileUploadTestItem::class);
			$this->postFiles(['one' => ['name' => 'a.txt', 'type' => 'text/plain', 'size' => 3, 'tmp_name' => '/tmp/a', 'error' => UPLOAD_ERR_OK]], function () {
				$upload = new TFileUpload();
				$upload->loadPostData('one', []);
				$this->assertInstanceOf(TFileUploadTestItem::class, $upload->getFiles()[0]);
			});
		} finally {
			PradoUnit::setStaticProp(TFileUpload::class, 'fileUploadItemClass', $saved);
		}
	}

	// ================================================================================
	// Rendering and PreRender
	// ================================================================================

	public function testDisabledParentRendersDisabled()
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$panel = new TPanel();
		$panel->setEnabled(false);
		$page->getControls()->add($panel);
		$upload = new TFileUpload();
		$panel->getControls()->add($upload);
		$this->assertStringContainsString('disabled="disabled"', $this->renderBeginTag($upload));
	}

	private function createPreRenderPage(bool $callback = false): array
	{
		$page = $this->getMockBuilder(TPage::class)->onlyMethods(['getIsCallback', 'getForm', 'getCallbackClient'])->getMock();
		$page->method('getIsCallback')->willReturn($callback);
		$form = new TForm();
		$page->method('getForm')->willReturn($form);
		$cs = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$page])->onlyMethods(['registerHiddenField'])->getMock();
		PradoUnit::setProp($page, '_clientScript', $cs);
		$upload = new TFileUpload();
		$upload->setID('upload1');
		$page->getControls()->add($upload);
		return [$page, $upload, $form, $cs];
	}

	public function testOnPreRenderSetsTheFormEnctypeAndRequiresPostData()
	{
		[$page, $upload, $form, $cs] = $this->createPreRenderPage();
		$upload->setMaxFileSize(500);
		$cs->expects($this->once())->method('registerHiddenField')->with('MAX_FILE_SIZE', 500);
		$upload->onPreRender(null);
		$this->assertSame(TMediaType::MULTIPART, $form->getEnctype());
		$this->assertArrayHasKey('upload1', PradoUnit::getProp($page, '_controlsRegisteredForPostData'));
	}

	public function testOnPreRenderInACallbackSetsTheEnctypeOnTheClient()
	{
		[$page, $upload, $form] = $this->createPreRenderPage(true);
		$client = $this->getMockBuilder(\Prado\Web\UI\ActiveControls\TCallbackClientScript::class)->disableOriginalConstructor()->onlyMethods(['setAttribute'])->getMock();
		$client->expects($this->once())->method('setAttribute')->with($form, 'enctype', TMediaType::MULTIPART);
		$page->method('getCallbackClient')->willReturn($client);
		$upload->onPreRender(null);
		$this->assertNotSame(TMediaType::MULTIPART, $form->getEnctype());
	}

	public function testOnPreRenderWhenDisabledDoesNotRequirePostData()
	{
		[$page, $upload] = $this->createPreRenderPage();
		$upload->setEnabled(false);
		$upload->onPreRender(null);
		$this->assertArrayNotHasKey('upload1', PradoUnit::getProp($page, '_controlsRegisteredForPostData'));
	}

	public function testOnPreRenderWithoutAForm()
	{
		$page = $this->getMockBuilder(TPage::class)->onlyMethods(['getForm', 'getIsCallback'])->getMock();
		$page->method('getForm')->willReturn(null);
		$page->expects($this->never())->method('getIsCallback');
		$cs = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$page])->onlyMethods(['registerHiddenField'])->getMock();
		PradoUnit::setProp($page, '_clientScript', $cs);
		$upload = new TFileUpload();
		$page->getControls()->add($upload);
		$upload->onPreRender(null);
	}

	// ================================================================================
	// TFileUploadItem
	// ================================================================================

	public function testFileUploadItemAccessors()
	{
		$item = new TFileUploadItem('a.txt', 3, 'text/plain', UPLOAD_ERR_OK, '/tmp/a');
		$this->assertSame('a.txt', $item->getFileName());
		$this->assertSame('a.txt', $item->getFullPath());
		$this->assertSame(3, $item->getFileSize());
		$this->assertSame('text/plain', $item->getFileType());
		$this->assertSame('/tmp/a', $item->getLocalName());
		$this->assertSame(UPLOAD_ERR_OK, $item->getErrorCode());
		$this->assertTrue($item->getHasFile());
		$item->setLocalName('/tmp/b');
		$item->setErrorCode(UPLOAD_ERR_INI_SIZE);
		$this->assertSame('/tmp/b', $item->getLocalName());
		$this->assertFalse($item->getHasFile());
		$this->assertSame([
			'fileName' => 'a.txt',
			'fileSize' => 3,
			'fileType' => 'text/plain',
			'errorCode' => UPLOAD_ERR_INI_SIZE,
			'localName' => '/tmp/b',
			'fullPath' => 'a.txt',
		], $item->toArray());
	}

	/**
	 * A CLI run has no uploaded files, so move_uploaded_file() and is_uploaded_file() refuse every
	 * file. An upload error fails before either is called.
	 */
	public function testFileUploadItemSaveAsRefusesAFileThatWasNotUploaded()
	{
		$target = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-never-saved-' . bin2hex(random_bytes(4));
		$source = tempnam(sys_get_temp_dir(), 'prado');
		try {
			$item = new TFileUploadItem('a.txt', 3, 'text/plain', UPLOAD_ERR_OK, $source);
			$this->assertFalse($item->saveAs($target));
			$this->assertFalse($item->saveAs($target, false));
			$item->setErrorCode(UPLOAD_ERR_PARTIAL);
			$this->assertFalse($item->saveAs($target));
			$this->assertFileDoesNotExist($target);
			$this->assertFileExists($source);
		} finally {
			unlink($source);
		}
	}

	public static function oneFileProvider(): array
	{
		return [
			'uploaded' => [UPLOAD_ERR_OK, true],
			'failed' => [UPLOAD_ERR_PARTIAL, false],
		];
	}

	/**
	 * @dataProvider oneFileProvider
	 */
	public function testHasAllFilesWithOneFile(int $error, bool $expected)
	{
		$this->postFiles(['one' => ['name' => 'a.txt', 'type' => 'text/plain', 'size' => 3, 'tmp_name' => '/tmp/a', 'error' => $error]], function () use ($expected) {
			$upload = new TFileUpload();
			$upload->loadPostData('one', []);
			$this->assertSame($expected, $upload->getHasAllFiles());
		});
	}

	public function testLoadPostDataWithAnEmptyFileList()
	{
		$this->postFiles(['many' => ['name' => [], 'type' => [], 'size' => [], 'tmp_name' => [], 'error' => []]], function () {
			$upload = new TFileUpload();
			$this->assertTrue($upload->loadPostData('many', []));
			$this->assertSame([], $upload->getFiles());
		});
	}

	public function testOnPreRenderInACallbackWhenDisabled()
	{
		[$page, $upload, $form] = $this->createPreRenderPage(true);
		$client = $this->getMockBuilder(\Prado\Web\UI\ActiveControls\TCallbackClientScript::class)->disableOriginalConstructor()->onlyMethods(['setAttribute'])->getMock();
		$client->expects($this->once())->method('setAttribute');
		$page->method('getCallbackClient')->willReturn($client);
		$upload->setEnabled(false);
		$upload->onPreRender(null);
		$this->assertArrayNotHasKey('upload1', PradoUnit::getProp($page, '_controlsRegisteredForPostData'));
	}

	public function testOnPreRenderWithoutAFormWhenDisabled()
	{
		$page = $this->getMockBuilder(TPage::class)->onlyMethods(['getForm'])->getMock();
		$page->method('getForm')->willReturn(null);
		$cs = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$page])->onlyMethods(['registerHiddenField'])->getMock();
		PradoUnit::setProp($page, '_clientScript', $cs);
		$upload = new TFileUpload();
		$upload->setID('upload1');
		$page->getControls()->add($upload);
		$upload->setEnabled(false);
		$upload->onPreRender(null);
		$this->assertArrayNotHasKey('upload1', PradoUnit::getProp($page, '_controlsRegisteredForPostData'));
	}
}

class TFileUploadTestItem extends TFileUploadItem
{
}
