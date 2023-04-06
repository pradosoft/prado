<?php

use Prado\IO\TTextWriter;
use Prado\Shell\TShellWriter;
use Prado\Security\Permissions\TPermissionsAction;
use Prado\Security\Permissions\TPermissionsManager;
use Prado\Security\TUserManager;
use Prado\Util\TDbParameterModule;

class TPermissionsActionTest extends PHPUnit\Framework\TestCase
{
	protected $dbparam;
	protected $manager;
	protected $obj;
	protected $writer, $_writer;
	
	protected function setUp(): void
	{
		$this->_writer = new TTextWriter();
		$this->writer = new TShellWriter($this->_writer);
		$this->manager = new TPermissionsManager();
		$this->dbparam = new TDbParameterModule();
		$this->obj = new TPermissionsAction();
		$this->obj->setWriter($this->writer);
		$this->manager->setDbParameter($this->dbparam);
	}

	protected function tearDown(): void
	{
		$this->dbparam->remove($this->manager->getLoadParameter());
		$this->manager->__destruct();
		$this->manager = null;
		$this->dbparam->__destruct();
		$this->dbparam = null;
	}
	
	public function testConstruct()
	{
		self::assertInstanceOf(TPermissionsAction::class, $this->obj);
	}
	
	public function testActionIndex()
	{
		$this->obj->setPermissionsManager(null);
		
		self::assertTrue($this->obj->actionIndex([]));
		self::assertTrue(is_numeric(stripos($this->writer->flush(), "No TPermissionsManager found")));
		
		$this->obj->setPermissionsManager($this->manager);
		
		Prado::getApplication()->getParameters()[$this->manager->getLoadParameter()] = [
			'roles' => [
				'sub_role' => ['final_role', 'general_perm']
			],
			'permissionrules' => [
				'*' => [new TAuthorizationRule('allow', '', '', '', '', 89), new TAuthorizationRule('deny', '', '', '', '', 102)]
			]
		];
		
		$this->manager->setSuperRoles("RoleA, RoleB");
		$this->manager->setDefaultRoles("DefaultA");
		$this->manager->registerPermission("general_perm", 'what general permissions do');
		
		$this->manager->init([
			'roles' => [
				'init_role' => ['hierarchy_role', 'permissions_shell']
			],
			'permissionrules' => [
				'*' => [new TAuthorizationRule('allow', '', '', '', '', 71), new TAuthorizationRule('deny', '', '', '', '', 72)]
			]
		]);
		
		self::assertTrue($this->obj->actionIndex(['perm/index']));
		$text = $this->writer->flush();
		
		self::assertTrue(is_numeric(strpos($text, "RoleA")));
		self::assertTrue(is_numeric(strpos($text, "RoleB")));
		self::assertTrue(is_numeric(strpos($text, "DefaultA")));
		self::assertTrue(is_numeric(strpos($text, "sub_role")));
		self::assertTrue(is_numeric(strpos($text, "final_role")));
		self::assertTrue(is_numeric(strpos($text, "general_perm")));
		self::assertTrue(is_numeric(strpos($text, "*")));
		self::assertTrue(is_numeric(strpos($text, "allow")));
		self::assertTrue(is_numeric(strpos($text, "89")));
		self::assertTrue(is_numeric(strpos($text, "deny")));
		self::assertTrue(is_numeric(strpos($text, "102")));
		
		self::assertFalse(strpos($text, "init_role"));
		self::assertFalse(strpos($text, "hierarchy_role"));
		self::assertFalse(strpos($text, "permissions_shell"));
		self::assertFalse(strpos($text, "71"));
		self::assertFalse(strpos($text, "72"));
		
		
		$this->obj->setAll(true);
		self::assertTrue($this->obj->actionIndex(['perm/index']));
		$text = $this->writer->flush();
		self::assertTrue(is_numeric(strpos($text, "RoleA")));
		self::assertTrue(is_numeric(strpos($text, "RoleB")));
		self::assertTrue(is_numeric(strpos($text, "DefaultA")));
		self::assertTrue(is_numeric(strpos($text, "sub_role")));
		self::assertTrue(is_numeric(strpos($text, "final_role")));
		self::assertTrue(is_numeric(strpos($text, "general_perm")));
		self::assertTrue(is_numeric(strpos($text, "allow")));
		self::assertTrue(is_numeric(strpos($text, "89")));
		self::assertTrue(is_numeric(strpos($text, "deny")));
		self::assertTrue(is_numeric(strpos($text, "102")));
		
		self::assertTrue(is_numeric(strpos($text, "init_role")));
		self::assertTrue(is_numeric(strpos($text, "hierarchy_role")));
		self::assertTrue(is_numeric(strpos($text, "permissions_shell")));
		self::assertTrue(is_numeric(strpos($text, "71")));
		self::assertTrue(is_numeric(strpos($text, "72")));
	}
	
