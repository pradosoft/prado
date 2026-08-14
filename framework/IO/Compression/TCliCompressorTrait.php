<?php

/**
 * TCliCompressorTrait trait file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Compression;

use Prado\Exceptions\TIOException;

/**
 * TCliCompressorTrait trait.
 *
 * The command-line backend shared by codecs that transform data through a system command
 * rather than a PHP extension, for a format PHP cannot handle in-process.  A using class
 * names the command through {@see commands()} and the arguments through {@see compressArgs()}
 * and {@see decompressArgs()}; the trait locates the command on the `PATH`, runs it shell-free,
 * pipes the data through its standard input and output, and reports a missing command or a
 * non-zero exit as a {@see TIOException}.
 *
 * The trait serves both shapes of CLI codec: {@see TCliCompressor} mixes it in for a codec
 * that is only ever a command, and a {@see TBuiltinCompressor} subclass mixes it in to fall
 * back to the command when its PHP extension is absent.  The `cli`-prefixed methods stay clear
 * of the {@see TBuiltinCompressor} surface so the two compose without collision.
 *
 * The command runs through {@see https://www.php.net/proc_open proc_open} with an argument
 * vector, so no shell parses the arguments and untrusted data cannot inject a command.  The
 * input and output stream through pipes with a non-blocking pump, so a payload larger than a
 * pipe buffer neither deadlocks nor stages to a temporary file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TCliCompressorTrait
{
	/**
	 * Returns the candidate command names, most preferred first; the first found on the
	 * `PATH` (or given as an absolute path) is used.
	 * @return string[] The candidate command names.
	 */
	abstract protected static function commands(): array;

	/**
	 * Returns the command arguments that compress standard input to standard output.
	 * @param int $level The compression level, or -1 for the command's default.
	 * @return string[] The argument vector after the command name.
	 */
	abstract protected static function compressArgs(int $level): array;

	/**
	 * Returns the command arguments that decompress standard input to standard output.
	 * @return string[] The argument vector after the command name.
	 */
	abstract protected static function decompressArgs(): array;

	/**
	 * Returns the bytes pumped to or from the command per pass.
	 * @return int The chunk size in bytes.
	 */
	protected static function cliChunkSize(): int
	{
		return 65536;
	}

	/**
	 * Returns whether the backing command is available on this system.
	 * @return bool Whether a candidate command was found.
	 */
	protected static function cliAvailable(): bool
	{
		return static::cliResolveCommand() !== null;
	}

	/**
	 * Compresses a byte string by running the command over a pipe.
	 * @param string $data The raw bytes.
	 * @param int $level The compression level, or -1 for the command's default.
	 * @throws TIOException When the command is missing or fails.
	 * @return string The compressed bytes.
	 */
	protected static function cliCompress(string $data, int $level): string
	{
		return static::cliRun(static::compressArgs($level), $data);
	}

	/**
	 * Decompresses a byte string by running the command over a pipe.
	 * @param string $data The compressed bytes.
	 * @throws TIOException When the command is missing, the data is corrupt, or the command fails.
	 * @return string The decompressed bytes.
	 */
	protected static function cliDecompress(string $data): string
	{
		return static::cliRun(static::decompressArgs(), $data);
	}

	/**
	 * Runs the backing command with the given arguments, feeding the input to its standard
	 * input and returning its standard output.
	 * @param string[] $args The argument vector after the command name.
	 * @param string $input The bytes to feed to the command.
	 * @throws TIOException When the command is missing, cannot start, or exits non-zero.
	 * @return string The command's standard output.
	 */
	protected static function cliRun(array $args, string $input): string
	{
		$command = static::cliResolveCommand();
		if ($command === null) {
			throw new TIOException('clicompressor_command_missing', static::NAME, implode("', '", static::commands()));
		}
		$descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
		$process = @proc_open([$command, ...$args], $descriptors, $pipes);
		if (!is_resource($process)) {
			throw new TIOException('clicompressor_process_failed', static::NAME, $command);
		}
		[$stdout, $stderr] = static::cliPump($pipes, $input);
		$exit = proc_close($process);
		if ($exit !== 0) {
			throw new TIOException('clicompressor_command_failed', static::NAME, $command, $exit, trim($stderr));
		}
		return $stdout;
	}

	/**
	 * Pumps the input to the command's standard input while draining its standard output and
	 * error, so neither side blocks on a full pipe buffer.  Closes the input pipe once the
	 * whole input is written, signaling end of input to the command.
	 * @param array{0: resource, 1: resource, 2: resource} $pipes The command's standard pipes.
	 * @param string $input The bytes to feed to the command.
	 * @return array{0: string, 1: string} The standard output and standard error.
	 */
	protected static function cliPump(array $pipes, string $input): array
	{
		[$in, $out, $err] = $pipes;
		stream_set_blocking($in, false);
		stream_set_blocking($out, false);
		stream_set_blocking($err, false);

		$chunkSize = static::cliChunkSize();
		$stdout = '';
		$stderr = '';
		$written = 0;
		$length = strlen($input);
		if ($length === 0) {
			fclose($in);
			$in = null;
		}

		while (true) {
			$read = [];
			if (!feof($out)) {
				$read[] = $out;
			}
			if (!feof($err)) {
				$read[] = $err;
			}
			$write = ($in !== null) ? [$in] : [];
			if ($read === [] && $write === []) {
				break;
			}
			$except = null;
			if (@stream_select($read, $write, $except, null) === false) {
				break;
			}
			foreach ($read as $stream) {
				$chunk = fread($stream, $chunkSize);
				if ($stream === $out) {
					$stdout .= $chunk;
				} else {
					$stderr .= $chunk;
				}
			}
			if ($write !== [] && $in !== null) {
				$n = @fwrite($in, substr($input, $written, $chunkSize));
				$written += ($n === false) ? 0 : $n;
				if ($n === false || $n === 0 || $written >= $length) {
					fclose($in);
					$in = null;
				}
			}
		}
		if ($in !== null) {
			fclose($in);
		}
		fclose($out);
		fclose($err);
		return [$stdout, $stderr];
	}

	/**
	 * Resolves the first candidate command that exists on the system.
	 * @return ?string The command path, or null when none is found.
	 */
	protected static function cliResolveCommand(): ?string
	{
		foreach (static::commands() as $name) {
			$path = static::cliLocate($name);
			if ($path !== null) {
				return $path;
			}
		}
		return null;
	}

	/**
	 * Locates an executable by name on the `PATH`, or accepts an absolute path.  The lookup
	 * scans the `PATH` in PHP rather than spawning a locator process.
	 * @param string $name The command name or absolute path.
	 * @return ?string The executable path, or null when it is not found.
	 */
	protected static function cliLocate(string $name): ?string
	{
		$isWindows = DIRECTORY_SEPARATOR === '\\';
		$extensions = $isWindows ? explode(';', (string) (getenv('PATHEXT') ?: '.EXE;.CMD;.BAT')) : [''];
		if (str_contains($name, DIRECTORY_SEPARATOR)) {
			return is_file($name) && is_executable($name) ? $name : null;
		}
		foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $dir) {
			if ($dir === '') {
				continue;
			}
			foreach ($extensions as $extension) {
				$candidate = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name . $extension;
				if (is_file($candidate) && is_executable($candidate)) {
					return $candidate;
				}
			}
		}
		return null;
	}
}
