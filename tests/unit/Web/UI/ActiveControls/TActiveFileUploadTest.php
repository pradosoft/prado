<?php


namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\Collections\TMap;
use Prado\Exceptions\TExitException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\Harness\Web\TTestRecordingResponse;
use Prado\Test\Unit\Harness\Web\TTestUrlAssetManager;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Web\Services\TPageService;
use Prado\Web\UI\ActiveControls\TActiveFileUpload;
use Prado\Web\UI\ActiveControls\TActiveFileUploadCallbackParams;
use Prado\Web\UI\ActiveControls\TActiveFileUploadItem;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TForm;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TRequiredFieldValidator;
use Prado\Test\Unit\PradoUnit;
use PHPUnit\Framework\TestCase;

class TActiveFileUploadTestWakeup
{
	public static bool $wokeUp = false;

	public function __wakeup()
	{
		self::$wokeUp = true;
	}
}

class TActiveFileUploadTest extends TestCase
{
	use TTempDirectoryTrait;

	private array $snapshot = [];
	private array $aliases = [];
	private array $files = [];
	private array $get = [];

	protected function setUp(): void
	{
		$this->snapshot = PradoUnit::snapshot(Prado::getApplication(), ['_response', '_errorHandler', '_assetManager', '_cache']);
		$this->aliases = PradoUnit::snapshotStatic(Prado::class, ['_aliases']);
		$this->files = $_FILES;
		$this->get = $_GET;
	}

	protected function tearDown(): void
	{
		$_FILES = $this->files;
		$_GET = $this->get;
		PradoUnit::restoreStatic(Prado::class, $this->aliases);
		PradoUnit::restore(Prado::getApplication(), $this->snapshot);
		$this->removeTempDirectories();
	}

	/**
	 * Builds a page with a form and an upload `Upload` whose TempPath is a new temporary
	 * directory, and creates the child controls of the upload.
	 * @return array{TTestCallbackPage, TActiveFileUpload, string} the page, the upload, and the temporary directory
	 */
	private function createUpload(bool $callback = false, bool $postBack = false): array
	{
		$app = Prado::getApplication();
		$app->setResponse(new TTestRecordingResponse());
		$app->setAssetManager(new TTestUrlAssetManager());
		$dir = $this->makeTempDirectory();
		Prado::setPathOfAlias('ActiveUploadTemp', $dir);
		$page = new TTestCallbackPage();
		$page->callback = $callback;
		if ($postBack) {
			PradoUnit::setProp($page, '_postData', new TMap());
		}
		PradoUnit::setProp($page, '_clientScript', new TClientScriptManager($page));
		$form = new TForm();
		$form->setID('Form');
		$page->getControls()->add($form);
		$page->setForm($form);
		$upload = new TActiveFileUpload();
		$upload->setID('Upload');
		$upload->setTempPath('ActiveUploadTemp.*');
		$form->getControls()->add($upload);
		$upload->ensureChildControls();
		return [$page, $upload, $dir];
	}

	/**
	 * @return TRequiredFieldValidator a validator of the upload, in the page
	 */
	private function addValidator(TActiveFileUpload $upload): TRequiredFieldValidator
	{
		$validator = new TRequiredFieldValidator();
		$validator->setControlToValidate('Upload');
		$upload->getParent()->getControls()->add($validator);
		$upload->getPage()->getValidators()->add($validator);
		return $validator;
	}

	/**
	 * Writes a file into the directory and sets `$_FILES` for the upload with it.
	 * @return string the path of the file
	 */
	private function postFile(TActiveFileUpload $upload, string $dir, string $content = 'data', int $error = UPLOAD_ERR_OK): string
	{
		$file = tempnam($dir, 'post');
		file_put_contents($file, $content);
		$_FILES[$upload->getUniqueID()] = ['name' => 'a.txt', 'size' => strlen($content), 'type' => 'text/plain', 'error' => $error, 'tmp_name' => $file];
		$upload->loadPostData($upload->getUniqueID(), null);
		return $file;
	}

