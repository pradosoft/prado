<?php

/**
 * TRestPagination class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\Rest;

use Prado\Prado;
use Prado\TApplicationComponent;
use Prado\Web\THttpRequest;

/**
 * TRestPagination class.
 *
 * TRestPagination is a stateless pagination helper for REST API list endpoints.
 * It reads the `page` and `per_page` request parameters from the current request
 * (via {@see \Prado\Web\THttpRequest::itemAt()}, which covers query-string, route,
 * and form values) and exposes SQL-friendly `offset` and `limit` values.
 *
 * ## Usage in a TRestResource
 *
 * ```php
 * public function doIndex(): array
 * {
 *     $pagination = TRestPagination::fromRequest();
 *
 *     $users = User::findAll(
 *         offset: $pagination->getOffset(),
 *         limit:  $pagination->getLimit(),
 *     );
 *     $total = User::count();
 *
 *     return $pagination->paginate($users, $total);
 * }
 * ```
 *
 * The response produced by {@see paginate()} follows the format:
 * ```json
 * {
 *   "data": [ ... ],
 *   "meta": {
 *     "total": 150,
 *     "per_page": 20,
 *     "current_page": 2,
 *     "last_page": 8,
 *     "from": 21,
 *     "to": 40
 *   }
 * }
 * ```
 *
 * ## Request Parameters
 *
 * | Parameter  | Description | Default |
 * |------------|-------------|---------|
 * | `page`     | 1-based page number (clamped to at least 1) | 1 |
 * | `per_page` | Items per page (clamped to `[1, maxPerPage]`) | 20 |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TRestPagination extends TApplicationComponent
{
	/**
	 * @var int Current page number (1-based).
	 */
	private int $_page;

	/**
	 * @var int Number of items per page.
	 */
	private int $_perPage;

	/**
	 * @var int Maximum allowed value for per_page.
	 */
	private int $_maxPerPage;

	/**
	 * Constructor. Clamps `$page` and `$perPage` to at least 1, and caps
	 * `$perPage` at `$maxPerPage`.
	 * @param int $page 1-based page number; values below 1 become 1.
	 * @param int $perPage Items per page; values below 1 become 1, values
	 *   above `$maxPerPage` become `$maxPerPage`.
	 * @param int $maxPerPage Maximum allowed `per_page` value. Defaults to 100.
	 */
	public function __construct(int $page = 1, int $perPage = 20, int $maxPerPage = 100)
	{
		parent::__construct();
		$this->setMaxPerPage($maxPerPage); // must be set before setPerPage — see setPerPage()
		$this->setPerPage($perPage);
		$this->setPage($page);
	}

	/**
	 * Creates a TRestPagination instance from the current HTTP request.
	 *
	 * Reads the `page` and `per_page` request parameters. The `page` value is
	 * clamped to at least 1, and `per_page` is clamped to the range
	 * `[1, $maxPerPage]`.
	 *
	 * @param ?THttpRequest $request Request to read parameters from.
	 *   Defaults to the application's current request.
	 * @param int $defaultPerPage Default items-per-page when `per_page` is absent. Defaults to 20.
	 * @param int $maxPerPage Maximum allowed per-page value. Defaults to 100.
	 * @return self The pagination helper built from the request parameters.
	 */
	public static function fromRequest(?THttpRequest $request = null, int $defaultPerPage = 20, int $maxPerPage = 100): self
	{
		if ($request === null) {
			$request = Prado::getApplication()->getRequest();
		}

		$page = max(1, (int) ($request->itemAt('page') ?? 1));
		$perPage = max(1, (int) ($request->itemAt('per_page') ?? $defaultPerPage));
		// Constructor calls setMaxPerPage, setPerPage, setPage — all clamping is applied there
		return new self($page, $perPage, $maxPerPage);
	}

	// ── Direct Accessors (UAP-SE) ──────────────────────────────────────────────

	/**
	 * @return int Stored current page number.
	 */
	protected function getPageDirect(): int
	{
		return $this->_page;
	}

	/**
	 * @param int $value Page number to store (raw, no clamping).
	 */
	protected function setPageDirect(int $value): void
	{
		$this->_page = $value;
	}

	/**
	 * @return int Stored per-page count.
	 */
	protected function getPerPageDirect(): int
	{
		return $this->_perPage;
	}

	/**
	 * @param int $value Per-page count to store (raw, no clamping).
	 */
	protected function setPerPageDirect(int $value): void
	{
		$this->_perPage = $value;
	}

	/**
	 * @return int Stored maximum per-page value.
	 */
	protected function getMaxPerPageDirect(): int
	{
		return $this->_maxPerPage;
	}

	/**
	 * @param int $value Maximum per-page value to store (raw, no clamping).
	 */
	protected function setMaxPerPageDirect(int $value): void
	{
		$this->_maxPerPage = $value;
	}

	// ── Accessors ──────────────────────────────────────────────────────────────

	/**
	 * @return int Current page number (1-based).
	 */
	public function getPage(): int
	{
		return $this->getPageDirect();
	}

	/**
	 * Sets the current page number. Values below 1 are clamped to 1, and the
	 * value is capped so that {@see getOffset()} cannot overflow `PHP_INT_MAX`
	 * for the current per-page size.
	 * @param int $value 1-based page number.
	 */
	protected function setPage(int $value): void
	{
		$maxPage = intdiv(PHP_INT_MAX, max(1, $this->getPerPageDirect())) + 1;
		$this->setPageDirect(min($maxPage, max(1, $value)));
	}

	/**
	 * @return int Items per page.
	 */
	public function getPerPage(): int
	{
		return $this->getPerPageDirect();
	}

	/**
	 * Sets the per-page count. Values below 1 are clamped to 1.
	 * Values above {@see getMaxPerPage()} are clamped to the maximum.
	 * {@see setMaxPerPage()} must be called before this method.
	 * @param int $value Items per page.
	 */
	protected function setPerPage(int $value): void
	{
		$this->setPerPageDirect(min($this->getMaxPerPageDirect(), max(1, $value)));
	}

	/**
	 * @return int Maximum allowed per-page value.
	 */
	public function getMaxPerPage(): int
	{
		return $this->getMaxPerPageDirect();
	}

	/**
	 * Sets the maximum allowed per-page value. Values below 1 are clamped to 1.
	 * @param int $value Maximum per-page value.
	 */
	protected function setMaxPerPage(int $value): void
	{
		$this->setMaxPerPageDirect(max(1, $value));
	}

	/**
	 * Returns the SQL `OFFSET` value for the current page.
	 *
	 * The product is guarded against integer overflow: if `(page - 1) * perPage`
	 * exceeds `PHP_INT_MAX` (which PHP would promote to a float), `PHP_INT_MAX`
	 * is returned so the declared `int` return type holds.
	 *
	 * @return int Zero-based row offset.
	 */
	public function getOffset(): int
	{
		$offset = ($this->getPage() - 1) * $this->getPerPage();
		return is_int($offset) ? $offset : PHP_INT_MAX;
	}

	/**
	 * Returns the SQL `LIMIT` value for the current page.
	 * Equivalent to {@see getPerPage()}.
	 * @return int Row count limit.
	 */
	public function getLimit(): int
	{
		return $this->getPerPage();
	}

	// ── Response helpers ───────────────────────────────────────────────────────

	/**
	 * Returns a pagination meta array for the given total item count.
	 *
	 * ```json
	 * {
	 *   "total": 150,
	 *   "per_page": 20,
	 *   "current_page": 2,
	 *   "last_page": 8,
	 *   "from": 21,
	 *   "to": 40
	 * }
	 * ```
	 *
	 * `from` and `to` are `null` when `$total` is 0.
	 *
	 * @param int $total Total number of items across all pages.
	 * @return array Pagination metadata.
	 */
	public function toMeta(int $total): array
	{
		$lastPage = max(1, (int) ceil($total / $this->getPerPage()));

		return [
			'total' => $total,
			'per_page' => $this->getPerPage(),
			'current_page' => $this->getPage(),
			'last_page' => $lastPage,
			'from' => $total > 0 ? $this->getOffset() + 1 : null,
			'to' => $total > 0 ? min($this->getOffset() + $this->getPerPage(), $total) : null,
		];
	}

	/**
	 * Wraps a page of data with pagination metadata.
	 *
	 * Returns an array with two keys: `data` containing the items and `meta`
	 * containing the result of {@see toMeta()}. This is the standard response
	 * envelope for paginated list endpoints.
	 *
	 * @param array $data Items for the current page.
	 * @param int $total Total number of items across all pages.
	 * @return array Paginated response envelope.
	 */
	public function paginate(array $data, int $total): array
	{
		return [
			'data' => $data,
			'meta' => $this->toMeta($total),
		];
	}
}
