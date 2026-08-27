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

	public function testInheritsAcceptAndCapture()
	{
		$upload = new TActiveFileUpload();
		$upload->setAccept('image/*');
		$upload->setCapture('user');
		$this->assertEquals('image/*', $upload->getAccept());
		$this->assertEquals('user', $upload->getCapture());
	}
}
