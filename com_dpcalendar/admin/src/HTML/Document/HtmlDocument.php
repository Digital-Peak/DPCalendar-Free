<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\HTML\Document;

\defined('_JEXEC') or die();

use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Document\HtmlDocument as CMSHtmlDocument;
use Joomla\CMS\Factory;

/**
 * Html document.
 */
class HtmlDocument
{
	private readonly CMSWebApplicationInterface $app;

	public function __construct(?CMSWebApplicationInterface $app = null)
	{
		if (!$app instanceof CMSWebApplicationInterface && Factory::getApplication() instanceof CMSWebApplicationInterface) {
			$app = Factory::getApplication();
		}

		if (!$app instanceof CMSWebApplicationInterface) {
			throw new \Exception('No web context, cannot load document.');
		}

		$this->app = $app;
	}

	public function loadScriptFile(string $path, string $extension = 'com_dpcalendar'): void
	{
		$doc = $this->app->getDocument();
		if (!$doc instanceof CMSHtmlDocument) {
			return;
		}

		$doc->getWebAssetManager()->registerAndUseScript(
			$extension . '/' . str_replace('.js', '', $path),
			$extension . '/' . str_replace('.js', '.min.js', $path),
			['relative' => true, 'version' => JDEBUG ? false : 'auto'],
			['type' => 'module'],
			['core', 'messages']
		);
	}

	public function addScriptOptions(string $key, mixed $options): void
	{
		$doc = $this->app->getDocument();
		if (!$doc instanceof CMSHtmlDocument) {
			return;
		}

		$doc->addScriptOptions('DPCalendar.' . $key, $options);
	}

	public function loadStyleFile(string $path, string $extension = 'com_dpcalendar'): void
	{
		$doc = $this->app->getDocument();
		if (!$doc instanceof CMSHtmlDocument) {
			return;
		}

		$doc->getWebAssetManager()->registerAndUseStyle(
			$extension . '/' . str_replace('.css', '', $path),
			$extension . '/' . str_replace('.css', '.min.css', $path),
			['relative' => true, 'version' => JDEBUG ? false : 'auto']
		);
	}

	public function addScript(?string $content = ''): void
	{
		if (\in_array($content, [null, '', '0'], true)) {
			return;
		}

		$doc = $this->app->getDocument();
		if (!$doc instanceof CMSHtmlDocument) {
			return;
		}

		$doc->getWebAssetManager()->addInlineScript($content);
	}

	public function addStyle(?string $content = ''): void
	{
		if (\in_array($content, [null, '', '0'], true)) {
			return;
		}

		$doc = $this->app->getDocument();
		if (!$doc instanceof CMSHtmlDocument) {
			return;
		}

		$doc->getWebAssetManager()->addInlineStyle($content);
	}
}
