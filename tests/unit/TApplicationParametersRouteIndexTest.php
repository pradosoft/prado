<?php

/**
 * TApplicationParametersRouteIndexTest class file.
 *
 * Covers the key index of {@see \Prado\TApplicationParameters} ({@see \Prado\Util\Behaviors\IMapRouteIndex}):
 * {@see \Prado\Util\Behaviors\TMapRouteBehavior}s register on attach, a change calls
 * only the routes for its key and the routes for every key, and indexed routes leave
 * the `dyAddItem` and `dyRemoveItem` call chains.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit;

use Prado\Collections\TMap;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TApplicationParameters;
use Prado\Util\Behaviors\IMapRouteIndex;
use Prado\Util\Behaviors\TMapRouteBehavior;

class TApplicationParametersRouteIndexTest extends \PHPUnit\Framework\TestCase
{
	private function route(?string $key, TApplicationParametersRouteRecorder $recorder): TMapRouteBehavior
	{
		return new TMapRouteBehavior($key, $key === null ? [$recorder, 'recordKeyValue'] : [$recorder, 'recordValue']);
	}

	private function chainLength(TApplicationParameters $params, string $method): int
	{
		return count(PradoUnit::invoke($params, 'getBehaviorsWithMethod', $method));
	}

	public function testImplementsIMapRouteIndex(): void
	{
		$this->assertInstanceOf(IMapRouteIndex::class, new TApplicationParameters());
	}

	public function testKeyRoute_calledOnlyForItsKey(): void
	{
		$params = new TApplicationParameters();
		$a = new TApplicationParametersRouteRecorder();
		$b = new TApplicationParametersRouteRecorder();
		$params->attachBehavior('a', $this->route('a', $a));
		$params->attachBehavior('b', $this->route('b', $b));

		$params['a'] = 1;
		$params['other'] = 2;
		$params['a'] = 3;

		$this->assertSame([1, 3], $a->calls);
		$this->assertSame([], $b->calls);
	}

	public function testAllKeysRoute_calledForEveryKey(): void
	{
		$params = new TApplicationParameters();
		$all = new TApplicationParametersRouteRecorder();
		$params->attachBehavior('all', $this->route(null, $all));

		$params['a'] = 1;
		$params[''] = 'blank';
		unset($params['a']);

		$this->assertSame([['a', 1], ['', 'blank'], ['a', null]], $all->calls);
	}

	public function testKeyRoutes_runBeforeAllKeysRoutes(): void
	{
		$params = new TApplicationParameters();
		$order = new TApplicationParametersRouteRecorder();
		$params->attachBehavior('all', new TMapRouteBehavior(null, fn ($k, $v) => $order->calls[] = 'all'));
		$params->attachBehavior('one', new TMapRouteBehavior('k', fn ($v) => $order->calls[] = 'one'));

		$params['k'] = 1;

		$this->assertSame(['one', 'all'], $order->calls);
	}

	public function testRemove_routesNullOnlyForExistingKey(): void
	{
		$params = new TApplicationParameters();
		$a = new TApplicationParametersRouteRecorder();
		$params->attachBehavior('a', $this->route('a', $a));

		$this->assertNull($params->remove('a'));
		$this->assertSame([], $a->calls);

		$params['a'] = 'v';
		$this->assertSame('v', $params->remove('a'));
		$this->assertSame(['v', null], $a->calls);
	}

	public function testIndexedRoute_leavesTheDyChain(): void
	{
		$params = new TApplicationParameters();
		$params->attachBehavior('a', $this->route('a', new TApplicationParametersRouteRecorder()));
		$params->attachBehavior('all', $this->route(null, new TApplicationParametersRouteRecorder()));

		$this->assertSame(0, $this->chainLength($params, 'dyAddItem'));
		$this->assertSame(0, $this->chainLength($params, 'dyRemoveItem'));
	}

	public function testIndexedRoute_calledOncePerChange(): void
	{
		$params = new TApplicationParameters();
		$a = new TApplicationParametersRouteRecorder();
		$params->attachBehavior('a', $this->route('a', $a));
		$params['a'] = 1;
		$this->assertSame([1], $a->calls);
	}

	public function testOtherDyAddItemBehaviors_stayInChain(): void
	{
		$params = new TApplicationParameters();
		$added = [];
		$params->attachBehavior('watch', new TApplicationParametersTestBehavior($added));
		$params->attachBehavior('a', $this->route('a', new TApplicationParametersRouteRecorder()));

		$this->assertSame(1, $this->chainLength($params, 'dyAddItem'));
		$params['a'] = 1;
		$params['b'] = 2;
		$this->assertSame(['a', 'b'], $added);
	}

	public function testRouteSubclassOverridingDyAddItem_staysInChain(): void
	{
		$params = new TApplicationParameters();
		$recorder = new TApplicationParametersRouteRecorder();
		$route = new TApplicationParametersOverridingRoute('a', [$recorder, 'recordValue']);
		$this->assertFalse($route->getIsIndexable());
		$params->attachBehavior('a', $route);

		$this->assertSame(1, $this->chainLength($params, 'dyAddItem'));
		$params['a'] = 1;
		$this->assertSame([1], $recorder->calls);
		$this->assertSame(['a'], $route->seen);
	}

	public function testDetach_unregistersRoute(): void
	{
		$params = new TApplicationParameters();
		$a = new TApplicationParametersRouteRecorder();
		$params->attachBehavior('a', $this->route('a', $a));
		$params->detachBehavior('a');

		$params['a'] = 1;
		$this->assertSame([], $a->calls);
		$this->assertSame([], PradoUnit::getProp($params, '_routeKeys'));
		$this->assertSame([], PradoUnit::getProp($params, '_keyRoutes'));
	}

	public function testSetParameter_movesRoute(): void
	{
		$params = new TApplicationParameters();
		$rec = new TApplicationParametersRouteRecorder();
		$route = $this->route('a', $rec);
		$params->attachBehavior('r', $route);

		$route->setParameter('b');
		$params['a'] = 1;
		$params['b'] = 2;
		$this->assertSame([2], $rec->calls);
		$this->assertArrayNotHasKey('a', PradoUnit::getProp($params, '_keyRoutes'));
	}

	public function testSetParameter_toNullRoutesEveryKey(): void
	{
		$params = new TApplicationParameters();
		$calls = [];
		$route = new TMapRouteBehavior('a', function (...$args) use (&$calls) {
			$calls[] = $args;
		});
		$params->attachBehavior('r', $route);

		$route->setParameter(null);
		$params['x'] = 1;
		$this->assertSame([['x', 1]], $calls);
	}

	public function testDisabledRoute_notCalled(): void
	{
		$params = new TApplicationParameters();
		$a = new TApplicationParametersRouteRecorder();
		$route = $this->route('a', $a);
		$params->attachBehavior('a', $route);

		$route->setEnabled(false);
		$params['a'] = 1;
		$route->setEnabled(true);
		$params['a'] = 2;
		$this->assertSame([2], $a->calls);
	}

	public function testBehaviorsDisabled_routesNotCalled(): void
	{
		$params = new TApplicationParameters();
		$a = new TApplicationParametersRouteRecorder();
		$params->attachBehavior('a', $this->route('a', $a));

		$params->disableBehaviors();
		$params['a'] = 1;
		$params->enableBehaviors();
		$params['a'] = 2;
		$this->assertSame([2], $a->calls);
	}

	public function testIntegerKey_matchesStringParameter(): void
	{
		$params = new TApplicationParameters();
		$rec = new TApplicationParametersRouteRecorder();
		$params->attachBehavior('ten', $this->route('10', $rec));

		$params[10] = 'int';
		$params['1e1'] = 'exponent';
		$this->assertSame(['int'], $rec->calls);
	}

	public function testFinalKey_rejectedChangeNotRouted(): void
	{
		$params = new TApplicationParameters();
		$rec = new TApplicationParametersRouteRecorder();
		$all = new TApplicationParametersRouteRecorder();
		$params['locked'] = 'v';
		$params->finalize('locked');
		$params->attachBehavior('a', $this->route('locked', $rec));
		$params->attachBehavior('all', $this->route(null, $all));

		foreach ([fn () => $params->add('locked', 'x'), fn () => $params->remove('locked')] as $change) {
			try {
				$change();
				$this->fail('Expected TInvalidOperationException');
			} catch (TInvalidOperationException $e) {
			}
		}
		$this->assertSame([], $rec->calls);
		$this->assertSame([], $all->calls);
	}

	public function testHandlerDetachingRoute_duringDispatch(): void
	{
		$params = new TApplicationParameters();
		$rec = new TApplicationParametersRouteRecorder();
		$params->attachBehavior('first', new TMapRouteBehavior('a', fn ($v) => $params->detachBehavior('second')));
		$params->attachBehavior('second', $this->route('a', $rec));

		$params['a'] = 1;
		$params['a'] = 2;
		$this->assertCount(1, $rec->calls, 'the copy taken before dispatch still calls the detached route once');
	}

	public function testClone_routesToClonedBehaviors(): void
	{
		$params = new TApplicationParameters();
		$rec = new TApplicationParametersRouteRecorder();
		$params->attachBehavior('a', $this->route('a', $rec));

		$copy = clone $params;
		$copyRoute = $copy->asa('a');
		$this->assertNotSame($params->asa('a'), $copyRoute);
		$this->assertSame([spl_object_id($copyRoute) => 'a'], PradoUnit::getProp($copy, '_routeKeys'));
		$this->assertSame([spl_object_id($params->asa('a')) => 'a'], PradoUnit::getProp($params, '_routeKeys'));

		$copy['a'] = 'copy';
		$params['a'] = 'orig';
		$this->assertSame(['copy', 'orig'], $rec->calls);
		$this->assertSame(0, $this->chainLength($copy, 'dyAddItem'));
	}

	public function testSerialization_rebuildsIndex(): void
	{
		$params = new TApplicationParameters();
		$params->attachBehavior('a', $this->route('a', new TApplicationParametersRouteRecorder()));
		$serialized = serialize($params);
		$this->assertStringNotContainsString('_keyRoutes', $serialized);

		$copy = unserialize($serialized);
		$route = $copy->asa('a');
		$this->assertInstanceOf(TMapRouteBehavior::class, $route);
		$this->assertSame([spl_object_id($route) => 'a'], PradoUnit::getProp($copy, '_routeKeys'));

		$copy['a'] = 1;
		$recorder = PradoUnit::getProp($route, '_handler')[0];
		$this->assertSame([1], $recorder->calls);
	}

	public function testPlainTMap_keepsRouteInChain(): void
	{
		$map = new TMap();
		$rec = new TApplicationParametersRouteRecorder();
		$map->attachBehavior('a', $this->route('a', $rec));
		$map['a'] = 1;
		$map['b'] = 2;
		$this->assertSame([1], $rec->calls);
	}

	public function testManyRoutes_noDeepStack(): void
	{
		$params = new TApplicationParameters();
		for ($i = 0; $i < 200; $i++) {
			$params->attachBehavior("r$i", new TMapRouteBehavior("k$i", fn ($v) => null));
		}
		$depth = 0;
		$params->attachBehavior('probe', new TMapRouteBehavior('k199', function ($v) use (&$depth) {
			$depth = count(debug_backtrace(0));
		}));

		$params['k199'] = 1;
		$this->assertLessThan(20, $depth);
	}
}

/**
 * Records the calls a TMapRouteBehavior handler receives.
 */
class TApplicationParametersRouteRecorder
{
	public array $calls = [];

	public function recordValue($value): void
	{
		$this->calls[] = $value;
	}

	public function recordKeyValue($key, $value): void
	{
		$this->calls[] = [$key, $value];
	}
}

/**
 * A route subclass that overrides dyAddItem, so it stays in the dy call chain.
 */
class TApplicationParametersOverridingRoute extends TMapRouteBehavior
{
	public array $seen = [];

	public function dyAddItem($key, $value, $callchain)
	{
		$this->seen[] = $key;
		return parent::dyAddItem($key, $value, $callchain);
	}
}
