<?php
/**
 * TShellWriter class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Shell
 */

namespace Prado\Shell;

/**
 * TShellWriter class.
 *
 * Similar to the {@link THtmlWriter}, the TShellWriter writes and formats text
 * with color, and processes other commands to the terminal to another ITextWriter.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Shell
 * @since 4.2.0
 */
class TShellWriter extends \Prado\TComponent implements \Prado\IO\ITextWriter
{
	public const BOLD = 1;
	public const DARK = 2;
	public const ITALIC = 3;
	public const UNDERLINE = 4;
	public const BLINK = 5;
	public const REVERSE = 7;
	public const CONCEALED = 8;
	public const CROSSED = 9;
	public const FRAMED = 51;
	public const ENCIRCLED = 52;
	public const OVERLINED = 53;
	
	public const BLACK = 30;
	public const RED = 31;
	public const GREEN = 32;
	public const YELLOW = 33;
	public const BLUE = 34;
	public const MAGENTA = 35;
	public const CYAN = 36;
	public const LIGHT_GRAY = 37;
	// '256' => '38', //  38:2:<red>:<green>:<blue> or 38:5:<256-color>
	public const DEFAULT = 39;
	
	public const DARK_GRAY = 90;
	public const LIGHT_RED = 91;
	public const LIGHT_GREEN = 92;
	public const LIGHT_YELLOW = 93;
	public const LIGHT_BLUE = 94;
	public const LIGHT_MAGENTA = 95;
	public const LIGHT_CYAN = 96;
	public const WHITE = 97;
	
	public const BG_BLACK = 40;
	public const BG_RED = 41;
	public const BG_GREEN = 42;
	public const BG_YELLOW = 43;
	public const BG_BLUE = 44;
	public const BG_MAGENTA = 45;
	public const BG_CYAN = 46;
	public const BG_LIGHT_GRAY = 47;
	//'256' => '48', // 48:2:<red>:<green>:<blue>   48:5:<256-color>
	public const BG_DEFAULT = 49;
	
	public const BG_DARK_GRAY = 100;
	public const BG_LIGHT_RED = 101;
	public const BG_LIGHT_GREEN = 102;
	public const BG_LIGHT_YELLOW = 103;
	public const BG_LIGHT_BLUE = 104;
	public const BG_LIGHT_MAGENTA = 105;
	public const BG_LIGHT_CYAN = 106;
	public const BG_WHITE = 107;
	
	/**
	 * @var ITextWriter writer
	 */
	protected $_writer;
	
	/** @var bool is color supported on tty */
	protected $_color;
	
	/**
	 * Constructor.
	 * @param ITextWriter $writer a writer that THtmlWriter will pass its rendering result to
	 */
	public function __construct($writer)
	{
		$this->_writer = $writer;
		$this->_color = $this->isColorSupported();
		parent::__construct();
	}
	
	/**
	 * @return ITextWriter the writer output to this class
	 */
	public function getWriter()
	{
		return $this->_writer;
	}
	
	/**
	 * @param ITextWriter $writer the writer output to this class
	 */
	public function setWriter($writer)
	{
		$this->_writer = $writer;
	}

	/**
	 * Flushes the rendering result.
	 * This will invoke the underlying writer's flush method.
	 * @return string the content being flushed
	 */
	public function flush()
	{
		return $this->_writer->flush();
	}

	/**
	 * Renders a string.
	 * @param string $str string to be rendered
	 * @param null|mixed $attr
	 */
	public function write($str, $attr = null)
	{
		if ($this->_color && $attr) {
			if (!is_array($attr)) {
				$attr = [$attr];
			}
			$this->_writer->write("\033[" . implode(';', $attr) . 'm');
		}
		$this->_writer->write($str);
		if ($this->_color && $attr) {
			$this->_writer->write("\033[0m");
		}
	}

	/**
	 * Renders a string and appends a newline to it.
	 * @param string $str string to be rendered
	 * @param null|mixed $attr
	 */
	public function writeLine($str = '', $attr = null)
	{
		if ($this->_color && $attr) {
			if (!is_array($attr)) {
				$attr = [$attr];
			}
			$this->_writer->write("\033[" . implode(';', $attr) . 'm');
		}
		$this->_writer->write($str . "\n");
		if ($this->_color && $attr) {
			$this->_writer->write("\033[0m");
		}
	}
	
	/**
	 * @param string $str the string to ANSI format.
	 * @param string|string[] $attr the attributes to format.
	 * @return string $str in the format of $attr.
	 */
	public function format($str, $attr)
	{
		if(!$this->_color) {
			return $str;
		}
		if (!is_array($attr)) {
			$attr = [$attr];
		}
		return "\033[" . implode(';', $attr) . 'm' . $str . "\033[0m";
	}
	
