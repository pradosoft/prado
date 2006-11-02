<?php
/**
 * TLogin class.
 * Provides user interface (UI) elements for logging in to a Web site..
 *
 * The TLogin control is a composite control that provides all the common UI elements 
 * needed to authenticate a user on a Web site. The following three elements are 
 * required for all login scenarios:
 *       A unique user name to identify the user.
 *       A password to verify the identity of the user.
 *       A login button to send the login information to the server.
 * The TLogin control also provides the following optional UI elements that support additional functions:
 *       A link for a password reminder.
 *       A Remember Me checkbox for retaining the login information between sessions.
 *       A Help link for users who are having trouble logging in.
 *       A Register New User link that redirects users to a registration page.
 *       Instruction text that appears on the login form.
 *       Custom error text that appears when the user clicks the login button without filling in the user name or password fields.
 *       Custom error text that appears if the login fails.
 *       A custom action that occurs when login succeeds.
 *       A way to hide the login control if the user is already logged in to the site.
 * 
 * The TLogin control uses a membership provider to obtain user credentials. 
 * Unless you specify otherwise, the TLogin control uses the default membership 
 * provider defined in the Web.config file. To specify a different provider, 
 * set the MembershipProvider property to one of the membership provider 
 * names defined in your application's Web.config file. For more information, 
 * see Membership Providers.
 * 
 * If you want to use a custom authentication service, you can use the OnAuthenticate 
 * method to call the service.
 * 
 * Styles and Templates
 * The appearance of the Login control is fully customizable through templates and 
 * style settings. All UI text messages are also customizable through properties 
 * of the TLogin class. The default interface text is automatically localized based 
 * on the locale setting on the server.
 * 
 * If the TLogin control is customized with templates, then the AccessKey property 
 * and the TabIndex property are ignored. In this case, set the AccessKey property 
 * and the TabIndex property of each template child control directly.
 * 
 * TLogin control properties represented by text boxes, such as UserName and Password, 
 * are accessible during all phases of the page life cycle. The control will pick up 
 * any changes made by the end user by means of the TextChanged event triggered by 
 * the textboxes. 
 * 
 * The following table lists the Login control style properties and explains which UI 
 * element each style property affects. For a list of which properties each style applies
 *  to, see the documentation for the individual style properties.
 * 
 * Style property			UI element affected
 * BorderPadding			The space between the control contents and the control's border.
 * CheckBoxStyle			Remember Me checkbox.
 * FailureTextStyle			Login failure text.
 * InstructionTextStyle		Instructional text on the page that tells users how to use the 
 * 							control.
 * LabelStyle				Labels for all input fields, such as text boxes.
 * TextBoxStyle				Text entry input fields.
 * TitleTextStyle			Title text.
 * ValidatorTextStyle		Text displayed to the user when a login attempt is unsuccessful 
 * 							due to validation errors
 * HyperLinkStyle			Links to other pages.
 * LoginButtonStyle			Login button.
 * 
 * Validation Groupings
 * The UserName and Password properties have RequiredFieldValidator controls associated 
 * with them to prevent users from submitting the page without providing required information.
 * 
 * The TLogin control uses a validation group so that other fields on the same page as the TLogin 
 * control can be validated separately. By default, the ID property of the Login control is 
 * used as the name of the validation group. For example, a TLogin control with the ID "Login1"
 * will use a validation group name of "Login1". If you want to set the validation group that 
 * the TLogin control is part of, you must template the control and change the validation group name.
 * 
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TLogin.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.UI.WebControls
 * @since 3.1
 */
Prado::using('System.Web.UI.WebControls.TDataGridColumn');

class TLogin extends TCompositeControl
{
	private $_borderPadding=1;
	private $_checkBoxStyle;
	private $_convertingToTemplate=false;
	private $_failureTextStyle;
	private $_hyperLinkStyle;
	private $_instructionTextStyle;
	private $_labelStyle;
	private $_layoutTemplate;
	private $_loginButtonStyle;
	private $_password;
	private $_passwordInternal;
	private $_tagKey;
	private $_templateContainer;
	private $_textBoxStyle;
	private $_titleTextStyle;
	private $_userNameInternal;
	private $_validatorTextStyle;

