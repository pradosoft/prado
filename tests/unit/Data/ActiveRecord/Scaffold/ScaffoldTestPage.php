<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold;

use Prado\Collections\TMap;
use Prado\IO\TTextWriter;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;

/**
 * Page that runs the control life cycle stages one at a time, without a request.
 */
class ScaffoldTestPage extends TPage
{
	public function setIsPostBack(bool $value): void
	{
		\Prado\Test\Unit\PradoUnit::setProp($this, '_postData', $value ? new TMap() : null);
	}

	public function runInit(): void
	{
		$this->onPreInit(null);
		$this->initRecursive();
		$this->onInitComplete(null);
	}

	public function runLoad(): void
	{
		$this->onPreLoad(null);
		$this->loadRecursive();
		$this->onLoadComplete(null);
	}

	public function runPreRender(): void
	{
		$this->preRenderRecursive();
		$this->onPreRenderComplete(null);
	}

	public function renderHtml(): string
	{
		$text = new TTextWriter();
		$this->renderControl(new THtmlWriter($text));
		return $text->flush();
	}
}
