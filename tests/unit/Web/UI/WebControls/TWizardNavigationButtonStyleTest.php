<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TImageButton;
use Prado\Web\UI\WebControls\TStyle;
use Prado\Web\UI\WebControls\TWizardNavigationButtonStyle;
use Prado\Web\UI\WebControls\TWizardNavigationButtonType;

class TWizardNavigationButtonStyleTest extends TestCase
{
	private function createStyle(?string $imageUrl, ?string $text, ?string $type, ?string $cssClass = null): TWizardNavigationButtonStyle
	{
		$style = new TWizardNavigationButtonStyle();
		if ($imageUrl !== null) {
			$style->setImageUrl($imageUrl);
		}
		if ($text !== null) {
			$style->setButtonText($text);
		}
		if ($type !== null) {
			$style->setButtonType($type);
		}
		if ($cssClass !== null) {
			$style->setCssClass($cssClass);
		}
		return $style;
	}

	private function fields(TWizardNavigationButtonStyle $style): array
	{
		return [$style->getImageUrl(), $style->getButtonText(), $style->getButtonType(), $style->getCssClass()];
	}

	public function testCopyFromOverwritesWithTheSourceFields()
	{
		$style = $this->createStyle('/a.png', 'Go', TWizardNavigationButtonType::Image, 'a');
		$style->copyFrom($this->createStyle('/b.png', 'Next', TWizardNavigationButtonType::Link, 'b'));
		$this->assertSame(['/b.png', 'Next', TWizardNavigationButtonType::Link, 'b'], $this->fields($style));

		// unset source fields keep the target fields
		$style->copyFrom(new TWizardNavigationButtonStyle());
		$this->assertSame(['/b.png', 'Next', TWizardNavigationButtonType::Link, 'b'], $this->fields($style));

		$empty = new TWizardNavigationButtonStyle();
		$empty->copyFrom($this->createStyle('/c.png', 'Done', TWizardNavigationButtonType::Button));
		$this->assertSame(['/c.png', 'Done', TWizardNavigationButtonType::Button, ''], $this->fields($empty));
	}

	public function testCopyFromEachSetOfSourceFields()
	{
		$source = ['/b.png', 'Next', TWizardNavigationButtonType::Link];
		$target = ['/a.png', 'Go', TWizardNavigationButtonType::Image];
		for ($mask = 0; $mask < 8; $mask++) {
			$set = fn ($bit, $value) => ($mask & $bit) ? $value : null;
			$style = $this->createStyle(...$target);
			$style->copyFrom($this->createStyle($set(1, $source[0]), $set(2, $source[1]), $set(4, $source[2])));
			$expected = [
				($mask & 1) ? $source[0] : $target[0],
				($mask & 2) ? $source[1] : $target[1],
				($mask & 4) ? $source[2] : $target[2],
				'',
			];
			$this->assertSame($expected, $this->fields($style), "mask $mask");
		}
	}

	public function testMergeWithKeepsTheTargetFields()
	{
		$style = $this->createStyle('/a.png', 'Go', TWizardNavigationButtonType::Image, 'a');
		$style->mergeWith($this->createStyle('/b.png', 'Next', TWizardNavigationButtonType::Link, 'b'));
		$this->assertSame(['/a.png', 'Go', TWizardNavigationButtonType::Image, 'a'], $this->fields($style));

		$empty = new TWizardNavigationButtonStyle();
		$empty->mergeWith($this->createStyle('/c.png', 'Done', TWizardNavigationButtonType::Link, 'c'));
		$this->assertSame(['/c.png', 'Done', TWizardNavigationButtonType::Link, 'c'], $this->fields($empty));

		$empty = new TWizardNavigationButtonStyle();
		$empty->mergeWith(new TWizardNavigationButtonStyle());
		$this->assertSame(['', '', TWizardNavigationButtonType::Button, ''], $this->fields($empty));
	}
}
