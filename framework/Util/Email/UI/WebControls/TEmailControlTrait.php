<?php

/**
 * TEmailControlTrait class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Util\Email\TDbEmailModule;
use Prado\Util\Email\UI\TEmailPage;

/**
 * TEmailControlTrait trait.
 *
 * TEmailControlTrait gives email controls access to their {@see TEmailPage}, the
 * {@see TDbEmailModule} tracking service, and files relative to the email template.
 * The page passes unknown methods to its TEmail, so a control calls TEmail methods,
 * such as addTrackedLink, on the page without rendering the body.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TEmailControlTrait
{
	/**
	 * @throws TConfigurationException when the control is not on a TEmailPage.
	 * @return TEmailPage the email page of the control.
	 */
	public function getEmailPage(): TEmailPage
	{
		$page = $this->getPage();
		if (!($page instanceof TEmailPage)) {
			throw new TConfigurationException('emailcontrol_emailpage_required', $this::class);
		}
		return $page;
	}

	/**
	 * @return bool whether the email of the control is sent by a TDbEmailModule with a tracking service.
	 */
	public function getHasTrackingService(): bool
	{
		$page = $this->getPage();
		if (!($page instanceof TEmailPage)) {
			return false;
		}
		$module = $page->getEmailerModule();
		return $module instanceof TDbEmailModule && $module->getServiceID() !== null;
	}

	/**
	 * Builds a URL of the tracking service for the email of the control.
	 * @param array $parameters the query parameters, e.g. ['open' => 1].
	 * @throws TConfigurationException when the email is not sent by a TDbEmailModule.
	 * @return string the absolute URL.
	 */
	public function getEmailTrackingUrl(array $parameters = []): string
	{
		$page = $this->getEmailPage();
		$module = $page->getEmailerModule();
		if (!($module instanceof TDbEmailModule)) {
			throw new TConfigurationException('emailcontrol_dbemailmodule_required', $this::class);
		}
		return $module->getTrackingUrl($page->getTrackingId(), $parameters);
	}

	/**
	 * Resolves a file path.  A relative path is relative to the directory of the
	 * email template; a namespace, e.g. "Application.Emails.logo", needs its extension.
	 * @param string $file the path or namespace of the file.
	 * @throws TConfigurationException when the file is not found.
	 * @return string the full path of the file.
	 */
	public function resolveEmailFile(string $file): string
	{
		$candidates = [$file];
		if (($tpl = $this->getTemplateControl()?->getTemplate()) !== null && ($dir = $tpl->getContextPath()) !== null) {
			$candidates[] = $dir . DIRECTORY_SEPARATOR . $file;
		}
		if (($pos = strrpos($file, '.')) !== false && ($path = Prado::getPathOfNamespace(substr($file, 0, $pos), substr($file, $pos))) !== null) {
			$candidates[] = $path;
		}
		foreach ($candidates as $candidate) {
			if (is_file($candidate)) {
				return realpath($candidate);
			}
		}
		throw new TConfigurationException('emailcontrol_file_unknown', $this::class, $file);
	}
}
