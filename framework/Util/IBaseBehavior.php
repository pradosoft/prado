<?php
/**
 * IBaseBehavior class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * Dynamic events, Class-wide behaviors, expanded behaviors
 * @author Brad Anderson <belisoful@icloud.com>
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Collections\IPriorityProperty;

/**
 * IBaseBehavior interface is the base behavior interface from which the two PRADO
 * behaviors interface types are derived.  All behaviors are attached to an owner
 * TComponent, essentially extending it with run time traits; implementing new methods,
 * properties, and fine internal process control for the owner component at run time.
 *
 * There are two specific types of behaviors in PRADO:
 * -{@link IClassBehavior} is stateless and one instance attaches to multiple owners.
 *    The owner is injected as the first parameter argument in behavior implemented
 *    methods called on the owner.
 * -{@link IBehavior} is stateful and each one instance attaches to one owner.
 *
 * All public methods and properties in the behavior are inherited by the owners
 * and so IBaseBehavior act like run-time traits.
 *
 * IBaseBehavior handler "dy" dynamic events their from owners as well.  "dy" dynamic
 * events are an optional event system between owners and behaviors.  When an owner
 * calls a method starting with "dy", all attached enabled behaviors implementing
 * the dynamic event are raised. For example:
 * ```php
 *		$filteredData = $componentObject->dyFilterData($data);
 * ```
 * would automatically call all enabled behaviors attached to $componentObject implementing
 * dyFilterData(..., ?TCallchain $chain = null).  The first parameter in "dy" dynamic
 * events is passed through as the return value and so acts like a filter or default
 * return value.  When there are no handlers for a dynamic event, the returned value
 * will always be the value of the first method parameter.
 *
 * "dy" dynamic event methods append a "TCallChain" to the argument lists and the
 * event must be called on with the dynamic event to continue the call chain. Dynamic
 * events work slightly differently between IClassBehavior and IBehavior where the
 * IClassBehavior has the owner object injected as the first method argument and
 * the IBehavior does not.  The call chain may be optional to make the dynamic event
 * method callable without the $chain but will always be present in owner called
 * behavior dynamic event methods.
 *
 * See {@link IBehavior} and {@link IClassBehavior} for examples of their respective
 * dynamic event implementations.
 *
 * @author Brad Anderson <belisoful@icloud.ocm>
 * @since 3.2.3
 */
interface IBaseBehavior extends IPriorityProperty
{
	/**
	 * The array key for the $config data in instancing behaviors with init($config),
	 */
	public const CONFIG_KEY = '=config=';

	/**
	 * Handles behavior configurations from TBehaviorsModule and TBehaviorParameterLoader.
	 * @param mixed $config The configuration data.
	 * @since 4.2.2
	 */
	public function init($config);

	/**
	 * Attaches the behavior object to the component.
	 * @param \Prado\TComponent $component The component that this behavior is being attached to.
	 */
	public function attach($component);

	/**
	 * Detaches the behavior object from the component.
	 * @param \Prado\TComponent $component The component that this behavior is being detached from.
	 */
	public function detach($component);

	/**
	 * @return ?string The name of the behavior in the owner[s].
	 * @since 4.2.3
	 */
	public function getName(): ?string;

	/**
	 * @param ?string $value The name of the behavior in the owner[s].
	 * @since 4.2.3
	 */
	public function setName($value);

	/**
	 * @return bool Whether this behavior is enabled.  See implementation for default.
	 * @since 4.2.3
	 */
	public function getEnabled(): bool;

	/**
	 * @param bool $value Whether this behavior is enabled.
	 * @since 4.2.3
	 */
	public function setEnabled($value);

	/**
	 * @return array The owner component(s) that this behavior is attached to. [] when none.
	 * @since 4.2.3
	 */
	public function getOwners(): array;

	/**
	 * @return bool Is the behavior attached to an owner.
	 * @since 4.2.3
	 */
	public function hasOwner(): bool;

	/**
	 * @param object $component The component to check if its an owner.
	 * @return bool Is the object an owner of the behavior.
	 * @since 4.2.3
	 */
	public function isOwner(object $component): bool;

	/**
	 * Synchronize the $component or all owners' events of the behavior event handlers
	 * by attaching or detaching handlers where needed.
	 * @param ?object $component The component to manage the behaviors handlers on. Default
	 *   is null for synchronizing all owners.
	 * @param null|bool|int $attachOverride Overrides the default attachment logic or whether
	 *   to install and forcibly attach or detach the handlers when true or null.  Default
	 *   is 0 for standard attachment logic.  false resets the overrides to default attachment
	 *   logic.
	 * @since 4.2.3
	 */
	public function syncEventHandlers(?object $component = null, $attachOverride = 0);
}