	/**
	 * Runs the upload in its frame, which echoes the completion script and ends the request.
	 * @return string the echoed script
	 */
	private function runFileUpload(TActiveFileUpload $upload, mixed $target): string
	{
		ob_start();
		try {
			$upload->onFileUpload($target);
			$this->fail('The upload frame ends the request.');
		} catch (TExitException $e) {
			return (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}
	}

	public function testExtendsTFileUpload()
	{
		$upload = new TActiveFileUpload();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TFileUpload::class, $upload);
	}

	public function testCausesValidationDefaultsToTrue()
	{
		$upload = new TActiveFileUpload();
		$this->assertTrue($upload->getCausesValidation());
	}

	public function testSetCausesValidation()
	{
		$upload = new TActiveFileUpload();
		$upload->setCausesValidation(false);
		$this->assertFalse($upload->getCausesValidation());
	}

	public function testValidationGroupDefaultsToEmpty()
	{
		$upload = new TActiveFileUpload();
		$this->assertEquals('', $upload->getValidationGroup());
	}

	public function testSetValidationGroup()
	{
		$upload = new TActiveFileUpload();
		$upload->setValidationGroup('uploadGroup');
		$this->assertEquals('uploadGroup', $upload->getValidationGroup());
	}

	public function testStatusTextsDefaultToLocalizedEnglish()
	{
		// Without a translation module, Prado::localize() returns the literal
		$upload = new TActiveFileUpload();
		$this->assertSame('Uploading file', $upload->getUploadingText());
		$this->assertSame('File upload complete', $upload->getCompleteText());
		$this->assertSame('File upload failed', $upload->getErrorText());
	}

	public function testStatusTextsAcceptCustomValues()
	{
		$upload = new TActiveFileUpload();
		$upload->setUploadingText('Wird hochgeladen');
		$upload->setCompleteText('Hochladen abgeschlossen');
		$upload->setErrorText('Hochladen fehlgeschlagen');
		$this->assertSame('Wird hochgeladen', $upload->getUploadingText());
		$this->assertSame('Hochladen abgeschlossen', $upload->getCompleteText());
		$this->assertSame('Hochladen fehlgeschlagen', $upload->getErrorText());
	}

	public function testEmptyStatusTextRestoresTheDefault()
	{
		$upload = new TActiveFileUpload();
		$upload->setUploadingText('Custom');
		$upload->setUploadingText('');
		$this->assertSame('Uploading file', $upload->getUploadingText());
	}

	public function testInheritsAcceptAndCapture()
	{
		$upload = new TActiveFileUpload();
		$upload->setAccept('image/*');
		$upload->setCapture('user');
		$this->assertEquals('image/*', $upload->getAccept());
		$this->assertEquals('user', $upload->getCapture());
	}

	public function testPreRenderQuotesRequestIds()
	{
		$app = Prado::getApplication();
		$service = $app->getService();
		$app->setService(new TPageService());
		$page = new TPage();
		$form = new TForm();
		$page->getControls()->add($form);
		$page->setForm($form);
		$upload = new TActiveFileUpload();
		$upload->setID('Upload');
		$form->getControls()->add($upload);

		$get = $_GET;
		$_GET['TActiveFileUpload_InputId'] = $upload->getClientID();
		$_GET['TActiveFileUpload_TargetId'] = "x';alert(1);//</script>";
		ob_start();
		try {
			$upload->onPreRender(null);
		} finally {
			$output = ob_get_clean();
			$_GET = $get;
			$app->setService($service);
		}

		$this->assertStringContainsString('Options.clientID = "' . $upload->getClientID() . '";', $output);
		$this->assertStringContainsString('Options.targetID = "x\\u0027;alert(1);\\/\\/\\u003C\\/script\\u003E";', $output);
		$this->assertStringNotContainsString("x';alert(1)", $output);
	}

