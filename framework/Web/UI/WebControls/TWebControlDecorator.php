<?php
/**
 * TWebControlDecorator class file.
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\UI\TCompositeControl;

/**
 * TWebControlDecorator class
 *
 * A TWebControlDecorator can be applied to a {@link TWebControl} to customize its rendering.
 * TWebControlDecorator can add custom html code before and after both the open and close
 * tag of a {@link TWebControl}.
 * The html code can be an user-defined text or an external template file that will be
 * instantiated and rendered in place.
 *
 * This is an easy way to have your look and feel depend upon the theme instead of writing
 * specific html in your templates to achieve your website desires.
 * Here is an example of how to code your theme skin:
 * <code>
 * <com:THeader3>
 *	<prop:Decorator.PreTagText>
 * 			<!-- Surround the control with a div and apply a css class to it -->
 *		<div class="imported-theme-h3-container">
 *	</prop:Decorator.PreTagText>
 *	<prop:Decorator.PostTagText>
 * 			<!-- Properly close the tag -->
 *		</div>
 *	</prop:Decorator.PostTagText>
 * </com:THeader3>
 * </code>
 *
 * The order of the inclusion of the decoration into the page goes like this:
 * * PreTagTemplate
 * * PreTagText
 * * TWebControl Open Tag Rendered
 * * PreContentsText
 * * PreContentsTemplate
 * * TWebControl Children Rendered
 * * PostContentsTemplate
 * * PostContentsText
 * * TWebControl CloseTag Rendered
 * * PostTagText
 * * PostTagTemplate
 *
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.2
 */

class TWebControlDecorator extends \Prado\TComponent
{

	/**
	 * @var bool tells if there should only be decoration around the inner content
	 */
	private $_internalonly;

	/**
	 * @var bool tells if the decoration uses state in its templates.  If there are no templates
	 * in the instance of the decoration this variable is unused.
	 */
	private $_usestate = false;

	/**
	 * @var TWebControl the control to decorate
	 */
	private $_control;

	/**
	 * @var \Prado\Web\UI\TControl to tell the decorator where to place the outer controls
	 */
	private $_outercontrol;

	/**
	 * @var bool This tells if the Templates have been
	 */
	private $_addedTemplateDecoration = false;


	/**
	 * @var string the text that goes before the open tag
	 */
	private $_pretagtext = '';
	/**
	 * @var string the text that goes after the open tag
	 */
	private $_precontentstext = '';
	/**
	 * @var string the text that goes before the close tag
	 */
	private $_postcontentstext = '';
	/**
	 * @var string the text that goes after the close tag
	 */
	private $_posttagtext = '';



	/**
	 * @var TTemplate the template that goes before the open tag
	 */
	private $_pretagtemplate;
	/**
	 * @var TTemplate the template that goes after the open tag
	 */
	private $_precontentstemplate;
	/**
	 * @var TTemplate the template that goes before the close tag
	 */
	private $_postcontentstemplate;
	/**
	 * @var TTemplate the template that goes after the close tag
	 */
	private $_posttagtemplate;

	/**
	 * Constructor.
	 * Initializes the control .
	 * @param TWebControl $control The control that is to be decorated.
	 * @param bool $onlyinternal whether decoration is just around the inner content
	 */
	public function __construct($control, $onlyinternal = false)
	{
		$this->_control = $control;
		$this->_internalonly = $onlyinternal;
	}

	/**
	 * @return bool if the templates in this decoration need state.  This defaults to false
	 */
	public function getUseState()
	{
		return $this->_usestate;
	}

