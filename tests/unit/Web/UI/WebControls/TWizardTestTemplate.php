<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Web\UI\ITemplate;

/**
 * Template that runs a closure on the parent it is instantiated in.
 */
class TWizardTestTemplate implements ITemplate
{
	public function __construct(private \Closure $build)
	{
	}

	public function instantiateIn($parent)
	{
		($this->build)($parent);
	}

	public function getIncludedFiles()
	{
		return [];
	}
}
