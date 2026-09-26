<?php

/**
 * A REST resource fixture implementing the collection and item convention
 * methods `doIndex`, `doShow`, `doStore`, and `doDestroy`, and recording each
 * call in {@see DoStyleResource::$log}. The TRestService tests dispatch to it,
 * and the `fixtures/rest-*` configuration files reference it by name.
 */

namespace Prado\Test\Unit\Web\Services;

use Prado\Web\Services\Rest\TRestResource;

class DoStyleResource extends TRestResource
{
	public static array $log = [];

	public function doIndex(): array
	{
		self::$log[] = 'doIndex';
		return ['list' => true];
	}

	public function doShow(string $id): array
	{
		self::$log[] = "doShow:{$id}";
		return ['id' => $id];
	}

	public function doStore(): array
	{
		self::$log[] = 'doStore';
		return $this->created(['created' => true]);
	}

	public function doDestroy(string $id): void
	{
		self::$log[] = "doDestroy:{$id}";
		$this->noContent();
	}
}
