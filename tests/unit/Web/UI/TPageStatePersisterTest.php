<?php

use Prado\Web\UI\TPage;
use Prado\Web\UI\TPageStatePersister;
use Prado\Web\UI\IPageStatePersister;
use Prado\Exceptions\THttpException;

/**
 * TPage subclass that makes getRequestClientState() controllable from tests.
 */
class TPageStatePersisterTestPage extends TPage
{
	private string $_requestClientState = '';

	public function setRequestClientState(string $value): void
	{
		$this->_requestClientState = $value;
	}

	public function getRequestClientState(): string
	{
		return $this->_requestClientState;
	}
}

class TPageStatePersisterTest extends PHPUnit\Framework\TestCase
{
	/**
	 * Create a page with all state transformation features disabled so that
	 * TPageStateFormatter produces a plain base64(serialize($data)) string.
	 * The security manager is still retrieved internally but is never called
	 * when both validation and encryption are off.
	 */
	private function newPage(): TPageStatePersisterTestPage
	{
		$page = new TPageStatePersisterTestPage();
		$page->setEnableStateValidation(false);
		$page->setEnableStateEncryption(false);
		$page->setEnableStateCompression(false);
		$page->setEnableStateIGBinary(false);
		return $page;
	}

	// -----------------------------------------------------------------------
	// Interface / construction
	// -----------------------------------------------------------------------

	public function testImplementsIPageStatePersister(): void
	{
		$this->assertInstanceOf(IPageStatePersister::class, new TPageStatePersister());
	}

	// -----------------------------------------------------------------------
	// setPage / getPage
	// -----------------------------------------------------------------------

	public function testSetAndGetPage(): void
	{
		$persister = new TPageStatePersister();
		$page = $this->newPage();
		$persister->setPage($page);
		$this->assertSame($page, $persister->getPage());
	}

	public function testSetPageReplacesExistingPage(): void
	{
		$persister = new TPageStatePersister();
		$page1 = $this->newPage();
		$page2 = $this->newPage();
		$persister->setPage($page1);
		$persister->setPage($page2);
		$this->assertSame($page2, $persister->getPage());
	}

	// -----------------------------------------------------------------------
	// save()
	// -----------------------------------------------------------------------

	public function testSaveSetsNonEmptyClientState(): void
	{
		$page = $this->newPage();
		$persister = new TPageStatePersister();
		$persister->setPage($page);

		$persister->save(['key' => 'value']);

		$this->assertNotEmpty($page->getClientState());
	}

	public function testSaveClientStateIsValidBase64(): void
	{
		$page = $this->newPage();
		$persister = new TPageStatePersister();
		$persister->setPage($page);

		$persister->save(['key' => 'value']);

		$decoded = base64_decode($page->getClientState(), true);
		$this->assertNotFalse($decoded, 'Client state must be valid base64');
	}

	public function testSaveProducesBase64OfSerializedDataWhenAllFeaturesDisabled(): void
	{
		$page = $this->newPage();
		$persister = new TPageStatePersister();
		$persister->setPage($page);

		$data = ['test' => 'value', 'num' => 42];
		$persister->save($data);

		// All features off → base64_encode(serialize($data))
		$this->assertEquals(base64_encode(serialize($data)), $page->getClientState());
	}

	public function testSaveOverwritesPreviousClientState(): void
	{
		$page = $this->newPage();
		$persister = new TPageStatePersister();
		$persister->setPage($page);

		$persister->save('first');
		$state1 = $page->getClientState();

		$persister->save('second');
		$state2 = $page->getClientState();

		$this->assertNotEquals($state1, $state2);
		$this->assertEquals(base64_encode(serialize('second')), $state2);
	}

	// -----------------------------------------------------------------------
	// load()
	// -----------------------------------------------------------------------

	public function testLoadReturnsOriginalArray(): void
	{
		$page = $this->newPage();
		$persister = new TPageStatePersister();
		$persister->setPage($page);

		$data = ['hello' => 'world', 'nested' => ['a', 'b', 3]];
		$persister->save($data);
		$page->setRequestClientState($page->getClientState());

		$this->assertEquals($data, $persister->load());
	}

	public function testLoadReturnsOriginalInteger(): void
	{
		$page = $this->newPage();
		$persister = new TPageStatePersister();
		$persister->setPage($page);

		$persister->save(99);
		$page->setRequestClientState($page->getClientState());

		$this->assertSame(99, $persister->load());
	}

	public function testLoadReturnsOriginalString(): void
	{
		$page = $this->newPage();
		$persister = new TPageStatePersister();
		$persister->setPage($page);

		$persister->save('page state string');
		$page->setRequestClientState($page->getClientState());

		$this->assertEquals('page state string', $persister->load());
	}

	public function testLoadReturnsOriginalBooleanFalse(): void
	{
		$page = $this->newPage();
		$persister = new TPageStatePersister();
		$persister->setPage($page);

		$persister->save(false);
		$page->setRequestClientState($page->getClientState());

		// false !== null, so load() returns false (not an exception)
		$this->assertFalse($persister->load());
	}

	public function testLoadReturnsOriginalEmptyArray(): void
	{
		$page = $this->newPage();
		$persister = new TPageStatePersister();
		$persister->setPage($page);

		$persister->save([]);
		$page->setRequestClientState($page->getClientState());

		$this->assertEquals([], $persister->load());
	}

	public function testLoadReturnsComplexNestedState(): void
	{
		$page = $this->newPage();
		$persister = new TPageStatePersister();
		$persister->setPage($page);

		$data = ['level1' => ['level2' => ['level3' => 'deep']], 'arr' => [1, 2, 3], 'flag' => true];
		$persister->save($data);
		$page->setRequestClientState($page->getClientState());

		$this->assertEquals($data, $persister->load());
	}

	// -----------------------------------------------------------------------
	// load() error cases — only empty string reliably returns null from
	// TPageStateFormatter::unserialize and thus triggers the 400 exception.
	// -----------------------------------------------------------------------

	public function testLoadWithEmptyClientStateThrows400(): void
	{
		$page = $this->newPage();
		$page->setRequestClientState('');
		$persister = new TPageStatePersister();
		$persister->setPage($page);

		try {
			$persister->load();
			$this->fail('Expected THttpException to be thrown');
		} catch (THttpException $e) {
			$this->assertEquals(400, $e->getStatusCode());
		}
	}

	// -----------------------------------------------------------------------
	// Round-trip consistency
	// -----------------------------------------------------------------------

	public function testSaveThenLoadIsDeterministic(): void
	{
		$page = $this->newPage();
		$persister = new TPageStatePersister();
		$persister->setPage($page);

		$data = ['x' => 1];
		$persister->save($data);
		$savedState = $page->getClientState();

		// Save again; same data must produce same client state
		$persister->save($data);
		$this->assertEquals($savedState, $page->getClientState());
	}
}
