<?php

namespace Prado\Test\Unit\Email;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prado\Email\TEmailAddress;
use Prado\Exceptions\TInvalidDataValueException;

class TEmailAddressTest extends TestCase
{
	public function testConstruct()
	{
		$address = new TEmailAddress(' ann@example.com ', ' Ann Lee ');
		$this->assertSame('ann@example.com', $address->getAddress());
		$this->assertSame('Ann Lee', $address->getName());
		$this->assertSame('example.com', $address->getDomain());
		$this->assertSame('', (new TEmailAddress('ann@example.com'))->getName());
	}

	public static function parseProvider(): array
	{
		return [
			'bare' => ['ann@example.com', 'ann@example.com', ''],
			'angle' => ['<ann@example.com>', 'ann@example.com', ''],
			'name' => ['Ann Lee <ann@example.com>', 'ann@example.com', 'Ann Lee'],
			'quoted' => ['"Lee, Ann" <ann@example.com>', 'ann@example.com', 'Lee, Ann'],
			'escaped quote' => ['"Ann \"The Boss\" Lee" <ann@example.com>', 'ann@example.com', 'Ann "The Boss" Lee'],
			'spaces' => ["  Ann   <  ann@example.com >  ", 'ann@example.com', 'Ann'],
			'utf-8 name' => ['Zoë Müller <zoe@example.com>', 'zoe@example.com', 'Zoë Müller'],
			'plus and dots' => ['a.b+tag@sub.example.co.uk', 'a.b+tag@sub.example.co.uk', ''],
		];
	}

	#[DataProvider('parseProvider')]
	public function testParse(string $mailbox, string $address, string $name)
	{
		$parsed = TEmailAddress::parse($mailbox);
		$this->assertSame($address, $parsed->getAddress());
		$this->assertSame($name, $parsed->getName());
	}

	public static function invalidProvider(): array
	{
		return [
			'empty' => [''],
			'no at' => ['ann'],
			'no local' => ['@example.com'],
			'no domain' => ['ann@'],
			'space' => ['ann lee@example.com'],
			'two addresses' => ['ann@example.com,bob@example.com'],
			'line break' => ["ann@example.com\r\nBcc: eve@example.com"],
			'angle in address' => ['ann<@example.com'],
			'quote' => ['"ann"@example.com'],
			'non-ascii local' => ['zoë@example.com'],
		];
	}

	#[DataProvider('invalidProvider')]
	public function testInvalidAddressThrows(string $address)
	{
		$this->expectException(TInvalidDataValueException::class);
		new TEmailAddress($address);
	}

	public function testNameWithLineBreakThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		new TEmailAddress('ann@example.com', "Ann\r\nBcc: eve@example.com");
	}

	public function testInternationalDomainBecomesAscii()
	{
		$address = new TEmailAddress('ann@bücher.example');
		$this->assertSame('ann@xn--bcher-kva.example', $address->getAddress());
		$this->assertSame('xn--bcher-kva.example', $address->getDomain());
	}

	public function testFrom()
	{
		$address = new TEmailAddress('ann@example.com', 'Ann');
		$this->assertSame($address, TEmailAddress::from($address));
		$this->assertSame('Ann', TEmailAddress::from('Ann <ann@example.com>')->getName());
		$fromArray = TEmailAddress::from(['ann@example.com', 'Ann']);
		$this->assertSame('ann@example.com', $fromArray->getAddress());
		$this->assertSame('Ann', $fromArray->getName());
		$this->assertSame('', TEmailAddress::from(['ann@example.com'])->getName());
	}

	public function testFromInvalidTypeThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		TEmailAddress::from(42);
	}

	public function testParseListString()
	{
		$list = TEmailAddress::parseList('"Doe, John" <john@example.com>, jane@example.com; Bob <bob@example.com>,');
		$this->assertCount(3, $list);
		$this->assertSame(['Doe, John', '', 'Bob'], array_map(fn ($a) => $a->getName(), $list));
		$this->assertSame(['john@example.com', 'jane@example.com', 'bob@example.com'], array_map(fn ($a) => $a->getAddress(), $list));
	}

	public function testParseListOtherInputs()
	{
		$this->assertSame([], TEmailAddress::parseList(null));
		$this->assertSame([], TEmailAddress::parseList(''));
		$this->assertSame([], TEmailAddress::parseList([]));
		$address = new TEmailAddress('ann@example.com');
		$this->assertSame([$address], TEmailAddress::parseList($address));
		$list = TEmailAddress::parseList([$address, 'bob@example.com', ['cat@example.com', 'Cat']]);
		$this->assertSame(['ann@example.com', 'bob@example.com', 'cat@example.com'], array_map(fn ($a) => $a->getAddress(), $list));
		$this->assertSame('Cat', $list[2]->getName());
	}

	public function testParseListInvalidTypeThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		TEmailAddress::parseList(42);
	}

	public function testFormat()
	{
		$this->assertSame('ann@example.com', (new TEmailAddress('ann@example.com'))->format());
		$this->assertSame('Ann Lee <ann@example.com>', (string) new TEmailAddress('ann@example.com', 'Ann Lee'));
		$this->assertSame('"Lee, Ann" <ann@example.com>', (string) new TEmailAddress('ann@example.com', 'Lee, Ann'));
		$this->assertSame('"Ann \"Boss\" Lee" <ann@example.com>', (string) new TEmailAddress('ann@example.com', 'Ann "Boss" Lee'));
		$this->assertSame('Zoë <zoe@example.com>', (string) new TEmailAddress('zoe@example.com', 'Zoë'));
	}

	public function testFormatRoundTrips()
	{
		foreach (['Lee, Ann', 'Ann "Boss" Lee', 'Back\\slash', 'A. B. <C>'] as $name) {
			$parsed = TEmailAddress::parse((new TEmailAddress('ann@example.com', $name))->format());
			$this->assertSame($name, $parsed->getName());
			$this->assertSame('ann@example.com', $parsed->getAddress());
		}
	}

	public function testFormatList()
	{
		$this->assertSame('', TEmailAddress::formatList([]));
		$this->assertSame('ann@example.com, Bob <bob@example.com>', TEmailAddress::formatList(TEmailAddress::parseList('ann@example.com, Bob <bob@example.com>')));
	}
}
