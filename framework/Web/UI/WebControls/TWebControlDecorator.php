<?
/**
 * TWebControlDecorator class file.
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2010 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TWebControlDecorator.php 2541 2008-10-21 15:05:13Z qiang.xue $
 * @package System.Web.UI.WebControls
 */


/**
 * TWebControlDecorator class
 * 
 * This places theme related html and templates before and after both the open and close 
 * tag of a {@link TWebControl}.
 * 
 * This is an easy way to have your look and feel depend upon the theme instead of writing 
 * specific html in your templates to achieve your website desires.  This makes updating the
 * look and feel of your website much more simple.  Here is an example of how to code your theme
 * skin:
 * <code>
 * <com:THeader2 TagName="h3">
 *	<prop:Decorator.PreTagText>
 * 			<!-- In case the them you are importing needs this for it's h3 to look right -->
 *		<div class="imported-theme-h3-container">
 *	</prop:Decorator.PreTagText>
 *	<prop:Decorator.PostTagText>
 * 			<!-- To close things properly -->
 *		</div>
 *	</prop:Decorator.PostTagText>
 * </com:THeader2>
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
 * To move controls around please see the {@link TMigrate} control.  You may use {@link TMigrate} 
 * in your Decorator templates to move controls in your MasterTemplate around using your theme 
 * elements around on your page.
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @version $Id: TWebControlDecorator.php 2541 2008-10-21 15:05:13Z qiang.xue $
 * @package System.Web.UI.WebControls
 * @since 3.2
 */

class TWebControlDecorator extends TComponent {
	
	/**
	 * @var boolean tells if there should only be decoration around the inner content
	 */
	private $_internalonly;
	
	/**
	 * @var TWebControl the control to decorate
	 */
	private $_control;
	
	/**
	 * @var boolean This tells if the Templates have been 
	 */
	private $_addedTemplateDecoration=false;
	
	
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
	 * @param TWebControl The control that is to be decorated.
	 * @param boolean whether decoration is just around the inner content
	 */
	public function __construct($control, $onlyinternal = false) {
		parent::__construct();
		
		$this->_control = $control;
		$this->_internalonly = $onlyinternal;
	}
	
	/**
	 * @return string gets the text before the open tag in the TWebControl
	 */
	public function getPreTagText() {
		return $this->_pretagtext;
	}
	
	/**
	 * @param string sets the text before the open tag in the TWebControl
	 */
	public function setPreTagText($value) {
		if(!$this->_internalonly && !$this->_control->getIsSkinApplied())
			$this->_pretagtext = TPropertyValue::ensureString($value);
	}
	
	
	/**
	 * @return string the text after the open tag in the TWebControl
	 */
	public function getPreContentsText() {
		return $this->_precontentstext;
	}
	
	/**
	 * @param string sets the text after the open tag in the TWebControl
	 */
	public function setPreContentsText($value) {
		if(!$this->_control->getIsSkinApplied())
			$this->_precontentstext = TPropertyValue::ensureString($value);
	}
	
	
	/**
	 * @return string the text before the close tag in the TWebControl
	 */
	public function getPostContentsText() {
		return $this->_postcontentstext;
	}
	
	/**
	 * @param string sets the text before the close tag in the TWebControl
	 */
	public function setPostContentsText($value) {
		if(!$this->_control->getIsSkinApplied())
			$this->_postcontentstext = TPropertyValue::ensureString($value);
	}
	
	
	/**
	 * @return string the text before the close tag in the TWebControl
	 */
	public function getPostTagText() {
		return $this->_posttagtext;
	}
	
	/**
	 * @param string sets the text after the close tag in the TWebControl
	 */
	public function setPostTagText($value) {
		if(!$this->_internalonly && !$this->_control->getIsSkinApplied())
			$this->_posttagtext = TPropertyValue::ensureString($value);
	}
	
	
	/**
	 * @return TTemplate|null the template before the open tag in the TWebControl.  Defaults to null.
	 */
	public function getPreTagTemplate() {
		return $this->_pretagtemplate;
	}
	
	/**
	 * @param TTemplate sets the template before the open tag in the TWebControl
	 */
	public function setPreTagTemplate($value) {
		if(!$this->_internalonly && !$this->_control->getIsSkinApplied())
			$this->_pretagtemplate = $value;
	}
	
	
	/**
	 * @return TTemplate|null the template after the open tag in the TWebControl.  Defaults to null.
	 */
	public function getPreContentsTemplate() {
		return $this->_precontentstemplate;
	}
	
