<?php

/**
 * TActiveHiddenField class file.
 *
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Prado;
use Prado\Web\UI\WebControls\THiddenField;

/**
 * TActiveHiddenField class
 *
 * TActiveHiddenField displays a hidden input field on a Web page.
 * The value of the input field can be accessed via {@see getValue Value} property.
 *
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @since 3.1
 * @method TActiveControlAdapter<\Prado\Web\UI\ActiveControls\TBaseActiveControl> getAdapter()
 */
class TActiveHiddenField extends THiddenField implements IActiveControl
{
	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveControlAdapter. If you override this class, be sure to set the
	 * adapter appropriately by, for example, by calling this constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}

	/**
	 * @return TBaseActiveControl standard active control options.
	 */
	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	/**
	 * Client-side Value property can only be updated after the OnLoad stage.
	 * @param string $value text content for the hidden field
	 */
	public function setValue($value)
	{
		if (parent::getValue() === $value) {
			return;
		}

		parent::setValue($value);
		if ($this->getActiveControl()->canUpdateClientSide() && $this->getHasLoadedPostData()) {
			$this->getPage()->getCallbackClient()->setValue($this, $value);
		}
	}
}
