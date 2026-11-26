<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Collections\TMap;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\THttpResponse;
use Prado\Web\UI\ActiveControls\TBaseActiveControl;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\ActiveControls\TCallbackResponseAdapter;
use Prado\Web\UI\ActiveControls\TMapCollectionDiff;
use Prado\Web\UI\ActiveControls\TScalarDiff;
use Prado\Web\UI\ActiveControls\TStyleDiff;
use Prado\Web\UI\WebControls\TLabel;

/**
 * The view state differences a callback sends ({@see TScalarDiff}, {@see TMapCollectionDiff},
 * {@see TStyleDiff}), the response data of a {@see TCallbackEventParameter}, and the options
 * map of a {@see TBaseActiveControl}.
 */
class TViewStateDiffTest extends TestCase
{
	private object $null;

	protected function setUp(): void
	{
		$this->null = new \stdClass();
	}

	public static function scalarProvider(): array
	{
		return [
			'same' => [1, 1, false],
			'same value of another type' => [1, '1', true],
			'changed' => ['b', 'a', true],
			'set' => ['a', null, true],
		];
	}

	/**
	 * @dataProvider scalarProvider
	 */
	public function testScalarDiffReturnsTheNewValueWhenChanged(mixed $new, mixed $old, bool $changed)
	{
		$diff = (new TScalarDiff($new, $old, $this->null))->getDifference();
		$this->assertSame($changed ? $new : $this->null, $diff);
	}

	public function testMapDiffOfANewMapReturnsItsItems()
	{
		$this->assertSame(['a' => 1], (new TMapCollectionDiff(new TMap(['a' => 1]), null, $this->null))->getDifference());
		$this->assertSame($this->null, (new TMapCollectionDiff(null, null, $this->null))->getDifference());
	}

	public function testMapDiffReturnsTheAddedAndChangedItems()
	{
		$old = new TMap(['a' => 1, 'b' => 2]);
		$this->assertSame(
			['b' => 3, 'c' => 4],
			(new TMapCollectionDiff(new TMap(['a' => 1, 'b' => 3, 'c' => 4]), $old, $this->null))->getDifference()
		);
		$this->assertSame($this->null, (new TMapCollectionDiff(new TMap(['a' => 1, 'b' => 2]), $old, $this->null))->getDifference());
	}

	/**
	 * An array item, such as a jQuery UI option, compares by its elements.
	 */
	public function testMapDiffComparesArrayItemsByTheirElements()
	{
		$old = new TMap(['a' => [1, 2], 'b' => ['x' => 1], 'c' => 'v']);
		$this->assertSame(
			['a' => [1, 3], 'b' => 'x', 'c' => ['v']],
			(new TMapCollectionDiff(new TMap(['a' => [1, 3], 'b' => 'x', 'c' => ['v']]), $old, $this->null))->getDifference()
		);
		$this->assertSame($this->null, (new TMapCollectionDiff(new TMap(['a' => [1, 2], 'b' => ['x' => 1], 'c' => 'v']), $old, $this->null))->getDifference());
	}

	public function testStyleFromStringOfANonStringIsEmpty()
	{
		$diff = new TStyleDiff(null, null, $this->null);
		$this->assertSame([], PradoUnit::invoke($diff, 'getStyleFromString', null));
		$this->assertSame(['color' => 'red'], PradoUnit::invoke($diff, 'getStyleFromString', 'color: red; :x; y'));
	}

	public static function oneDeclarationProvider(): array
	{
		return [
			'declaration' => ['color: red', ['color' => 'red']],
			'no name' => [':x', []],
			'no value' => ['y', []],
			'empty' => ['', []],
		];
	}

	/**
	 * @dataProvider oneDeclarationProvider
	 */
	public function testStyleFromStringOfOneDeclaration(string $css, array $expected)
	{
		$diff = new TStyleDiff(null, null, $this->null);
		$this->assertSame($expected, PradoUnit::invoke($diff, 'getStyleFromString', $css));
	}

	public function testCallbackEventParameterKeepsTheResponseDataInTheResponseAdapter()
	{
		$response = new THttpResponse();
		$response->setAdapter(new TCallbackResponseAdapter($response));
		$param = new TCallbackEventParameter($response, 'p');
		$this->assertSame('p', $param->getCallbackParameter());
		$this->assertNull($param->getResponseData());
		$param->setResponseData(['a' => 1]);
		$this->assertSame(['a' => 1], $param->getResponseData());
		$this->assertSame(['a' => 1], $response->getAdapter()->getResponseData());
	}

	public function testBaseActiveControlKeepsItsOptionsInAMap()
	{
		$base = new TBaseActiveControl(new TLabel());
		$base->setEnableUpdate(false);
		$options = PradoUnit::invoke($base, 'getOptions');
		$this->assertInstanceOf(TMap::class, $options);
		$this->assertSame(['EnableUpdate' => false], $options->toArray());
	}
}
