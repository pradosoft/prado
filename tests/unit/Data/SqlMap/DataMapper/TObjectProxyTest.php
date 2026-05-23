<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\SqlMap\DataMapper\TObjectProxy;

/**
 * A concrete target object whose methods may be intercepted.
 */
class ObjectProxyTarget
{
	public function greet(string $name): string
	{
		return "Hello, $name!";
	}

	public function add(int $a, int $b): int
	{
		return $a + $b;
	}
}

/**
 * A handler that intercepts specific methods.
 */
class ObjectProxyHandler
{
	private array $_methods;
	public array $intercepted = [];

	public function __construct(array $methods = [])
	{
		$this->_methods = $methods;
	}

	public function hasMethod(string $method): bool
	{
		return in_array($method, $this->_methods, true);
	}

	public function intercept(string $method, array $params): mixed
	{
		$this->intercepted[] = [$method, $params];
		return 'intercepted:' . $method;
	}
}

class TObjectProxyTest extends PHPUnit\Framework\TestCase
{
	public function test_non_intercepted_method_delegates_to_object()
	{
		$target = new ObjectProxyTarget();
		$handler = new ObjectProxyHandler([]);  // intercepts nothing
		$proxy = new TObjectProxy($handler, $target);

		$result = $proxy->greet('World');
		$this->assertSame('Hello, World!', $result);
	}

	public function test_intercepted_method_calls_handler()
	{
		$target = new ObjectProxyTarget();
		$handler = new ObjectProxyHandler(['greet']);
		$proxy = new TObjectProxy($handler, $target);

		$result = $proxy->greet('World');
		$this->assertSame('intercepted:greet', $result);
		$this->assertCount(1, $handler->intercepted);
		$this->assertSame('greet', $handler->intercepted[0][0]);
		$this->assertSame(['World'], $handler->intercepted[0][1]);
	}

	public function test_handler_receives_correct_params()
	{
		$target = new ObjectProxyTarget();
		$handler = new ObjectProxyHandler(['add']);
		$proxy = new TObjectProxy($handler, $target);

		$proxy->add(3, 5);
		$this->assertSame([3, 5], $handler->intercepted[0][1]);
	}

	public function test_non_intercepted_method_with_multiple_args()
	{
		$target = new ObjectProxyTarget();
		$handler = new ObjectProxyHandler([]);
		$proxy = new TObjectProxy($handler, $target);

		$result = $proxy->add(10, 20);
		$this->assertSame(30, $result);
	}

	public function test_multiple_interceptions()
	{
		$target = new ObjectProxyTarget();
		$handler = new ObjectProxyHandler(['greet', 'add']);
		$proxy = new TObjectProxy($handler, $target);

		$proxy->greet('Alice');
		$proxy->add(1, 2);

		$this->assertCount(2, $handler->intercepted);
		$this->assertSame('greet', $handler->intercepted[0][0]);
		$this->assertSame('add', $handler->intercepted[1][0]);
	}

	public function test_handler_intercepts_takes_priority_over_object()
	{
		$target = new ObjectProxyTarget();
		$handler = new ObjectProxyHandler(['greet']);
		$proxy = new TObjectProxy($handler, $target);

		// Handler intercepts greet — result is NOT the target's greeting
		$result = $proxy->greet('Bob');
		$this->assertNotSame('Hello, Bob!', $result);
		$this->assertSame('intercepted:greet', $result);
	}

	public function test_handler_does_not_intercept_unknown_method()
	{
		$target = new ObjectProxyTarget();
		$handler = new ObjectProxyHandler(['unknownMethod']);
		$proxy = new TObjectProxy($handler, $target);

		// 'greet' is not in handler's list — should delegate
		$result = $proxy->greet('Charlie');
		$this->assertSame('Hello, Charlie!', $result);
		$this->assertCount(0, $handler->intercepted);
	}
}
