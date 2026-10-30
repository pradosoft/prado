<?php

/**
 * A REST resource fixture declaring its routes with {@see TRestRoute} attributes
 * and recording each call in {@see UsersFixtureResource::$log}. The TRestService
 * tests dispatch to it, and the `fixtures/rest-include.*` configuration files
 * reference it by name.
 */

namespace Prado\Test\Unit\Web\Services\Rest;

use Prado\Web\Services\Rest\TRestResource;
use Prado\Web\Services\Rest\TRestRoute;

#[TRestRoute(pattern: 'users')]
class UsersFixtureResource extends TRestResource
{
	public static array $log = [];

	private string $_tag = '';

	public function getTag(): string
	{
		return $this->_tag;
	}

	public function setTag(string $value): void
	{
		$this->_tag = $value;
	}

	#[TRestRoute('GET')]
	public function index(): array
	{
		self::$log[] = 'index';
		return ['list' => true, 'tag' => $this->getTag()];
	}

	#[TRestRoute('GET', '{id:\d+}')]
	public function show(int $id): array
	{
		self::$log[] = "show:{$id}";
		return ['id' => $id];
	}

	#[TRestRoute('POST')]
	public function store(): array
	{
		self::$log[] = 'store';
		return $this->created(['created' => true]);
	}

	#[TRestRoute('DELETE', '{id}')]
	public function destroy(string $id): void
	{
		self::$log[] = "destroy:{$id}";
		$this->noContent();
	}
}
