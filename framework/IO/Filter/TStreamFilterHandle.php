<?php

/**
 * TStreamFilterHandle class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Filter;

use Prado\IO\TStream;
use Prado\TComponent;

/**
 * TStreamFilterHandle class.
 *
 * Wraps the resource that {@see stream_filter_append()}/{@see stream_filter_prepend()} return
 * for a filter attached to a stream, so a caller removes the filter through the handle itself
 * ({@see remove()}) rather than holding the raw resource.
 *
 * A handle from {@see TStream::appendFilter()}/{@see TStream::prependFilter()} carries the
 * owning stream, so {@see remove()} both detaches the filter and untracks it on that stream.
 * A handle from the static {@see \Prado\IO\Filter\TStreamFilter::append()} has no owner and only
 * detaches the filter.  {@see getResource()} exposes the raw handle for {@see stream_get_meta_data()}
 * or direct calls.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TStreamFilterHandle extends TComponent
{
	/** @var mixed The raw filter resource, or null once removed. */
	private mixed $_filter;

	/** @var string The filter name this handle was attached under. */
	private string $_name;

	/** @var ?TStream The stream tracking this filter, or null for a free-standing handle. */
	private ?TStream $_ownerStream;

	/**
	 * @param mixed $filter The raw filter resource from stream_filter_append/prepend.
	 * @param string $name The filter name.
	 * @param ?TStream $ownerStream The stream tracking this filter, or null.
	 */
	public function __construct(mixed $filter, string $name, ?TStream $ownerStream = null)
	{
		$this->_filter = $filter;
		$this->_name = $name;
		$this->_ownerStream = $ownerStream;
		parent::__construct();
	}

	/**
	 * Sets the raw filter resource.
	 * @param mixed $value The raw filter resource, or null.
	 */
	protected function setFilterDirect(mixed $value): void
	{
		$this->_filter = $value;
	}

	/**
	 * Returns the raw owner stream.
	 * @return ?TStream The raw owner stream, or null.
	 */
	protected function getOwnerStreamDirect(): ?TStream
	{
		return $this->_ownerStream;
	}

	/**
	 * Sets the raw owner stream.
	 * @param ?TStream $value The raw owner stream, or null.
	 */
	protected function setOwnerStreamDirect(?TStream $value): void
	{
		$this->_ownerStream = $value;
	}

	/**
	 * Returns the raw filter resource for direct use (metadata, manual calls).
	 * @return mixed The raw filter resource, or null once removed.
	 */
	public function getResource(): mixed
	{
		return $this->_filter;
	}

	/**
	 * Returns the filter name this handle was attached under.
	 * @return string The filter name.
	 */
	public function getName(): string
	{
		return $this->_name;
	}

	/**
	 * Returns the stream this filter is attached to, or null for a handle on a raw resource.
	 * @return ?TStream The stream this filter is attached to, or null.
	 */
	public function getStream(): ?TStream
	{
		return $this->_ownerStream;
	}

	/**
	 * Indicates whether the filter is still attached (the resource is live).
	 * @return bool Whether the filter is active.
	 */
	public function isActive(): bool
	{
		return is_resource($this->getResource());
	}

	/**
	 * Clears this handle's filter reference after the owning stream has detached it
	 * ({@see TStream::removeFilter()}), marking the handle inactive.
	 */
	public function markRemoved(): void
	{
		$this->setFilterDirect(null);
	}

	/**
	 * Removes the filter by asking the owning stream to detach it ({@see TStream::removeFilter()}),
	 * which is the source of truth and untracks it there.  A free-standing handle (no owner stream)
	 * detaches the filter itself ({@see stream_filter_remove()}).  The handle becomes inactive; a
	 * second call is a no-op.
	 * @return bool Whether a live filter was removed.
	 */
	public function remove(): bool
	{
		$stream = $this->getStream();
		if ($stream !== null) {
			return $stream->removeFilter($this);
		}
		$filter = $this->getResource();
		if (!is_resource($filter)) {
			return false;
		}
		$result = stream_filter_remove($filter);
		if ($result) {
			$this->setFilterDirect(null);
		}
		return $result;
	}
}
