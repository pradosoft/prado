<?php

/**
 * LabeledTextBox
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class LabeledTextBox extends TTemplateControl
{
	/**
	 * @return TTextBox textbox instance
	 */
	public function getTextBox()
	{
		return $this->textbox;
	}

	/**
	 * @return TLabel textbox label
	 */
	public function getLabel()
	{
		return $this->label;
	}
}

?>