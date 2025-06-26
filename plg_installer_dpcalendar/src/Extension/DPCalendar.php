<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Installer\DPCalendar\Extension;

\defined('_JEXEC') or die();

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Installer\BeforeUpdateSiteDownloadEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareTrait;

class DPCalendar extends CMSPlugin
{
	use DatabaseAwareTrait;

	public function onInstallerBeforeUpdateSiteDownload(BeforeUpdateSiteDownloadEvent $event): void
	{
		$url = $event->getUrl();
		if (!str_contains($url, 'digital-peak.com')) {
			return;
		}

		$query = $this->getDatabase()->getQuery(true);
		$query->select('name')->from('#__update_sites');
		$query->where('location = :location')->bind(':location', $url);

		$this->getDatabase()->setQuery($query);
		if (!str_contains((string)$this->getDatabase()->loadResult(), 'DPCalendar')) {
			return;
		}

		$uri = Uri::getInstance($url);
		$uri->setVar('j', JVERSION);
		$uri->setVar('p', phpversion());
		$uri->setVar('m', $this->getDatabase()->getVersion());

		$path = JPATH_ADMINISTRATOR . '/components/com_dpcalendar/dpcalendar.xml';
		if (file_exists($path)) {
			$manifest = simplexml_load_file($path);
			$uri->setVar('v', $manifest instanceof \SimpleXMLElement ? (string)$manifest->version : '');
		}

		if ($uri->getVar('v') === 'DP_DEPLOY_VERSION') {
			return;
		}

		$event->updateUrl($uri->toString());
	}

	public function onInstallerBeforePackageDownload(string &$url): void
	{
		if (!str_contains($url, '/download/dpcalendar/')) {
			return;
		}

		$app = $this->getApplication();
		if (!$app instanceof CMSApplicationInterface) {
			return;
		}

		$downloadId = ComponentHelper::getParams('com_dpcalendar')->get('downloadid');

		$model = $app->bootComponent('com_installer')->getMVCFactory()->createModel('Updatesites', 'Administrator', ['ignore_request' => true]);
		$model->setState('filter.search', 'DPCalendar Core');
		$model->setState('filter.enabled', 1);
		$model->setState('list.start', 0);
		$model->setState('list.limit', 1);

		$updateSite = $model->getItems();

		// Check if there is a download ID
		if (!empty($updateSite) && !empty($updateSite[0]->downloadKey) && !empty($updateSite[0]->downloadKey['value'])) {
			$downloadId = $updateSite[0]->downloadKey['value'];
		}

		if (empty($downloadId)) {
			return;
		}

		$uri = Uri::getInstance($url);
		$uri->setVar('dlid', $downloadId);

		$url = $uri->toString();
	}
}
