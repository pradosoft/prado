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
 * A filter of a control with {@see TControl::getRenderFilterDepth} sees the output of
 * the controls at most that many levels below the control. Marker comments bound
 * the output of each control from two levels to one level past the limit
 * ({@see beginControl}); {@see createDepthParameter} gives the markers to the
 * {@see TRenderFilterParameter}, which hides the deeper output from the handlers
 * ({@see TRenderFilterParameter::getDepthLimit}). The filters of the deeper controls,
 * and the filters around the control, see the output. {@see endCapture} removes
 * the markers of the filter.
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
	 * The comment data that starts each marker of a control level.
	 */
	public const DEPTH_MARKER = 'prado-depth-';

	/**
	 * @var array[] the filters capturing output, innermost last: the serial, the depth
	 *   limit, and the depth of the control rendering below the filtering control
	 */
	private array $_filters = [];

	/**
	 * @var int the number of filters that have captured output
	 */
	private int $_serial = 0;

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
		return $this->_filters !== [];
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
	 *
	 * The controls rendering into the output are `$depth` levels below the filtering
	 * control, so a control rendered alone in a callback starts below its ancestors
	 * ({@see wrapDepth}).
	 * @param int $depthLimit the {@see TControl::getRenderFilterDepth} of the filtering control
	 * @param int $depth the levels below the filtering control of the output
	 */
	public function beginCapture(int $depthLimit = 0, int $depth = 0): void
	{
		$this->_filters[] = [++$this->_serial, max(0, $depthLimit), max(0, $depth)];
	}

	/**
	 * Records that a filter stops capturing output, and returns its filtered output
	 * without the markers of its control levels. When the outermost filter stops,
	 * the placeholders in `$output` are restored, and the remaining markers removed.
	 *
	 * @param string $output the filtered output
	 * @return string the output, with the exempt output restored by the outermost filter
	 */
	public function endCapture(string $output): string
	{
		[$serial, $limit] = array_pop($this->_filters);
		if ($limit > 0) {
			$output = $this->stripDepthMarkers($output, (string) $serial);
		}
		if ($this->_filters !== [] || $this->_exemptOutput === []) {
			return $output;
		}
		$prefix = '<!--' . self::EXEMPT_TOKEN . $this->_nonce . '-';
		while (str_contains($output, $prefix) && ($restored = strtr($output, $this->_exemptOutput)) !== $output) {
			$output = $restored;
		}
		$this->_exemptOutput = [];
		return $this->stripDepthMarkers($output, '\\d+');
	}

	/**
	 * Starts the output of a control. For each filter with a depth limit, the control
	 * is one level further below the filtering control; a marker starts the output
	 * of a control from two levels to one level past the limit.
	 * @return string the markers to write before the output of the control
	 */
	public function beginControl(): string
	{
		$markers = '';
		foreach ($this->_filters as $i => [$serial, $limit]) {
			if ($limit > 0 && $this->getIsMarkedDepth(++$this->_filters[$i][2], $limit)) {
				$markers .= $this->depthMarker($serial, $this->_filters[$i][2]);
			}
		}
		return $markers;
	}

	/**
	 * Ends the output of a control that {@see beginControl} started.
	 * @return string the markers to write after the output of the control
	 */
	public function endControl(): string
	{
		$markers = '';
		for ($i = count($this->_filters) - 1; $i >= 0; $i--) {
			[$serial, $limit, $depth] = $this->_filters[$i];
			if ($limit > 0) {
				if ($this->getIsMarkedDepth($depth, $limit)) {
					$markers .= $this->depthMarker($serial, $depth, true);
				}
				$this->_filters[$i][2]--;
			}
		}
		return $markers;
	}

	/**
	 * Encloses output in the markers of the levels above it, for the innermost
	 * filter. The output of a control rendered alone in a callback lies inside the
	 * output of the controls between it and the filtering control ({@see beginCapture}).
	 * @param string $output the output of the control
	 * @return string the output in the markers of the levels above it
	 */
	public function wrapDepth(string $output): string
	{
		[$serial, $limit, $depth] = end($this->_filters) ?: [0, 0, 0];
		for ($level = min($depth, $limit + 1); $limit > 0 && $level >= 2; $level--) {
			$output = $this->depthMarker($serial, $level) . $output . $this->depthMarker($serial, $level, true);
		}
		return $output;
	}

	/**
	 * Returns a {@see TRenderFilterParameter} that hides the output of the controls
	 * deeper than the depth limit of the innermost filter.
	 * @param string $html the output to filter
	 * @return ?TRenderFilterParameter the parameter, or null when the filter has no depth limit
	 */
	public function createDepthParameter(string $html): ?TRenderFilterParameter
	{
		[$serial, $limit] = end($this->_filters) ?: [0, 0];
		if ($limit === 0) {
			return null;
		}
		$param = new TRenderFilterParameter($html);
		$param->setDepthMarker(self::DEPTH_MARKER . $this->_nonce . '-' . $serial . '-', $limit);
		return $param;
	}

	/**
	 * Passes output through the `onRenderFilter` handlers of the innermost filter.
	 * For a control with a depth limit, the event is raised on a {@see createDepthParameter}
	 * parameter; otherwise {@see IFilterRenderable::onRenderFilter} filters the output.
	 * @param IFilterRenderable $control the filtering control
	 * @param string $output the output to filter
	 * @return string the filtered output
	 */
	public function filterOutput(IFilterRenderable $control, string $output): string
	{
		if (!($control instanceof TControl) || ($param = $this->createDepthParameter($output)) === null) {
			return (string) $control->onRenderFilter($output);
		}
		$control->raiseEvent('onRenderFilter', $control, $param);
		return $param->getFilterText();
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
	 * @param int $depth the levels below a filtering control
	 * @param int $limit the depth limit of the filter
	 * @return bool whether a control at the depth has markers
	 */
	private function getIsMarkedDepth(int $depth, int $limit): bool
	{
		return $depth >= 2 && $depth <= $limit + 1;
	}

	/**
	 * @param int $serial the serial of the filter
	 * @param int $depth the levels below the filtering control
	 * @param bool $end whether the marker ends the output
	 * @return string the marker comment
	 */
	private function depthMarker(int $serial, int $depth, bool $end = false): string
	{
		return '<!--' . ($end ? '/' : '') . self::DEPTH_MARKER . $this->_nonce . '-' . $serial . '-' . $depth . '-->';
	}

	/**
	 * @param string $output the output
	 * @param string $serial the serial of the filter, as a pattern
	 * @return string the output without the markers of the filter
	 */
	private function stripDepthMarkers(string $output, string $serial): string
	{
		if (!str_contains($output, self::DEPTH_MARKER . $this->_nonce)) {
			return $output;
		}
		return preg_replace('/<!--\/?' . self::DEPTH_MARKER . $this->_nonce . '-' . $serial . '-\d+-->/', '', $output);
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