	/**
	 * @param TTemplate sets the template after the open tag in the TWebControl
	 */
	public function setPreContentsTemplate($value) {
		if(!$this->_control->getIsSkinApplied())
			$this->_precontentstemplate = $value;
	}
	
	
	/**
	 * @return TTemplate|null the template before the close tag in the TWebControl.  Defaults to null.
	 */
	public function getPostContentsTemplate() {
		return $this->_postcontentstemplate;
	}
	
	/**
	 * @param TTemplate sets the template before the close tag in the TWebControl
	 */
	public function setPostContentsTemplate($value) {
		if(!$this->_control->getIsSkinApplied())
			$this->_postcontentstemplate = $value;
	}
	
	
	/**
	 * @return TTemplate|null the template after the close tag in the TWebControl.  Defaults to null.
	 */
	public function getPostTagTemplate() {
		return $this->_posttagtemplate;
	}
	
	/**
	 * @param TTemplate sets the template before the close tag in the TWebControl
	 */
	public function setPostTagTemplate($value) {
		if(!$this->_internalonly && !$this->_control->getIsSkinApplied())
			$this->_posttagtemplate = $value;
	}
	
	/**
	 *	this is a framework call.  The Text decoration can't 
	 * influence the object hierarchy because they are rendered into into the writer directly.
	 * This call attaches the ensureTemplateDecoration to the TPage onSaveStateComplete so 
	 * these controls don't have page states.  This is as close to not influencing the page as possible.
	 */
	public function instantiate() {
		
		$this->_addedTemplateDecoration = false;
		
		if($this->getPreTagTemplate() || $this->getPreContentsTemplate() || 
			$this->getPostContentsTemplate() || $this->getPostTagTemplate())
			$control->Page->onSaveStateComplete[] = array($this, 'ensureTemplateDecoration');
		// OnPreRenderComplete onSaveStateComplete
	}
	
	
	/**
	 *	This method places the templates around the open and close tag
	 * @return boolean returns true if the template decorations have been added
	 */
	public function ensureTemplateDecoration() {
		$control = $this->_control;
		if($this->_addedTemplateDecoration || !$control->Parent) return $this->_addedTemplateDecoration;
		
		$this->_addedTemplateDecoration = true;
		
		if($this->getPreTagTemplate()) {
			$pretag = Prado::createComponent('TCompositeControl');
			$this->getPreTagTemplate()->instantiateIn($pretag);
			$control->getParent()->getControls()->insertBefore($control, $pretag);
		}
		
		if($this->getPreContentsTemplate()) {
			$precontents = Prado::createComponent('TCompositeControl');
			$this->getPreContentsTemplate()->instantiateIn($precontents);
			$control->getControls()->insertAt(0, $precontents);
		}
		
		if($this->getPostContentsTemplate()) {
			$postcontents = Prado::createComponent('TCompositeControl');
			$this->getPostContentsTemplate()->instantiateIn($postcontents);
			$control->getControls()->add($postcontents);
		}
		
		if($this->getPostTagTemplate()) {
			$posttag = Prado::createComponent('TCompositeControl');
			$this->getPostTagTemplate()->instantiateIn($posttag);
			$control->getParent()->getControls()->insertAfter($control, $posttag);
		}
		return true;
	}
	
	
	/**
	 * This method places the pre tag text into the {@link TTextWriter}
	 * @param {@link TTextWriter} the writer to which the text is written
	 */
	public function renderPreTagText($writer) {
		$writer->write($this->getPreTagText());
	}
	
	/**
	 * This method places the pre contents text into the {@link TTextWriter}
	 * @param {@link TTextWriter} the writer to which the text is written
	 */
	public function renderPreContentsText($writer) {
		$writer->write($this->getPreContentsText());
	}
	
	/**
	 * This method places the post contents text into the {@link TTextWriter}
	 * @param {@link TTextWriter} the writer to which the text is written
	 */
	public function renderPostContentsText($writer) {
		$writer->write($this->getPostContentsText());
	}
	
	/**
	 * This method places the post tag text into the {@link TTextWriter}
	 * @param {@link TTextWriter} the writer to which the text is written
	 */
	public function renderPostTagText($writer) {
		$writer->write($this->getPostTagText());
	}
}
