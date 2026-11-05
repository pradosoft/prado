<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Security\TUser;
use Prado\Security\TUserManager;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TPage;
use Prado\Web\UI\TTemplate;
use Prado\Web\UI\WebControls\TAuthView;
use Prado\Web\UI\WebControls\TAuthViewTemplate;

/**
 * A TUser with `can()`, granting the permissions it lists.
 */
class TAuthViewTestUser extends TUser
{
	public array $granted = [];

	public function can($permission, $extraData = null)
	{
		return in_array($permission, $this->granted, true);
	}
}

class TAuthViewTest extends TestCase
{
	use TWebControlRenderTrait;

	private array $_snap = [];

	protected function setUp(): void
	{
		$this->_snap = PradoUnit::snapshot(Prado::getApplication(), ['_user']);
	}

	protected function tearDown(): void
	{
		PradoUnit::restore(Prado::getApplication(), $this->_snap);
	}

	private function signIn(string $name, array $roles = [], bool $guest = false, string $class = TUser::class): TUser
	{
		$user = new $class(new TUserManager());
		$user->setName($name);
		$user->setRoles($roles);
		$user->setIsGuest($guest);
		Prado::getApplication()->setUser($user);
		return $user;
	}

	private function signOut(): void
	{
		$this->signIn('Guest', [], true);
	}

	/**
	 * Instantiates the template in a control on a page and returns the control.
	 */
	private function build(string $template): TControl
	{
		$page = new TPage();
		$page->setID('TestPage');
		$container = new TControl();
		$container->setID('tplControl');
		$page->getControls()->add($container);
		(new TTemplate($template, sys_get_temp_dir(), null, 0, true))->instantiateIn($container);
		return $container;
	}

	private function init(TControl $container): void
	{
		PradoUnit::invoke($container, 'initRecursive', $container->getPage());
	}

	private function loadAndPreRender(TControl $container): void
	{
		PradoUnit::invoke($container, 'loadRecursive');
		PradoUnit::invoke($container, 'preRenderRecursive');
	}

	/**
	 * Runs the lifecycle to PreRender and returns the trimmed rendering.
	 */
	private function renderView(string $template): string
	{
		$container = $this->build($template);
		$this->init($container);
		$this->loadAndPreRender($container);
		return trim($this->render($container));
	}

	private const MULTI = '<com:TAuthView ID="view">'
		. '<com:TAuthViewTemplate Users="?">[sign in]</com:TAuthViewTemplate>'
		. '<com:TAuthViewTemplate Roles="admin">[admin]</com:TAuthViewTemplate>'
		. '<com:TAuthViewTemplate Permissions="blog.edit">[edit]</com:TAuthViewTemplate>'
		. '[welcome]'
		. '</com:TAuthView>';

	// ================================================================================
	// Properties
	// ================================================================================

	public function testDefaults()
	{
		$view = new TAuthView();
		$this->assertInstanceOf(TControl::class, $view);
		$this->assertSame('@', $view->getUsers());
		$this->assertSame('', $view->getRoles());
		$this->assertSame('', $view->getPermissions());
		$this->assertSame([], $view->getTemplates());
		$this->assertNull($view->getActiveTemplate());
		$this->assertInstanceOf(TAuthViewTemplate::class, $view->getSelfTemplate());
	}

	public function testConditionsDelegateToSelfTemplate()
	{
		$view = new TAuthView();
		$view->setUsers(' ?, joe ');
		$view->setRoles('admin');
		$view->setPermissions('blog.edit');
		$self = $view->getSelfTemplate();
		$this->assertSame('?, joe', $self->getUsers());
		$this->assertSame('admin', $self->getRoles());
		$this->assertSame('blog.edit', $self->getPermissions());

		$view->setUsers('');
		$this->assertSame('@', $view->getUsers());
	}

	// ================================================================================
	// Matching
	// ================================================================================

	public function testTemplateMatchesAuthenticatedByDefault()
	{
		$template = new TAuthViewTemplate();
		$this->assertTrue($template->isUserMatched($this->signIn('joe')));
		$this->assertFalse($template->isUserMatched($this->signIn('Guest', [], true)));
		$this->assertFalse($template->isUserMatched(null));
	}