	public function getBorderPadding()
	{
		return $this->getViewState('BorderPadding',1);
	}
	public function setBorderPadding($value)
	{
		$this->setViewState('BorderPadding',TPropertyValue::ensureInteger($value),1);
	}
	public function getCheckBoxStyle()
	{
		return $this->_checkBoxStyle;
	}
	public function getConvertingToTemplate()
	{
		return $this->_convertingToTemplate;
	}
	public function getCreateUserIconUrl()
	{
		return $this->getViewState('CreateUserIconUrl','');
	}
	public function setCreateUserIconUrl($value)
	{
		$this->setViewState('CreateUserIconUrl',TPropertyValue::ensureString($value),'');
	}
	public function getCreateUserText()
	{
		return $this->getViewState('CreateUserText','');
	}
	public function setCreateUserText($value)
	{
		$this->setViewState('CreateUserText',TPropertyValue::ensureString($value),'');
	}
	public function getCreateUserUrl()
	{
		return $this->getViewState('CreateUserUrl','');
	}
	public function setCreateUserUrl($value)
	{
		$this->setViewState('CreateUserUrl',TPropertyValue::ensureString($value),'');
	}
	public function getDestinationPageUrl()
	{
		return $this->getViewState('DestinationPageUrl','');
	}
	public function setDestinationPageUrl($value)
	{
		$this->setViewState('DestinationPageUrl',TPropertyValue::ensureString($value),'');
	}
	public function getDisplayRememberMe()
	{
		return $this->getViewState('DisplayRememberMe',true);
	}
	public function setDisplayRememberMe($value)
	{
		$this->setViewState('DisplayRememberMe',TPropertyValue::ensureBoolean($value),true);
	}
	public function getFailureAction()
	{
		return $this->getViewState('FailureAction','');
	}
	public function setFailureAction($value)
	{
		$this->setViewState('FailureAction',TPropertyValue::ensureString($value),'');
	}
	public function getFailureText()
	{
		return $this->getViewState('FailureText','');
	}
	public function setFailureText($value)
	{
		$this->setViewState('FailureText',TPropertyValue::ensureString($value),'');
	}
	public function getFailureTextStyle()
	{
		return $this->_failureTextStyle;
	}
	public function getHelpPageIconUrl()
	{
		return $this->getViewState('HelpPageIconUrl','');
	}
	public function setHelpPageIconUrl($value)
	{
		$this->setViewState('HelpPageIconUrl',TPropertyValue::ensureString($value),'');
	}
	public function getHelpPageText()
	{
		return $this->getViewState('HelpPageText','');
	}
	public function setHelpPageText($value)
	{
		$this->setViewState('HelpPageText',TPropertyValue::ensureString($value),'');
	}
	public function getHelpPageUrl()
	{
		return $this->getViewState('HelpPageUrl','');
	}
	public function setHelpPageUrl($value)
	{
		$this->setViewState('HelpPageUrl',TPropertyValue::ensureString($value),'');
	}
	public function getHyperLinkStyle()
	{
		return $this->_hyperLinkStyle;
	}
	public function getInstructionText()
	{
		return $this->getViewState('InstructionText','');
	}
	public function setInstructionText($value)
	{
		$this->setViewState('InstructionText',TPropertyValue::ensureString($value),'');
	}
	public function getInstructionTextStyle()
	{
		return $this->_instructionTextStyle;
	}
	public function getLabelStyle()
	{
		return $this->_labelStyle;
	}
	public function getLayoutTemplate()
	{
		return $this->_layoutTemplate;
	}
	public function setLayoutTemplate($value)
	{
		$this->_layoutTemplate = TPropertyValue::ensureString($value);
		//		parent::ChildControlsCreated=false;
	}
	public function getLoginButtonImageUrl()
	{
		return $this->getViewState('LoginButtonImageUrl','');
	}
	public function setLoginButtonImageUrl($value)
	{
		$this->setViewState('LoginButtonImageUrl',TPropertyValue::ensureString($value),'');
	}
	public function getLoginButtonStyle()
	{
		return $this->_loginButtonStyle;
	}
	public function getLoginButtonText()
	{
		return $this->getViewState('LoginButtonText','Login');
	}
	public function setLoginButtonText($value)
	{
		$this->setViewState('LoginButtonText',TPropertyValue::ensureString($value),'Login');
	}
	public function getLoginButtonType()
	{
		return $this->getViewState('LoginButtonType',TButtonColumnType::PushButton);
	}
	public function setLoginButtonType($value)
	{
		$this->setViewState('LoginButtonType',TPropertyValue::ensureEnum($value,'TButtonColumnType'),TButtonColumnType::PushButton);
	}
	public function getMembershipProvider()
	{
		return $this->getViewState('MembershipProvider','');
	}
	public function setMembershipProvider($value)
	{
		$this->setViewState('MembershipProvider',TPropertyValue::ensureString($value),'');
	}
	public function getOrientation()
	{
		return $this->getViewState('Orientation',TOrientation::Horizontal);
	}
	public function setOrientation($value)
	{
		$this->setViewState('Orientation',TPropertyValue::ensureEnum($value,'TOrientation'),TOrientation::Horizontal);
	}
	public function getPassword()
	{
		return $this->_password;
	}
	public function getPasswordInternal()
	{
		return $this->_passwordInternal;
	}
	public function getPasswordLabelText()
	{
		return $this->getViewState('PasswordLabelText','Password:');
	}
	public function setPasswordLabelText($value)
	{
		$this->setViewState('PasswordLabelText',TPropertyValue::ensureString($value),'Password:');
	}
	public function getPasswordRecoveryIconUrl()
	{
		return $this->getViewState('PasswordRecoveryIconUrl','');
	}
	public function setPasswordRecoveryIconUrl($value)
	{
		$this->setViewState('PasswordRecoveryIconUrl',TPropertyValue::ensureString($value),'');
	}
	public function getPasswordRecoveryText()
	{
		return $this->getViewState('PasswordRecoveryText','');
	}
	public function setPasswordRecoveryText($value)
	{
		$this->setViewState('PasswordRecoveryText',TPropertyValue::ensureString($value),'');
	}
	public function getPasswordRecoveryUrl()
	{
		return $this->getViewState('PasswordRecoveryUrl','');
	}
	public function setPasswordRecoveryUrl($value)
	{
		$this->setViewState('PasswordRecoveryUrl',TPropertyValue::ensureString($value),'');
	}
	public function getPasswordRequiredErrorMessage()
	{
		return $this->getViewState('PasswordRequiredErrorMessage','A Password Is Required');
	}
	public function setPasswordRequiredErrorMessage($value)
	{
		$this->setViewState('PasswordRequiredErrorMessage',TPropertyValue::ensureString($value),'A Password Is Required');
	}
	public function getRememberMeSet()
	{
		return $this->getViewState('RememberMeSet',false);
	}
	public function setRememberMeSet($value)
	{
		$this->setViewState('RememberMeSet',TPropertyValue::ensureBoolean($value),false);
	}
	public function getRememberMeText()
	{
		return $this->getViewState('RememberMeText','Remember Me Next Time:');
	}
	public function setRememberMeText($value)
	{
		$this->setViewState('RememberMeText',TPropertyValue::ensureString($value),'Remember Me Next Time:');
	}
	public function getTagKey()
	{
		//return HtmlTextWriterTag.Table;
	}
	public function getTemplateContainer()
	{
		$this->ensureChildControls();
		return $this->_templateContainer;
	}
	public function getTextBoxStyle()
	{
		return $this->_textBoxStyle;
	}
	public function getTextLayout()
	{
		return $this->getViewState('TextLayout',TLoginTextLayout::TextOnLeft);
	}
	public function setTextLayout($value)
	{
		$this->setViewState('TextLayout',TPropertyValue::ensureEnum($value,'TLoginTextLayout'),TLoginTextLayout::TextOnLeft);
		//		parent::ChildControlsCreated=false;
	}
	public function getTitleText()
	{
		return $this->getViewState('TitleText','Log In');
	}
	public function setTitleText($value)
	{
		$this->setViewState('TitleText',TPropertyValue::ensureString($value),'Log In');
	}
	public function getTitleTextStyle()
	{
		return $this->_titleTextStyle;
	}
	public function getUserName()
	{
		return $this->getViewState('UserName','');
	}
	public function setUserName($value)
	{
		$this->setViewState('UserName',TPropertyValue::ensureString($value),'');
	}
	public function getUserNameInternal()
	{
		$this->_userNameInternal;
	}
	public function getUserNameLabelText()
	{
		return $this->getViewState('UserNameLabelText','User Name:');
	}
	public function setUserNameLabelText($value)
	{
		$this->setViewState('UserNameLabelText',TPropertyValue::ensureString($value),'User Name:');
	}
	public function getUserNameRequiredErrorMessage()
	{
		return $this->getViewState('UserNameRequiredErrorMessage','A User Name Is Required');
	}
	public function setUserNameRequiredErrorMessage($value)
	{
		$this->setViewState('UserNameRequiredErrorMessage',TPropertyValue::ensureString($value),'A User Name Is Required');
	}
	public function getValidatorTextStyle()
	{
		return $this->_validatorTextStyle;
	}
	public function getVisibleWhenLoggedIn()
	{
		return $this->getViewState('VisibleWhenLoggedIn',true);
	}
	public function setVisibleWhenLoggedIn($value)
	{
		$this->setViewState('VisibleWhenLoggedIn',TPropertyValue::ensureBoolean($value),true);
	}
	private function attemptLogin()
	{
		echo TVarDumper::dump(__METHOD__,10,true);
		if (($this->getPage() === null) || $this->getPage()->getIsValid())
		{
			//			$args1 = new LoginCancelEventArgs();
			$this->onLoggingIn($args1);
			if (!$args1.Cancel)
			{
				//				$args2 = new AuthenticateEventArgs();
				$this->onAuthenticate($args2);
				if ($args2.Authenticated)
				{
					TFormsAuthentication::SetAuthCookie($this->getUserNameInternal(),$this->getRememberMeSet());
					//					$this->onLoggedIn(EventArgs.Empty);
					$this->getPage()->getResponse()->redirect($this->getRedirectUrl(),false);
				}
				else
				{
					//					$this->onLoginError(EventArgs.Empty);
					if ($this->getFailureAction() === TLoginFailureAction::RedirectToLoginPage)
					{
						TFormsAuthentication::RedirectToLoginPage("loginfailure=1");
					}
					$control1 = $this->getTemplateContainer()->getFailureTextLabel();
					if ($control1 !== null)
					{
						$control1->setText($this->getFailureText());
					}
				}
			}
		}
	}
	private function authenticateUsingMembershipProvider($param)
	{
		echo TVarDumper::dump(__METHOD__,10,true);
		TLoginUtil::getProvider($this->getMembershipProvider())->validateUser($this->getUserNameInternal(),$this->getPasswordInternal());
		//		e.Authenticated = LoginUtil.GetProvider(this.MembershipProvider).ValidateUser(this.UserNameInternal,this.PasswordInternal);
	}
	private function getRedirectUrl()
	{
		if ($this->onLoginPage())
		{
			$text1 = TFormsAuthentication::GetReturnUrl(false);
			if ($text1!==null || strlen($text1) === 0)
			{
				return $text1;
			}
			$text2 = $this->getDestinationPageUrl();
			if ($text2!==null || strlen($text2) === 0)
			{
				//				return base.ResolveClientUrl($text2);
			}
			return TFormsAuthentication::getDefaultUrl();
		}
		$text3 = $this->getDestinationPageUrl();
		if ($text3!==null || strlen($text3) === 0)
		{
			//			return base.ResolveClientUrl($text3);
		}
		if (($this->getPage()->getForm() !== null))
		{
			return $this->getPage()->getRequest()->getPathInfo();
		}
		return $this->getPage()->getRequest()->getPathInfo();
	}
	public function onAuthenticate($param)
	{
		echo TVarDumper::dump(__METHOD__,10,true);
		//		AuthenticateEventHandler handler1 = (AuthenticateEventHandler) base.Events[Login.EventAuthenticate];
		$handler1;
		if ($handler1!==null)
		{
			$handler1($this,$param);
		}
		else
		{
			$this->authenticateUsingMembershipProvider($param);
		}
	}
	public function onBubbleEvent($sender,$param)
	{
		echo TVarDumper::dump(__METHOD__,10,true);
		//		bool flag1 = false;
		//		if (e is CommandEventArgs)
		//		{
		//			CommandEventArgs args1 = (CommandEventArts) e;
		//			if (string.Equals(args1.CommandName,Login.LoginButtonCommandName,StringComparison.OrdinalIgnoreCase))
		//			{
		//				this.AttemptLogin();
		//				flag1=true;
		//			}
		//		}
		//		return flag1;
	}
	public function onLoggedIn($param)
	{
		echo TVarDumper::dump(__METHOD__,10,true);
		//		EventHandler handler1 = (EventHandler) base.Events[Login.EventLoggedIn];
		//		if ($handler1!==null)
		//		{
		//			$handler1($this,$param);
		//		}
	}
	public function onLoggingIn($param)
	{
		echo TVarDumper::dump(__METHOD__,10,true);
		//		LoginCancelEventHandler handler1 = (LoginCancelEventHandler) base.Events[Login.EventLoggingIn];
		//		if ($handler1!==null)
		//		{
		//			$handler1($this,$param);
		//		}
	}
	public function onLoginError($param)
	{
		echo TVarDumper::dump(__METHOD__,10,true);
		//		EventHandler handler1 = (EventHandler) base.Events[Login.EventLoginError];
		//		if ($handler1!==null)
		//		{
		//			$handler1($this,$param);
		//		}
	}
	private function onLoginPage()
	{

	}
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->setEditableChildProperties();
		$this->_templateContainer->setVisible(($this->getVisibleWhenLoggedIn() || !$this->getPage()->getRequest()->IsAuthenticated()) || $this->onLoginPage());
	}
	private function passwordTextChanged($sender,$param)
	{
		$this->_password = $sender->Text;
	}
	private function redirectedFromFailedLogin()
	{

	}
	private function rememberMeCheckedChanged($sender,$param)
	{
		$this->_rememberMeSet = $sender->Checked;
	}
	public function render($writer)
	{
		//		echo TVarDumper::dump(__METHOD__,10,true);
		if ($this->_templateContainer->getVisible())
		{
			$this->setChildProperties();
			$this->renderChildren($writer);
		}
	}
	public function createChildControls()
	{
		//		echo TVarDumper::dump(__METHOD__,10,true);
		$this->getControls()->clear();
		$this->_templateContainer = new TLoginContainer($this);
		$template1 = new TLoginTemplate($this);
		$template1->instantiateIn($this->_templateContainer);
		$this->_templateContainer->setVisible(true);
		$this->getControls()->add($this->_templateContainer);
		$this->setEditableChildProperties();
	}
	//	protected function saveViewState()
	//	{

	//	}
	public function setChildProperties()
	{
		//		echo TVarDumper::dump(__METHOD__,10,true);
		$this->setCommonChildProperties();
		if ($this->_layoutTemplate === null)
		{
			$this->setDefaultTemplateChildProperties();
		}
	}
	private function setCommonChildProperties()
	{
		//		echo TVarDumper::dump(__METHOD__,10,true);
		/* @VAR $container1 TLoginContainer */
		$container1 = $this->_templateContainer;

	}
	private function setDefaultTemplateChildProperties()
	{
		//		echo TVarDumper::dump(__METHOD__,10,true);
		/* @VAR $container1 TLoginContainer */
		$container1 = $this->_templateContainer;
		$container1->getBorderTable()->setCellPadding($this->getBorderPadding());
		$container1->getBorderTable()->setCellSpacing(0);

		$literal1 = $container1->getTitle();
		$text1 = $this->getTitleText();
		if (strlen($text1)>0)
		{
			$literal1->setText($text1);
			if ($this->_titleTextStyle !== null)
			{
				TLoginUtil::setTableCellStyle($literal1,$this->_titleTextStyle);
			}
			TLoginUtil::setTableCellVisible($literal1,true);
		}
		else
		{
			TLoginUtil::setTableCellVisible($literal1,false);
		}
		$literal2 = $container1->getInstruction();
		$text2 = $this->getInstructionText();
		if (strlen($text2)>0)
		{
			$literal2->setText($text2);
			if ($this->_instructionTextStyle !== null)
			{
				TLoginUtil::setTableCellStyle($literal2,$this->_instructionTextStyle);
			}
			TLoginUtil::setTableCellVisible($literal2,true);
		}
		else
		{
			TLoginUtil::setTableCellVisible($literal2,false);
		}
		$control1 = $container1->getUserNameLabel();
		$text3 = $this->getUserNameLabelText();
		if (strlen($text3)>0)
		{
			$control1->setText($text3);
			if ($this->_instructionTextStyle !== null)
			{
				TLoginUtil::setTableCellStyle($control1,$this->_labelStyle);
			}
			TLoginUtil::setTableCellVisible($control1,true);
		}
		else
		{
			TLoginUtil::setTableCellVisible($control1,false);
		}
		$control2 = $container1->getUserNameTextBox();
		if ($this->_textBoxStyle !== null)
		{
			//            $control2->ApplyStyle(this.TextBoxStyle);//This comes from WebControl
		}
		//		$control2->setTabIndex($this->getTabIndex());//This comes from WebControl
		//		$control2->setAccessKey($this->getAccessKey());//This comes from WebControl
		$flag1 = true;
		/* @VAR $validator1 TRequiredFieldValidator */
		$validator1 = $container1->getUserNameRequired();
		$validator1->setErrorMessage($this->getUserNameRequiredErrorMessage());
		$validator1->setToolTip($this->getUserNameRequiredErrorMessage());
		$validator1->setEnabled($flag1);
		$validator1->setVisible($flag1);
		if ($this->_validatorTextStyle !== null)
		{
			//            validator1.ApplyStyle(this._validatorTextStyle);
		}
		$control3 = $container1->getPasswordLabel();
		$text4 = $this->getPasswordLabelText();
		if (strlen($text4) > 0)
		{
			$control3->setText($text4);
			if ($this->_labelStyle !== null)
			{
				TLoginUtil::setTableCellStyle($control3,$this->_labelStyle);
			}
			$control3->setVisible(true);
		}
		else
		{
			$control3->setVisible(false);
		}
		$control4 = $container1->getPasswordTextBox();
		if ($this->_textBoxStyle !== null)
		{
			//            control4.ApplyStyle(this.TextBoxStyle);
		}
		//		$control4.TabIndex = this.TabIndex;
		$validator2 = $container1->getPasswordRequired();
		$validator2->setErrorMessage($this->getPasswordRequiredErrorMessage());
		$validator2->setToolTip($this->getPasswordRequiredErrorMessage());
		$validator2->setEnabled($flag1);
		$validator2->setVisible($flag1);
		if ($this->_validatorTextStyle !== null)
		{
			//		            validator2.ApplyStyle(this._validatorTextStyle);
		}
		$box1 = $container1->getRememberMeCheckBox();
		if ($this->getDisplayRememberMe())
		{
			$box1->setText($this->getRememberMeText());
			if ($this->_checkBoxStyle !== null)
			{
				TLoginUtil::setTableCellStyle($box1,$this->getCheckBoxStyle());
			}
			TLoginUtil::setTableCellVisible($box1,true);
		}
		else
		{
			TLoginUtil::setTableCellVisible($box1,false);
		}
		//      box1.TabIndex = this.TabIndex;
		$button1 = $container1->getLinkButton();
		$button2 = $container1->getImageButton();
		$button3 = $container1->getPushButton();
		$control5 = null;
		switch ($this->getLoginButtonType())
		{
			case TButtonColumnType::PushButton:
				$button3->setText($this->getLoginButtonText());
				$control5 = $button3;
				break;
			case TButtonColumnType::ImageButton:
				$button2->setImageUrl($this->getLoginButtonImageUrl());
				$button2->setAlternateText($this->getLoginButtonText());
				$control5 = $button2;
				break;

			case TButtonColumnType::LinkButton:
				$button1->setText($this->getLoginButtonText());
				$control5 = $button1;
				break;
		}
		$button1->setVisible(false);
		$button2->setVisible(false);
		$button3->setVisible(false);
		$control5->setVisible(true);
		//      control5.TabIndex = this.TabIndex;
		if ($this->getLoginButtonStyle() !== null)
		{
			//            control5.ApplyStyle(this.LoginButtonStyle);
		}
		$image1 = $container1->getCreateUserIcon();
		$link1 = $container1->getCreateUserLink();
		$control6 = $container1->getCreateUserLinkSeparator();
		$link2 = $container1->getPasswordRecoveryLink();
		$image2 = $container1->getPasswordRecoveryIcon();
		$link3 = $container1->getHelpPageLink();
		$image3 = $container1->getHelpPageIcon();
		$control7 = $container1->getPasswordRecoveryLinkSeparator();
		$text5 = $this->getCreateUserText();
		$text6 = $this->getCreateUserIconUrl();
		$text7 = $this->getPasswordRecoveryText();
		$text8 = $this->getPasswordRecoveryIconUrl();
		$text9 = $this->getHelpPageText();
		$text10 = $this->getHelpPageIconUrl();
		$flag2 = strlen($text5) > 0;
		$flag3 = strlen($text7) > 0;
		$flag4 = strlen($text9) > 0;
		$flag5 = strlen($text10) > 0;
		$flag6 = strlen($text6) > 0;
		$flag7 = strlen($text8) > 0;
		$flag8 = $flag4 || $flag5;
		$flag9 = $flag2 || $flag6;
		$flag10 = $flag3 || $flag7;
		$link3->setVisible($flag4);
		$control7->setVisible($flag8 && ($flag10 || $flag9));
		if ($flag4)
		{
			$link3->setText($text9);
			$link3->setNavigateUrl($this->getHelpPageUrl());
			//			$link3->setTabIndex($this.TabIndex);
		}
		$image3->setVisible($flag5);
		if ($flag5)
		{
			$image3->setImageUrl($text10);
			$image3->setAlternateText($this->getHelpPageText());
		}
		$link1->setVisible($flag2);
		$control6->setVisible($flag9 && $flag10);
		if ($flag2)
		{
			$link1->setText($text5);
			$link1->setNavigateUrl($this->getCreateUserUrl());
			//			$link1->setTabIndex($this.TabIndex);
		}
		$image1->setVisible($flag6);
		if ($flag6)
		{
			$image1->setImageUrl($text6);
			$image1->setAlternateText($this->getCreateUserText());
		}
		$link2->setVisible($flag3);
		if ($flag3)
		{
			$link2->setText($text7);
			$link2->setNavigateUrl($this->getPasswordRecoveryUrl());
			//			$link2->setTabIndex($this.TabIndex);
		}
		$image2->setVisible($flag7);
		if ($flag7)
		{
			$image2->setImageUrl($text8);
			$image2->setAlternateText($this->getPasswordRecoveryText());
		}
		if (($flag9 || $flag10) || $flag8)
		{
			if ($this->getHyperLinkStyle() !== null)
			{
				$style1 = new TTableItemStyle();
				//				$style1.CopyFrom($this->getHyperLinkStyle());
				$style1->getFont()->reset();
				TLoginUtil::setTableCellStyle($link1,$style1);
				//				$link1.Font.CopyFrom(this.HyperLinkStyle.Font);
				//				$link1.ForeColor = this.HyperLinkStyle.ForeColor;
				//				$link2.Font.CopyFrom(this.HyperLinkStyle.Font);
				//				$link2.ForeColor = this.HyperLinkStyle.ForeColor;
				//				$link3.Font.CopyFrom(this.HyperLinkStyle.Font);
				//				$link3.ForeColor = this.HyperLinkStyle.ForeColor;
			}
			TLoginUtil::setTableCellVisible($link3, true);
		}
		else
		{
			TLoginUtil::setTableCellVisible($link3, false);
		}
		$control8 = $container1->getFailureTextLabel();
		if (strlen($control8->getText()) > 0)
		{
			TLoginUtil::setTableCellStyle($control8, $this->getFailureTextStyle());
			TLoginUtil::setTableCellVisible($control8, true);
		}
		else
		{
			TLoginUtil::setTableCellVisible($control8, false);
		}
	}
	private function setEditableChildProperties()
	{
		//		echo TVarDumper::dump(__METHOD__,10,true);
	}
	//	protected function trackViewState()
	//	{

	//	}
	private function userNameTextChanged($sender,$param)
	{
		$this->_userName = $sender->Text;
	}
}

