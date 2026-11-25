<?php

/**
 * TTestDataTemplate class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Web\UI;

use Prado\IDataRenderer;
use Prado\Web\UI\ITemplate;
use Prado\Web\UI\WebControls\TLiteral;

/**
 * TTestDataTemplate is an item template for the data-bound controls.
 *
 * Each instantiation adds one TLiteral whose text is the label. When the literal
 * is data bound inside an {@see IDataRenderer}, its text becomes "label:data", with
 * non-scalar data JSON-encoded. {@see getInstantiations()} counts the instantiations.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestDataTemplate implements ITemplate
{
	private int $_instantiations = 0;

	/**
	 * @param string $_label the text of the literal.
	 */
	public function __construct(private string $_label)
	{
	}

	/**
	 * @return int the number of instantiations.
	 */
	public function getInstantiations(): int
	{
		return $this->_instantiations;
	}

	/**
	 * Adds the literal to the parent.
	 * @param \Prado\Web\UI\TControl $parent the control receiving the literal.
	 */
	public function instantiateIn($parent)
	{
		$this->_instantiations++;
		$literal = new TLiteral();
		$literal->setText($this->_label);
		$label = $this->_label;
		$literal->attachEventHandler('OnDataBinding', function ($sender) use ($parent, $label) {
			$data = $parent instanceof IDataRenderer ? $parent->getData() : null;
			$sender->setText($data === null ? $label : $label . ':' . (is_scalar($data) ? $data : json_encode($data)));
		});
		$parent->getControls()->add($literal);
	}

	/**
	 * @return array no included files.
	 */
	public function getIncludedFiles()
	{
		return [];
	}
}
