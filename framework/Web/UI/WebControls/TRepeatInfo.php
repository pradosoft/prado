<?php
/**
 * IRepeatInfoUser, TRepeatInfo class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;
use Prado\Exceptions\TInvalidDataValueException;

/**
 * TRepeatInfo class.
 * TRepeatInfo represents repeat information for controls like {@link TCheckBoxList}.
 * The layout of the repeated items is specified via {@link setRepeatLayout RepeatLayout},
 * which can be either Table (default), Flow or Raw.
 * A table layout uses HTML table cells to organize the items while
 * a flow layout uses line breaks to organize the items.
 * The number of columns used to display the items is specified via
 * {@link setRepeatColumns RepeatColumns} property, while the {@link setRepeatDirection RepeatDirection}
 * governs the order of the items being rendered.
 *
 * Note, the Raw layout does not contain any formatting tags and thus ignores
 * the column and repeat direction settings.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TRepeatInfo extends \Prado\TComponent
{
	/**
	 * @var string caption of the table used to organize the repeated items
	 */
	private $_caption = '';
	/**
	 * @var TTableCaptionAlign alignment of the caption of the table used to organize the repeated items
	 */
	private $_captionAlign = TTableCaptionAlign::NotSet;
	/**
	 * @var int number of columns that the items should be arranged in
	 */
	private $_repeatColumns = 0;
	/**
	 * @var TRepeatDirection direction of the repetition
	 */
	private $_repeatDirection = TRepeatDirection::Vertical;
	/**
	 * @var TRepeatLayout layout of the repeated items
	 */
	private $_repeatLayout = TRepeatLayout::Table;

	/**
	 * @return string caption of the table layout
	 */
	public function getCaption()
	{
		return $this->_caption;
	}

	/**
	 * @param string $value caption of the table layout
	 */
	public function setCaption($value)
	{
		$this->_caption = $value;
	}

	/**
	 * @return TTableCaptionAlign alignment of the caption of the table layout. Defaults to TTableCaptionAlign::NotSet.
	 */
	public function getCaptionAlign()
	{
		return $this->_captionAlign;
	}

	/**
	 * @param mixed $value
	 * @return TTableCaptionAlign alignment of the caption of the table layout.
	 */
	public function setCaptionAlign($value)
	{
		$this->_captionAlign = TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TTableCaptionAlign');
	}

	/**
	 * @return int the number of columns that the repeated items should be displayed in. Defaults to 0, meaning not set.
	 */
	public function getRepeatColumns()
	{
		return $this->_repeatColumns;
	}

	/**
	 * @param int $value the number of columns that the repeated items should be displayed in.
	 */
	public function setRepeatColumns($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			throw new TInvalidDataValueException('repeatinfo_repeatcolumns_invalid');
		}
		$this->_repeatColumns = $value;
	}

	/**
	 * @return TRepeatDirection the direction of traversing the repeated items, defaults to TRepeatDirection::Vertical
	 */
	public function getRepeatDirection()
	{
		return $this->_repeatDirection;
	}

	/**
	 * @param TRepeatDirection $value the direction of traversing the repeated items
	 */
	public function setRepeatDirection($value)
	{
		$this->_repeatDirection = TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TRepeatDirection');
	}

	/**
	 * @return TRepeatLayout how the repeated items should be displayed, using table or using line breaks. Defaults to TRepeatLayout::Table.
	 */
	public function getRepeatLayout()
	{
		return $this->_repeatLayout;
	}

	/**
	 * @param TRepeatLayout $value how the repeated items should be displayed, using table or using line breaks.
	 */
	public function setRepeatLayout($value)
	{
		$this->_repeatLayout = TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TRepeatLayout');
	}

	/**
	 * Renders the repeated items.
	 * @param THtmlWriter $writer writer for the rendering purpose
	 * @param IRepeatInfoUser $user repeat information user
	 */
	public function renderRepeater($writer, IRepeatInfoUser $user)
	{
		if ($this->_repeatLayout === TRepeatLayout::Table) {
			$control = new TTable;
			if ($this->_caption !== '') {
				$control->setCaption($this->_caption);
				$control->setCaptionAlign($this->_captionAlign);
			}
		} elseif ($this->_repeatLayout === TRepeatLayout::Raw) {
			$this->renderRawContents($writer, $user);
			return;
		} else {
			$control = new TWebControl;
		}
		$control->setID($user->getClientID());
		$control->copyBaseAttributes($user);
		if ($user->getHasStyle()) {
			$control->getStyle()->copyFrom($user->getStyle());
		}
		$control->renderBeginTag($writer);
		$writer->writeLine();

		if ($this->_repeatDirection === TRepeatDirection::Vertical) {
			$this->renderVerticalContents($writer, $user);
		} else {
			$this->renderHorizontalContents($writer, $user);
		}

		$control->renderEndTag($writer);
	}

	/**
	 * Renders contents in raw format.
	 * @param THtmlWriter $writer writer for the rendering purpose
	 * @param IRepeatInfoUser $user repeat information user
	 */
	protected function renderRawContents($writer, $user)
	{
		if ($user->getHasHeader()) {
			$user->renderItem($writer, $this, 'Header', -1);
		}

		// render items
		$hasSeparators = $user->getHasSeparators();
		$itemCount = $user->getItemCount();
		for ($i = 0; $i < $itemCount; ++$i) {
			$user->renderItem($writer, $this, 'Item', $i);
			if ($hasSeparators && $i != $itemCount - 1) {
				$user->renderItem($writer, $this, 'Separator', $i);
			}
		}
		if ($user->getHasFooter()) {
			$user->renderItem($writer, $this, 'Footer', -1);
		}
	}

	/**
	 * Renders contents in horizontal repeat direction.
	 * @param THtmlWriter $writer writer for the rendering purpose
	 * @param IRepeatInfoUser $user repeat information user
	 */
	protected function renderHorizontalContents($writer, $user)
	{
		$tableLayout = ($this->_repeatLayout === TRepeatLayout::Table);
		$hasSeparators = $user->getHasSeparators();
		$itemCount = $user->getItemCount();
		$columns = $this->_repeatColumns === 0 ? $itemCount : $this->_repeatColumns;
		$totalColumns = $hasSeparators ? $columns + $columns : $columns;
		$needBreak = $columns < $itemCount;

		if ($user->getHasHeader()) {
			$this->renderHeader($writer, $user, $tableLayout, $totalColumns, $needBreak);
		}

		// render items
		if ($tableLayout) {
			$writer->renderBeginTag('tbody');
			$column = 0;
			for ($i = 0; $i < $itemCount; ++$i) {
				if ($column == 0) {
					$writer->renderBeginTag('tr');
				}
				if (($style = $user->generateItemStyle('Item', $i)) !== null) {
					$style->addAttributesToRender($writer);
				}
				$writer->renderBeginTag('td');
				$user->renderItem($writer, $this, 'Item', $i);
				$writer->renderEndTag();
				$writer->writeLine();
				if ($hasSeparators && $i != $itemCount - 1) {
					if (($style = $user->generateItemStyle('Separator', $i)) !== null) {
						$style->addAttributesToRender($writer);
					}
					$writer->renderBeginTag('td');
					$user->renderItem($writer, $this, 'Separator', $i);
					$writer->renderEndTag();
					$writer->writeLine();
				}
				$column++;
				if ($i == $itemCount - 1) {
					$restColumns = $columns - $column;
					if ($hasSeparators) {
						$restColumns = $restColumns ? $restColumns + $restColumns + 1 : 1;
					}
					for ($j = 0; $j < $restColumns; ++$j) {
						$writer->write("<td></td>\n");
					}
				}
				if ($column == $columns || $i == $itemCount - 1) {
					$writer->renderEndTag();
					$writer->writeLine();
					$column = 0;
				}
			}
			$writer->renderEndTag();
		} else {
			$column = 0;
			for ($i = 0; $i < $itemCount; ++$i) {
				$user->renderItem($writer, $this, 'Item', $i);
				if ($hasSeparators && $i != $itemCount - 1) {
					$user->renderItem($writer, $this, 'Separator', $i);
				}
				$column++;
				if ($column == $columns || $i == $itemCount - 1) {
					if ($needBreak) {
						$writer->writeBreak();
					}
					$column = 0;
				}
				$writer->writeLine();
			}
		}

		if ($user->getHasFooter()) {
			$this->renderFooter($writer, $user, $tableLayout, $totalColumns, $needBreak);
		}
	}

	/**
	 * Renders contents in veritcal repeat direction.
	 * @param THtmlWriter $writer writer for the rendering purpose
	 * @param IRepeatInfoUser $user repeat information user
	 */
	protected function renderVerticalContents($writer, $user)
	{
		$tableLayout = ($this->_repeatLayout === TRepeatLayout::Table);
		$hasSeparators = $user->getHasSeparators();
		$itemCount = $user->getItemCount();
		if ($this->_repeatColumns <= 1) {
			$rows = $itemCount;
			$columns = 1;
			$lastColumns = 1;
		} else {
			$columns = $this->_repeatColumns;
			$rows = (int) (($itemCount + $columns - 1) / $columns);
			if ($rows == 0 && $itemCount > 0) {
				$rows = 1;
			}
			if (($lastColumns = $itemCount % $columns) == 0) {
				$lastColumns = $columns;
			}
		}
		$totalColumns = $hasSeparators ? $columns + $columns : $columns;

		if ($user->getHasHeader()) {
			$this->renderHeader($writer, $user, $tableLayout, $totalColumns, false);
		}

		if ($tableLayout) {
			$writer->renderBeginTag('tbody');
			$renderedItems = 0;
			for ($row = 0; $row < $rows; ++$row) {
				$index = $row;
				$writer->renderBeginTag('tr');
				for ($col = 0; $col < $columns; ++$col) {
					if ($renderedItems >= $itemCount) {
						break;
					}
					if ($col > 0) {
						$index += $rows;
						if ($col - 1 >= $lastColumns) {
							$index--;
						}
					}
					if ($index >= $itemCount) {
						continue;
					}
					$renderedItems++;
					if (($style = $user->generateItemStyle('Item', $index)) !== null) {
						$style->addAttributesToRender($writer);
					}
					$writer->renderBeginTag('td');
					$user->renderItem($writer, $this, 'Item', $index);
					$writer->renderEndTag();
					$writer->writeLine();
					if (!$hasSeparators) {
						continue;
					}
					if ($renderedItems < $itemCount - 1) {
						if ($columns == 1) {
							$writer->renderEndTag();
							$writer->renderBeginTag('tr');
						}
						if (($style = $user->generateItemStyle('Separator', $index)) !== null) {
							$style->addAttributesToRender($writer);
						}
						$writer->renderBeginTag('td');
						$user->renderItem($writer, $this, 'Separator', $index);
						$writer->renderEndTag();
						$writer->writeLine();
					} elseif ($columns > 1) {
						$writer->write("<td></td>\n");
					}
				}
				if ($row == $rows - 1) {
					$restColumns = $columns - $lastColumns;
					if ($hasSeparators) {
						$restColumns += $restColumns;
					}
					for ($col = 0; $col < $restColumns; ++$col) {
						$writer->write("<td></td>\n");
					}
				}
				$writer->renderEndTag();
				$writer->writeLine();
			}
			$writer->renderEndTag();
		} else {
			$renderedItems = 0;
			for ($row = 0; $row < $rows; ++$row) {
				$index = $row;
				for ($col = 0; $col < $columns; ++$col) {
					if ($renderedItems >= $itemCount) {
						break;
					}
					if ($col > 0) {
						$index += $rows;
						if ($col - 1 >= $lastColumns) {
							$index--;
						}
					}
					if ($index >= $itemCount) {
						continue;
					}
					$renderedItems++;
					$user->renderItem($writer, $this, 'Item', $index);
					$writer->writeLine();
					if (!$hasSeparators) {
						continue;
					}
					if ($renderedItems < $itemCount - 1) {
						if ($columns == 1) {
							$writer->writeBreak();
						}
						$user->renderItem($writer, $this, 'Separator', $index);
					}
					$writer->writeLine();
				}
				if ($row < $rows - 1 || $user->getHasFooter()) {
					$writer->writeBreak();
				}
			}
		}

		if ($user->getHasFooter()) {
			$this->renderFooter($writer, $user, $tableLayout, $totalColumns, false);
		}
	}

	/**
	 * Renders header.
	 * @param THtmlWriter $writer writer for the rendering purpose
	 * @param IRepeatInfoUser $user repeat information user
	 * @param bool $tableLayout whether to render using table layout
	 * @param int $columns number of columns to be rendered
	 * @param bool $needBreak if a line break is needed at the end
	 */
	protected function renderHeader($writer, $user, $tableLayout, $columns, $needBreak)
	{
		if ($tableLayout) {
			$writer->renderBeginTag('thead');
			$writer->renderBeginTag('tr');
			if ($columns > 1) {
				$writer->addAttribute('colspan', "$columns");
			}
			$writer->addAttribute('scope', 'col');
			if (($style = $user->generateItemStyle('Header', -1)) !== null) {
				$style->addAttributesToRender($writer);
			}
			$writer->renderBeginTag('th');
			$user->renderItem($writer, $this, 'Header', -1);
			$writer->renderEndTag();
			$writer->renderEndTag();
			$writer->renderEndTag();
		} else {
			$user->renderItem($writer, $this, 'Header', -1);
			if ($needBreak) {
				$writer->writeBreak();
			}
		}
		$writer->writeLine();
	}

	/**
	 * Renders footer.
	 * @param THtmlWriter $writer writer for the rendering purpose
	 * @param IRepeatInfoUser $user repeat information user
	 * @param bool $tableLayout whether to render using table layout
	 * @param int $columns number of columns to be rendered
	 */
	protected function renderFooter($writer, $user, $tableLayout, $columns)
	{
		if ($tableLayout) {
			$writer->renderBeginTag('tfoot');
			$writer->renderBeginTag('tr');
			if ($columns > 1) {
				$writer->addAttribute('colspan', "$columns");
			}
			if (($style = $user->generateItemStyle('Footer', -1)) !== null) {
				$style->addAttributesToRender($writer);
			}
			$writer->renderBeginTag('td');
			$user->renderItem($writer, $this, 'Footer', -1);
			$writer->renderEndTag();
			$writer->renderEndTag();
			$writer->renderEndTag();
		} else {
			$user->renderItem($writer, $this, 'Footer', -1);
		}
		$writer->writeLine();
	}
}
