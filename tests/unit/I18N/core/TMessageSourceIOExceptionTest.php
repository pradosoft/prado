<?php

namespace Prado\Test\Unit\I18N\core;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TIOException;
use Prado\I18N\core\MessageSource_PHP;
use Prado\I18N\core\TMessageSourceIOException;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;

class TMessageSourceIOExceptionTest extends TestCase
{
	use TTempDirectoryTrait;

	protected function tearDown(): void
	{
		$this->removeTempDirectories();
	}

	public function testIsATIOException(): void
	{
		$this->assertInstanceOf(TIOException::class, new TMessageSourceIOException('messagesource_file_unwritable', 'messages.xml'));
	}

	public function testAnUnwritableCatalogueThrowsItWithTheFile(): void
	{
		$dir = $this->makeTempDirectory();
		file_put_contents($dir . '/messages.php', "<?php\nreturn ['trans-unit' => []];");
		chmod($dir . '/messages.php', 0o444);
		if (is_writable($dir . '/messages.php')) {
			$this->markTestSkipped('The process writes a read-only file.');
		}
		$source = new MessageSource_PHP($dir);
		$source->setCulture('');
		$source->append('Hello');
		try {
			$source->save();
			$this->fail('Expected TMessageSourceIOException.');
		} catch (TMessageSourceIOException $e) {
			$this->assertSame('messagesource_file_unwritable', $e->getErrorCode());
			$this->assertStringContainsString("'" . $dir . "/messages.php'", $e->getMessage());
		}
	}
}