	public function testActionRole()
	{
		$this->obj->setPermissionsManager(null);
		
		self::assertTrue($this->obj->actionRole(['perm/role']));
		self::assertTrue(is_numeric(stripos($this->writer->flush(), "No TPermissionsManager found")));
		$this->obj->setPermissionsManager($this->manager);
		
		$this->manager->setDbParameter(null);
		self::assertTrue($this->obj->actionRole(['perm/role']));
		self::assertTrue(is_numeric(stripos($this->writer->flush(), "TPermissionsManager has no DbParameter")));
		$this->manager->setDbParameter($this->dbparam);
		
		Prado::getApplication()->getParameters()[$this->manager->getLoadParameter()] = [
			'roles' => [
				'sub_role' => ['final_role', 'general_perm']
			]
		];
		self::assertTrue($this->obj->actionRole(['perm/role']));
		self::assertTrue(is_numeric(stripos($this->writer->flush(), "Action requires <role-name>")));
		
		self::assertTrue($this->obj->actionRole(['perm/role', 'sub_role']));
		$text = $this->writer->flush();
		self::assertFalse(is_numeric(stripos($text, "Success")));
		self::assertTrue(is_numeric(stripos($text, "sub_role")));
		self::assertTrue(is_numeric(stripos($text, "final_role")));
		self::assertTrue(is_numeric(stripos($text, "general_perm")));
		
		self::assertTrue($this->obj->actionRole(['perm/role', 'sub_role', '-final_role', '+other_perm', 'abc_perm']));
		$text = $this->writer->flush();
		$roles = $this->manager->getDbConfigRoles();
		self::assertTrue(is_numeric(stripos($text, "Success")));
		self::assertTrue(is_numeric(stripos($text, "sub_role")));
		self::assertTrue(is_numeric(stripos($text, "general_perm")));
		self::assertTrue(is_numeric(stripos($text, "other_perm")));
		self::assertTrue(is_numeric(stripos($text, "abc_perm")));
		self::assertEquals(['general_perm', 'other_perm', 'abc_perm'], $roles['sub_role']);
		
		self::assertTrue($this->obj->actionRole(['perm/role', 'sub_role', '-general_perm', '-other_perm', '-abc_perm']));
		$text = $this->writer->flush();
		$roles = $this->manager->getDbConfigRoles();
		self::assertTrue(is_numeric(stripos($text, "Success")));
		self::assertTrue(is_numeric(stripos($text, "sub_role")));
		self::assertTrue(is_numeric(stripos($text, "no children")));
		self::assertEquals([], $roles);
	}
	
