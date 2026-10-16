<?php


namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\Prado;
use Prado\Web\Services\TPageService;
use Prado\Web\UI\ActiveControls\TActiveFileUpload;
use Prado\Web\UI\ActiveControls\TActiveFileUploadCallbackParams;
use Prado\Web\UI\TForm;
use Prado\Web\UI\TPage;
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
}
