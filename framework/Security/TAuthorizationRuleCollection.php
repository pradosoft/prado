<?php
/**
 * TAuthorizationRule, TAuthorizationRuleCollection class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Security;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * TAuthorizationRuleCollection class.
 * TAuthorizationRuleCollection represents a collection of authorization rules {@see TAuthorizationRule}.
 * To check if a user is allowed, call {@see isUserAllowed}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TAuthorizationRuleCollection extends \Prado\Collections\TPriorityList
{
	/**
	 * @param IUser $user the user to be authorized
	 * @param string $verb verb, can be empty, 'post' or 'get'.
	 * @param string $ip the request IP address
	 * @param null|array $extra
	 * @return bool whether the user is allowed
	 */
	public function isUserAllowed($user, $verb, $ip, $extra = null)
	{
		if ($user instanceof IUser) {
			$verb = strtolower(trim($verb));
			foreach ($this as $rule) {
				if (($decision = $rule->isUserAllowed($user, $verb, $ip, $extra)) !== 0) {
					return ($decision > 0);
				}
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by performing additional
	 * operations for each newly added TAuthorizationRule object.
	 * @param int $index the specified position.
	 * @param mixed $item new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a TAuthorizationRule object.
	 * @return ?float The priority of the item inserted at the index.
	 */
	public function insertAt($index, $item)
	{
		if ($item instanceof TAuthorizationRule) {
			parent::insertAtIndexInPriority($item, $index);
			return $item->getPriority();
		} else {
			throw new TInvalidDataTypeException('authorizationrulecollection_authorizationrule_required');
		}
	}
}
