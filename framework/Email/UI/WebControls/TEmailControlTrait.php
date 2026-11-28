<?php

/**
 * TEmailControlTrait trait file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\UI\WebControls;

use Prado\Email\TEmail;
use Prado\Email\TEmailModule;
use Prado\Email\UI\TEmailBody;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;

/**
 * TEmailControlTrait trait.
 *
 * TEmailControlTrait gives a control access to the {@see TEmailBody} it is on, the
 * {@see TEmail} being rendered, and the tracking service of the email module.
 *
 * A tracking URL is written with a placeholder for the tracking ID, which
 * {@see TEmail::finalize} replaces.  A template rendered once and sent to many
 * recipients therefore gives each recipient their own URLs.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TEmailControlTrait
{
	/**
	 * @throws TConfigurationException when the control is not on a TEmailBody.
	 * @return TEmailBody the body the control is on.
	 */
	public function getEmailBody(): TEmailBody
	{
		$page = $this->getPage();
		if (!($page instanceof TEmailBody)) {
			throw new TConfigurationException('emailcontrol_body_required', $this::class);
		}
		return $page;
	}

	/**
	 * @throws TConfigurationException when the control is not on a TEmailBody.
	 * @return TEmail the email being rendered.
	 */
	public function getEmail(): TEmail
	{
		return $this->getEmailBody()->getEmail();
	}

	/**
	 * @return ?TEmailModule the email module of the body, null when the control is not on a body or the body has no module.
	 */
	public function getEmailModule(): ?TEmailModule
	{
		$page = $this->getPage();
		return $page instanceof TEmailBody ? $page->getEmailModule() : null;
	}

	/**
	 * @return bool whether the email module has a tracking service and a store.
	 */
	public function getHasTracking(): bool
	{
		return $this->getEmailModule()?->getHasTracking() ?? false;
	}

	/**
	 * Builds a URL of the tracking service for the email being rendered.
	 * @param array $parameters the query parameters, e.g. ['open' => 1].
	 * @throws TConfigurationException when the body has no email module with a service.
	 * @return string the absolute URL, with the placeholder of the tracking ID.
	 */
	public function getEmailTrackingUrl(array $parameters = []): string
	{
		if (($module = $this->getEmailModule()) === null) {
			throw new TConfigurationException('emailcontrol_module_required', $this::class);
		}
		return $module->getTrackingUrl(null, $parameters);
	}

	/**
	 * Resolves a file path.  A relative path is relative to the directory of the
	 * template of the control; a namespace, e.g. "Application.Emails.logo.png", includes the extension.
	 * @param string $file the path or namespace of the file.
	 * @throws TConfigurationException when the file is not found.
	 * @return string the absolute path of the file.
	 */
	public function resolveEmailFile(string $file): string
	{
		$candidates = [$file];
		if (($template = $this->getTemplateControl()?->getTemplate()) !== null && ($directory = $template->getContextPath()) !== null) {
			$candidates[] = $directory . DIRECTORY_SEPARATOR . $file;
		}
		if (($dot = strrpos($file, '.')) !== false && ($path = Prado::getPathOfNamespace(substr($file, 0, $dot), substr($file, $dot))) !== null) {
			$candidates[] = $path;
		}
		foreach ($candidates as $candidate) {
			if (is_file($candidate)) {
				return (string) realpath($candidate);
			}
		}
		throw new TConfigurationException('emailcontrol_file_unknown', $this::class, $file);
	}
}