	public function testParamsTokenRoundTrip()
	{
		$upload = new TActiveFileUpload();
		$params = new TActiveFileUploadCallbackParams();
		$params->files[] = ['fileName' => 'a.txt', 'fileSize' => 3];
		$token = PradoUnit::invoke($upload, 'pushParamsAndGetToken', $params);
		$restored = PradoUnit::invoke($upload, 'popParamsByToken', $token);
		$this->assertInstanceOf(TActiveFileUploadCallbackParams::class, $restored);
		$this->assertSame($params->files, $restored->files);
	}

	public function testParamsTokenDoesNotInstantiateOtherClasses()
	{
		$upload = new TActiveFileUpload();
		$sm = Prado::getApplication()->getSecurityManager();
		$token = urlencode(base64_encode($sm->encrypt(serialize(new TActiveFileUploadTestWakeup()))));
		TActiveFileUploadTestWakeup::$wokeUp = false;
		try {
			PradoUnit::invoke($upload, 'popParamsByToken', $token);
		} catch (\AssertionError $e) {
		}
		$this->assertFalse(TActiveFileUploadTestWakeup::$wokeUp);
	}

	public function testPropertiesHaveDefaults()
	{
		$upload = new TActiveFileUpload();
		$upload->setID('Upload');
		$this->assertSame('Application.runtime.*', $upload->getTempPath());
		$this->assertTrue($upload->getAutoPostBack());
		$upload->setTempPath('Other.*');
		$upload->setAutoPostBack('false');
		$this->assertSame('Other.*', $upload->getTempPath());
		$this->assertFalse($upload->getAutoPostBack());
		$this->assertSame('Prado.WebUI.TActiveFileUpload.fileChanged("Upload")', $upload->getCallbackJavascript());
		$this->assertSame($upload->getActiveControl()->getClientSide(), $upload->getClientSide());
	}

	public function testChildControlsShowTheUploadStateImages()
	{
		[, $upload] = $this->createUpload();
		foreach ([
			[$upload->getBusyImage(), 'ActiveFileUploadIndicator.gif', 'Uploading file'],
			[$upload->getSuccessImage(), 'ActiveFileUploadComplete.png', 'File upload complete'],
			[$upload->getErrorImage(), 'ActiveFileUploadError.png', 'File upload failed'],
		] as [$image, $file, $alt]) {
			$this->assertStringEndsWith('/activefileupload/' . $file, $image->getImageUrl());
			$this->assertSame($alt, $image->getAlternateText());
		}
		$this->assertCount(6, $upload->getControls());
	}

	public function testRenderRegistersTheClientOptions()
	{
		[$page, $upload] = $this->createUpload();
		PradoUnit::setProp($page, '_inFormRender', true);
		$writer = new THtmlWriter(new TTextWriter());
		$upload->render($writer);
		$this->assertStringContainsString('id="Upload"', $writer->flush());
		$script = implode('', PradoUnit::getProp($page->getClientScript(), '_endScripts'));
		$this->assertStringContainsString('new Prado.WebUI.TActiveFileUpload(', $script);
		foreach (['flagID' => 'Upload_Flag', 'targetID' => 'Upload:Target', 'formID' => 'Form', 'indicatorID' => 'Upload_Busy', 'completeID' => 'Upload_Success', 'errorID' => 'Upload_Error', 'statusID' => 'Upload_Status'] as $name => $id) {
			$this->assertStringContainsString("'$name':\"$id\"", $script);
		}
	}

	public function testInitRequiresAWritableTempPath()
	{
		[, $upload] = $this->createUpload();
		$upload->onInit(null);
		$upload->setTempPath('ActiveUploadTemp.missing');
		$this->expectException(TInvalidDataValueException::class);
		$upload->onInit(null);
	}

	public function testFileUploadInTheFrameKeepsTheFileAndEndsTheRequest()
	{
		[, $upload, $dir] = $this->createUpload(false, true);
		$posted = $this->postFile($upload, $dir);
		PradoUnit::getProp($upload, '_flag')->setValue('1');
		$output = $this->runFileUpload($upload, PradoUnit::getProp($upload, '_target')->getUniqueID());
		$this->assertFileDoesNotExist($posted);
		$kept = $upload->getFiles()[0]->getLocalName();
		$this->assertStringStartsWith(str_replace('\\', '/', $dir), $kept);
		$this->assertSame('data', file_get_contents($kept));
		$this->assertStringContainsString("Options.clientID = 'Upload';", $output);
		$this->assertStringContainsString("Options.fileName = '\"a.txt\"';", $output);
		$this->assertStringContainsString('parent.Prado.WebUI.TActiveFileUpload.onFileUpload(Options);', $output);
	}