	public function testActionAddRemoveRule()
	{
		$this->obj->setPermissionsManager(null);
		self::assertTrue($this->obj->actionAddRule(['perm/add-rule']));
		self::assertTrue(is_numeric(stripos($this->writer->flush(), "No TPermissionsManager found")));
		self::assertTrue($this->obj->actionRemoveRule(['perm/remove-rule']));
		self::assertTrue(is_numeric(stripos($this->writer->flush(), "No TPermissionsManager found")));
		$this->obj->setPermissionsManager($this->manager);
		
		$this->manager->setDbParameter(null);
		self::assertTrue($this->obj->actionAddRule(['perm/add-rule']));
		self::assertTrue(is_numeric(stripos($this->writer->flush(), "TPermissionsManager has no DbParameter")));
		self::assertTrue($this->obj->actionRemoveRule(['perm/remove-rule']));
		self::assertTrue(is_numeric(stripos($this->writer->flush(), "TPermissionsManager has no DbParameter")));
		$this->manager->setDbParameter($this->dbparam);
		
		self::assertTrue($this->obj->actionAddRule(['perm/add-rule']));
		self::assertTrue(is_numeric(stripos($this->writer->flush(), "Permissions needs a name")));
		self::assertTrue($this->obj->actionRemoveRule(['perm/remove-rule']));
		self::assertTrue(is_numeric(stripos($this->writer->flush(), "Permissions needs a name")));
		
		self::assertTrue($this->obj->actionAddRule(['perm/add-rule', 'perm_name', 'altaction']));
		self::assertTrue(is_numeric(stripos($this->writer->flush(), "not [allow, deny]")));
		
		self::assertTrue($this->obj->actionAddRule(['perm/add-rule', 'perm_name', 'allow', '*', '*', 'back']));
		self::assertTrue(is_numeric(stripos($this->writer->flush(), "not [*, get, post]")));
		
		self::assertTrue($this->obj->actionAddRule(['perm/add-rule', 'perm_name', 'allow', 'user1', 'role2', 'post', '10.0.*', '333']));
		self::assertTrue(is_numeric(stripos($text = $this->writer->flush(), "Success")));
		self::assertTrue(is_numeric(stripos($text, "perm_name")));
		self::assertTrue(is_numeric(stripos($text, "allow")));
		self::assertFalse(is_numeric(stripos($text, "deny")));
		self::assertFalse(is_numeric(stripos($text, "User Owner")));
		self::assertTrue(is_numeric(stripos($text, "user1")));
		self::assertTrue(is_numeric(stripos($text, "role2")));
		self::assertTrue(is_numeric(stripos($text, "post")));
		self::assertTrue(is_numeric(stripos($text, "10.0.*")));
		self::assertTrue(is_numeric(stripos($text, "333")));
		$rules = $this->manager->getDbConfigPermissionRules();
		self::assertEquals(1, count($rules));
		self::assertEquals(1, count($rules['perm_name']));
		self::assertEquals('allow', $rules['perm_name'][0]->getAction());
		self::assertEquals(['user1'], $rules['perm_name'][0]->getUsers());
		self::assertEquals(['role2'], $rules['perm_name'][0]->getRoles());
		self::assertEquals('post', $rules['perm_name'][0]->getVerb());
		self::assertEquals(['10.0.*'], $rules['perm_name'][0]->getIpRules());
		self::assertEquals(333, $rules['perm_name'][0]->getPriority());
		
		self::assertTrue($this->obj->actionAddRule(['perm/add-rule', 'perm_name', 'allow', '', '', '', '', '', 'TUserOwnerRule']));
		self::assertTrue(is_numeric(stripos($text = $this->writer->flush(), "Success")));
		self::assertTrue(is_numeric(stripos($text, "User Owner")));
		$rules = $this->manager->getDbConfigPermissionRules();
		self::assertEquals(1, count($rules));
		self::assertEquals(2, count($rules['perm_name']));
		self::assertEquals('allow', $rules['perm_name'][1]->getAction());
		self::assertEquals([], $rules['perm_name'][1]->getUsers());
		self::assertEquals(['*'], $rules['perm_name'][1]->getRoles());
		self::assertEquals('*', $rules['perm_name'][1]->getVerb());
		self::assertEquals(['*'], $rules['perm_name'][1]->getIpRules());
		self::assertNull($rules['perm_name'][1]->getPriority());
		self::assertInstanceOf(TUserOwnerRule::class, $rules['perm_name'][1]);
		
		self::assertTrue($this->obj->actionAddRule(['perm/add-rule', 'perm_name', 'deny', '', '', '', '', '10001']));
		self::assertTrue(is_numeric(stripos($text = $this->writer->flush(), "Success")));
		self::assertTrue(is_numeric(stripos($text, "deny")));
		self::assertTrue(is_numeric(stripos($text, "10001")));
		self::assertTrue(is_numeric(stripos($text, "2")));
		$rules = $this->manager->getDbConfigPermissionRules();
		self::assertEquals(1, count($rules));
		self::assertEquals(3, count($rules['perm_name']));
		self::assertEquals('deny', $rules['perm_name'][2]->getAction());
		self::assertEquals([], $rules['perm_name'][2]->getUsers());
		self::assertEquals(['*'], $rules['perm_name'][2]->getRoles());
		self::assertEquals('*', $rules['perm_name'][2]->getVerb());
		self::assertEquals(['*'], $rules['perm_name'][2]->getIpRules());
		self::assertEquals(10001, $rules['perm_name'][2]->getPriority());
		
		
		self::assertTrue($this->obj->actionRemoveRule(['perm/remove-rule', 'perm_name', '3']));
		self::assertTrue(is_numeric(stripos($text = $this->writer->flush(), "No rule at index")));
		
		self::assertTrue($this->obj->actionRemoveRule(['perm/remove-rule', 'perm_name_alt', '1']));
		self::assertTrue(is_numeric(stripos($text = $this->writer->flush(), "No rules")));
		
		self::assertTrue($this->obj->actionRemoveRule(['perm/remove-rule', 'perm_name', '1']));
		self::assertTrue(is_numeric(stripos($text = $this->writer->flush(), "Success")));
		self::assertTrue(is_numeric(stripos($text, "perm_name")));
		self::assertTrue(is_numeric(stripos($text, "allow")));
		self::assertTrue(is_numeric(stripos($text, "deny")));
		self::assertFalse(is_numeric(stripos($text, "User Owner")));
		self::assertTrue(is_numeric(stripos($text, "user1")));
		self::assertTrue(is_numeric(stripos($text, "role2")));
		self::assertTrue(is_numeric(stripos($text, "post")));
		self::assertTrue(is_numeric(stripos($text, "10.0.*")));
		self::assertTrue(is_numeric(stripos($text, "333")));
		self::assertTrue(is_numeric(stripos($text, "10001")));
		$rules = $this->manager->getDbConfigPermissionRules();
		self::assertEquals(1, count($rules));
		self::assertEquals(2, count($rules['perm_name']));
		self::assertEquals(333, $rules['perm_name'][0]->getPriority());
		self::assertEquals(10001, $rules['perm_name'][1]->getPriority());
		
		self::assertTrue($this->obj->actionRemoveRule(['perm/remove-rule', 'perm_name', '1']));
		self::assertTrue(is_numeric(stripos($text = $this->writer->flush(), "Success")));
		self::assertTrue(is_numeric(stripos($text, "perm_name")));
		self::assertTrue(is_numeric(stripos($text, "allow")));
		self::assertTrue(is_numeric(stripos($text, "user1")));
		self::assertTrue(is_numeric(stripos($text, "role2")));
		self::assertTrue(is_numeric(stripos($text, "post")));
		self::assertTrue(is_numeric(stripos($text, "10.0.*")));
		self::assertTrue(is_numeric(stripos($text, "333")));
		self::assertFalse(is_numeric(stripos($text, "deny")));
		self::assertFalse(is_numeric(stripos($text, "10001")));
		$rules = $this->manager->getDbConfigPermissionRules();
		self::assertEquals(1, count($rules));
		self::assertEquals(1, count($rules['perm_name']));
		self::assertEquals(333, $rules['perm_name'][0]->getPriority());
		
		self::assertTrue($this->obj->actionRemoveRule(['perm/remove-rule', 'perm_name', '0']));
		self::assertTrue(is_numeric(stripos($text = $this->writer->flush(), "Success")));
		self::assertTrue(is_numeric(stripos($text, "perm_name")));
		self::assertTrue(is_numeric(stripos($text, "perm_name")));
		
	}
	
	public function testPermissionsManager()
	{
		$this->obj->setPermissionsManager($v = new stdClass());
		self::assertEquals($v, $this->obj->getPermissionsManager());
	}

}
