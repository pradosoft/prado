<?php
class Home extends TPage
{
	public function OnLoad($param)
	{
		Prado::using('System.Util.TVarDumper');
		Prado::using('System.Web.Security.TSqlMembershipProvider');
		Prado::using('System.Configuration.TProtectedConfiguration');
		//		TRoles::CreateRole('test');
		//		TMembership::ValidateUser('test','test');
		//		echo TVarDumper::dump(TProtectedConfiguration::getDefaultProvider(),10,true);
		//		echo TVarDumper::dump($this->Application->getModule('ProtectedConfiguration'),10,true);


		// Access by provider id
		//		$MembershipProvider = $this->Application->getModule('MembershipProvider')->getProvider('SqlMembershipProvider');
		// or just get the default provider
		$MembershipProvider = $this->Application->getModule('MembershipProvider')->Provider;
		//		$RoleProvider = $this->Application->getModule('RoleProvider')->Provider;
		/* @VAR $MembershipProvider TSqlMembershipProvider */
		/* @VAR $RoleProvider TSqlRoleProvider */
		echo TVarDumper::dump($MembershipProvider,10,true);
		echo TVarDumper::dump($MembershipProvider->getMembershipUser('testUser'),10,true);
		//		echo TVarDumper::dump($RoleProvider,10,true);
	}
}
?>