	public function testFileUploadOfSeveralFilesSendsTheirLists()
	{
		[, $upload, $dir] = $this->createUpload(false, true);
		$upload->setMultiple(true);
		$posted = tempnam($dir, 'post');
		file_put_contents($posted, 'one');
		$_FILES[$upload->getUniqueID()] = ['name' => ['a.txt'], 'size' => [3], 'type' => ['text/plain'], 'error' => [UPLOAD_ERR_OK], 'tmp_name' => [$posted]];
		$upload->loadPostData($upload->getUniqueID(), null);
		PradoUnit::getProp($upload, '_flag')->setValue('1');
		$output = $this->runFileUpload($upload, PradoUnit::getProp($upload, '_target')->getUniqueID());
		$this->assertStringContainsString("Options.fileName = '[\"a.txt\"]';", $output);
		$this->assertStringContainsString("Options.fileSize = '[3]';", $output);
	}

	public function testFileUploadOfAnotherFrameOrWithoutTheFlagDoesNothing()
	{
		[, $upload] = $this->createUpload(false, true);
		$upload->onFileUpload(PradoUnit::getProp($upload, '_target')->getUniqueID());
		PradoUnit::getProp($upload, '_flag')->setValue('1');
		$upload->onFileUpload('Other');
		$upload->raisePostDataChangedEvent();
		$this->assertSame([], $upload->getFiles());
	}

	public function testUploadCallbackOfAnotherFrameOrWithoutValidationRaisesNoValidation()
	{
		[$page, $upload] = $this->createUpload(true);
		$upload->setCausesValidation(false);
		$validated = false;
		$this->addValidator($upload)->attachEventHandler('OnValidate', function () use (&$validated) {
			$validated = true;
		});
		$raised = 0;
		$upload->attachEventHandler('OnFileUpload', function () use (&$raised) {
			$raised++;
		});
		$upload->raiseCallbackEvent(new TCallbackEventParameter($page->getResponse(), (object) ['targetID' => 'Other', 'callbackToken' => '']));
		$this->assertSame(0, $raised);
		$token = PradoUnit::invoke($upload, 'pushParamsAndGetToken', new TActiveFileUploadCallbackParams());
		$upload->raiseCallbackEvent(new TCallbackEventParameter($page->getResponse(), (object) ['targetID' => PradoUnit::getProp($upload, '_target')->getUniqueID(), 'callbackToken' => $token]));
		$this->assertSame(1, $raised);
		$this->assertFalse($validated);
	}

	public function testPreRenderOfAnotherInputOrAPostBackEchoesNothing()
	{
		[, $upload] = $this->createUpload(false, true);
		$_GET['TActiveFileUpload_InputId'] = $upload->getClientID();
		$_GET['TActiveFileUpload_TargetId'] = 'Target';
		ob_start();
		try {
			$upload->onPreRender(null);
			[, $other] = $this->createUpload();
			$_GET['TActiveFileUpload_InputId'] = 'Other';
			$other->onPreRender(null);
		} finally {
			$output = ob_get_clean();
		}
		$this->assertSame('', $output);
	}

	public function testPreRenderOfSeveralFilesMarksThemTooLarge()
	{
		[, $upload, $dir] = $this->createUpload();
		$upload->setMultiple(true);
		$_FILES[$upload->getUniqueID()] = ['name' => ['a.txt'], 'size' => [0], 'type' => [''], 'error' => [UPLOAD_ERR_INI_SIZE], 'tmp_name' => ['']];
		$upload->loadPostData($upload->getUniqueID(), null);
		$_GET['TActiveFileUpload_InputId'] = $upload->getClientID();
		$_GET['TActiveFileUpload_TargetId'] = 'Target';
		ob_start();
		try {
			$upload->onPreRender(null);
		} finally {
			$output = ob_get_clean();
		}
		$this->assertStringContainsString("Options.errorCode = '[" . UPLOAD_ERR_FORM_SIZE . "]';", $output);
		$this->assertStringContainsString("Options.fileName = '[\"a.txt\"]';", $output);
	}

