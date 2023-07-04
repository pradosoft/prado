<?php
/**
 * THyperLinkColumn class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * THyperLinkColumn class
 *
 * THyperLinkColumn contains a hyperlink for each item in the column.
 * You can set the text and the url of the hyperlink by {@see setText Text}
 * and {@see setNavigateUrl NavigateUrl} properties, respectively.
 * You can also bind the text and url to specific data field in datasource
 * by setting {@see setDataTextField DataTextField} and
 * {@see setDataNavigateUrlField DataNavigateUrlField}.
 * Both can be formatted before rendering according to the
 * {@see setDataTextFormatString DataTextFormatString} and
 * and {@see setDataNavigateUrlFormatString DataNavigateUrlFormatString}
 * properties, respectively. If both {@see setText Text} and {@see setDataTextField DataTextField}
 * are present, the latter takes precedence.
 * The same rule applies to {@see setNavigateUrl NavigateUrl} and
 * {@see setDataNavigateUrlField DataNavigateUrlField} properties.
 *
 * The hyperlinks in the column can be accessed by one of the following two methods:
 * ```php
 * $datagridItem->HyperLinkColumnID->HyperLink
 * $datagridItem->HyperLinkColumnID->Controls[0]
 * ```
 * The second method is possible because the hyperlink control created within the
 * datagrid cell is the first child.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class THyperLinkColumn extends TDataGridColumn
{
	/**
	 * @return string the text caption of the hyperlink
	 */
	public function getText()
	{
		return $this->getViewState('Text', '');
	}

	/**
	 * Sets the text caption of the hyperlink.
	 * @param string $value the text caption to be set
	 */
	public function setText($value)
	{
		$this->setViewState('Text', $value, '');
	}

	/**
	 * @return string the field name from the data source to bind to the hyperlink caption
	 */
	public function getDataTextField()
	{
		return $this->getViewState('DataTextField', '');
	}

	/**
	 * @param string $value the field name from the data source to bind to the hyperlink caption
	 */
	public function setDataTextField($value)
	{
		$this->setViewState('DataTextField', $value, '');
	}

	/**
	 * @return string the formatting string used to control how the hyperlink caption will be displayed.
	 */
	public function getDataTextFormatString()
	{
		return $this->getViewState('DataTextFormatString', '');
	}

	/**
	 * @param string $value the formatting string used to control how the hyperlink caption will be displayed.
	 */
	public function setDataTextFormatString($value)
	{
		$this->setViewState('DataTextFormatString', $value, '');
	}

	/**
	 * @return string height of the image in the THyperLink
	 */
	public function getImageHeight()
	{
		return $this->getViewState('ImageHeight', '');
	}

	/**
	 * @param string $value height of the image in the THyperLink
	 */
	public function setImageHeight($value)
	{
		$this->setViewState('ImageHeight', $value, '');
	}

	/**
	 * @return string url of the image in the THyperLink
	 */
	public function getImageUrl()
	{
		return $this->getViewState('ImageUrl', '');
	}

	/**
	 * @param string $value url of the image in the THyperLink
	 */
	public function setImageUrl($value)
	{
		$this->setViewState('ImageUrl', $value, '');
	}

	/**
	 * @return string width of the image in the THyperLink
	 */
	public function getImageWidth()
	{
		return $this->getViewState('ImageWidth', '');
	}

	/**
	 * @param string $value width of the image in the THyperLink
	 */
	public function setImageWidth($value)
	{
		$this->setViewState('ImageWidth', $value, '');
	}

	/**
	 * @return string the URL to link to when the hyperlink is clicked.
	 */
	public function getNavigateUrl()
	{
		return $this->getViewState('NavigateUrl', '');
	}

	/**
	 * Sets the URL to link to when the hyperlink is clicked.
	 * @param string $value the URL
	 */
	public function setNavigateUrl($value)
	{
		$this->setViewState('NavigateUrl', $value, '');
	}

	/**
	 * @return string the field name from the data source to bind to the navigate url of hyperlink
	 */
	public function getDataNavigateUrlField()
	{
		return $this->getViewState('DataNavigateUrlField', '');
	}

	/**
	 * @param string $value the field name from the data source to bind to the navigate url of hyperlink
	 */
	public function setDataNavigateUrlField($value)
	{
		$this->setViewState('DataNavigateUrlField', $value, '');
	}

	/**
	 * @return string the formatting string used to control how the navigate url of hyperlink will be displayed.
	 */
	public function getDataNavigateUrlFormatString()
	{
		return $this->getViewState('DataNavigateUrlFormatString', '');
	}

	/**
	 * @param string $value the formatting string used to control how the navigate url of hyperlink will be displayed.
	 */
	public function setDataNavigateUrlFormatString($value)
	{
		$this->setViewState('DataNavigateUrlFormatString', $value, '');
	}

	/**
	 * @return string the target window or frame to display the Web page content linked to when the hyperlink is clicked.
	 */
	public function getTarget()
	{
		return $this->getViewState('Target', '');
	}

	/**
	 * Sets the target window or frame to display the Web page content linked to when the hyperlink is clicked.
	 * @param string $value the target window, valid values include '_blank', '_parent', '_self', '_top' and empty string.
	 */
	public function setTarget($value)
	{
		$this->setViewState('Target', $value, '');
	}

	/**
	 * Initializes the specified cell to its initial values.
	 * This method overrides the parent implementation.
	 * It creates a hyperlink within the cell.
	 * @param TTableCell $cell the cell to be initialized.
	 * @param int $columnIndex the index to the Columns property that the cell resides in.
	 * @param string $itemType the type of cell (Header,Footer,Item,AlternatingItem,EditItem,SelectedItem)
	 */
	public function initializeCell($cell, $columnIndex, $itemType)
	{
		if ($itemType === TListItemType::Item || $itemType === TListItemType::AlternatingItem || $itemType === TListItemType::SelectedItem || $itemType === TListItemType::EditItem) {
			$link = new THyperLink();
			if (($url = $this->getImageUrl()) !== '') {
				$link->setImageUrl($url);
				if (($width = $this->getImageWidth()) !== '') {
					$link->setImageWidth($width);
				}
				if (($height = $this->getImageHeight()) !== '') {
					$link->setImageHeight($height);
				}
			}
			$link->setText($this->getText());
			$link->setNavigateUrl($this->getNavigateUrl());
			$link->setTarget($this->getTarget());
			if ($this->getDataTextField() !== '' || $this->getDataNavigateUrlField() !== '') {
				$link->attachEventHandler('OnDataBinding', [$this, 'dataBindColumn']);
			}
			$cell->getControls()->add($link);
			$cell->registerObject('HyperLink', $link);
		} else {
			parent::initializeCell($cell, $columnIndex, $itemType);
		}
	}

	/**
	 * Databinds a cell in the column.
	 * This method is invoked when datagrid performs databinding.
	 * It populates the content of the cell with the relevant data from data source.
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function dataBindColumn($sender, $param)
	{
		$item = $sender->getNamingContainer();
		$data = $item->getData();
		if (($field = $this->getDataTextField()) !== '') {
			$value = $this->getDataFieldValue($data, $field);
			$text = $this->formatDataValue($this->getDataTextFormatString(), $value);
			$sender->setText($text);
		}
		if (($field = $this->getDataNavigateUrlField()) !== '') {
			$value = $this->getDataFieldValue($data, $field);
			$url = $this->formatDataValue($this->getDataNavigateUrlFormatString(), $value);
			$sender->setNavigateUrl($url);
		}
	}
}
