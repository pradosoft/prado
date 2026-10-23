<?php

namespace Prado\Test\Unit\Util\Behaviors;

use Prado\Exceptions\TInvalidOperationException;
use Prado\TComponent;
use Prado\Util\Behaviors\TEvaluationFilterBehavior;
use Prado\Util\TBehavior;
use Prado\Util\TCallChain;

class TEvaluationFilterBehaviorTestComponent extends TComponent
{
	private $_text = 'abc';

	public function getText()
	{
		return $this->_text;
	}

	public function setText($value)
	{
		$this->_text = $value;
	}
}

class TEvaluationFilterBehaviorTestRewriter extends TBehavior
{
	public function dyEvaluateExpressionFilter($expression, TCallChain $chain)
	{
		return $chain->dyEvaluateExpressionFilter(str_replace('SAFE', "system('id')", $expression));
	}
}

class TEvaluationFilterBehaviorTest extends \PHPUnit\Framework\TestCase
{
	protected $behavior;
	protected $component;

	protected function setUp(): void
	{
		$this->behavior = new TEvaluationFilterBehavior();
		$this->component = new TEvaluationFilterBehaviorTestComponent();
		$this->component->attachBehavior('evaluationFilter', $this->behavior);
	}

	protected function tearDown(): void
	{
		$this->component = null;
		$this->behavior = null;
	}

	public static function allowedExpressionProvider(): array
	{
		return [
			'property' => ['$this->Text', 'abc'],
			'method' => ['$this->getText()', 'abc'],
			'nullsafe' => ['$this?->Text', 'abc'],
			'function' => ['strtoupper($this->Text)', 'ABC'],
			'qualified function' => ['\strlen($this->Text)', 3],
			'static method' => ["Prado::localize('Hello')", 'Hello'],
			'class constant' => ['TComponent::GLOBAL_RAISE_EVENT_LISTENER', 'fxGlobalListener'],
			'class name' => ['TComponent::class', 'TComponent'],
			'constructs' => ['isset($x) || empty($this->Text) ? 1 : 2', 2],
			'match' => ["match(\$this->Text) { 'abc' => 'yes', default => 'no' }", 'yes'],
			'interpolation' => ['"x{$this->Text}"', 'xabc'],
			'parse error' => ['{{{', null],
		];
	}

	/**
	 * @dataProvider allowedExpressionProvider
	 */
	public function testAllowedExpression($expression, $expected)
	{
		$this->assertNull($this->behavior->findDeniedCode('return ' . $expression . ';'));
		if ($expected !== null) {
			$this->assertSame($expected, $this->component->evaluateExpression($expression));
		}
	}

	public function testArrowFunction()
	{
		$format = $this->component->evaluateExpression('fn ($value) => number_format($value, 2)');
		$this->assertSame('1.50', $format(1.5));
	}

	public static function deniedExpressionProvider(): array
	{
		return [
			'function' => ["system('id')", 'system('],
			'qualified function' => ["\\system('id')", '\system('],
			'second statement' => ["1; system('id')", 'system('],
			'backtick' => ['`id`', '`'],
			'eval' => ["eval('1;')", 'eval'],
			'include' => ["include 'x.php'", 'include'],
			'exit' => ['exit()', 'exit'],
			'variable variable' => ['$$x', '$'],
			'variable call' => ['$f()', '$f('],
			'string call' => ["'system'('id')", "'system'("],
			'expression call' => ['(fn () => 1)()', ')('],
			'array call' => ["\$a['f']()", ']('],
			'private member' => ['$this->_m', '->_m'],
			'interpolated private member' => ['"{$this->_m}"', '->_m'],
			'dynamic member' => ['$this->$name', '->$name'],
			'dynamic member expression' => ["\$this->{'_m'}", '->{'],
			'denied method' => ["\$this->evaluateStatements('echo 1;')", '->evaluateStatements'],
			'denied getter' => ['$this->getBehaviors()', '->getBehaviors'],
			'denied property' => ['$this->BehaviorsEnabled = false', '->BehaviorsEnabled'],
			'static method' => ["Prado::createComponent('x')", 'Prado::createComponent'],
			'static property' => ['self::$_um', 'self::$_um'],
			'late static call' => ['static::foo()', 'static::foo'],
			'new' => ["new \\SplFileObject('x')", 'new \SplFileObject'],
			'anonymous class' => ['new class {}', 'new class'],
			'close tag' => ['1 ?>x<?php ', '?>'],
		];
	}

	/**
	 * @dataProvider deniedExpressionProvider
	 */
	public function testDeniedExpression($expression, $denied)
	{
		$this->assertSame($denied, $this->behavior->findDeniedCode('return ' . $expression . ';'));
		try {
			$this->component->evaluateExpression($expression);
			$this->fail('TInvalidOperationException not raised for ' . $expression);
		} catch (TInvalidOperationException $e) {
			$this->assertSame('evaluationfilter_code_denied', $e->getErrorCode());
		}
	}

	public function testStatementsDeniedByDefault()
	{
		$this->assertFalse($this->behavior->getAllowStatements());
		$level = ob_get_level();
		try {
			$this->component->evaluateStatements('echo 1;');
			$this->fail('TInvalidOperationException not raised for statements');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('evaluationfilter_statements_denied', $e->getErrorCode());
		}
		$this->assertSame($level, ob_get_level());
	}