	public function testTemplateUsers()
	{
		$template = new TAuthViewTemplate();
		$template->setUsers('*');
		$this->assertTrue($template->isUserMatched($this->signIn('Guest', [], true)));
		$template->setUsers('?');
		$this->assertTrue($template->isUserMatched($this->signIn('Guest', [], true)));
		$this->assertFalse($template->isUserMatched($this->signIn('joe')));
		$template->setUsers('Ann, JOE');
		$this->assertTrue($template->isUserMatched($this->signIn('joe')));
		$this->assertFalse($template->isUserMatched($this->signIn('bob')));
	}

	public function testTemplateRolesMatchAny()
	{
		$template = new TAuthViewTemplate();
		$template->setRoles('admin, editor');
		$this->assertTrue($template->isUserMatched($this->signIn('joe', ['Editor'])));
		$this->assertFalse($template->isUserMatched($this->signIn('joe', ['writer'])));
	}

	public function testTemplatePermissionsMatchAny()
	{
		$template = new TAuthViewTemplate();
		$template->setPermissions('blog.edit, blog.delete');
		$user = $this->signIn('joe', [], false, TAuthViewTestUser::class);
		$this->assertFalse($template->isUserMatched($user));
		$user->granted = ['blog.delete'];
		$this->assertTrue($template->isUserMatched($user));
	}

	public function testTemplateConditionsAreAnded()
	{
		$template = new TAuthViewTemplate();
		$template->setRoles('admin');
		$template->setPermissions('blog.edit');
		$user = $this->signIn('joe', ['admin'], false, TAuthViewTestUser::class);
		$this->assertFalse($template->isUserMatched($user));
		$user->granted = ['blog.edit'];
		$this->assertTrue($template->isUserMatched($user));
		$user->setRoles([]);
		$this->assertFalse($template->isUserMatched($user));
	}

	public function testTemplatePermissionsWithoutCanThrows()
	{
		$template = new TAuthViewTemplate();
		$template->setPermissions('blog.edit');
		$this->expectException(TConfigurationException::class);
		$template->isUserMatched($this->signIn('joe'));
	}

	public function testTemplatePermissionsNotCheckedWhenUsersFail()
	{
		$template = new TAuthViewTemplate();
		$template->setUsers('?');
		$template->setPermissions('blog.edit');
		$this->assertFalse($template->isUserMatched($this->signIn('joe')));
	}

	// ================================================================================
	// Single case
	// ================================================================================

	public function testSingleCaseRendersForAuthenticatedUser()
	{
		$this->signIn('joe');
		$this->assertSame('Hello <b>joe</b>', $this->renderView('<com:TAuthView>Hello <b><%= $this->User->Name %></b></com:TAuthView>'));
	}

	public function testSingleCaseRendersNothingForGuest()
	{
		$this->signOut();
		$this->assertSame('', $this->renderView('<com:TAuthView>Hello</com:TAuthView>'));
	}

	public function testSingleCaseRendersNothingWithoutUser()
	{
		PradoUnit::setProp(Prado::getApplication(), '_user', null);
		$this->assertSame('', $this->renderView('<com:TAuthView Users="*">Hello</com:TAuthView>'));
	}

	public function testSingleCaseWithConditions()
	{
		$template = '<com:TAuthView Roles="admin">[admin]</com:TAuthView>';
		$this->signIn('joe', ['admin']);
		$this->assertSame('[admin]', $this->renderView($template));
		$this->signIn('joe', ['writer']);
		$this->assertSame('', $this->renderView($template));
	}

	// ================================================================================
	// Several cases
	// ================================================================================

	public function testFirstMatchingChildRenders()
	{
		$this->signOut();
		$this->assertSame('[sign in]', $this->renderView(self::MULTI));
		$this->signIn('joe', ['admin'], false, TAuthViewTestUser::class)->granted = ['blog.edit'];
		$this->assertSame('[admin]', $this->renderView(self::MULTI));
		$this->signIn('joe', [], false, TAuthViewTestUser::class)->granted = ['blog.edit'];
		$this->assertSame('[edit]', $this->renderView(self::MULTI));
	}

	public function testSelfCaseIsFallback()
	{
		$this->signIn('joe', [], false, TAuthViewTestUser::class);
		$this->assertSame('[welcome]', $this->renderView(self::MULTI));
	}