class TLoginTemplate implements ITemplate
{
	private $_owner;

	public function __construct($owner)
	{
		//		echo TVarDumper::dump(__METHOD__,10,true);
		$this->_owner=$owner;
	}

	private function createControls(TLoginContainer $loginContainer)
	{
		//		echo TVarDumper::dump(__METHOD__,10,true);
		$text1 = $this->_owner->getUniqueID();
		$literal1 = new TLiteral();
		$loginContainer->setTitle($literal1);
		$literal2 = new TLiteral();
		$loginContainer->setInstruction($literal2);
		$box1 = new TTextBox();
		$box1->setID('UserName');
		$loginContainer->setUserNameTextBox($box1);
		$label1 = new TLabel();
		$loginContainer->setUserNameLabel($label1);
		$flag1 = true;
		$validator1 = new TRequiredFieldValidator();
		$validator1->setID('UserNameRequired');
		$validator1->setValidationGroup($text1);
		$validator1->setControlToValidate($box1->getID());
		$validator1->setDisplay(TValidatorDisplayStyle::Dynamic);
		$validator1->setText('*');
		$validator1->setEnabled($flag1);
		$validator1->setVisible($flag1);
		$loginContainer->setUserNameRequired($validator1);
		$box2 = new TTextBox();
		$box2->setID('Password');
		$box2->setTextMode(TTextBoxMode::Password);
		$loginContainer->setPasswordTextBox($box2);
		$label2 = new TLabel();
		$loginContainer->setPasswordLabel($label2);
		$validator2 = new TRequiredFieldValidator();
		$validator2->setID('PasswordRequired');
		$validator2->setValidationGroup($text1);
		$validator2->setControlToValidate($box2->getID());
		$validator2->setDisplay(TValidatorDisplayStyle::Dynamic);
		$validator2->setText('*');
		$validator2->setEnabled($flag1);
		$validator2->setVisible($flag1);
		$loginContainer->setPasswordRequired($validator2);
		$box3 = new TCheckBox();
		$box3->setID('RememberMe');
		$loginContainer->setRememberMeCheckBox($box3);
		$button1 = new TLinkButton();
		$button1->setID('LoginLinkButton');
		$button1->setValidationGroup($text1);
		//		$button1->setCommandName(TLogin::LoginButtonCommandName);
		$loginContainer->setLinkButton($button1);
		$button2 = new TImageButton();
		$button2->setID('LoginImageButton');
		$button2->setValidationGroup($text1);
		//		$button2->setCommandName(TLogin::LoginButtonCommandName);
		$loginContainer->setImageButton($button2);
		$button3 = new TButton();
		$button3->setID('LoginButton');
		$button3->setValidationGroup($text1);
		//		$button3->setCommandName(TLogin::LoginButtonCommandName);
		$loginContainer->setPushButton($button3);
		$link1 = new THyperLink();
		$link1->setID('PasswordRecoveryLink');
		$loginContainer->setPasswordRecoveryLink($link1);
		$control1 = new TLiteral();
		$loginContainer->setPasswordRecoveryLinkSeparator($control1);
		$link2 = new THyperLink();
		$link2->setID('CreateUserLink');
		$loginContainer->setCreateUserLink($link2);
		$control2 = new TLiteral();
		$loginContainer->setCreateUserLinkSeparator($control2);
		$link3 = new THyperLink();
		$link3->setID('HelpLink');
		$loginContainer->setHelpPageLink($link3);
		$literal3 = new TLiteral();
		$literal3->setID('FailureText');
		$loginContainer->setFailureTextLabel($literal3);
		$loginContainer->setPasswordRecoveryIcon(new TImage());
		$loginContainer->setPasswordRecoveryIcon(new TImage());
		$loginContainer->setHelpPageIcon(new TImage());
		$loginContainer->setCreateUserIcon(new TImage());
	}
	private function layoutControls(TLoginContainer $loginContainer)
	{
		$orientation1 = $this->_owner->getOrientation();
		$layout1 = $this->_owner->getTextLayout();

		if (($orientation1 === TOrientation::Vertical) && ($layout1 === TLoginTextLayout::TextOnLeft))
		{
			$this->layoutVerticalTextOnLeft($loginContainer);
		}
		elseif (($orientation1 === TOrientation::Vertical) && ($layout1 === TLoginTextLayout::TextOnTop))
		{
			$this->layoutVerticalTextOnTop($loginContainer);
		}
		elseif (($orientation1 === TOrientation::Horizontal) && ($layout1 === TLoginTextLayout::TextOnLeft))
		{
			$this->layoutHorizontalTextOnLeft($loginContainer);
		}
		else
		{
			$this->layoutHorizontalTextOnTop($loginContainer);
		}
	}
	private function layoutHorizontalTextOnLeft(TLoginContainer $loginContainer)
	{
		$table1 = new TTable();
		$table1->setCellPadding(0);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setColumnSpan(6);
		$cell1->setHorizontalAlign(THorizontalAlign::Center);
		$cell1->getControls()->add($loginContainer->getTitle());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setColumnSpan(6);
		$cell1->setHorizontalAlign(THorizontalAlign::Center);
		$cell1->getControls()->add($loginContainer->getInstruction());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		if ($this->_owner->getConvertingToTemplate())
		{
			//			$loginContainer->getUserNameLabel()->RenderAsLabel = true;
		}
		$cell1->getControls()->add($loginContainer->getUserNameLabel());
		$row1->getCells()->add($cell1);
		$cell1 = new TTableCell();
		$cell1->getControls()->add($loginContainer->getUserNameTextBox());
		$cell1->getControls()->add($loginContainer->getUserNameRequired());
		$row1->getCells()->add($cell1);
		$cell1 = new TTableCell();
		if ($this->_owner->getConvertingToTemplate())
		{
			//			$loginContainer->getPasswordLabel()->RenderAsLabel = true;
		}
		$cell1->getControls()->add($loginContainer->getPasswordLabel());
		$row1->getCells()->add($cell1);
		$cell1 = new TTableCell();
		$cell1->getControls()->add($loginContainer->getPasswordTextBox());
		$cell1->getControls()->add($loginContainer->getPasswordRequired());
		$row1->getCells()->add($cell1);
		$cell1 = new TTableCell();
		$cell1->getControls()->add($loginContainer->getRememberMeCheckBox());
		$row1->getCells()->add($cell1);
		$cell1 = new TTableCell();
		$cell1->getControls()->add($loginContainer->getLinkButton());
		$cell1->getControls()->add($loginContainer->getImageButton());
		$cell1->getControls()->add($loginContainer->getPushButton());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setColumnSpan(6);
		$cell1->getControls()->add($loginContainer->getFailureTextLabel());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setColumnSpan(6);
		$loginContainer->getCreateUserLinkSeparator()->setText('');
		$loginContainer->getPasswordRecoveryLinkSeparator()->setText('');
		$cell1->getControls()->add($loginContainer->getCreateUserIcon());
		$cell1->getControls()->add($loginContainer->getCreateUserLink());
		$cell1->getControls()->add($loginContainer->getCreateUserLinkSeparator());
		$cell1->getControls()->add($loginContainer->getPasswordRecoveryIcon());
		$cell1->getControls()->add($loginContainer->getPasswordRecoveryLink());
		$cell1->getControls()->add($loginContainer->getPasswordRecoveryLinkSeparator());
		$cell1->getControls()->add($loginContainer->getHelpPageIcon());
		$cell1->getControls()->add($loginContainer->getHelpPageLink());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$table2 = TLoginUtil::createChildTable($this->_owner->getConvertingToTemplate());
		$row1 = new TTableRow();
		$cell1 = new TTableCell();
		$cell1->getControls()->add($table1);
		$row1->getCells()->add($cell1);
		$table2->getRows()->add($row1);
		$loginContainer->setLayoutTable($table1);
		$loginContainer->setBorderTable($table2);
		$loginContainer->getControls()->add($table2);
	}
	private function layoutHorizontalTextOnTop(TLoginContainer $loginContainer)
	{
		$table1 = new TTable();
		$table1->setCellPadding(0);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setColumnSpan(4);
		$cell1->setHorizontalAlign(THorizontalAlign::Center);
		$cell1->getControls()->add($loginContainer->getTitle());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setColumnSpan(4);
		$cell1->setHorizontalAlign(THorizontalAlign::Center);
		$cell1->getControls()->add($loginContainer->getInstruction());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		if ($this->_owner->getConvertingToTemplate())
		{
			//			$loginContainer->getUserNameLabel()->RenderAsLabel = true;
		}
		$cell1->getControls()->add($loginContainer->getUserNameLabel());
		$row1->getCells()->add($cell1);
		$cell1 = new TTableCell();
		if ($this->_owner->getConvertingToTemplate())
		{
			//			$loginContainer->getPasswordLabel()->RenderAsLabel = true;
		}
		$cell1->getControls()->add($loginContainer->getPasswordLabel());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->getControls()->add($loginContainer->getUserNameTextBox());
		$cell1->getControls()->add($loginContainer->getUserNameRequired());
		$row1->getCells()->add($cell1);
		$cell1 = new TTableCell();
		$cell1->getControls()->add($loginContainer->getPasswordTextBox());
		$cell1->getControls()->add($loginContainer->getPasswordRequired());
		$row1->getCells()->add($cell1);
		$cell1 = new TTableCell();
		$cell1->getControls()->add($loginContainer->getRememberMeCheckBox());
		$row1->getCells()->add($cell1);
		$cell1 = new TTableCell();
		$cell1->setHorizontalAlign(THorizontalAlign::Right);
		$cell1->getControls()->add($loginContainer->getLinkButton());
		$cell1->getControls()->add($loginContainer->getImageButton());
		$cell1->getControls()->add($loginContainer->getPushButton());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setColumnSpan(4);
		$cell1->getControls()->add($loginContainer->getFailureTextLabel());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setColumnSpan(4);
		$loginContainer->getCreateUserLinkSeparator()->setText('');
		$loginContainer->getPasswordRecoveryLinkSeparator()->setText('');
		$cell1->getControls()->add($loginContainer->getCreateUserIcon());
		$cell1->getControls()->add($loginContainer->getCreateUserLink());
		$cell1->getControls()->add($loginContainer->getCreateUserLinkSeparator());
		$cell1->getControls()->add($loginContainer->getPasswordRecoveryIcon());
		$cell1->getControls()->add($loginContainer->getPasswordRecoveryLink());
		$cell1->getControls()->add($loginContainer->getPasswordRecoveryLinkSeparator());
		$cell1->getControls()->add($loginContainer->getHelpPageIcon());
		$cell1->getControls()->add($loginContainer->getHelpPageLink());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$table2 = TLoginUtil::createChildTable($this->_owner->getConvertingToTemplate());
		$row1 = new TTableRow();
		$cell1 = new TTableCell();
		$cell1->getControls()->add($table1);
		$row1->getCells()->add($cell1);
		$table2->getRows()->add($row1);
		$loginContainer->setLayoutTable($table1);
		$loginContainer->setBorderTable($table2);
		$loginContainer->getControls()->add($table2);
	}
	private function layoutVerticalTextOnLeft(TLoginContainer $loginContainer)
	{
		$table1 = new TTable();
		$table1->setCellPadding(0);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setColumnSpan(2);
		$cell1->setHorizontalAlign(THorizontalAlign::Center);
		$cell1->getControls()->add($loginContainer->getTitle());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setColumnSpan(2);
		$cell1->setHorizontalAlign(THorizontalAlign::Center);
		$cell1->getControls()->add($loginContainer->getInstruction());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setHorizontalAlign(THorizontalAlign::Right);
		if ($this->_owner->getConvertingToTemplate())
		{
			//			$loginContainer->getUserNameLabel()->RenderAsLabel = true;
		}
		$cell1->getControls()->add($loginContainer->getUserNameLabel());
		$row1->getCells()->add($cell1);
		$cell1 = new TTableCell();
		$cell1->getControls()->add($loginContainer->getUserNameTextBox());
		$cell1->getControls()->add($loginContainer->getUserNameRequired());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setHorizontalAlign(THorizontalAlign::Right);
		if ($this->_owner->getConvertingToTemplate())
		{
			//			$loginContainer->getPasswordLabel()->RenderAsLabel = true;
		}
		$cell1->getControls()->add($loginContainer->getPasswordLabel());
		$row1->getCells()->add($cell1);
		$cell1 = new TTableCell();
		$cell1->getControls()->add($loginContainer->getPasswordTextBox());
		$cell1->getControls()->add($loginContainer->getPasswordRequired());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setColumnSpan(2);
		$cell1->getControls()->add($loginContainer->getRememberMeCheckBox());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setColumnSpan(2);
		$cell1->setHorizontalAlign(THorizontalAlign::Center);
		$cell1->getControls()->add($loginContainer->getFailureTextLabel());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setColumnSpan(2);
		$cell1->setHorizontalAlign(THorizontalAlign::Right);
		$cell1->getControls()->add($loginContainer->getLinkButton());
		$cell1->getControls()->add($loginContainer->getImageButton());
		$cell1->getControls()->add($loginContainer->getPushButton());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setColumnSpan(2);
		$loginContainer->getPasswordRecoveryLinkSeparator()->setText('<br />');
		$loginContainer->getCreateUserLinkSeparator()->setText('<br />');
		$cell1->getControls()->add($loginContainer->getCreateUserIcon());
		$cell1->getControls()->add($loginContainer->getCreateUserLink());
		$cell1->getControls()->add($loginContainer->getCreateUserLinkSeparator());
		$cell1->getControls()->add($loginContainer->getPasswordRecoveryIcon());
		$cell1->getControls()->add($loginContainer->getPasswordRecoveryLink());
		$cell1->getControls()->add($loginContainer->getPasswordRecoveryLinkSeparator());
		$cell1->getControls()->add($loginContainer->getHelpPageIcon());
		$cell1->getControls()->add($loginContainer->getHelpPageLink());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$table2 = TLoginUtil::createChildTable($this->_owner->getConvertingToTemplate());
		$row1 = new TTableRow();
		$cell1 = new TTableCell();
		$cell1->getControls()->add($table1);
		$row1->getCells()->add($cell1);
		$table2->getRows()->add($row1);
		$loginContainer->setLayoutTable($table1);
		$loginContainer->setBorderTable($table2);
		$loginContainer->getControls()->add($table2);
	}
	private function layoutVerticalTextOnTop(TLoginContainer $loginContainer)
	{
		$table1 = new TTable();
		$table1->setCellPadding(0);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setColumnSpan(2);
		$cell1->setHorizontalAlign(THorizontalAlign::Center);
		$cell1->getControls()->add($loginContainer->getTitle());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setHorizontalAlign(THorizontalAlign::Center);
		$cell1->getControls()->add($loginContainer->getInstruction());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		if ($this->_owner->getConvertingToTemplate())
		{
			//			$loginContainer->getUserNameLabel()->RenderAsLabel = true;
		}
		$cell1->getControls()->add($loginContainer->getUserNameLabel());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->getControls()->add($loginContainer->getUserNameTextBox());
		$cell1->getControls()->add($loginContainer->getUserNameRequired());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		if ($this->_owner->getConvertingToTemplate())
		{
			//			$loginContainer->getPasswordLabel()->RenderAsLabel = true;
		}
		$cell1->getControls()->add($loginContainer->getPasswordLabel());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->getControls()->add($loginContainer->getPasswordTextBox());
		$cell1->getControls()->add($loginContainer->getPasswordRequired());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->getControls()->add($loginContainer->getRememberMeCheckBox());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setHorizontalAlign(THorizontalAlign::Center);
		$cell1->getControls()->add($loginContainer->getFailureTextLabel());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$cell1->setHorizontalAlign(THorizontalAlign::Right);
		$cell1->getControls()->add($loginContainer->getLinkButton());
		$cell1->getControls()->add($loginContainer->getImageButton());
		$cell1->getControls()->add($loginContainer->getPushButton());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$row1 = new TDisappearingTableRow();
		$cell1 = new TTableCell();
		$loginContainer->getPasswordRecoveryLinkSeparator()->setText('<br />');
		$loginContainer->getCreateUserLinkSeparator()->setText('<br />');
		$cell1->getControls()->add($loginContainer->getCreateUserIcon());
		$cell1->getControls()->add($loginContainer->getCreateUserLink());
		$cell1->getControls()->add($loginContainer->getCreateUserLinkSeparator());
		$cell1->getControls()->add($loginContainer->getPasswordRecoveryIcon());
		$cell1->getControls()->add($loginContainer->getPasswordRecoveryLink());
		$cell1->getControls()->add($loginContainer->getPasswordRecoveryLinkSeparator());
		$cell1->getControls()->add($loginContainer->getHelpPageIcon());
		$cell1->getControls()->add($loginContainer->getHelpPageLink());
		$row1->getCells()->add($cell1);
		$table1->getRows()->add($row1);
		$table2 = TLoginUtil::createChildTable($this->_owner->getConvertingToTemplate());
		$row1 = new TTableRow();
		$cell1 = new TTableCell();
		$cell1->getControls()->add($table1);
		$row1->getCells()->add($cell1);
		$table2->getRows()->add($row1);
		$loginContainer->setLayoutTable($table1);
		$loginContainer->setBorderTable($table2);
		$loginContainer->getControls()->add($table2);
	}
	public function instantiateIn($parent)
	{
		//		echo TVarDumper::dump(__METHOD__,10,true);
		$this->createControls($parent);
		$this->layoutControls($parent);
	}

}

