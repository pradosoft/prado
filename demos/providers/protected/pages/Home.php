<?php
class Home extends TPage
{
	public function OnLoad($param)
	{
		Prado::using('System.Util.TVarDumper');
		Prado::using('System.Web.Security.TSqlMembershipProvider');
		Prado::using('System.Configuration.TProtectedConfiguration');

		// Access by provider id
		//		$MembershipProvider = $this->Application->getModule('MembershipProvider')->getProvider('SqlMembershipProvider');


		/* @VAR $MembershipProvider TSqlMembershipProvider */
		//		$MembershipProvider = $this->Application->getModule('MembershipProvider')->Provider;
		//		echo TVarDumper::dump($MembershipProvider,10,true);

		/* @VAR $RoleProvider TSqlRoleProvider */
		//		$RoleProvider = $this->Application->getModule('RoleProvider')->Provider;
		//		echo TVarDumper::dump($RoleProvider,10,true);

		/* @VAR $FormsAuthentication TFormsAuthenticationModule */
		$FormsAuthentication = $this->Application->getModule('FormsAuthentication');
		//		echo TVarDumper::dump($FormsAuthentication,10,true);
	}
}
?>