<?php

namespace Prado\Test\Unit\Email;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prado\Email\TEmailFile;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TIOException;

class TEmailFileTest extends TestCase
{
	public function testFromPath()
	{
		$file = TEmailFile::fromPath(__FILE__);
		$this->assertSame(__FILE__, $file->getPath());
		$this->assertSame('TEmailFileTest.php', $file->getName());
		$this->assertFalse($file->getIsInline());
		$this->assertNull($file->getContentId());
		$this->assertSame(file_get_contents(__FILE__), $file->getContent());
		$this->assertSame('report.txt', TEmailFile::fromPath(__FILE__, 'report.txt')->getName());
	}

	public function testFromData()
	{
		$file = TEmailFile::fromData("a,b\n1,2\n", 'export.csv');
		$this->assertNull($file->getPath());
		$this->assertSame("a,b\n1,2\n", $file->getContent());
		$this->assertSame('text/csv', $file->getMimeType());
	}

	public function testMimeType()
	{
		$this->assertSame('image/png', TEmailFile::fromData('x', 'logo.png')->getMimeType());
		$this->assertSame('application/x-custom', TEmailFile::fromData('x', 'logo.png', 'application/x-custom')->getMimeType());
		$this->assertSame(TEmailFile::DEFAULT_MIME_TYPE, TEmailFile::fromData('x', 'file.unknownext')->getMimeType());
	}

	public function testWithContentId()
	{
		$file = TEmailFile::fromData('x', 'logo.png');
		$inline = $file->withContentId('logo');
		$this->assertNotSame($file, $inline);
		$this->assertFalse($file->getIsInline());
		$this->assertTrue($inline->getIsInline());
		$this->assertSame('logo', $inline->getContentId());
		$this->assertFalse($inline->withContentId(null)->getIsInline());
	}

	public function testArrayRoundTrip()
	{
		$data = TEmailFile::fromData("\x00\xFFbinary", 'a.bin', 'application/x-bin')->withContentId('cid.1');
		$array = $data->toArray();
		$this->assertSame(['name' => 'a.bin', 'data' => base64_encode("\x00\xFFbinary"), 'mime' => 'application/x-bin', 'cid' => 'cid.1'], $array);
		$this->assertSame($array, json_decode(json_encode($array), true));
		$restored = TEmailFile::fromArray($array);
		$this->assertSame("\x00\xFFbinary", $restored->getContent());
		$this->assertSame('cid.1', $restored->getContentId());

		$path = TEmailFile::fromPath(__FILE__);
		$this->assertSame(['name' => 'TEmailFileTest.php', 'path' => __FILE__], $path->toArray());
		$this->assertSame(__FILE__, TEmailFile::fromArray($path->toArray())->getPath());
	}

	public function testMissingFileThrowsOnRead()
	{
		$file = TEmailFile::fromPath(__DIR__ . '/no-such-file.txt');
		$this->expectException(TIOException::class);
		$file->getContent();
	}

	public static function invalidProvider(): array
	{
		return [
			'no content' => [null, null, 'a.txt', null, null],
			'path and data' => ['/a', 'data', 'a.txt', null, null],
			'empty name' => [null, 'data', '', null, null],
			'name with slash' => [null, 'data', 'a/b.txt', null, null],
			'name with quote' => [null, 'data', 'a".txt', null, null],
			'name with line break' => [null, 'data', "a\r\nb.txt", null, null],
			'mime type' => [null, 'data', 'a.txt', "text/plain\r\nX: y", null],
			'content id' => [null, 'data', 'a.txt', null, 'bad id>'],
		];
	}

	#[DataProvider('invalidProvider')]
	public function testInvalidThrows($path, $data, $name, $mime, $cid)
	{
		$this->expectException(TInvalidDataValueException::class);
		new TEmailFile($path, $data, $name, $mime, $cid);
	}
}