	/**
	 * is color TTY supported
	 * @return bool color is supported
	 */
	public function isColorSupported()
	{
		if (static::isRunningOnWindows()) {
			return getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON';
		}
	
		return function_exists('posix_isatty') && @posix_isatty(STDOUT) && strpos(getenv('TERM'), '256color') !== false;
	}

	/**
	 * Moves the terminal cursor down by sending ANSI control code CUD to the terminal.
	 * If the cursor is already at the edge of the screen, this has no effect.
	 * @param int $rows number of rows the cursor should be moved down
	 */
	public function moveCursorDown($rows = 1)
	{
		$this->_writer->write("\033[" . (int) $rows . 'B');
	}

	/**
	 * Moves the terminal cursor forward by sending ANSI control code CUF to the terminal.
	 * If the cursor is already at the edge of the screen, this has no effect.
	 * @param int $steps number of steps the cursor should be moved forward
	 */
	public function moveCursorForward($steps = 1)
	{
		$this->_writer->write("\033[" . (int) $steps . 'C');
	}

	/**
	 * Moves the terminal cursor backward by sending ANSI control code CUB to the terminal.
	 * If the cursor is already at the edge of the screen, this has no effect.
	 * @param int $steps number of steps the cursor should be moved backward
	 */
	public function moveCursorBackward($steps = 1)
	{
		$this->_writer->write("\033[" . (int) $steps . 'D');
	}

	/**
	 * Moves the terminal cursor to the beginning of the next line by sending ANSI control code CNL to the terminal.
	 * @param int $lines number of lines the cursor should be moved down
	 */
	public function moveCursorNextLine($lines = 1)
	{
		$this->_writer->write("\033[" . (int) $lines . 'E');
	}

	/**
	 * Moves the terminal cursor to the beginning of the previous line by sending ANSI control code CPL to the terminal.
	 * @param int $lines number of lines the cursor should be moved up
	 */
	public function moveCursorPrevLine($lines = 1)
	{
		$this->_writer->write("\033[" . (int) $lines . 'F');
	}

	/**
	 * Moves the cursor to an absolute position given as column and row by sending ANSI control code CUP or CHA to the terminal.
	 * @param int $column 1-based column number, 1 is the left edge of the screen.
	 * @param null|int $row 1-based row number, 1 is the top edge of the screen. if not set, will move cursor only in current line.
	 */
	public function moveCursorTo($column, $row = null)
	{
		if ($row === null) {
			$this->_writer->write("\033[" . (int) $column . 'G');
		} else {
			$this->_writer->write("\033[" . (int) $row . ';' . (int) $column . 'H');
		}
	}

	/**
	 * Scrolls whole page up by sending ANSI control code SU to the terminal.
	 * New lines are added at the bottom. This is not supported by ANSI.SYS used in windows.
	 * @param int $lines number of lines to scroll up
	 */
	public function scrollUp($lines = 1)
	{
		$this->_writer->write("\033[" . (int) $lines . 'S');
	}

	/**
	 * Scrolls whole page down by sending ANSI control code SD to the terminal.
	 * New lines are added at the top. This is not supported by ANSI.SYS used in windows.
	 * @param int $lines number of lines to scroll down
	 */
	public function scrollDown($lines = 1)
	{
		$this->_writer->write("\033[" . (int) $lines . 'T');
	}

	/**
	 * Saves the current cursor position by sending ANSI control code SCP to the terminal.
	 * Position can then be restored with {@link restoreCursorPosition}.
	 */
	public function saveCursorPosition()
	{
		$this->_writer->write("\033[s");
	}

	/**
	 * Restores the cursor position saved with {@link saveCursorPosition} by sending ANSI control code RCP to the terminal.
	 */
	public function restoreCursorPosition()
	{
		$this->_writer->write("\033[u");
	}

	/**
	 * Hides the cursor by sending ANSI DECTCEM code ?25l to the terminal.
	 * Use {@link showCursor} to bring it back.
	 * Do not forget to show cursor when your application exits. Cursor might stay hidden in terminal after exit.
	 */
	public function hideCursor()
	{
		$this->_writer->write("\033[?25l");
	}

	/**
	 * Will show a cursor again when it has been hidden by {@link hideCursor}  by sending ANSI DECTCEM code ?25h to the terminal.
	 */
	public function showCursor()
	{
		$this->_writer->write("\033[?25h");
	}

	/**
	 * Clears entire screen content by sending ANSI control code ED with argument 2 to the terminal.
	 * Cursor position will not be changed.
	 * **Note:** ANSI.SYS implementation used in windows will reset cursor position to upper left corner of the screen.
	 */
	public function clearScreen()
	{
		$this->_writer->write("\033[2J");
	}