	/**
	 * @param bool $value $value true to tell the decoration that the templates need state and should be
	 * placed in a control step before the state is saved.
	 */
	public function setUseState($value)
	{
		$this->_usestate = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string gets the text before the open tag in the TWebControl
	 */
	public function getPreTagText()
	{
		return $this->_pretagtext;
	}

	/**
	 * @param string $value sets the text before the open tag in the TWebControl
	 */
	public function setPreTagText($value)
	{
		if (!$this->_internalonly && !$this->_control->getIsSkinApplied()) {
			$this->_pretagtext = TPropertyValue::ensureString($value);
		}
	}


	/**
	 * @return string the text after the open tag in the TWebControl
	 */
	public function getPreContentsText()
	{
		return $this->_precontentstext;
	}

	/**
	 * @param string $value sets the text after the open tag in the TWebControl
	 */
	public function setPreContentsText($value)
	{
		if (!$this->_control->getIsSkinApplied()) {
			$this->_precontentstext = TPropertyValue::ensureString($value);
		}
	}


	/**
	 * @return string the text before the close tag in the TWebControl
	 */
	public function getPostContentsText()
	{
		return $this->_postcontentstext;
	}

	/**
	 * @param string $value sets the text before the close tag in the TWebControl
	 */
	public function setPostContentsText($value)
	{
		if (!$this->_control->getIsSkinApplied()) {
			$this->_postcontentstext = TPropertyValue::ensureString($value);
		}
	}


	/**
	 * @return string the text before the close tag in the TWebControl
	 */
	public function getPostTagText()
	{
		return $this->_posttagtext;
	}

	/**
	 * @param string $value sets the text after the close tag in the TWebControl
	 */
	public function setPostTagText($value)
	{
		if (!$this->_internalonly && !$this->_control->getIsSkinApplied()) {
			$this->_posttagtext = TPropertyValue::ensureString($value);
		}
	}


	/**
	 * @return null|TTemplate the template before the open tag in the TWebControl.  Defaults to null.
	 */
	public function getPreTagTemplate()
	{
		return $this->_pretagtemplate;
	}

	/**
	 * @param TTemplate $value sets the template before the open tag in the TWebControl
	 */
	public function setPreTagTemplate($value)
	{
		if (!$this->_internalonly && !$this->_control->getIsSkinApplied()) {
			$this->_pretagtemplate = $value;
		}
	}


	/**
	 * @return null|TTemplate the template after the open tag in the TWebControl.  Defaults to null.
	 */
	public function getPreContentsTemplate()
	{
		return $this->_precontentstemplate;
	}

	/**
	 * @param TTemplate $value sets the template after the open tag in the TWebControl
	 */
	public function setPreContentsTemplate($value)
	{
		if (!$this->_control->getIsSkinApplied()) {
			$this->_precontentstemplate = $value;
		}
	}


	/**
	 * @return null|TTemplate the template before the close tag in the TWebControl.  Defaults to null.
	 */
	public function getPostContentsTemplate()
	{
		return $this->_postcontentstemplate;
	}

	/**
	 * @param TTemplate $value sets the template before the close tag in the TWebControl
	 */
	public function setPostContentsTemplate($value)
	{
		if (!$this->_control->getIsSkinApplied()) {
			$this->_postcontentstemplate = $value;
		}
	}


	/**
	 * @return null|TTemplate the template after the close tag in the TWebControl.  Defaults to null.
	 */
	public function getPostTagTemplate()
	{
		return $this->_posttagtemplate;
	}

	/**
	 * @param TTemplate $value sets the template before the close tag in the TWebControl
	 */
	public function setPostTagTemplate($value)
	{
		if (!$this->_internalonly && !$this->_control->getIsSkinApplied()) {
			$this->_posttagtemplate = $value;
		}
	}

	/**
	 * This is a framework call.  The Text decoration can't
	 * influence the object hierarchy because they are rendered into into the writer directly.
	 * This call attaches the ensureTemplateDecoration to the TPage onSaveStateComplete so
	 * these controls don't have page states.  This is as close to not influencing the page as possible.
	 * @param null|mixed $outercontrol
	 */
	public function instantiate($outercontrol = null)
	{
		if ($this->getPreTagTemplate() || $this->getPreContentsTemplate() ||
			$this->getPostContentsTemplate() || $this->getPostTagTemplate()) {
			$this->_outercontrol = $outercontrol;
			if ($this->getUseState()) {
				$this->ensureTemplateDecoration();
			} else {
				$this->_control->getPage()->onSaveStateComplete[] = [$this, 'ensureTemplateDecoration'];
			}
		}
	}


	/**
	 *	This method places the templates around the open and close tag.  This takes a parameter which is
	 * to specify the control to get the outer template decoration.  If no outer control is specified
	 * @param \Prado\TComponent $sender this indicates the component or control to get the outer tag elements, just in case it's
	 * different than attached TWebControl.  If none is provided, the outer templates default to the attached
	 * control
	 * @param null|mixed $param
	 * @return bool returns true if the template decorations have been added
	 */
	public function ensureTemplateDecoration($sender = null, $param = null)
	{
		$control = $this->_control;
		$outercontrol = $this->_outercontrol;
		if ($outercontrol === null) {
			$outercontrol = $control;
		}

		if ($this->_addedTemplateDecoration) {
			return $this->_addedTemplateDecoration;
		}

		$this->_addedTemplateDecoration = true;

		if ($this->getPreContentsTemplate()) {
			$precontents = new TCompositeControl;
			$this->getPreContentsTemplate()->instantiateIn($precontents);
			$control->getControls()->insertAt(0, $precontents);
		}

		if ($this->getPostContentsTemplate()) {
			$postcontents = new TCompositeControl;
			$this->getPostContentsTemplate()->instantiateIn($postcontents);
			$control->getControls()->add($postcontents);
		}

		if (!$outercontrol->getParent()) {
			return $this->_addedTemplateDecoration;
		}


		if ($this->getPreTagTemplate()) {
			$pretag = new TCompositeControl;
			$this->getPreTagTemplate()->instantiateIn($pretag);
			$outercontrol->getParent()->getControls()->insertBefore($outercontrol, $pretag);
		}

		if ($this->getPostTagTemplate()) {
			$posttag = new TCompositeControl;
			$this->getPostTagTemplate()->instantiateIn($posttag);
			$outercontrol->getParent()->getControls()->insertAfter($outercontrol, $posttag);
		}
		return true;
	}


	/**
	 * This method places the pre tag text into the {@link TTextWriter}
	 * @param \Prado\IO\TTextWriter $writer the writer to which the text is written
	 */
	public function renderPreTagText($writer)
	{
		$writer->write($this->getPreTagText());
	}

	/**
	 * This method places the pre contents text into the {@link TTextWriter}
	 * @param \Prado\IO\TTextWriter $writer the writer to which the text is written
	 */
	public function renderPreContentsText($writer)
	{
		$writer->write($this->getPreContentsText());
	}

	/**
	 * This method places the post contents text into the {@link TTextWriter}
	 * @param \Prado\IO\TTextWriter $writer the writer to which the text is written
	 */
	public function renderPostContentsText($writer)
	{
		$writer->write($this->getPostContentsText());
	}

	/**
	 * This method places the post tag text into the {@link TTextWriter}
	 * @param \Prado\IO\TTextWriter $writer the writer to which the text is written
	 */
	public function renderPostTagText($writer)
	{
		$writer->write($this->getPostTagText());
	}
}