Prado::using('System.Web.UI.WebControls.TLoginUtil');
class TLoginContainer extends TGenericContainer
{
	private $_convertingToTemplate=false;
	private $_createUserIcon;
	private $_createUserLink;
	private $_createUserLinkSeparator;
	private $_failureTextLabel;
	private $_helpPageIcon;
	private $_helpPageLink;
	private $_imageButton;
	private $_instruction;
	private $_linkButton;
	private $_passwordLabel;
	private $_passwordRecoveryIcon;
	private $_passwordRecoveryLink;
	private $_passwordRecoveryLinkSeparator;
	private $_passwordRequired;
	private $_passwordTextBox;
	private $_pushButton;
	private $_rememberMeCheckBox;
	private $_title;
	private $_userNameLabel;
	private $_userNameRequired;
	private $_userNameTextBox;

	public function getConvertingToTemplate()
	{
		return parent::getOwner()->getConvertingToTemplate();
	}
	public function getCreateUserIcon()
	{
		return $this->_createUserIcon;
	}
	public function setCreateUserIcon(TImage $value)
	{
		$this->_createUserIcon = TPropertyValue::ensureObject($value);
	}
	public function getCreateUserLink()
	{
		return $this->_createUserLink;
	}
	public function setCreateUserLink(THyperLink $value)
	{
		$this->_createUserLink = TPropertyValue::ensureObject($value);
	}
	public function getCreateUserLinkSeparator()
	{
		return $this->_createUserLinkSeparator;
	}
	public function setCreateUserLinkSeparator(TLiteral $value)
	{
		$this->_createUserLinkSeparator = TPropertyValue::ensureObject($value);
	}
	public function getFailureTextLabel()
	{
		return $this->_failureTextLabel;
	}
	public function setFailureTextLabel(TControl $value)
	{
		$this->_failureTextLabel = TPropertyValue::ensureObject($value);
	}
	public function getHelpPageIcon()
	{
		return $this->_helpPageIcon;
	}
	public function setHelpPageIcon(TImage $value)
	{
		$this->_helpPageIcon = TPropertyValue::ensureObject($value);
	}
	public function getHelpPageLink()
	{
		return $this->_helpPageLink;
	}
	public function setHelpPageLink(THyperLink $value)
	{
		$this->_helpPageLink = TPropertyValue::ensureObject($value);
	}
	public function getImageButton()
	{
		return $this->_imageButton;
	}
	public function setImageButton(TImageButton $value)
	{
		$this->_imageButton = TPropertyValue::ensureObject($value);
	}
	public function getInstruction()
	{
		return $this->_instruction;
	}
	public function setInstruction(TLiteral $value)
	{
		$this->_instruction = TPropertyValue::ensureObject($value);
	}
	public function getLinkButton()
	{
		return $this->_linkButton;
	}
	public function setLinkButton(TLinkButton $value)
	{
		$this->_linkButton = TPropertyValue::ensureObject($value);
	}
	public function getPasswordLabel()
	{
		return $this->_passwordLabel;
	}
	public function setPasswordLabel(TLabel $value)
	{
		$this->_passwordLabel = TPropertyValue::ensureObject($value);
	}
	public function getPasswordRecoveryIcon()
	{
		return $this->_passwordRecoveryIcon;
	}
	public function setPasswordRecoveryIcon(TImage $value)
	{
		$this->_passwordRecoveryIcon = TPropertyValue::ensureObject($value);
	}
	public function getPasswordRecoveryLink()
	{
		return $this->_passwordRecoveryLink;
	}
	public function setPasswordRecoveryLink(THyperLink $value)
	{
		$this->_passwordRecoveryLink = TPropertyValue::ensureObject($value);
	}
	public function getPasswordRecoveryLinkSeparator()
	{
		return $this->_passwordRecoveryLinkSeparator;
	}
	public function setPasswordRecoveryLinkSeparator($value)
	{
		$this->_passwordRecoveryLinkSeparator = TPropertyValue::ensureObject($value);
	}
	public function getPasswordRequired()
	{
		return $this->_passwordRequired;
	}
	public function setPasswordRequired(TRequiredFieldValidator $value)
	{
		$this->_passwordRequired = TPropertyValue::ensureObject($value);
	}
	public function getPasswordTextBox()
	{
		return $this->_passwordTextBox;
	}
	public function setPasswordTextBox(TControl $value)
	{
		$this->_passwordTextBox = TPropertyValue::ensureObject($value);
	}
	public function getPushButton()
	{
		return $this->_pushButton;
	}
	public function setPushButton(TControl $value)
	{
		$this->_pushButton = TPropertyValue::ensureObject($value);
	}
	public function getRememberMeCheckBox()
	{
		return $this->_rememberMeCheckBox;
	}
	public function setRememberMeCheckBox(TControl $value)
	{
		$this->_rememberMeCheckBox = TPropertyValue::ensureObject($value);
	}
	public function getTitle()
	{
		return $this->_title;
	}
	public function setTitle(TLiteral $value)
	{
		$this->_title = TPropertyValue::ensureObject($value);
	}
	public function getUserNameLabel()
	{
		return $this->_userNameLabel;
	}
	public function setUserNameLabel(TLabel $value)
	{
		$this->_userNameLabel = TPropertyValue::ensureObject($value);
	}
	public function getUserNameRequired()
	{
		return $this->_userNameRequired;
	}
	public function setUserNameRequired(TRequiredFieldValidator $value)
	{
		$this->_userNameRequired = TPropertyValue::ensureObject($value);
	}
	public function getUserNameTextBox()
	{
		return $this->_userNameTextBox;
	}
	public function setUserNameTextBox(TControl $value)
	{
		$this->_userNameTextBox = TPropertyValue::ensureObject($value);
	}
}

