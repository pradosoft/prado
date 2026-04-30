<?php

use Prado\Web\UI\TPage;
use Prado\Web\UI\TSessionPageStatePersister;
use Prado\Web\UI\IPageStatePersister;
use Prado\Exceptions\THttpException;
use Prado\Exceptions\TInvalidDataValueException;

/**
 * In-memory HTTP session stub used for unit testing without a real PHP session.
 */
class FakeHttpSession
{
	private array $_data = [];

	public function open(): void
	{
	}

	public function add(string $key, mixed $value): void
	{
		$this->_data[$key] = $value;
	}

	public function itemAt(string $key): mixed
	{
		return $this->_data[$key] ?? null;
	}

	public function remove(string $key): void
	{
		unset($this->_data[$key]);
	}

	public function hasKey(string $key): bool
	{
		return isset($this->_data[$key]);
	}

	public function getData(): array
	{
		return $this->_data;
	}
}

/**
 * TPage subclass with controllable session and request client state.
 */
class TSessionPersisterTestPage extends TPage
{
	private string $_requestClientState = '';
	private ?FakeHttpSession $_fakeSession = null;

	public function setFakeSession(FakeHttpSession $session): void
	{
		$this->_fakeSession = $session;
	}

	public function getSession()
	{
		if ($this->_fakeSession !== null) {
			return $this->_fakeSession;
		}
		return parent::getSession();
	}

	public function setRequestClientState(string $value): void
	{
		$this->_requestClientState = $value;
	}

	public function getRequestClientState(): string
	{
		return $this->_requestClientState;
	}
}

class TSessionPageStatePersisterTest extends PHPUnit\Framework\TestCase
{
	/**
	 * Create a page + fake session with all state features disabled for
	 * deterministic TPageStateFormatter output (plain base64/serialize).
	 */
	private function newPage(): TSessionPersisterTestPage
	{
		$page = new TSessionPersisterTestPage();
		$page->setEnableStateValidation(false);
		$page->setEnableStateEncryption(false);
		$page->setEnableStateCompression(false);
		$page->setEnableStateIGBinary(false);
		$page->setFakeSession(new FakeHttpSession());
		return $page;
	}

	// -----------------------------------------------------------------------
	// Interface / constants
	// -----------------------------------------------------------------------

	public function testImplementsIPageStatePersister(): void
	{
		$this->assertInstanceOf(IPageStatePersister::class, new TSessionPageStatePersister());
	}

	public function testStateSessionKeyConstant(): void
	{
		$this->assertEquals('PRADO_SESSION_PAGESTATE', TSessionPageStatePersister::STATE_SESSION_KEY);
	}

	public function testQueueSessionKeyConstant(): void
	{
		$this->assertEquals('PRADO_SESSION_STATEQUEUE', TSessionPageStatePersister::QUEUE_SESSION_KEY);
	}

	// -----------------------------------------------------------------------
	// setPage / getPage
	// -----------------------------------------------------------------------

	public function testSetAndGetPage(): void
	{
		$persister = new TSessionPageStatePersister();
		$page = $this->newPage();
		$persister->setPage($page);
		$this->assertSame($page, $persister->getPage());
	}

	// -----------------------------------------------------------------------
	// HistorySize
	// -----------------------------------------------------------------------

	public function testGetHistorySizeDefaultIsTen(): void
	{
		$persister = new TSessionPageStatePersister();
		$this->assertEquals(10, $persister->getHistorySize());
	}

	public function testSetHistorySizeToValidValue(): void
	{
		$persister = new TSessionPageStatePersister();
		$persister->setHistorySize(5);
		$this->assertEquals(5, $persister->getHistorySize());
	}

	public function testSetHistorySizeToOneIsValid(): void
	{
		$persister = new TSessionPageStatePersister();
		$persister->setHistorySize(1);
		$this->assertEquals(1, $persister->getHistorySize());
	}

	public function testSetHistorySizeToZeroThrows(): void
	{
		$persister = new TSessionPageStatePersister();
		$this->expectException(TInvalidDataValueException::class);
		$persister->setHistorySize(0);
	}

	public function testSetHistorySizeToNegativeThrows(): void
	{
		$persister = new TSessionPageStatePersister();
		$this->expectException(TInvalidDataValueException::class);
		$persister->setHistorySize(-1);
	}

	public function testSetHistorySizeAcceptsStringRepresentingInteger(): void
	{
		$persister = new TSessionPageStatePersister();
		$persister->setHistorySize('7');
		$this->assertEquals(7, $persister->getHistorySize());
	}

	// -----------------------------------------------------------------------
	// save() and load() round-trip
	// -----------------------------------------------------------------------

	public function testSaveStoresSerializedDataInSession(): void
	{
		$page = $this->newPage();
		$session = $page->getSession();
		$persister = new TSessionPageStatePersister();
		$persister->setPage($page);

		$data = ['field' => 'value'];
		$persister->save($data);

		// Queue must have been created
		$queue = $session->itemAt(TSessionPageStatePersister::QUEUE_SESSION_KEY);
		$this->assertIsArray($queue);
		$this->assertCount(1, $queue);

		// The state key in the queue must exist in session
		$key = $queue[0];
		$this->assertStringStartsWith(TSessionPageStatePersister::STATE_SESSION_KEY, $key);
		$stored = $session->itemAt($key);
		$this->assertNotNull($stored);
		$this->assertEquals($data, unserialize($stored));
	}

	public function testSaveSetsClientState(): void
	{
		$page = $this->newPage();
		$persister = new TSessionPageStatePersister();
		$persister->setPage($page);

		$persister->save(['x' => 1]);

		$this->assertNotEmpty($page->getClientState());
		// Client state is base64-encoded
		$this->assertNotFalse(base64_decode($page->getClientState(), true));
	}