	/**
	 * Clears text from cursor to the beginning of the screen by sending ANSI control code ED with argument 1 to the terminal.
	 * Cursor position will not be changed.
	 */
	public function clearScreenBeforeCursor()
	{
		$this->_writer->write("\033[1J");
	}

	/**
	 * Clears text from cursor to the end of the screen by sending ANSI control code ED with argument 0 to the terminal.
	 * Cursor position will not be changed.
	 */
	public function clearScreenAfterCursor()
	{
		$this->_writer->write("\033[0J");
	}

	/**
	 * Clears the line, the cursor is currently on by sending ANSI control code EL with argument 2 to the terminal.
	 * Cursor position will not be changed.
	 */
	public function clearLine()
	{
		$this->_writer->write("\033[2K");
	}

	/**
	 * Clears text from cursor position to the beginning of the line by sending ANSI control code EL with argument 1 to the terminal.
	 * Cursor position will not be changed.
	 */
	public function clearLineBeforeCursor()
	{
		$this->_writer->write("\033[1K");
	}

	/**
	 * Clears text from cursor position to the end of the line by sending ANSI control code EL with argument 0 to the terminal.
	 * Cursor position will not be changed.
	 */
	public function clearLineAfterCursor()
	{
		$this->_writer->write("\033[0K");
	}
	

	/**
	 * Returns terminal screen size.
	 *
	 * Usage:
	 *
	 * <code>
	 * [$width, $height] = TShellWriter::getScreenSize();
	 * </code>
	 *
	 * @param bool $refresh whether to force checking and not re-use cached size value.
	 * This is useful to detect changing window size while the application is running but may
	 * not get up to date values on every terminal.
	 * @return array|bool An array of ($width, $height) or false when it was not able to determine size.
	 */
	public static function getScreenSize($refresh = false)
	{
		static $size;
		if ($size !== null && !$refresh) {
			return $size;
		}

		if (static::isRunningOnWindows()) {
			$output = [];
			exec('mode con', $output);
			if (isset($output[1]) && strpos($output[1], 'CON') !== false) {
				return $size = [(int) preg_replace('~\D~', '', $output[4]), (int) preg_replace('~\D~', '', $output[3])];
			}
		} else {
			// try stty if available
			$stty = [];
			if (exec('stty -a 2>&1', $stty)) {
				$stty = implode(' ', $stty);

				// Linux stty output
				if (preg_match('/rows\s+(\d+);\s*columns\s+(\d+);/mi', $stty, $matches)) {
					return $size = [(int) $matches[2], (int) $matches[1]];
				}

				// MacOS stty output
				if (preg_match('/(\d+)\s+rows;\s*(\d+)\s+columns;/mi', $stty, $matches)) {
					return $size = [(int) $matches[2], (int) $matches[1]];
				}
			}

			// fallback to tput, which may not be updated on terminal resize
			if (($width = (int) exec('tput cols 2>&1')) > 0 && ($height = (int) exec('tput lines 2>&1')) > 0) {
				return $size = [$width, $height];
			}

			// fallback to ENV variables, which may not be updated on terminal resize
			if (($width = (int) getenv('COLUMNS')) > 0 && ($height = (int) getenv('LINES')) > 0) {
				return $size = [$width, $height];
			}
		}

		return $size = false;
	}

	/**
	 * Word wrap text with indentation to fit the screen size.
	 *
	 * If screen size could not be detected, or the indentation is greater than the screen size, the text will not be wrapped.
	 *
	 * The first line will **not** be indented, so `TShellWriter::wrapText("Lorem ipsum dolor sit amet.", 4)` will result in the
	 * following output, given the screen width is 16 characters:
	 *
	 * <code>
	 * Lorem ipsum
	 *     dolor sit
	 *     amet.
	 * </code>
	 *
	 * @param string $text the text to be wrapped
	 * @param int $indent number of spaces to use for indentation.
	 * @param bool $refresh whether to force refresh of screen size.
	 * This will be passed to {@link getScreenSize}.
	 * @return string the wrapped text.
	 */
	public function wrapText($text, $indent = 0, $refresh = false)
	{
		$size = static::getScreenSize($refresh);
		if ($size === false || $size[0] <= $indent) {
			return $text;
		}
		$pad = str_repeat(' ', $indent);
		$lines = explode("\n", wordwrap($text, $size[0] - $indent, "\n"));
		$first = true;
		foreach ($lines as $i => $line) {
			if ($first) {
				$first = false;
				continue;
			}
			$lines[$i] = $pad . $line;
		}

		return implode("\n", $lines);
	}

	/**
	 * Returns true if the console is running on windows.
	 * @return bool
	 */
	public static function isRunningOnWindows()
	{
		return DIRECTORY_SEPARATOR === '\\';
	}
}
