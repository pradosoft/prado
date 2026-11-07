<?php

namespace Prado\Test\Unit\Web\Services\ServiceWorker;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\Services\ServiceWorker\TServiceWorkerDefinition;
use Prado\Web\Services\ServiceWorker\TServiceWorkerScript;

class TServiceWorkerScriptTest extends TestCase
{
	private function createScript(string $name = 'default', string $scope = '/app/'): TServiceWorkerScript
	{
		$worker = new TServiceWorkerDefinition();
		$worker->setName($name);
		$worker->setScope($scope);
		return new TServiceWorkerScript($worker);
	}

	private function optionsOf(string $script): array
	{
		$this->assertMatchesRegularExpression('/^const PRADO_SERVICE_WORKER = (\{.*\});\n/', $script);
		preg_match('/^const PRADO_SERVICE_WORKER = (\{.*\});\n/', $script, $match);
		return json_decode($match[1], true);
	}

	public function testWorkerIsTheDefinition()
	{
		$worker = new TServiceWorkerDefinition();
		$script = new TServiceWorkerScript($worker);
		$this->assertSame($worker, $script->getWorker());
	}

	public function testFragmentsRunByPriorityThenOrderAdded()
	{
		$script = $this->createScript();
		$script->addFragment('late', 'late();', 20);
		$script->addFragment('first', 'first();', 5);
		$script->addFragment('second', 'second();', 5);
		$script->addFragment('default', 'base();');
		$this->assertSame(['first', 'second', 'default', 'late'], array_keys($script->getFragments()));
		$this->assertSame(['code' => 'first();', 'priority' => 5.0, 'config' => []], $script->getFragments()['first']);
	}

	public function testFragmentOfTheSameNameIsReplaced()
	{
		$script = $this->createScript();
		$script->addFragment('a', 'one();');
		$script->addFragment('b', 'two();');
		$script->addFragment('a', 'three();', 30, ['x' => 1]);
		$this->assertSame(['b', 'a'], array_keys($script->getFragments()));
		$this->assertSame('three();', $script->getFragments()['a']['code']);
		$this->assertTrue($script->hasFragment('a'));
		$script->removeFragment('a');
		$this->assertFalse($script->hasFragment('a'));
	}

	public function testFragmentNameIsValidated()
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->createScript()->addFragment('bad name', '');
	}

	public function testFragmentFileIsRead()
	{
		$file = tempnam(sys_get_temp_dir(), 'swfrag');
		file_put_contents($file, 'prado.onMessage("x", () => 1);');
		try {
			$script = $this->createScript();
			$script->addFragmentFile('file', $file, 1, ['a' => true]);
			$this->assertSame('prado.onMessage("x", () => 1);', $script->getFragments()['file']['code']);
			$this->assertSame(['a' => true], $script->getFragments()['file']['config']);
		} finally {
			unlink($file);
		}
	}

	public function testMissingFragmentFileThrows()
	{
		$this->expectException(TConfigurationException::class);
		$this->createScript()->addFragmentFile('missing', sys_get_temp_dir() . '/no-such-fragment.js');
	}

	public function testPrecacheAndExcludeKeepOrderWithoutDuplicates()
	{
		$script = $this->createScript();
		$script->addPrecache('/offline');
		$script->addPrecache(['/logo.png', '/offline', '']);
		$script->addExclude(['/index.php?sw=worker', '/index.php?sw=worker']);
		$this->assertSame(['/offline', '/logo.png'], $script->getPrecache());
		$this->assertSame(['/index.php?sw=worker'], $script->getExclude());
	}

	public function testOptionsDescribeTheWorker()
	{
		$script = $this->createScript('admin', '/admin/');
		$script->addPrecache('/offline');
		$script->addFragment('one', '', 10, ['k' => 'v']);
		$script->addFragment('two', '');
		$options = json_decode(json_encode($script->getOptions()), true);
		$this->assertSame([
			'worker' => 'admin',
			'scope' => '/admin/',
			'precache' => ['/offline'],
			'exclude' => [],
			'fragments' => ['one' => ['k' => 'v'], 'two' => []],
		], $options);
		$this->assertSame('{}', json_encode($script->getOptions()['fragments']->two));
	}

	public function testRenderDeclaresOptionsThenRuntimeFragmentsAndStart()
	{
		$script = $this->createScript();
		$script->addFragment('greeting', 'prado.onMessage("hello", () => "hi");', 10, ['n' => 1]);
		$js = $script->render();

		$options = $this->optionsOf($js);
		$this->assertSame($script->getVersion(), $options['version']);
		$this->assertSame('default', $options['worker']);
		$this->assertSame(['n' => 1], $options['fragments']['greeting']);

		$runtime = file_get_contents(TServiceWorkerScript::getSourcePath(TServiceWorkerScript::RUNTIME_FILE));
		$this->assertStringContainsString($runtime, $js);
		$this->assertStringContainsString(
			"prado.addFragment(\"greeting\", function (prado, config) {\nprado.onMessage(\"hello\", () => \"hi\");\n}, PRADO_SERVICE_WORKER.fragments[\"greeting\"]);",
			$js
		);
		$this->assertStringEndsWith("prado.start();\n", $js);
		$this->assertLessThan(strpos($js, 'prado.addFragment("greeting"'), strpos($js, $runtime));
	}

	public function testVersionIsStableAndChangesWithContent()
	{
		$a = $this->createScript();
		$a->addFragment('x', 'one();');
		$b = $this->createScript();
		$b->addFragment('x', 'one();');
		$this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $a->getVersion());
		$this->assertSame($a->getVersion(), $b->getVersion());
		$this->assertSame($a->render(), $b->render());

		$b->addFragment('x', 'two();');
		$this->assertNotSame($a->getVersion(), $b->getVersion());

		$c = $this->createScript();
		$c->addFragment('x', 'one();');
		$c->addPrecache('/offline');
		$this->assertNotSame($a->getVersion(), $c->getVersion());

		$d = $this->createScript('default', '/other/');
		$d->addFragment('x', 'one();');
		$this->assertNotSame($a->getVersion(), $d->getVersion());
	}

	public function testRenderUnregisterNamesTheWorker()
	{
		$worker = new TServiceWorkerDefinition();
		$worker->setName('admin');
		$worker->setScope('/admin/');
		$js = TServiceWorkerScript::renderUnregister($worker);
		$this->assertSame(['worker' => 'admin', 'scope' => '/admin/'], $this->optionsOf($js));
		$this->assertStringContainsString(file_get_contents(TServiceWorkerScript::getSourcePath(TServiceWorkerScript::UNREGISTER_FILE)), $js);
		$this->assertStringContainsString('registration.unregister()', $js);
	}

	public function testSourcePathIsInTheFramework()
	{
		$this->assertFileExists(TServiceWorkerScript::getSourcePath(TServiceWorkerScript::RUNTIME_FILE));
		$this->assertFileExists(TServiceWorkerScript::getSourcePath(TServiceWorkerScript::UNREGISTER_FILE));
		$this->assertStringEndsWith('/Web/Javascripts/source/prado/serviceworker/worker.js', TServiceWorkerScript::getSourcePath('worker.js'));
	}
}
