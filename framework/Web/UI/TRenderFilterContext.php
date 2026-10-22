<?php

/**
 * TRenderFilterContext class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

use Prado\IO\TTextWriter;

/**
 * TRenderFilterContext class
 *
 * TRenderFilterContext tracks the render filters capturing the output of a
 * {@see THtmlWriter} and the controls exempt from them. {@see TControl::renderControl}
 * drives it; {@see THtmlWriter::getRenderFilterContext} holds it.
 *
 * A filter sees output unless the nearest {@see TControl::getRenderFilterExempt}
 * setting between the filter and the output is `true`:
 *
 * | Control | Inside | Output |
 * |---|---|---|
 * | `RenderFilterExempt` `true` | a capturing filter | renders to a buffer; each filter capturing it receives a placeholder comment |
 * | `RenderFilterExempt` `false` | an exempt control | renders between marker comments; the exempt control's placeholders leave it out |
 * | `RenderFilterExempt` `true` | no capturing filter | renders unchanged |
 * | `RenderFilterExempt` `false` | no exempt control | renders unchanged |
 *
 * The exempt control's own `onRenderFilter` handlers, and the filters inside it,
 * see its output. The outermost filter restores the placeholders after its
 * handlers run ({@see endCapture}). A handler that removes a placeholder removes
 * the exempt output.
 *
 * Output a control renders into another `THtmlWriter` has no context of its own,
 * so a control exempts itself only from the filters of its own writer.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @see TControl::getRenderFilterExempt()
 * @see TRenderFilterParameter
 */
class TRenderFilterContext
{
	/**
	 * The comment data that starts each placeholder of exempt output.
	 */
	public const EXEMPT_TOKEN = 'prado-exempt-';

	/**
	 * The comment data that starts each marker of re-enabled output.
	 */
	public const FILTER_MARKER = 'prado-filter-';

	/**
	 * @var int the number of filters capturing output
	 */
	private int $_depth = 0;

	/**
	 * @var \Prado\IO\ITextWriter[] the writers of the exempt controls rendering, innermost last
	 */
	private array $_exemptWriters = [];

	/**
	 * @var array<string, string> the exempt output, by placeholder
	 */
	private array $_exemptOutput = [];

	/**
	 * @var string the random part of the placeholders and markers
	 */
	private string $_nonce;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->_nonce = bin2hex(random_bytes(4));
	}

	/**
	 * @return bool whether a filter is capturing output
	 */
	public function getIsCapturing(): bool
	{
		return $this->_depth > 0;
	}

	/**
	 * @return bool whether an exempt control is rendering into a buffer
	 */
	public function getIsExempt(): bool
	{
		return $this->_exemptWriters !== [];
	}

	/**
	 * Records that a filter starts capturing output.
	 */
	public function beginCapture(): void
	{
		$this->_depth++;
	}

	/**
	 * Records that a filter stops capturing output, and returns its filtered output.
	 * When the outermost filter stops, the placeholders in `$output` are restored.
	 *
	 * @param string $output the filtered output
	 * @return string the output, with the exempt output restored by the outermost filter
	 */
	public function endCapture(string $output): string
	{
		if (--$this->_depth > 0 || $this->_exemptOutput === []) {
			return $output;
		}
		$prefix = '<!--' . self::EXEMPT_TOKEN . $this->_nonce . '-';
		while (str_contains($output, $prefix) && ($restored = strtr($output, $this->_exemptOutput)) !== $output) {
			$output = $restored;
		}
		$this->_exemptOutput = [];
		return $output;
	}

	/**
	 * Starts an exempt control's output. While a filter is capturing, the output
	 * renders to a buffer that {@see endExempt} writes as placeholders.
	 *
	 * @param THtmlWriter $writer the writer of the control
	 * @return bool whether the output renders to a buffer
	 */
	public function beginExempt(THtmlWriter $writer): bool
	{
		if (!$this->getIsCapturing()) {
			return false;
		}
		$this->_exemptWriters[] = $writer->getWriter();
		$writer->setWriter(new TTextWriter());
		return true;
	}

	/**
	 * Ends an exempt control's output and writes it as placeholders. Re-enabled
	 * output between markers ({@see beginFilter}) is written in place. The markers
	 * remain until the outermost exempt control ends.
	 *
	 * @param THtmlWriter $writer the writer of the control
	 */
	public function endExempt(THtmlWriter $writer): void
	{
		$output = $writer->getWriter()->flush();
		$writer->setWriter(array_pop($this->_exemptWriters));
		$keepMarkers = $this->getIsExempt();
		$marker = '/(<!--\/?' . self::FILTER_MARKER . $this->_nonce . '-->)/';
		$depth = 0;
		$result = '';
		foreach (preg_split($marker, $output, -1, PREG_SPLIT_DELIM_CAPTURE) as $i => $part) {
			if ($i % 2 === 0) {
				$result .= ($depth > 0) ? $part : $this->exemptToken($part);
				continue;
			}
			$depth = max(0, $depth + ($part[4] === '/' ? -1 : 1));
			if ($keepMarkers) {
				$result .= $part;
			}
		}
		$writer->write($result);
	}

	/**
	 * Starts re-enabled output. Inside an exempt control, a marker comment
	 * starts the output that the filters see.
	 *
	 * @param THtmlWriter $writer the writer of the control
	 * @return bool whether a marker is written
	 */
	public function beginFilter(THtmlWriter $writer): bool
	{
		if (!$this->getIsExempt()) {
			return false;
		}
		$writer->write('<!--' . self::FILTER_MARKER . $this->_nonce . '-->');
		return true;
	}

	/**
	 * Ends re-enabled output that {@see beginFilter} started.
	 *
	 * @param THtmlWriter $writer the writer of the control
	 */
	public function endFilter(THtmlWriter $writer): void
	{
		$writer->write('<!--/' . self::FILTER_MARKER . $this->_nonce . '-->');
	}

	/**
	 * Stores exempt output for {@see endCapture} to restore.
	 *
	 * @param string $output the exempt output
	 * @return string the placeholder comment, or '' for empty output
	 */
	private function exemptToken(string $output): string
	{
		if ($output === '') {
			return '';
		}
		$token = '<!--' . self::EXEMPT_TOKEN . $this->_nonce . '-' . count($this->_exemptOutput) . '-->';
		$this->_exemptOutput[$token] = $output;
		return $token;
	}
}