	public function testAllowStatements()
	{
		$this->assertSame($this->behavior, $this->behavior->setAllowStatements('true'));
		$this->assertTrue($this->behavior->getAllowStatements());
		$this->assertSame('ABC', $this->component->evaluateStatements('foreach ([$this->Text] as $t) { echo strtoupper($t); }'));
		try {
			$this->component->evaluateStatements('function f() {} echo 1;');
			$this->fail('TInvalidOperationException not raised for a function declaration');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('evaluationfilter_code_denied', $e->getErrorCode());
		}
		try {
			$this->component->evaluateStatements("passthru('id');");
			$this->fail('TInvalidOperationException not raised for passthru');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('evaluationfilter_code_denied', $e->getErrorCode());
		}
	}

	public function testChecksCodeFromTheFilterChain()
	{
		$this->component->attachBehavior('rewriter', new TEvaluationFilterBehaviorTestRewriter(), 20);
		try {
			$this->component->evaluateExpression('SAFE');
			$this->fail('TInvalidOperationException not raised for rewritten code');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('evaluationfilter_code_denied', $e->getErrorCode());
		}
	}

	public function testDisabledBehavior()
	{
		$this->component->disableBehavior('evaluationFilter');
		$this->assertSame('ABC', $this->component->evaluateExpression('mb_convert_case($this->Text, MB_CASE_UPPER)'));
		$this->component->enableBehavior('evaluationFilter');
		$this->expectException(TInvalidOperationException::class);
		$this->component->evaluateExpression('mb_convert_case($this->Text, MB_CASE_UPPER)');
	}

	public function testClassBehavior()
	{
		TEvaluationFilterBehaviorTestComponent::attachClassBehavior('classEvaluationFilter', TEvaluationFilterBehavior::class, TEvaluationFilterBehaviorTestComponent::class);
		try {
			$component = new TEvaluationFilterBehaviorTestComponent();
			$this->assertSame('abc', $component->evaluateExpression('$this->Text'));
			$this->expectException(TInvalidOperationException::class);
			$component->evaluateExpression("system('id')");
		} finally {
			TEvaluationFilterBehaviorTestComponent::detachClassBehavior('classEvaluationFilter', TEvaluationFilterBehaviorTestComponent::class);
		}
	}

	public function testAllowedFunctions()
	{
		$this->assertContains('strtoupper', $this->behavior->getAllowedFunctions());
		$this->assertFalse($this->behavior->isAllowedFunction('mb_convert_case'));

		$this->assertSame($this->behavior, $this->behavior->setAllowedFunctions(' Mb_Convert_Case, \strrev '));
		$this->assertSame(['mb_convert_case', 'strrev'], $this->behavior->getAllowedFunctions());
		$this->assertTrue($this->behavior->isAllowedFunction('\MB_CONVERT_CASE'));
		$this->assertFalse($this->behavior->isAllowedFunction('strtoupper'));
		$this->assertSame('cba', $this->component->evaluateExpression('strrev($this->Text)'));

		$this->behavior->setAllowedFunctions([]);
		$this->assertSame([], $this->behavior->getAllowedFunctions());

		$this->behavior->setAllowedFunctions(null);
		$this->assertTrue($this->behavior->isAllowedFunction('strtoupper'));
	}

	public function testAllowedStaticMethods()
	{
		$this->assertSame(['prado::localize', 'prado\prado::localize'], $this->behavior->getAllowedStaticMethods());
		$this->assertTrue($this->behavior->isAllowedStaticMethod('\Prado\Prado', 'Localize'));
		$this->assertFalse($this->behavior->isAllowedStaticMethod('Prado', '*'));

		$this->behavior->setAllowedStaticMethods('ArrayObject::__construct, Prado\TPropertyValue::*');
		$this->assertTrue($this->behavior->isAllowedStaticMethod('ArrayObject', '__construct'));
		$this->assertFalse($this->behavior->isAllowedStaticMethod('ArrayObject', '*'));
		$this->assertTrue($this->behavior->isAllowedStaticMethod('\Prado\TPropertyValue', 'ensureInteger'));
		$this->assertTrue($this->behavior->isAllowedStaticMethod('Prado\TPropertyValue', '*'));
		$this->assertFalse($this->behavior->isAllowedStaticMethod('Prado', 'localize'));

		$this->assertSame(2, $this->component->evaluateExpression('count(new ArrayObject([1, 2]))'));
		$this->assertSame(5, $this->component->evaluateExpression("\\Prado\\TPropertyValue::ensureInteger('5')"));

		$this->behavior->setAllowedStaticMethods(null);
		$this->assertTrue($this->behavior->isAllowedStaticMethod('Prado', 'localize'));
	}

	public function testDeniedMembers()
	{
		$this->assertTrue($this->behavior->isDeniedMember('evaluateExpression'));
		$this->assertTrue($this->behavior->isDeniedMember('setBehaviorsEnabled'));
		$this->assertTrue($this->behavior->isDeniedMember('_anything'));
		$this->assertTrue($this->behavior->isDeniedMember('__call'));
		$this->assertFalse($this->behavior->isDeniedMember('getText'));

		$this->behavior->setDeniedMembers(['Text']);
		$this->assertSame(['text'], $this->behavior->getDeniedMembers());
		$this->assertTrue($this->behavior->isDeniedMember('getText'));
		$this->assertTrue($this->behavior->isDeniedMember('_m'));
		$this->assertFalse($this->behavior->isDeniedMember('evaluateExpression'));
		$this->assertSame('->Text', $this->behavior->findDeniedCode('return $this->Text;'));

		$this->behavior->setDeniedMembers(null);
		$this->assertTrue($this->behavior->isDeniedMember('evaluateExpression'));
	}
}
