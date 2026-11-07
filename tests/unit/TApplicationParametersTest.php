<?php

/**
 * TApplicationParametersTest class file.
 *
 * Covers {@see \Prado\TApplicationParameters}: finalizing keys, the guard on every
 * TMap mutation path, and serialization of the final-key list.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit;

use Prado\Collections\TMap;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TApplicationParameters;

class TApplicationParametersTest extends \PHPUnit\Framework\TestCase
{
	private function params(array $data = []): TApplicationParameters
	{
		$params = new TApplicationParameters();
		foreach ($data as $key => $value) {
			$params->add($key, $value);
		}
		return $params;
	}

	private function assertFinalThrows(callable $mutation, string $key = 'locked'): void
	{
		try {
			$mutation();
			$this->fail('Expected TInvalidOperationException');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('appparameters_final', $e->getErrorCode());
			$this->assertStringContainsString($key, $e->getMessage());
		}
	}

	public function testIsTMap(): void
	{
		$this->assertInstanceOf(TMap::class, new TApplicationParameters());
	}

	public function testNoKeyIsFinalByDefault(): void
	{
		$params = $this->params(['a' => 1]);
		$this->assertFalse($params->getIsFinal('a'));
		$this->assertSame([], $params->getFinalKeys());
	}

	public function testFinalize_marksKeyFinal(): void
	{
		$params = $this->params(['a' => 1, 'b' => 2]);
		$params->finalize('b');
		$params->finalize('a');
		$this->assertTrue($params->getIsFinal('a'));
		$this->assertTrue($params->getIsFinal('b'));
		$this->assertSame(['b', 'a'], $params->getFinalKeys());
	}

	public function testFinalize_isIdempotent(): void
	{
		$params = $this->params(['a' => 1]);
		$params->finalize('a');
		$params->finalize('a');
		$this->assertSame(['a'], $params->getFinalKeys());
		$this->assertSame(1, $params['a']);
	}

	public function testFinalize_nullKeyIsEmptyStringKey(): void
	{
		$params = $this->params(['' => 'blank']);
		$params->finalize(null);
		$this->assertTrue($params->getIsFinal(''));
		$this->assertTrue($params->getIsFinal(null));
		$this->assertFinalThrows(fn () => $params->remove(null), "''");
	}

	public function testFinalKey_isReadable(): void
	{
		$params = $this->params(['locked' => 'v']);
		$params->finalize('locked');
		$this->assertSame('v', $params->itemAt('locked'));
		$this->assertSame('v', $params['locked']);
		$this->assertTrue(isset($params['locked']));
		$this->assertTrue($params->contains('locked'));
	}

	public function testFinalKey_addThrows(): void
	{
		$params = $this->params(['locked' => 'v']);
		$params->finalize('locked');
		$this->assertFinalThrows(fn () => $params->add('locked', 'other'));
		$this->assertSame('v', $params['locked']);
	}

	public function testFinalKey_sameValueAddThrows(): void
	{
		$params = $this->params(['locked' => 'v']);
		$params->finalize('locked');
		$this->assertFinalThrows(fn () => $params->add('locked', 'v'));
	}

	public function testFinalKey_offsetSetThrows(): void
	{
		$params = $this->params(['locked' => 'v']);
		$params->finalize('locked');
		$this->assertFinalThrows(function () use ($params) {
			$params['locked'] = 'other';
		});
		$this->assertSame('v', $params['locked']);
	}

	public function testFinalKey_removeThrows(): void
	{
		$params = $this->params(['locked' => 'v']);
		$params->finalize('locked');
		$this->assertFinalThrows(fn () => $params->remove('locked'));
		$this->assertSame('v', $params['locked']);
	}

	public function testFinalKey_offsetUnsetThrows(): void
	{
		$params = $this->params(['locked' => 'v']);
		$params->finalize('locked');
		$this->assertFinalThrows(function () use ($params) {
			unset($params['locked']);
		});
		$this->assertSame('v', $params['locked']);
	}

	public function testFinalKey_removeItemThrows(): void
	{
		$params = $this->params(['locked' => 'v']);
		$params->finalize('locked');
		$this->assertFinalThrows(fn () => $params->removeItem('v'));
		$this->assertSame('v', $params['locked']);
	}

	public function testFinalKey_mergeWithThrows(): void
	{
		$params = $this->params(['locked' => 'v']);
		$params->finalize('locked');
		$this->assertFinalThrows(fn () => $params->mergeWith(['locked' => 'other']));
		$this->assertSame('v', $params['locked']);
	}

	public function testFinalKey_clearThrows(): void
	{
		$params = $this->params(['locked' => 'v']);
		$params->finalize('locked');
		$this->assertFinalThrows(fn () => $params->clear());
		$this->assertSame('v', $params['locked']);
	}

	public function testFinalKey_copyFromThrows(): void
	{
		$params = $this->params(['locked' => 'v']);
		$params->finalize('locked');
		$this->assertFinalThrows(fn () => $params->copyFrom(['x' => 1]));
		$this->assertSame('v', $params['locked']);
	}

	public function testFinalAbsentKey_staysAbsent(): void
	{
		$params = $this->params();
		$params->finalize('locked');
		$this->assertFalse($params->contains('locked'));
		$this->assertFinalThrows(fn () => $params->add('locked', 'v'));
		$this->assertFalse($params->contains('locked'));
	}

	public function testOtherKeys_remainMutable(): void
	{
		$params = $this->params(['locked' => 'v', 'open' => 1]);
		$params->finalize('locked');
		$params['open'] = 2;
		$params->add('new', 3);
		$this->assertSame(2, $params['open']);
		$this->assertSame(3, $params['new']);
		$this->assertSame(2, $params->remove('open'));
		$this->assertFalse($params->contains('open'));
	}

	public function testNullKeyAdd_appends(): void
	{
		$params = $this->params();
		$params->finalize('locked');
		$this->assertSame(0, $params->add(null, 'appended'));
		$this->assertSame('appended', $params[0]);
	}

	public function testIntegerKey_finalize(): void
	{
		$params = $this->params([5 => 'five']);
		$params->finalize(5);
		$this->assertTrue($params->getIsFinal('5'));
		$this->assertFinalThrows(fn () => $params->add(5, 'other'), '5');
	}

	public function testFinalCheck_precedesDyAddItem(): void
	{
		$params = $this->params(['locked' => 'v']);
		$params->finalize('locked');
		$added = [];
		$params->attachBehavior('watch', new TApplicationParametersTestBehavior($added));
		$this->assertFinalThrows(fn () => $params->add('locked', 'other'));
		$params->add('open', 1);
		$this->assertSame(['open'], $added);
	}

	public function testSerialization_keepsFinalKeys(): void
	{
		$params = $this->params(['locked' => 'v', 'open' => 1]);
		$params->finalize('locked');
		$copy = unserialize(serialize($params));
		$this->assertTrue($copy->getIsFinal('locked'));
		$this->assertFalse($copy->getIsFinal('open'));
		$this->assertSame('v', $copy['locked']);
	}

	public function testSerialization_omitsEmptyFinalKeys(): void
	{
		$params = $this->params(['open' => 1]);
		$this->assertStringNotContainsString('_final', serialize($params));
		$copy = unserialize(serialize($params));
		$this->assertSame([], $copy->getFinalKeys());
	}
}

/**
 * Records the keys passed to dyAddItem.
 */
class TApplicationParametersTestBehavior extends \Prado\Util\TBehavior
{
	private array $_added;

	public function __construct(array &$added)
	{
		$this->_added = &$added;
		parent::__construct();
	}

	public function dyAddItem($key, $value, $chain)
	{
		$this->_added[] = $key;
		return $chain->dyAddItem($key, $value);
	}
}
