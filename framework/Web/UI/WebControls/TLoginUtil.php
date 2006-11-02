<?php
class TLoginUtil
{
	const _passwordReplacementKey='<%\s*Password\s*%>';
	const _userNameReplacementKey='<%\s*UserName\s*%>';

	//	public function onSendingMailDelegate($param)
	//	{
	//
	//	}
	//	public function onSendMailErrorDelegate($param)
	//	{
	//
	//	}
	public static function createChildTable($convertingToTemplate)
	{
		if ($convertingToTemplate)
		{
			return new TTable();
		}
		else
		return new TTable();
		//		else
		//		return new TChildTable(2);
	}
	public static function applyStyleToLiteral(TLiteral $literal,$text,$setTableCellVisible)
	{

	}
	public static function copyBorderStyles(TControl $control,$style)
	{
		if (($style!==null) && strlen($providerName)!==0)
		{
			$control->BorderStyle = $style->BorderStyle;
			$control->BorderColor = $style->BorderColor;
			$control->BorderWidth = $style->BorderWidth;
			$control->BackColor = $style->BackColor;
			$control->CssClass = $style->CssClass;
		}
	}
	public static function copyStyleToInnerControl(TControl $control,$style)
	{
		if (($style!==null) && strlen($providerName)!==0)
		{
			$control->ForeColor = $style->ForeColor;
			$control->Font = $style->Font;
		}
	}
	private static function createMailMessage($email,$userName,$password,$mailDefinition,$defaultSubject,$defaultBody,$owner)
	{

	}
	public static function getProvider($providerName)
	{
		if (strlen($providerName)===0)
		{
			return TMembership::getProvider();
		}
		$provider1 = TMembership::getProviders($providerName);
		if ($provider1===null)
		{
			throw new TException('WebControl_CantFindProvider');
		}
		return $provider1;
	}
	public static function getUser(TControl $c)
	{

	}
	public static function getUserName(TControl $c)
	{

	}
	public static function sendPasswordMail($email,$userName,$password,$mailDefinition,$defaultSubject,$defaultBody,$onSendmailDelegate,$onSendMailErrorDelegate,$owner)
	{

	}
	public static function setTableCellStyle(TControl $control,$style)
	{
		//		$control1 = $control->Parent;
		//		if ($control1!==null)
		//		{
		//			$control1->ApplyStyle=$style;
		//		}
	}
	public static function setTableCellVisible(TControl $control,$visible)
	{
		$control1 = $control->Parent;
		if ($control1!==null)
		{
			$control1->Visible=$visible;
		}
	}
}
class TDisappearingTableRow extends TTableRow
{
	public function render($writer)
	{
		$flag1 = false;
		foreach ($this->getCells() as $cell1)
		{
			if ($cell1->getVisible())
			{
				$flag1 = true;
				break;
			}
		}
		if ($flag1)
		{
			parent::render($writer);
		}
	}
}
class TGenericContainer extends TWebControl
{
	private $_borderTable;
	private $_convertingToTemplate=false;
	private $_layoutTable;
	private $_owner;
	private $_usingDefaultTemplate=false;

	public function getBorderTable()
	{
		return $this->_borderTable;
	}
	public function setBorderTable($value)
	{
		$this->_borderTable = $value;
	}
	public function getConvertingToTemplate()
	{
		return $this->_convertingToTemplate;
	}
	public function getLayoutTable()
	{
		return $this->_layoutTable;
	}
	public function setLayoutTable($value)
	{
		$this->_layoutTable = $value;
	}
	public function getOwner()
	{
		return $this->_owner;
	}
	public function getUsingDefaultTemplate()
	{
		return $this->_usingDefaultTemplate;
	}
	public function __construct($owner)
	{
		$this->_owner=$owner;
	}
	//	public function findControl($id,$required,$errorResourceKey)
	//	{
	//
	//	}
	//	protected function findOptionalControl($id)
	//	{
	//
	//	}
	//	protected function findRequiredControl($id,$errorResourceKey)
	//	{
	//
	//	}
	//	public function focus()
	//	{
	//
	//	}
	//	public function render($writer)
	//	{
	//
	//	}
	//	private function renderContentsInUnitTable($writer)
	//	{
	//
	//	}
	//	protected function verifyControlNotPresent($id,$errorResourceKey)
	//	{
	//
	//	}
}
?>