	public function testUnloadOfACallbackRemovesTheKeptFiles()
	{
		[, $upload, $dir] = $this->createUpload(true);
		$kept = $this->postFile($upload, $dir);
		$upload->onUnload(null);
		$this->assertFileDoesNotExist($kept);
	}

	public function testUnloadOutsideACallbackOrOfAFailedFileKeepsTheFile()
	{
		[, $upload, $dir] = $this->createUpload();
		$posted = $this->postFile($upload, $dir);
		$upload->onUnload(null);
		$this->assertFileExists($posted);
		[, $callback, $callbackDir] = $this->createUpload(true);
		$failed = $this->postFile($callback, $callbackDir, 'x', UPLOAD_ERR_PARTIAL);
		$callback->onUnload(null);
		$this->assertFileExists($failed);
		$missing = $this->postFile($callback, $callbackDir);
		unlink($missing);
		$callback->onUnload(null);
		$this->assertFileDoesNotExist($missing);
	}

	public function testUploadItemSavesAKeptFile()
	{
		$dir = $this->makeTempDirectory();
		$source = $dir . '/source';
		file_put_contents($source, 'data');
		$item = new TActiveFileUploadItem('a.txt', 4, 'text/plain', UPLOAD_ERR_OK, $source);
		$this->assertTrue($item->saveAs($dir . '/copy', false));
		$this->assertFileExists($source);
		$this->assertTrue($item->saveAs($dir . '/moved'));
		$this->assertFileDoesNotExist($source);
		$this->assertSame('data', file_get_contents($dir . '/moved'));
		$this->assertFalse($item->saveAs($dir . '/again'));
		$failed = new TActiveFileUploadItem('a.txt', 4, 'text/plain', UPLOAD_ERR_PARTIAL, $dir . '/copy');
		$this->assertFalse($failed->saveAs($dir . '/failed'));
	}

	public function testUploadCallbackWithoutFilesValidates()
	{
		[$page, $upload] = $this->createUpload(true);
		$validated = false;
		$this->addValidator($upload)->attachEventHandler('OnValidate', function () use (&$validated) {
			$validated = true;
		});
		$token = PradoUnit::invoke($upload, 'pushParamsAndGetToken', new TActiveFileUploadCallbackParams());
		$upload->raiseCallbackEvent(new TCallbackEventParameter($page->getResponse(), (object) ['targetID' => PradoUnit::getProp($upload, '_target')->getUniqueID(), 'callbackToken' => $token]));
		$this->assertTrue($validated);
		$this->assertSame([], $upload->getFiles());
	}

	public function testUnloadOfACallbackWithoutFiles()
	{
		[, $upload] = $this->createUpload(true);
		$upload->onUnload(null);
		$this->assertSame([], $upload->getFiles());
	}

	public function testUploadCallbackOfOneFileWithoutValidation()
	{
		[$page, $upload, $dir] = $this->createUpload(true);
		$upload->setCausesValidation(false);
		$params = new TActiveFileUploadCallbackParams();
		$params->files[] = ['fileName' => 'a.txt', 'fileSize' => 1, 'fileType' => 'text/plain', 'errorCode' => 0, 'localName' => tempnam($dir, 'kept')];
		$token = PradoUnit::invoke($upload, 'pushParamsAndGetToken', $params);
		$upload->raiseCallbackEvent(new TCallbackEventParameter($page->getResponse(), (object) ['targetID' => PradoUnit::getProp($upload, '_target')->getUniqueID(), 'callbackToken' => $token]));
		$this->assertSame('a.txt', $upload->getFileName());
	}
}
