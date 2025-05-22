<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\HTML\Document;

\defined('_JEXEC') or die();

use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Document\HtmlDocument as DocumentHtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

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
		/** @var DocumentHtmlDocument $doc */
		$doc = $this->app->getDocument();
		$doc->getWebAssetManager()->registerAndUseScript(
			$extension . '/' . str_replace('.js', '', $path),
			$extension . '/' . str_replace('.js', '.min.js', $path),
			['relative' => true, 'version' => JDEBUG ? false : 'auto'],
			['type'     => 'module'],
			['core', 'messages']
		);
	}

	public function addScriptOptions(string $key, mixed $options): void
	{
		$this->app->getDocument()->addScriptOptions('DPCalendar.' . $key, $options);
	}

	public function loadStyleFile(string $path, string $extension = 'com_dpcalendar'): void
	{
		$path = str_replace('.css', '.min.css', $path);
		HTMLHelper::_('stylesheet', $extension . '/' . $path, ['relative' => true, 'version' => JDEBUG ? false : 'auto']);
	}

	public function addScript(?string $content = ''): void
	{
		if ($content === null || $content === '' || $content === '0') {
			return;
		}

		$this->app->getDocument()->getWebAssetManager()->addInlineScript($content);
	}

	public function addStyle(?string $content = ''): void
	{
		if ($content === null || $content === '' || $content === '0') {
			return;
		}

		$this->app->getDocument()->getWebAssetManager()->addInlineStyle($content);
	}
}
