<?php


use Prado\Web\UI\TForm;
use Prado\Web\UI\WebControls\TFileUpload;
use Prado\Web\UI\WebControls\TFileUploadItem;
use Prado\Web\UI\WebControls\TImageValidator;
use PHPUnit\Framework\TestCase;

class TImageValidatorTest extends TestCase
{
	private array $_tempFiles = [];

	protected function tearDown(): void
	{
		foreach ($this->_tempFiles as $file) {
			if (is_file($file)) {
				unlink($file);
			}
		}
		$this->_tempFiles = [];
		parent::tearDown();
	}

	private function createPageWithUpload()
	{
		$page = new \Prado\Web\UI\TPage();
		$upload = new TFileUpload();
		$upload->setID('upload1');
		$page->getControls()->add($upload);
		return [$page, $upload];
	}

	private function createValidator($page, $target, $id = 'v1')
	{
		$validator = new TImageValidator();
		$validator->setID($id);
		$validator->setControlToValidate($target->getID());
		$page->getControls()->add($validator);
		return $validator;
	}

	private function setFiles($upload, array $files)
	{
		PradoUnit::setProp($upload, '_files', $files);
	}

	private function makeTempFile($content)
	{
		$file = tempnam(sys_get_temp_dir(), 'tiv');
		file_put_contents($file, $content);
		$this->_tempFiles[] = $file;
		return $file;
	}

	private function makeImageFile($name, $width, $height)
	{
		$localName = $this->makeTempFile('GIF89a' . pack('v', $width) . pack('v', $height) . "\x00\x00\x00");
		return new TFileUploadItem($name, filesize($localName), 'image/gif', UPLOAD_ERR_OK, $localName);
	}

	private function invokeEvaluateIsValid($validator)
	{
		return PradoUnit::invoke($validator, 'evaluateIsValid');
	}

	// ================================================================================
	// Constructor and Default State Tests
	// ================================================================================

	public function testExtendsTFileValidator()
	{
		$validator = new TImageValidator();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TFileValidator::class, $validator);
	}

	public function testDefaultPropertyValues()
	{
		$validator = new TImageValidator();
		$this->assertEquals(0, $validator->getMinImageWidth());
		$this->assertEquals(0, $validator->getMaxImageWidth());
		$this->assertEquals(0, $validator->getMinImageHeight());
		$this->assertEquals(0, $validator->getMaxImageHeight());
	}

	public function testPropertySettersAndGetters()
	{
		$validator = new TImageValidator();
		$validator->setMinImageWidth(10);
		$this->assertEquals(10, $validator->getMinImageWidth());
		$validator->setMaxImageWidth(200);
		$this->assertEquals(200, $validator->getMaxImageWidth());
		$validator->setMinImageHeight(20);
		$this->assertEquals(20, $validator->getMinImageHeight());
		$validator->setMaxImageHeight(400);
		$this->assertEquals(400, $validator->getMaxImageHeight());
	}

	public function testGetClientClassName()
	{
		$validator = new TImageValidator();
		$this->assertEquals('Prado.WebUI.TImageValidator', PradoUnit::invoke($validator, 'getClientClassName'));
	}

	// ================================================================================
	// Image Dimension Tests
	// ================================================================================

	public function testNoFilesIsValid()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	public function testImageWithinBounds()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setMinImageWidth(10);
		$validator->setMaxImageWidth(200);
		$validator->setMinImageHeight(10);
		$validator->setMaxImageHeight(200);
		$this->setFiles($upload, [$this->makeImageFile('a.gif', 100, 100)]);

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	public function testImageOverMaxImageWidth()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setMaxImageWidth(100);
		$this->setFiles($upload, [$this->makeImageFile('wide.gif', 150, 50)]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
		$this->assertEquals(['wide.gif'], $validator->getInvalidFileNames());
	}

	public function testImageUnderMinImageHeight()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setMinImageHeight(100);
		$this->setFiles($upload, [$this->makeImageFile('short.gif', 150, 50)]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	public function testNonImageFileIsInvalid()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$localName = $this->makeTempFile('not an image at all');
		$this->setFiles($upload, [new TFileUploadItem('fake.gif', 18, 'image/gif', UPLOAD_ERR_OK, $localName)]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	public function testMissingLocalFileIsInvalid()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$this->setFiles($upload, [new TFileUploadItem('a.gif', 10, 'image/gif', UPLOAD_ERR_OK, '')]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	// ================================================================================
	// Inherited Restriction Tests
	// ================================================================================

	public function testInheritedExtensionRestrictionApplies()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setAllowedFileExtensions('png');
		$this->setFiles($upload, [$this->makeImageFile('a.gif', 50, 50)]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	public function testInheritedMaxFileSizeApplies()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setMaxFileSize(5);
		$this->setFiles($upload, [$this->makeImageFile('a.gif', 50, 50)]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	// ================================================================================
	// Client Script Options Tests
	// ================================================================================

	public function testGetClientScriptOptions()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$form = new TForm();
		$page->getControls()->add($form);
		$page->setForm($form);
		$validator = $this->createValidator($page, $upload);
		$validator->setMinImageWidth(10);
		$validator->setMaxImageWidth(200);
		$validator->setMinImageHeight(20);
		$validator->setMaxImageHeight(400);

		$options = PradoUnit::invoke($validator, 'getClientScriptOptions');

		$this->assertEquals(10, $options['MinImageWidth']);
		$this->assertEquals(200, $options['MaxImageWidth']);
		$this->assertEquals(20, $options['MinImageHeight']);
		$this->assertEquals(400, $options['MaxImageHeight']);
	}
}
