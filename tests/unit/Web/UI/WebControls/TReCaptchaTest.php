<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Prado;
use Prado\Util\Log\TLogger;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TReCaptcha;
use Prado\Web\UI\WebControls\TReCaptchaValidator;

class TReCaptchaTest extends \PHPUnit\Framework\TestCase
{
	public static function provideDeprecatedClasses(): array
	{
		return [
			'TReCaptcha' => [TReCaptcha::class, 'TReCaptcha2'],
			'TReCaptchaValidator' => [TReCaptchaValidator::class, 'TReCaptcha2Validator'],
		];
	}

	/**
	 * @dataProvider provideDeprecatedClasses
	 * @param string $class
	 * @param string $replacement
	 */
	public function testIsDeprecatedInFavorOfTheV2Class(string $class, string $replacement): void
	{
		$doc = (new \ReflectionClass($class))->getDocComment();
		self::assertMatchesRegularExpression('/@deprecated 4\.4\.0 .*' . $replacement . '/', $doc);
	}

	public function testInitLogsADeprecationWarning(): void
	{
		$captcha = new TReCaptcha();
		$captcha->setPage(new TPage());
		$captcha->onInit(null);

		$logs = Prado::getLogger()->getLogs(TLogger::WARNING, TReCaptcha::class);
		self::assertNotEmpty($logs);
		self::assertStringContainsString('TReCaptcha is deprecated', end($logs)[TLogger::LOG_MESSAGE]);
	}
}
