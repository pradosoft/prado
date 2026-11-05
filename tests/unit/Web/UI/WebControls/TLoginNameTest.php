<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\Security\IUser;
use Prado\Security\TUser;
use Prado\Security\TUserManager;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\WebControls\TLoginName;
use Prado\Web\UI\WebControls\TWebControl;

/**
 * A TUser with a display name, for NameProperty.
 */
class TLoginNameTestUser extends TUser
{
	private string $_displayName = '';

	public function getDisplayName(): string
	{
		return $this->_displayName;
	}

	public function setDisplayName(string $value): void
	{
		$this->_displayName = $value;
	}
}

/**
 * An IUser that is not a TComponent.
 */
class TLoginNamePlainUser implements IUser
{
	public function getName() { return 'plain'; }
	public function setName($value) {}
	public function getIsGuest() { return false; }
	public function setIsGuest($value) {}
	public function getRoles() { return []; }
	public function setRoles($value) {}
	public function isInRole($role) { return false; }
	public function saveToString() { return ''; }
	public function loadFromString($string) { return $this; }
}

class TLoginNameTest extends TestCase
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

	private function signIn(string $name, bool $guest = false, string $class = TUser::class): TUser
	{
		$user = new $class(new TUserManager());
		$user->setName($name);
		$user->setIsGuest($guest);
		Prado::getApplication()->setUser($user);
		return $user;
	}

	public function testDefaults()
	{
		$control = new TLoginName();
		$this->assertInstanceOf(TWebControl::class, $control);
		$this->assertSame('', $control->getFormatString());
		$this->assertSame('Name', $control->getNameProperty());
		$this->assertFalse($control->getDisplayGuest());
		$this->assertSame('span', PradoUnit::invoke($control, 'getTagName'));
	}

	public function testProperties()
	{
		$control = new TLoginName();
		$control->setFormatString('Hi {0}');
		$control->setNameProperty('DisplayName');
		$control->setDisplayGuest('true');
		$this->assertSame('Hi {0}', $control->getFormatString());
		$this->assertSame('DisplayName', $control->getNameProperty());
		$this->assertTrue($control->getDisplayGuest());

		$control->setNameProperty('');
		$this->assertSame('Name', $control->getNameProperty());
	}

	public function testAuthenticatedUserRendersName()
	{
		$this->signIn('joe');
		$control = new TLoginName();
		$this->assertSame('joe', $control->getUserName());
		$this->assertSame('<span>joe</span>', $this->render($control));
	}

	public function testNoUserRendersNothing()
	{
		PradoUnit::setProp(Prado::getApplication(), '_user', null);
		$control = new TLoginName();
		$this->assertSame('', $control->getUserName());
		$this->assertSame('', $this->render($control));
	}

	public function testGuestRendersNothingByDefault()
	{
		$this->signIn('Guest', true);
		$control = new TLoginName();
		$this->assertSame('', $control->getUserName());
		$this->assertSame('', $this->render($control));
	}

	public function testDisplayGuestRendersGuestName()
	{
		$this->signIn('Guest', true);
		$control = new TLoginName();
		$control->setDisplayGuest(true);
		$this->assertSame('<span>Guest</span>', $this->render($control));
	}

	public function testEmptyNameRendersNothing()
	{
		$this->signIn('');
		$this->assertSame('', $this->render(new TLoginName()));
	}

	public function testNameIsEncoded()
	{
		$this->signIn('<b>"x"&</b>');
		$this->assertSame('<span>&lt;b&gt;&quot;x&quot;&amp;&lt;/b&gt;</span>', $this->render(new TLoginName()));
	}

	public function testFormatString()
	{
		$this->signIn('a<b');
		$control = new TLoginName();
		$control->setFormatString('Signed in as <b>{0}</b> ({0})');
		$this->assertSame('<span>Signed in as <b>a&lt;b</b> (a&lt;b)</span>', $this->render($control));
	}

	public function testFormatStringWithoutPlaceholder()
	{
		$this->signIn('joe');
		$control = new TLoginName();
		$control->setFormatString('Signed in');
		$this->assertSame('<span>Signed in</span>', $this->render($control));
	}

	public function testNameProperty()
	{
		$user = $this->signIn('joe', false, TLoginNameTestUser::class);
		$user->setDisplayName('Joe <Smith>');
		$control = new TLoginName();
		$control->setNameProperty('DisplayName');
		$this->assertSame('<span>Joe &lt;Smith&gt;</span>', $this->render($control));
	}

	public function testNamePropertyEmptyRendersNothing()
	{
		$this->signIn('joe', false, TLoginNameTestUser::class);
		$control = new TLoginName();
		$control->setNameProperty('DisplayName');
		$this->assertSame('', $this->render($control));
	}

	public function testNamePropertyUndefinedThrows()
	{
		$this->signIn('joe');
		$control = new TLoginName();
		$control->setNameProperty('NoSuchProperty');
		$this->expectException(TInvalidOperationException::class);
		$control->getUserName();
	}

	public function testNamePropertyOnPlainUserUsesGetName()
	{
		Prado::getApplication()->setUser(new TLoginNamePlainUser());
		$control = new TLoginName();
		$control->setNameProperty('DisplayName');
		$this->assertSame('<span>plain</span>', $this->render($control));
	}

	public function testAttributesRender()
	{
		$this->signIn('joe');
		$control = new TLoginName();
		$control->setID('who');
		$control->setCssClass('user');
		$this->assertSame('<span id="who" class="user">joe</span>', $this->render($control));
	}
}
