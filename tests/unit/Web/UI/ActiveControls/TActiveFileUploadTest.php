<?php


use Prado\Web\UI\ActiveControls\TActiveFileUpload;
use PHPUnit\Framework\TestCase;

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
}
