<?php

/**
 * TLiveProcessFork class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Process;

use Prado\Exceptions\TInvalidOperationException;

/**
 * TLiveProcessFork class.
 *
 * A {@see TProcessFork} with a live data map the child streams to the parent.  Reached as an array
 * ({@see \ArrayAccess}), each `$fork[$key] = $value` (or `unset($fork[$key])`) in the child is framed
 * and sent over the channel; the parent's reactor decodes the frames into its own copy as they
 * arrive, so the parent watches the child's progress and partial results live rather than only at the
 * end.
 *
 * The flow is one-way, child to parent: the child mutates, the parent observes.  The parent applies
 * each update as the channel drains (through {@see wait()} or a registered
 * {@see \Prado\IO\Socket\TSocketReactor}), raising {@see onData} per update; {@see getData()} returns
 * the current map.  Only serializable values cross the channel.  Updates are framed with a 4-byte
 * length prefix, since a stream of messages needs a boundary the single end of stream cannot give.
 *
 * The wire is symmetric and the channel is full-duplex, so a future bidirectional (parent to child)
 * mode is additive, not a breaking change: it overrides {@see shouldSend()} so the parent streams its
 * mutations too, and adds a child-side drain.  {@see applyOp()} already applies received ops directly,
 * so neither side echoes what it receives.
 *
 * ```php
 * $fork = TLiveProcessFork::fork(function (TLiveProcessFork $self) {
 *     foreach (range(1, 100) as $i) {
 *         $self['progress'] = $i;   // streamed to the parent as it changes
 *     }
 *     return 0;
 * });
 * $fork->attachEventHandler('onData', fn ($f) => print($f['progress'] . "\n"));
 * $fork->wait();                    // pumps every update, then reaps
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TLiveProcessFork extends TProcessFork implements \ArrayAccess
{
	/** The live fork always carries a channel to stream updates. */
	protected const REQUIRES_CHANNEL = true;

	/** @var array<array-key, mixed> The live map: the child's working copy, mirrored on the parent. */
	private array $_data = [];

	/** @var string The parent's frame-reassembly buffer. */
	private string $_buffer = '';

	/**
	 * Indicates whether a key is set.
	 * @param mixed $offset The key.
	 * @return bool Whether the key is set.
	 */
	public function offsetExists(mixed $offset): bool
	{
		return isset($this->_data[$offset]);
	}

	/**
	 * Returns a key's value, or null when unset.
	 * @param mixed $offset The key.
	 * @return mixed The value, or null.
	 */
	public function offsetGet(mixed $offset): mixed
	{
		return $this->_data[$offset] ?? null;
	}

	/**
	 * Sets a key's value, streaming the change to the parent.  The map is read-only on the receiving
	 * side, so a write from a non-sending side ({@see shouldSend()} false) throws rather than mutating
	 * a mirror that goes nowhere.
	 * @param mixed $offset The key, or null to append.
	 * @param mixed $value The serializable value.
	 * @throws TInvalidOperationException When written from the receiving side.
	 */
	public function offsetSet(mixed $offset, mixed $value): void
	{
		$this->assertWritable();
		if ($offset === null) {
			$this->_data[] = $value;
			$offset = array_key_last($this->_data);
		} else {
			$this->_data[$offset] = $value;
		}
		$this->sendOp('set', $offset, $value);
	}

	/**
	 * Unsets a key, streaming the removal to the parent.  The map is read-only on the receiving side.
	 * @param mixed $offset The key.
	 * @throws TInvalidOperationException When written from the receiving side.
	 */
	public function offsetUnset(mixed $offset): void
	{
		$this->assertWritable();
		unset($this->_data[$offset]);
		$this->sendOp('unset', $offset, null);
	}

	/**
	 * Asserts that this side may mutate the map.  The receiving side ({@see shouldSend()} false) is
	 * read-only; its mirror is updated only by the ops it receives through {@see applyOp()}.
	 * @throws TInvalidOperationException When this side does not send.
	 */
	protected function assertWritable(): void
	{
		if (!$this->shouldSend()) {
			throw new TInvalidOperationException('liveprocessfork_readonly_receiver');
		}
	}

	/**
	 * Returns the live map: the child's working copy, or the parent's mirror as drained so far.
	 * @return array<array-key, mixed> The live data.
	 */
	public function getData(): array
	{
		return $this->_data;
	}

	/**
	 * Reports whether a local mutation is streamed over the channel.  One-way today: only the child
	 * sends.  A bidirectional mode overrides this so the parent streams its mutations too; the wire
	 * and {@see applyOp()} are already symmetric, so nothing else changes.
	 * @return bool Whether to stream a local mutation to the peer.
	 */
	protected function shouldSend(): bool
	{
		return $this->getIsChild();
	}

	/**
	 * Frames a mutation and writes it to the channel.
	 * @param string $kind The operation, 'set' or 'unset'.
	 * @param mixed $key The key.
	 * @param mixed $value The value (null for 'unset').
	 */
	private function sendOp(string $kind, mixed $key, mixed $value): void
	{
		$blob = serialize([$kind, $key, $value]);
		$this->writeChannel(pack('N', strlen($blob)) . $blob);
	}

	/**
	 * Decodes complete length-prefixed frames from the drained bytes and applies them to the mirror.
	 * @param string $bytes The bytes read from the channel.
	 */
	protected function consume(string $bytes): void
	{
		$this->_buffer .= $bytes;
		while (strlen($this->_buffer) >= 4) {
			$length = (int) unpack('N', substr($this->_buffer, 0, 4))[1];
			if (strlen($this->_buffer) < 4 + $length) {
				break;   // the frame is not fully arrived yet
			}
			$op = @unserialize(substr($this->_buffer, 4, $length));
			$this->_buffer = substr($this->_buffer, 4 + $length);
			if (is_array($op)) {
				$this->applyOp($op);
			}
		}
	}

	/**
	 * Applies one decoded operation to the parent's mirror and raises {@see onData}.
	 * @param array $op The decoded [kind, key, value] operation.
	 */
	private function applyOp(array $op): void
	{
		// Assign the mirror directly, never through offsetSet(): a received op must not re-enter the
		// send path, or a bidirectional peer would echo it back in a loop.  The op tuple may grow
		// (e.g. a version field); extra elements are ignored, so the frame stays forward-compatible.
		[$kind, $key, $value] = $op + [null, null, null];
		if ($kind === 'set') {
			$this->_data[$key] = $value;
		} elseif ($kind === 'unset') {
			unset($this->_data[$key]);
		}
		$this->onData($op);
	}

	/**
	 * Raised on the parent for each live update applied from the child.
	 * @param mixed $param The decoded [kind, key, value] operation.
	 */
	public function onData(mixed $param): void
	{
		$this->raiseEvent('onData', $this, $param);
	}

	/**
	 * Excludes the transient frame-reassembly buffer from serialization; the live map is kept.
	 * @param array $exprops The properties excluded from __sleep.
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . __CLASS__ . "\0_buffer";
	}
}
