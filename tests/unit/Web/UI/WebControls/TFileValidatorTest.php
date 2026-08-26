<?php


use Prado\Exceptions\TConfigurationException;
use Prado\Web\UI\TForm;
use Prado\Web\UI\WebControls\TFileUpload;
use Prado\Web\UI\WebControls\TFileUploadItem;
use Prado\Web\UI\WebControls\TFileValidator;
use Prado\Web\UI\WebControls\TTextBox;
use PHPUnit\Framework\TestCase;

class TFileValidatorTest extends TestCase
{
	use TWebControlRenderTrait;

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
		$validator = new TFileValidator();
		$validator->setID($id);
		$validator->setControlToValidate($target->getID());
		$page->getControls()->add($validator);
		return $validator;
	}

	private function setFiles($upload, array $files)
	{
		PradoUnit::setProp($upload, '_files', $files);
	}

	private function makeFile($name, $size, $type = '', $errorCode = UPLOAD_ERR_OK, $localName = '')
	{
		return new TFileUploadItem($name, $size, $type, $errorCode, $localName);
	}

	private function makeTempFile($content)
	{
		$file = tempnam(sys_get_temp_dir(), 'tfv');
		file_put_contents($file, $content);
		$this->_tempFiles[] = $file;
		return $file;
	}

	private function invokeEvaluateIsValid($validator)
	{
		return PradoUnit::invoke($validator, 'evaluateIsValid');
	}

	// ================================================================================
	// Constructor and Default State Tests
	// ================================================================================

	public function testSetForeColorToRed()
	{
		$validator = new TFileValidator();
		$this->assertEquals('red', $validator->getForeColor());
	}

	public function testExtendsTBaseValidator()
	{
		$validator = new TFileValidator();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TBaseValidator::class, $validator);
	}

	public function testDefaultPropertyValues()
	{
		$validator = new TFileValidator();
		$this->assertEquals(0, $validator->getMaxFileSize());
		$this->assertEquals(0, $validator->getMinFileSize());
		$this->assertEquals(0, $validator->getTotalMaxFileSize());
		$this->assertEquals(0, $validator->getMaxFileCount());
		$this->assertEquals(0, $validator->getMinFileCount());
		$this->assertFalse($validator->getCheckExtensionMimeType());
		$this->assertEquals('', $validator->getAllowedFileExtensions());
		$this->assertEquals('', $validator->getAllowedFileTypes());
		$this->assertEquals([], $validator->getInvalidFileNames());
	}

	public function testPropertySettersAndGetters()
	{
		$validator = new TFileValidator();
		$validator->setTotalMaxFileSize(8192);
		$this->assertEquals(8192, $validator->getTotalMaxFileSize());
		$validator->setCheckExtensionMimeType(true);
		$this->assertTrue($validator->getCheckExtensionMimeType());
		$validator->setMaxFileSize(2048);
		$this->assertEquals(2048, $validator->getMaxFileSize());
		$validator->setMinFileSize(16);
		$this->assertEquals(16, $validator->getMinFileSize());
		$validator->setMaxFileCount(5);
		$this->assertEquals(5, $validator->getMaxFileCount());
		$validator->setMinFileCount(2);
		$this->assertEquals(2, $validator->getMinFileCount());
		$validator->setAllowedFileExtensions('jpg, png');
		$this->assertEquals('jpg, png', $validator->getAllowedFileExtensions());
		$validator->setAllowedFileTypes('image/*');
		$this->assertEquals('image/*', $validator->getAllowedFileTypes());
	}

	public function testGetClientClassName()
	{
		$validator = new TFileValidator();
		$this->assertEquals('Prado.WebUI.TFileValidator', PradoUnit::invoke($validator, 'getClientClassName'));
	}

	// ================================================================================
	// Validation Target Tests
	// ================================================================================

	public function testNonFileUploadTargetThrowsException()
	{
		$page = new \Prado\Web\UI\TPage();
		$textbox = new TTextBox();
		$textbox->setID('text1');
		$page->getControls()->add($textbox);
		$validator = $this->createValidator($page, $textbox);

		$this->expectException(TConfigurationException::class);
		$this->invokeEvaluateIsValid($validator);
	}

	// ================================================================================
	// Empty Selection Tests
	// ================================================================================

	public function testNoFilesIsValid()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	public function testNoFileErrorCodeIsFilteredOut()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setMinFileCount(1);
		$this->setFiles($upload, [$this->makeFile('', 0, '', UPLOAD_ERR_NO_FILE)]);

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	// ================================================================================
	// File Size Tests
	// ================================================================================

	public function testFileWithinMaxFileSize()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setMaxFileSize(1000);
		$this->setFiles($upload, [$this->makeFile('a.txt', 500)]);

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	public function testFileOverMaxFileSize()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setMaxFileSize(100);
		$this->setFiles($upload, [$this->makeFile('a.txt', 500)]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
		$this->assertEquals(['a.txt'], $validator->getInvalidFileNames());
	}

	public function testMaxFileSizeFallsBackToTargetMaxFileSize()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$upload->setMaxFileSize(400);
		$validator = $this->createValidator($page, $upload);
		$this->setFiles($upload, [$this->makeFile('a.txt', 500)]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));

		$this->setFiles($upload, [$this->makeFile('a.txt', 300)]);
		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	public function testFileUnderMinFileSize()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setMinFileSize(50);
		$this->setFiles($upload, [$this->makeFile('a.txt', 10)]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	// ================================================================================
	// Upload Error Code Tests
	// ================================================================================

	public function testFormSizeErrorCodeIsInvalid()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$this->setFiles($upload, [$this->makeFile('a.txt', 0, '', UPLOAD_ERR_FORM_SIZE)]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	public function testPartialErrorCodeIsInvalid()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$this->setFiles($upload, [$this->makeFile('a.txt', 10, '', UPLOAD_ERR_PARTIAL)]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	// ================================================================================
	// File Extension Tests
	// ================================================================================

	public function testAllowedExtensionMatchesCaseInsensitively()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setAllowedFileExtensions('jpg, png');
		$this->setFiles($upload, [$this->makeFile('photo.JPG', 10)]);

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	public function testDisallowedExtensionIsInvalid()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setAllowedFileExtensions('jpg, png');
		$this->setFiles($upload, [$this->makeFile('anim.gif', 10)]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
		$this->assertEquals(['anim.gif'], $validator->getInvalidFileNames());
	}

	public function testExtensionListAcceptsLeadingDots()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setAllowedFileExtensions('.png');
		$this->setFiles($upload, [$this->makeFile('logo.png', 10)]);

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	public function testFileWithoutExtensionIsInvalidWithExtensionList()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setAllowedFileExtensions('txt');
		$this->setFiles($upload, [$this->makeFile('README', 10)]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	// ================================================================================
	// MIME Type Tests
	// ================================================================================

	public function testAllowedMimeTypeMatches()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setAllowedFileTypes('text/plain');
		$this->setFiles($upload, [$this->makeFile('a.txt', 10, 'text/plain')]);

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	public function testDisallowedMimeTypeIsInvalid()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setAllowedFileTypes('text/plain');
		$this->setFiles($upload, [$this->makeFile('a.png', 10, 'image/png')]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	public function testWildcardMimeTypeMatchesSubtypes()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setAllowedFileTypes('image/*');
		$this->setFiles($upload, [$this->makeFile('a.png', 10, 'image/png')]);

		$this->assertTrue($this->invokeEvaluateIsValid($validator));

		$this->setFiles($upload, [$this->makeFile('a.txt', 10, 'text/plain')]);
		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	public function testMimeTypeSniffedFromFileContent()
	{
		if (!function_exists('finfo_open')) {
			$this->markTestSkipped('The fileinfo extension is not available.');
		}
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setAllowedFileTypes('text/plain');
		$localName = $this->makeTempFile('plain text content');
		$this->setFiles($upload, [$this->makeFile('a.txt', 18, 'application/octet-stream', UPLOAD_ERR_OK, $localName)]);

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	public function testExplicitExtensionAndTypeListsBothApply()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setAllowedFileExtensions('txt');
		$validator->setAllowedFileTypes('text/plain');
		$this->setFiles($upload, [$this->makeFile('a.txt', 10, 'text/plain')]);

		$this->assertTrue($this->invokeEvaluateIsValid($validator));

		$this->setFiles($upload, [$this->makeFile('a.txt', 10, 'image/png')]);
		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	// ================================================================================
	// Accept Fallback Tests
	// ================================================================================

	public function testAcceptPropertyFallbackMatchesAnyToken()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$upload->setAccept('.txt, image/png');
		$validator = $this->createValidator($page, $upload);

		$this->setFiles($upload, [$this->makeFile('a.txt', 10, 'application/octet-stream')]);
		$this->assertTrue($this->invokeEvaluateIsValid($validator));

		$this->setFiles($upload, [$this->makeFile('b.png', 10, 'image/png')]);
		$this->assertTrue($this->invokeEvaluateIsValid($validator));

		$this->setFiles($upload, [$this->makeFile('c.exe', 10, 'application/x-msdownload')]);
		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	public function testAcceptAttributeFallback()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$upload->setAttribute('accept', '.csv');
		$validator = $this->createValidator($page, $upload);

		$this->setFiles($upload, [$this->makeFile('data.csv', 10)]);
		$this->assertTrue($this->invokeEvaluateIsValid($validator));

		$this->setFiles($upload, [$this->makeFile('data.xml', 10)]);
		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	public function testExplicitListsOverrideAccept()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$upload->setAccept('.png');
		$validator = $this->createValidator($page, $upload);
		$validator->setAllowedFileExtensions('txt');

		$this->setFiles($upload, [$this->makeFile('a.png', 10)]);
		$this->assertFalse($this->invokeEvaluateIsValid($validator));

		$this->setFiles($upload, [$this->makeFile('a.txt', 10)]);
		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	// ================================================================================
	// File Count Tests
	// ================================================================================

	public function testMaxFileCountExceeded()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$upload->setMultiple(true);
		$validator = $this->createValidator($page, $upload);
		$validator->setMaxFileCount(2);
		$this->setFiles($upload, [
			$this->makeFile('a.txt', 10),
			$this->makeFile('b.txt', 10),
			$this->makeFile('c.txt', 10),
		]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	public function testMinFileCountNotReached()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$upload->setMultiple(true);
		$validator = $this->createValidator($page, $upload);
		$validator->setMinFileCount(2);
		$this->setFiles($upload, [$this->makeFile('a.txt', 10)]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));

		$this->setFiles($upload, [$this->makeFile('a.txt', 10), $this->makeFile('b.txt', 10)]);
		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	// ================================================================================
	// Total File Size Tests
	// ================================================================================

	public function testTotalMaxFileSizeExceeded()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$upload->setMultiple(true);
		$validator = $this->createValidator($page, $upload);
		$validator->setTotalMaxFileSize(150);
		$this->setFiles($upload, [$this->makeFile('a.txt', 100), $this->makeFile('b.txt', 100)]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	public function testTotalMaxFileSizeWithinLimit()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$upload->setMultiple(true);
		$validator = $this->createValidator($page, $upload);
		$validator->setTotalMaxFileSize(150);
		$this->setFiles($upload, [$this->makeFile('a.txt', 60), $this->makeFile('b.txt', 60)]);

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	// ================================================================================
	// Extension MIME Type Cross-Check Tests
	// ================================================================================

	public function testCheckExtensionMimeTypeCatchesRenamedFile()
	{
		if (!function_exists('finfo_open')) {
			$this->markTestSkipped('The fileinfo extension is not available.');
		}
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setCheckExtensionMimeType(true);
		$localName = $this->makeTempFile('plain text pretending to be an image');
		$this->setFiles($upload, [$this->makeFile('photo.png', 36, 'image/png', UPLOAD_ERR_OK, $localName)]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	public function testCheckExtensionMimeTypeAcceptsMatchingFile()
	{
		if (!function_exists('finfo_open')) {
			$this->markTestSkipped('The fileinfo extension is not available.');
		}
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setCheckExtensionMimeType(true);
		$localName = $this->makeTempFile('plain text content');
		$this->setFiles($upload, [$this->makeFile('notes.txt', 18, 'text/plain', UPLOAD_ERR_OK, $localName)]);

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	public function testCheckExtensionMimeTypePassesUnknownExtension()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setCheckExtensionMimeType(true);
		$localName = $this->makeTempFile('arbitrary content');
		$this->setFiles($upload, [$this->makeFile('data.xyz', 17, '', UPLOAD_ERR_OK, $localName)]);

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	public function testCheckExtensionMimeTypePassesWithoutLocalFile()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setCheckExtensionMimeType(true);
		$this->setFiles($upload, [$this->makeFile('photo.png', 10, 'image/png')]);

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	public function testCheckExtensionMimeTypeDisabledByDefault()
	{
		if (!function_exists('finfo_open')) {
			$this->markTestSkipped('The fileinfo extension is not available.');
		}
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$localName = $this->makeTempFile('plain text pretending to be an image');
		$this->setFiles($upload, [$this->makeFile('photo.png', 36, 'image/png', UPLOAD_ERR_OK, $localName)]);

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	// ================================================================================
	// ErrorMessage Token Tests
	// ================================================================================

	public function testErrorMessageFilesTokenSubstitution()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setAllowedFileExtensions('txt');
		$validator->setErrorMessage('Invalid files: {files}');
		$this->setFiles($upload, [$this->makeFile('bad.gif', 10), $this->makeFile('worse.exe', 10)]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
		$this->assertEquals('Invalid files: bad.gif, worse.exe', $validator->getErrorMessage());
	}

	public function testErrorMessageFilesTokenIsHtmlEncoded()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setAllowedFileExtensions('txt');
		$validator->setErrorMessage('{files}');
		$this->setFiles($upload, [$this->makeFile('<b>.gif', 10)]);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
		$this->assertEquals('&lt;b&gt;.gif', $validator->getErrorMessage());
	}

	// ================================================================================
	// Validate Method Integration Tests
	// ================================================================================

	public function testValidateMethodMarksTargetInvalid()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setAllowedFileExtensions('txt');
		$this->setFiles($upload, [$this->makeFile('bad.gif', 10)]);

		$this->assertFalse($validator->validate());
		$this->assertFalse($validator->getIsValid());
		$this->assertFalse($upload->getIsValid());
	}

	public function testDisabledValidatorIsAlwaysValid()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$validator = $this->createValidator($page, $upload);
		$validator->setAllowedFileExtensions('txt');
		$validator->setEnabled(false);
		$this->setFiles($upload, [$this->makeFile('bad.gif', 10)]);

		$this->assertTrue($validator->validate());
		$this->assertTrue($validator->getIsValid());
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
		$validator->setMaxFileSize(2048);
		$validator->setMinFileSize(16);
		$validator->setTotalMaxFileSize(9000);
		$validator->setMaxFileCount(3);
		$validator->setMinFileCount(1);
		$validator->setAllowedFileExtensions('.JPG, png');
		$validator->setAllowedFileTypes('Image/*');

		$options = PradoUnit::invoke($validator, 'getClientScriptOptions');

		$this->assertEquals(2048, $options['MaxFileSize']);
		$this->assertEquals(16, $options['MinFileSize']);
		$this->assertEquals(9000, $options['TotalMaxFileSize']);
		$this->assertEquals(3, $options['MaxFileCount']);
		$this->assertEquals(1, $options['MinFileCount']);
		$this->assertEquals(['jpg', 'png'], $options['AllowedFileExtensions']);
		$this->assertEquals(['image/*'], $options['AllowedFileTypes']);
		$this->assertFalse($options['MatchAnyType']);
	}

	public function testGetClientScriptOptionsWithAcceptFallback()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$form = new TForm();
		$page->getControls()->add($form);
		$page->setForm($form);
		$upload->setAccept('.txt, image/*');
		$upload->setMaxFileSize(4096);
		$validator = $this->createValidator($page, $upload);

		$options = PradoUnit::invoke($validator, 'getClientScriptOptions');

		$this->assertEquals(4096, $options['MaxFileSize']);
		$this->assertEquals(['txt'], $options['AllowedFileExtensions']);
		$this->assertEquals(['image/*'], $options['AllowedFileTypes']);
		$this->assertTrue($options['MatchAnyType']);
	}

	public function testGetClientScriptOptionsKeepsRawErrorMessageToken()
	{
		[$page, $upload] = $this->createPageWithUpload();
		$form = new TForm();
		$page->getControls()->add($form);
		$page->setForm($form);
		$validator = $this->createValidator($page, $upload);
		$validator->setAllowedFileExtensions('txt');
		$validator->setErrorMessage('Invalid: {files}');
		$this->setFiles($upload, [$this->makeFile('bad.gif', 10)]);
		$validator->validate();

		$options = PradoUnit::invoke($validator, 'getClientScriptOptions');

		$this->assertEquals('Invalid: {files}', $options['ErrorMessage']);
		$this->assertEquals('Invalid: bad.gif', $validator->getErrorMessage());
	}
}