	public function testLoadReturnsOriginalArray(): void
	{
		$page = $this->newPage();
		$persister = new TSessionPageStatePersister();
		$persister->setPage($page);

		$data = ['hello' => 'world', 'num' => 42];
		$persister->save($data);
		// Simulate client sending back the page-state token
		$page->setRequestClientState($page->getClientState());

		$this->assertEquals($data, $persister->load());
	}

	public function testLoadReturnsOriginalString(): void
	{
		$page = $this->newPage();
		$persister = new TSessionPageStatePersister();
		$persister->setPage($page);

		$persister->save('state string');
		$page->setRequestClientState($page->getClientState());

		$this->assertEquals('state string', $persister->load());
	}

	public function testLoadReturnsOriginalInteger(): void
	{
		$page = $this->newPage();
		$persister = new TSessionPageStatePersister();
		$persister->setPage($page);

		$persister->save(12345);
		$page->setRequestClientState($page->getClientState());

		$this->assertSame(12345, $persister->load());
	}

	public function testMultipleSavesAndLoadLastState(): void
	{
		$page = $this->newPage();
		$persister = new TSessionPageStatePersister();
		$persister->setPage($page);

		$persister->save(['first']);
		usleep(1000);
		$persister->save(['second']);
		usleep(1000);
		$persister->save(['third']);

		$page->setRequestClientState($page->getClientState());
		$this->assertEquals(['third'], $persister->load());
	}

	// -----------------------------------------------------------------------
	// load() error cases
	// -----------------------------------------------------------------------

	public function testLoadWithEmptyClientStateThrows400(): void
	{
		$page = $this->newPage();
		$page->setRequestClientState('');
		$persister = new TSessionPageStatePersister();
		$persister->setPage($page);

		try {
			$persister->load();
			$this->fail('Expected THttpException to be thrown');
		} catch (THttpException $e) {
			$this->assertEquals(400, $e->getStatusCode());
		}
	}

	public function testLoadWithMissingSessionKeyThrows400(): void
	{
		$page = $this->newPage();
		$persister = new TSessionPageStatePersister();
		$persister->setPage($page);

		// Create a valid-looking client state that points to a non-existent session key.
		// Encode a fake timestamp directly.
		$fakeTimestamp = '9999999999.999999';
		$fakeClientState = base64_encode(serialize($fakeTimestamp));
		$page->setRequestClientState($fakeClientState);

		try {
			$persister->load();
			$this->fail('Expected THttpException to be thrown');
		} catch (THttpException $e) {
			$this->assertEquals(400, $e->getStatusCode());
		}
	}

	// -----------------------------------------------------------------------
	// History queue management
	// -----------------------------------------------------------------------

	public function testQueueGrowsWithEachSave(): void
	{
		$page = $this->newPage();
		$session = $page->getSession();
		$persister = new TSessionPageStatePersister();
		$persister->setPage($page);

		$persister->save('a');
		usleep(1000);
		$persister->save('b');
		usleep(1000);
		$persister->save('c');

		$queue = $session->itemAt(TSessionPageStatePersister::QUEUE_SESSION_KEY);
		$this->assertCount(3, $queue);
	}

	public function testHistoryLimitEvictsOldestEntry(): void
	{
		$page = $this->newPage();
		$session = $page->getSession();
		$persister = new TSessionPageStatePersister();
		$persister->setHistorySize(2);
		$persister->setPage($page);

		$persister->save('first');
		$queue1 = $session->itemAt(TSessionPageStatePersister::QUEUE_SESSION_KEY);
		$firstKey = $queue1[0];

		// Confirm first key is in session
		$this->assertTrue($session->hasKey($firstKey));

		usleep(1000);
		$persister->save('second');
		usleep(1000);
		$persister->save('third'); // Should evict 'first'

		// Queue should still have only historySize entries
		$queue3 = $session->itemAt(TSessionPageStatePersister::QUEUE_SESSION_KEY);
		$this->assertCount(2, $queue3);

		// The first key must have been removed
		$this->assertFalse($session->hasKey($firstKey));
	}

	public function testHistoryLimitOfOneKeepsOnlyLatestEntry(): void
	{
		$page = $this->newPage();
		$session = $page->getSession();
		$persister = new TSessionPageStatePersister();
		$persister->setHistorySize(1);
		$persister->setPage($page);

		$persister->save('first');
		$queue1 = $session->itemAt(TSessionPageStatePersister::QUEUE_SESSION_KEY);
		$firstKey = $queue1[0];

		usleep(1000);
		$persister->save('second');

		$queue2 = $session->itemAt(TSessionPageStatePersister::QUEUE_SESSION_KEY);
		$this->assertCount(1, $queue2);
		$this->assertFalse($session->hasKey($firstKey));
	}

	public function testEachSaveAddsEntryToQueue(): void
	{
		$page = $this->newPage();
		$session = $page->getSession();
		$persister = new TSessionPageStatePersister();
		$persister->setPage($page);

		$persister->save('a');
		$queueAfterFirst = $session->itemAt(TSessionPageStatePersister::QUEUE_SESSION_KEY);
		$this->assertCount(1, $queueAfterFirst);

		usleep(1000); // 1 ms — ensures distinct microtime() values
		$persister->save('b');
		$queueAfterSecond = $session->itemAt(TSessionPageStatePersister::QUEUE_SESSION_KEY);
		$this->assertCount(2, $queueAfterSecond);

		// The two keys in the queue must be distinct
		$this->assertNotEquals($queueAfterSecond[0], $queueAfterSecond[1]);
	}
}
