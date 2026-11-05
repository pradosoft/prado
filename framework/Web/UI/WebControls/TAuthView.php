<?php

/**
 * TAuthView class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TAuthView class.
 *
 * TAuthView renders content chosen by the authentication state of the current user,
 * {@see \Prado\TApplication::getUser()}.  Each case is a {@see TAuthViewTemplate}
 * with the conditions `Users`, `Roles`, and `Permissions`.  TAuthView renders no tag
 * of its own.
 *
 * The body of TAuthView, the content outside its TAuthViewTemplate children, is its
 * self case: a TAuthViewTemplate ({@see getSelfTemplate()}) whose conditions are the
 * {@see setUsers Users}, {@see setRoles Roles}, and {@see setPermissions Permissions}
 * of TAuthView.  With no children, TAuthView shows its body to the users it matches:
 *
 * ```php
 * <com:TAuthView Permissions="blog.post.edit">
 *     <a href="...">Edit post</a>
 * </com:TAuthView>
 * ```
 *
 * TAuthView renders the first case that matches the user, in this order:
 * 1. The TAuthViewTemplate children, in document order.
 * 2. The self case, so its body is the fallback of the children.
 * 3. No case matches, or there is no user: nothing.
 *
 * ```php
 * <com:TAuthView>
 *     <com:TAuthViewTemplate Users="?"><a href="...">Sign in</a></com:TAuthViewTemplate>
 *     <com:TAuthViewTemplate Roles="admin"><a href="...">Administration</a></com:TAuthViewTemplate>
 *     Welcome back.
 * </com:TAuthView>
 * ```
 *
 * Every case defaults to `Users="@"`, so a case without conditions matches any
 * authenticated user, and a case for guests sets `Users="?"` or `Users="*"`.
 *
 * Only the chosen case joins the control tree.  The other cases are built with the
 * template and kept out of the tree, so their controls do not load post data, raise
 * events, or render.  A control with an ID in an unchosen case is still a property of
 * the template control.  The IDs of controls in different cases must differ.
 *
 * The case is chosen when child controls are created, in the Init stage, and again in
 * {@see onPreRender}, so a login or logout during a postback renders the new case.  A
 * case added late catches up to the Load stage.  Each case has its own ID,
 * `<TAuthView ID>_<index>` or `<TAuthView ID>_self` unless it sets one, so it never
 * loads the view state of another case.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TAuthView extends \Prado\Web\UI\TControl
{
	/**
	 * The ID suffix of the self case.
	 */
	public const SELF_ID_SUFFIX = 'self';

	/** @var TAuthViewTemplate[] the TAuthViewTemplate children, in document order. */
	private array $_templates = [];
	/** @var ?TAuthViewTemplate the self case. */
	private ?TAuthViewTemplate $_selfTemplate = null;
	/** @var ?TAuthViewTemplate the case in the control tree. */
	private ?TAuthViewTemplate $_activeTemplate = null;

	/**
	 * Keeps a parsed TAuthViewTemplate as a case, and adds other parsed content to the self case.
	 * @param \Prado\TComponent|string $object text or a component parsed from the template.
	 */
	public function addParsedObject($object)
	{
		if ($object instanceof TAuthViewTemplate) {
			$this->_templates[] = $object;
		} else {
			$this->getSelfTemplate()->addParsedObject($object);
		}
	}

	/**
	 * @return TAuthViewTemplate[] the TAuthViewTemplate children, in document order.
	 */
	public function getTemplates()
	{
		return $this->_templates;
	}

	/**
	 * @return TAuthViewTemplate the self case, holding the body and the conditions of the view.
	 */
	public function getSelfTemplate()
	{
		if ($this->_selfTemplate === null) {
			$this->_selfTemplate = new TAuthViewTemplate();
		}
		return $this->_selfTemplate;
	}

	/**
	 * @return ?TAuthViewTemplate the case in the control tree; null when none matches.
	 */
	public function getActiveTemplate()
	{
		return $this->_activeTemplate;
	}

	/**
	 * @return string the comma-separated users the self case matches.  Defaults to '@'.
	 */
	public function getUsers()
	{
		return $this->getSelfTemplate()->getUsers();
	}

	/**
	 * @param string $value the comma-separated users the self case matches: names, `*`, `?`, or `@`.
	 */
	public function setUsers($value)
	{
		$this->getSelfTemplate()->setUsers($value);
	}

	/**
	 * @return string the comma-separated roles the self case matches.  Defaults to ''.
	 */
	public function getRoles()
	{
		return $this->getSelfTemplate()->getRoles();
	}

	/**
	 * @param string $value the comma-separated roles the self case matches.
	 */
	public function setRoles($value)
	{
		$this->getSelfTemplate()->setRoles($value);
	}

	/**
	 * @return string the comma-separated permissions the self case matches.  Defaults to ''.
	 */
	public function getPermissions()
	{
		return $this->getSelfTemplate()->getPermissions();
	}

	/**
	 * @param string $value the comma-separated permissions the self case matches.
	 */
	public function setPermissions($value)
	{
		$this->getSelfTemplate()->setPermissions($value);
	}

	/**
	 * Returns the case to render for the current user: the first matching child, then the self case.
	 * @return ?TAuthViewTemplate the matching case; null when none matches.
	 */
	public function findMatchingTemplate()
	{
		$user = $this->getUser();
		foreach ([...$this->getTemplates(), $this->getSelfTemplate()] as $template) {
			if ($template->isUserMatched($user)) {
				return $template;
			}
		}
		return null;
	}

	/**
	 * Adds the matching case to the control tree.
	 */
	public function createChildControls()
	{
		$this->applyTemplate($this->findMatchingTemplate());
	}

	/**
	 * Chooses the case again, so a login or logout during the request renders the new case.
	 * @param \Prado\TEventParameter $param the event parameter.
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->applyTemplate($this->findMatchingTemplate());
	}

	/**
	 * Replaces the case in the control tree.
	 * @param ?TAuthViewTemplate $template the case to render; null for none.
	 */
	protected function applyTemplate(?TAuthViewTemplate $template)
	{
		if ($template === $this->_activeTemplate) {
			return;
		}
		$this->getControls()->clear();
		$this->_activeTemplate = $template;
		if ($template === null) {
			return;
		}
		if ($template->getID() === '') {
			$index = array_search($template, $this->getTemplates(), true);
			$template->setID($this->getID(false) . '_' . ($index === false ? self::SELF_ID_SUFFIX : $index));
		}
		$this->getControls()->add($template);
	}
}
