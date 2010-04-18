<?php
/**
 * TEvent is the base class for all event classes.
 *
 * It encapsulates the parameters associated with an event.
 * The {@link sender} property describes who raises the event.
 * And the {@link handled} property indicates if the event is handled.
 * If an event handler sets {@link handled} to true, those handlers
 * that are not invoked yet will not be invoked anymore.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: TEvent.php 978 2009-05-06 03:36:09Z qiang.xue $
 * @package system.base
 * @since 1.0
 */
class TEvent extends TComponent
{
	/**
	 * @var object the sender of this event
	 */
	public $sender;
	/**
	 * @var boolean whether the event is handled. Defaults to false.
	 * When a handler sets this true, the rest uninvoked handlers will not be invoked anymore.
	 */
	public $handled=false;

	/**
	 * Constructor.
	 * @param mixed sender of the event
	 */
	public function __construct($sender=null)
	{
		parent::__construct();
		
		$this->sender=$sender;
	}
}