	public function testSelfCaseWithConditionsIsStillLast()
	{
		$this->signIn('joe', ['admin']);
		$template = '<com:TAuthView ID="view" Roles="admin">[self]<com:TAuthViewTemplate>[child]</com:TAuthViewTemplate></com:TAuthView>';
		$this->assertSame('[child]', $this->renderView($template));
	}

	public function testNoMatchRendersNothing()
	{
		$this->signOut();
		$template = '<com:TAuthView><com:TAuthViewTemplate Roles="admin">[admin]</com:TAuthViewTemplate>[welcome]</com:TAuthView>';
		$this->assertSame('', $this->renderView($template));
	}

	// ================================================================================
	// Control tree
	// ================================================================================

	public function testOnlyChosenCaseJoinsTree()
	{
		$this->signIn('joe', ['admin']);
		$container = $this->build('<com:TAuthView ID="view">'
			. '<com:TAuthViewTemplate Roles="admin"><com:TLabel ID="AdminLabel" Text="a" /></com:TAuthViewTemplate>'
			. '<com:TLabel ID="WelcomeLabel" Text="w" />'
			. '</com:TAuthView>');
		$this->init($container);
		$view = $container->findControl('view');
		$this->assertSame($view->getTemplates()[0], $view->getActiveTemplate());
		$this->assertSame(1, $view->getControls()->getCount());

		$this->assertSame($view->getActiveTemplate(), $container->AdminLabel->getParent());
		$this->assertNotNull($container->AdminLabel->getPage());
		$this->assertSame($view->getSelfTemplate(), $container->WelcomeLabel->getParent());
		$this->assertNull($view->getSelfTemplate()->getParent());
		$this->assertNull($container->findControl('WelcomeLabel'));
	}

	public function testCaseIds()
	{
		$this->signIn('joe', ['admin']);
		$container = $this->build('<com:TAuthView ID="view">'
			. '<com:TAuthViewTemplate Users="?">g</com:TAuthViewTemplate>'
			. '<com:TAuthViewTemplate Roles="admin">a</com:TAuthViewTemplate>'
			. 'w</com:TAuthView>');
		$this->init($container);
		$view = $container->findControl('view');
		$this->assertSame('view_1', $view->getActiveTemplate()->getID());

		$this->signIn('joe');
		$this->loadAndPreRender($container);
		$this->assertSame('view_self', $view->getActiveTemplate()->getID());
	}

	public function testCaseKeepsItsOwnId()
	{
		$this->signIn('joe');
		$container = $this->build('<com:TAuthView ID="view"><com:TAuthViewTemplate ID="Members">m</com:TAuthViewTemplate></com:TAuthView>');
		$this->init($container);
		$this->assertSame('Members', $container->findControl('view')->getActiveTemplate()->getID());
	}

	public function testPreRenderSwitchesCaseAfterLogin()
	{
		$this->signOut();
		$container = $this->build(self::MULTI);
		$this->init($container);
		$view = $container->findControl('view');
		$guestCase = $view->getActiveTemplate();
		$this->assertSame($view->getTemplates()[0], $guestCase);

		$this->signIn('joe', ['admin']);
		$this->loadAndPreRender($container);
		$this->assertSame($view->getTemplates()[1], $view->getActiveTemplate());
		$this->assertNull($guestCase->getParent());
		$this->assertSame('[admin]', trim($this->render($container)));
	}

	public function testPreRenderClearsCaseAfterLogout()
	{
		$this->signIn('joe');
		$container = $this->build('<com:TAuthView ID="view">[welcome]</com:TAuthView>');
		$this->init($container);
		$this->signOut();
		$this->loadAndPreRender($container);
		$view = $container->findControl('view');
		$this->assertNull($view->getActiveTemplate());
		$this->assertSame(0, $view->getControls()->getCount());
		$this->assertSame('', trim($this->render($container)));
	}

	public function testTemplateOutsideAuthViewThrows()
	{
		$this->signIn('joe');
		$container = $this->build('<com:TAuthViewTemplate>x</com:TAuthViewTemplate>');
		$this->expectException(TConfigurationException::class);
		$this->init($container);
	}
}
