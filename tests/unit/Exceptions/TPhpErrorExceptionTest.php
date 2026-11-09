<?php

/**
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Exceptions;

use PHPUnit\Framework\Attributes\DataProvider;
use Prado\Exceptions\TPhpErrorException;
use Prado\Exceptions\TPhpFatalErrorException;
use Prado\Exceptions\TSystemException;

/**
 * Tests for {@see TPhpErrorException}: the error type label in the message and
 * the fatal types of {@see TPhpErrorException::isFatalError()}.
 */
class TPhpErrorExceptionTest extends \PHPUnit\Framework\TestCase
{
	// ── Construction ──────────────────────────────────────────────────────────

	public function testMessageCarriesTypeLabelMessageLineAndFile(): void
	{
		$ex = new TPhpErrorException(E_COMPILE_WARNING, 'bad declare', '/app/index.php', 12);

		$this->assertInstanceOf(TSystemException::class, $ex);
		$this->assertSame('[Compile Warning] bad declare (@line 12 in file /app/index.php).', $ex->getMessage());
	}

	public function testUnknownTypeIsLabeledUnknownError(): void
	{
		$ex = new TPhpErrorException(0, 'odd', '/app/index.php', 3);

		$this->assertSame('[Unknown Error] odd (@line 3 in file /app/index.php).', $ex->getMessage());
	}

	public function testFatalErrorExceptionIsAPhpErrorException(): void
	{
		$ex = new TPhpFatalErrorException(E_ERROR, 'boom', '/app/index.php', 7);

		$this->assertInstanceOf(TPhpErrorException::class, $ex);
		$this->assertSame('[Error] boom (@line 7 in file /app/index.php).', $ex->getMessage());
	}

	// ── isFatalError() ────────────────────────────────────────────────────────

	/**
	 * @return array<string, array{int, bool}> the error type and whether PHP stops after it.
	 */
	public static function errorTypeProvider(): array
	{
		return [
			'E_ERROR' => [E_ERROR, true],
			'E_PARSE' => [E_PARSE, true],
			'E_CORE_ERROR' => [E_CORE_ERROR, true],
			'E_COMPILE_ERROR' => [E_COMPILE_ERROR, true],
			// PHP continues after a warning.
			'E_CORE_WARNING' => [E_CORE_WARNING, false],
			'E_COMPILE_WARNING' => [E_COMPILE_WARNING, false],
			'E_WARNING' => [E_WARNING, false],
			'E_NOTICE' => [E_NOTICE, false],
			'E_DEPRECATED' => [E_DEPRECATED, false],
			// Prado::phpErrorHandler() throws for these before PHP stops.
			'E_RECOVERABLE_ERROR' => [E_RECOVERABLE_ERROR, false],
			'E_USER_ERROR' => [E_USER_ERROR, false],
		];
	}

	#[DataProvider('errorTypeProvider')]
	public function testIsFatalErrorByType(int $type, bool $fatal): void
	{
		$error = ['type' => $type, 'message' => 'm', 'file' => __FILE__, 'line' => __LINE__];

		$this->assertSame($fatal, TPhpErrorException::isFatalError($error));
	}

	public function testIsFatalErrorWithoutTypeIsFalse(): void
	{
		$this->assertFalse(TPhpErrorException::isFatalError([]));
		$this->assertFalse(TPhpErrorException::isFatalError(['message' => 'm', 'file' => __FILE__, 'line' => 1]));
		$this->assertFalse(TPhpErrorException::isFatalError(['type' => null]));
	}

	public function testIsFatalErrorWithNullErrorIsFalse(): void
	{
		// error_get_last() returns null when no error occurred.
		$this->assertFalse(TPhpErrorException::isFatalError(null));
	}

	public function testIsFatalErrorBitMaskIsNotAFatalType(): void
	{
		// A bit mask holding a fatal type is not a fatal type.
		$this->assertFalse(TPhpErrorException::isFatalError(['type' => E_ERROR | E_WARNING]));
		$this->assertFalse(TPhpErrorException::isFatalError(['type' => E_ALL]));
	}
}
