<?php

/**
 * TModule class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * TModule class
 *
 * TModule implements {@see IModule} and is the base class for all application modules.
 *
 * ## Initialization Phases
 *
 * {@see TApplication} initializes modules in four ordered phases:
 *
 * 1. **Instantiate & configure** — module created; configuration properties applied.
 * 2. **dyPreInit** — dispatched to attached behaviors before `init()`.
 * 3. **init** — {@see init()} called; the primary initialization point.
 * 4. **dyPostInit** — dispatched to attached behaviors after `init()` returns.
 *
 * To dispatch `dyPreInit` or `dyPostInit` to behaviors from a TModule subclass with
 * custom `dyPreInit` or `dyPostInit` methods, calling `$this->callBehaviorsMethod(...)`
 * is required.  If it is not custom dynamic event, the dynamic event is passed
 * through to behaviors automatically.
 * For example:
 * ```php
 * class MyModule extends TModule {
 *      public function dyPreInit($config) {
 *          ...
 *          $this->callBehaviorsMethod('dyPreInit', $return, $config);
 *      }
 *  }
 * ```
 *
 * ## Dependencies
 *
 * Implement {@see IModuleDependency} on the module or on an attached behavior to
 * declare dependencies. Both sources are re-evaluated before each sort pass.
 *
 * ```php
 * class TMyModule extends TModule implements IModuleDependency {
 *     public function getModuleDependencies(bool $isPreInit = false): ?array {
 *         return ['db', 'cache'];
 *     }
 * }
 * ```
 *
 * After both sources have been collected, {@see TApplication::collectModuleDependencies()}
 * dispatches `dyFilterDependencies` to all behaviors attached to the module, passing
 * the accumulated dependency map and returning the (possibly modified) map. Behaviors
 * may add, remove, or replace entries before the map is used for topological sorting.
 *
 * When multiple behaviors implement `dyFilterDependencies`, the `TCallChain` dispatch
 * calls each behavior with the **original** map and returns the **last** behavior's
 * return value — unless each behavior accepts the `$callchain` parameter and explicitly
 * forwards its modified map via `$callchain->dyFilterDependencies($deps)`. Use the
 * callchain pattern when multiple behaviors must each see the previous behavior's output:
 *
 * ```php
 * class TMyDependencyBehavior extends TBehavior {
 *     public function dyFilterDependencies(array $deps, \Prado\Util\TCallChain $callchain): array {
 *         unset($deps['moduleId']);   // drop an optional dependency
 *         $deps['otherModule'] = ['id' => 'otherModule', 'required' => true];
 *         return $callchain->dyFilterDependencies($deps);  // forward to next behavior
 *     }
 * }
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 * @method void dyPreInit(mixed $config) Dispatched by {@see TApplication} before {@see init()} runs.
 * @method void dyInit(mixed $config) Dispatched from within {@see init()} to all attached behaviors.
 * @method void dyPostInit(mixed $config) Dispatched by {@see TApplication} after {@see init()} returns.
 * @method array dyFilterDependencies(array $deps) Filters the collected dependency map before topological sorting. Behaviors may add, remove, or replace entries; the (possibly modified) map is returned.
 */
abstract class TModule extends \Prado\TApplicationComponent implements IModule
{
	/**
	 * @var string module id
	 */
	private $_id;

	/**
	 * Initializes the module. Required by {@see IModule}; invoked by the application.
	 * Raises {@see dyInit()} on all attached behaviors.
	 * @param null|array|\Prado\Xml\TXmlElement $config Module configuration element.
	 *  `TXmlElement` for XML configuration, `array` for PHP configuration, and null
	 *   when invoked without configuration.
	 */
	public function init($config)
	{
		$this->dyInit($config);
	}

	/**
	 * Returns the module ID assigned by the application.
	 * @return string id of this module
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * Sets the module ID. Called by the application during configuration.
	 * @param string $value id of this module
	 */
	public function setID($value)
	{
		$this->_id = $value;
	}
}