/**
 * TLoginFailureAction class.
 * Determines the page that the user will go to when a login attempt is not successful.
 *
 * RedirectToLoginPage	Redirects the user to the login page defined in the site's 
 * 						configuration files. 
 * Refresh				Refreshes the current page so that the Login control can display 
 * 						an error message. 
 * 
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TLoginFailureAction.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.UI.WebControls
 * @since 3.1
 */
class TLoginFailureAction extends TEnumerable
{
	const RedirectToLoginPage='RedirectToLoginPage';
	const Refresh='Refresh';
}
/**
 * TLoginTextLayout class.
 * Specifies the position of labels relative to their associated text boxes for the Login control.
 *
 * TextOnLeft	Places labels to the left of the associated text entry fields. 
 * TextOnTop	Places labels above the associated text entry fields.  
 * 
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TLoginTextLayout.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.UI.WebControls
 * @since 3.1
 */
class TLoginTextLayout extends TEnumerable
{
	const TextOnLeft='TextOnLeft';
	const TextOnTop='TextOnTop';
}
/**
 * TLogoutAction class.
 * Indicates the page that the user will be directed to when he or she logs out of the Web site.
 *
 * Redirect				Redirects the user to a specified URL. 
 * RedirectToLoginPage	Redirects the user to the login page defined in the site's configuration files. 
 * Refresh				Reloads the current page with the user logged out. 
 * 
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TLogoutAction.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.UI.WebControls
 * @since 3.1
 */
class TLogoutAction extends TEnumerable
{
	const Redirect='Redirect';
	const RedirectToLoginPage='RedirectToLoginPage';
	const Refresh='Refresh';
}
?>