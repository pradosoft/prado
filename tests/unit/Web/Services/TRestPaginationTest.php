<?php

use Prado\Web\Services\Rest\TRestPagination;

/**
 * Tests for TRestPagination.
 */
class TRestPaginationTest extends PHPUnit\Framework\TestCase
{
	/** Keys touched by fromRequest tests — always restored in tearDown. */
	private const PAGINATION_KEYS = ['page', 'per_page'];

	/** @var array Snapshot of the pagination keys present in the request before each test. */
	private array $requestBackup = [];

	protected function setUp(): void
	{
		$request = Prado::getApplication()->getRequest();
		foreach (self::PAGINATION_KEYS as $key) {
			$this->requestBackup[$key] = $request->itemAt($key);
		}
	}

	protected function tearDown(): void
	{
		$request = Prado::getApplication()->getRequest();
		foreach (self::PAGINATION_KEYS as $key) {
			if ($this->requestBackup[$key] === null) {
				$request->remove($key);
			} else {
				$request->add($key, $this->requestBackup[$key]);
			}
		}
	}

	// ── Constructor ────────────────────────────────────────────────────────────

	public function testConstructorDefaults(): void
	{
		$p = new TRestPagination();
		$this->assertSame(1, $p->getPage());
		$this->assertSame(20, $p->getPerPage());
		$this->assertSame(100, $p->getMaxPerPage());
	}

	public function testConstructorClampsPageToOne(): void
	{
		$p = new TRestPagination(0);
		$this->assertSame(1, $p->getPage());

		$p2 = new TRestPagination(-5);
		$this->assertSame(1, $p2->getPage());
	}

	public function testConstructorClampsPerPageToOne(): void
	{
		$p = new TRestPagination(1, 0);
		$this->assertSame(1, $p->getPerPage());
	}

	public function testConstructorClampsPerPageToMaxPerPage(): void
	{
		$p = new TRestPagination(1, 500, 100);
		$this->assertSame(100, $p->getPerPage());
	}

	public function testConstructorAcceptsCustomMaxPerPage(): void
	{
		$p = new TRestPagination(1, 50, 200);
		$this->assertSame(200, $p->getMaxPerPage());
		$this->assertSame(50, $p->getPerPage());
	}

	// ── Offset and Limit ───────────────────────────────────────────────────────

	public function testGetOffsetPageOne(): void
	{
		$p = new TRestPagination(1, 20);
		$this->assertSame(0, $p->getOffset());
	}

	public function testGetOffsetPageTwo(): void
	{
		$p = new TRestPagination(2, 20);
		$this->assertSame(20, $p->getOffset());
	}

	public function testGetOffsetPageThree(): void
	{
		$p = new TRestPagination(3, 15);
		$this->assertSame(30, $p->getOffset());
	}

	public function testGetLimitEqualsPerPage(): void
	{
		$p = new TRestPagination(1, 25);
		$this->assertSame(25, $p->getLimit());
	}

	// ── toMeta ─────────────────────────────────────────────────────────────────

	public function testToMetaFirstPage(): void
	{
		$p = new TRestPagination(1, 20);
		$meta = $p->toMeta(100);

		$this->assertSame(100, $meta['total']);
		$this->assertSame(20, $meta['per_page']);
		$this->assertSame(1, $meta['current_page']);
		$this->assertSame(5, $meta['last_page']);
		$this->assertSame(1, $meta['from']);
		$this->assertSame(20, $meta['to']);
	}

	public function testToMetaSecondPage(): void
	{
		$p = new TRestPagination(2, 20);
		$meta = $p->toMeta(100);

		$this->assertSame(2, $meta['current_page']);
		$this->assertSame(21, $meta['from']);
		$this->assertSame(40, $meta['to']);
	}

	public function testToMetaLastPartialPage(): void
	{
		$p = new TRestPagination(3, 20);
		$meta = $p->toMeta(55);

		$this->assertSame(3, $meta['last_page']);
		$this->assertSame(41, $meta['from']);
		$this->assertSame(55, $meta['to']); // capped at total
	}

	public function testToMetaEmptyCollection(): void
	{
		$p = new TRestPagination(1, 20);
		$meta = $p->toMeta(0);

		$this->assertSame(0, $meta['total']);
		$this->assertSame(1, $meta['last_page']); // at least 1 page
		$this->assertNull($meta['from']);
		$this->assertNull($meta['to']);
	}

	public function testToMetaLastPageIsAtLeastOne(): void
	{
		$p = new TRestPagination(1, 20);
		$meta = $p->toMeta(0);
		$this->assertSame(1, $meta['last_page']);
	}

	public function testToMetaExactlyOnePage(): void
	{
		$p = new TRestPagination(1, 10);
		$meta = $p->toMeta(10);
		$this->assertSame(1, $meta['last_page']);
		$this->assertSame(10, $meta['to']);
	}

	// ── paginate ───────────────────────────────────────────────────────────────

	public function testPaginateWrapsDataAndMeta(): void
	{
		$p = new TRestPagination(1, 2);
		$data = [['id' => 1], ['id' => 2]];
		$result = $p->paginate($data, 5);

		$this->assertArrayHasKey('data', $result);
		$this->assertArrayHasKey('meta', $result);
		$this->assertSame($data, $result['data']);
		$this->assertSame(5, $result['meta']['total']);
		$this->assertSame(3, $result['meta']['last_page']);
	}

	public function testPaginateEmptyData(): void
	{
		$p = new TRestPagination(1, 20);
		$result = $p->paginate([], 0);
		$this->assertSame([], $result['data']);
		$this->assertSame(0, $result['meta']['total']);
	}

	// ── fromRequest ────────────────────────────────────────────────────────────

	public function testFromRequestReadsPageAndPerPageFromGet(): void
	{
		$request = Prado::getApplication()->getRequest();
		$request->add('page', '3');
		$request->add('per_page', '15');

		$p = TRestPagination::fromRequest($request);

		$this->assertSame(3, $p->getPage());
		$this->assertSame(15, $p->getPerPage());
	}

	public function testFromRequestDefaultsWhenParamsAbsent(): void
	{
		$request = Prado::getApplication()->getRequest();
		$request->remove('page');
		$request->remove('per_page');

		$p = TRestPagination::fromRequest($request, 25, 50);

		$this->assertSame(1, $p->getPage());
		$this->assertSame(25, $p->getPerPage());
		$this->assertSame(50, $p->getMaxPerPage());
	}

	public function testFromRequestCapsPerPageAtMax(): void
	{
		$request = Prado::getApplication()->getRequest();
		$request->add('per_page', '9999');

		$p = TRestPagination::fromRequest($request, 20, 50);

		$this->assertSame(50, $p->getPerPage());
	}

	public function testFromRequestClampsPageBelowOneToOne(): void
	{
		$request = Prado::getApplication()->getRequest();
		$request->add('page', '0');

		$p = TRestPagination::fromRequest($request);

		$this->assertSame(1, $p->getPage());
	}

	public function testFromRequestUsesCustomDefaultPerPage(): void
	{
		$p = TRestPagination::fromRequest(null, 50);
		$this->assertSame(50, $p->getPerPage());
	}

	public function testToMetaWithZeroTotalReturnsNullFromAndTo(): void
	{
		$p = new TRestPagination(1, 20);
		$meta = $p->toMeta(0);
		$this->assertSame(0, $meta['total']);
		$this->assertNull($meta['from']);
		$this->assertNull($meta['to']);
		$this->assertSame(1, $meta['last_page']);
	}
}
