<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\ThinHTTP\CurlClient;
use Joomla\CMS\Cache\CacheControllerFactoryAwareInterface;
use Joomla\CMS\Cache\CacheControllerFactoryAwareTrait;
use Joomla\CMS\Cache\Controller\OutputController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class VersionModel extends BaseDatabaseModel implements CacheControllerFactoryAwareInterface
{
	use CacheControllerFactoryAwareTrait;

	/**
	 * Returns the remote version.
	 */
	public function getRemoteVersion(string $currentVersion): string
	{
		/** @var OutputController $cache */
		$cache = $this->getCacheControllerFactory()->createCacheController('output', ['defaultgroup' => 'com_dpcalendar_version']);
		$cache->setCaching(true);
		$cache->setLifeTime(3600);
		if ($cache->contains($currentVersion)) {
			return $cache->get($currentVersion);
		}

		// Ignore dev
		if ($currentVersion === 'DP_DEPLOY_VERSION') {
			$cache->store('0', $currentVersion);

			return '0';
		}

		$url = 'https://cdn.digital-peak.com/update/stream.php?id=19';
		if ($id = DPCalendarHelper::getComponentParameter('downloadid')) {
			$url .= '&dlid=' . $id;
		}

		// Get the current data
		$response = (new CurlClient())->get(
			$url . '&j=' . JVERSION . '&p=' . phpversion() . '&m=' . $this->getDatabase()->getVersion() . '&v=' . $currentVersion
		);

		// Check for errors
		if ($response->dp->info->http_code !== 200) {
			$cache->store('0', $currentVersion);

			return '0';
		}

		// Create the XML
		$xml = new \SimpleXMLElement($response->dp->body);
		if (empty($xml->update)) {
			$cache->store('0', $currentVersion);

			return '0';
		}

		// The first element contains the most recent version
		$update = $xml->update[0];
		if (!$update instanceof \SimpleXMLElement) {
			$cache->store('0', $currentVersion);

			return '0';
		}

		// When there is a mismatch, trigger the updates fetch
		if ((string)$update->version !== $currentVersion) {
			$model = $this->bootComponent('installer')->getMVCFactory()->createModel('Update', 'Administrator', ['ignore_request' => true]);
			$model->purge();
			$model->findUpdates(0, 300);
		}
		$cache->store((string)$update->version, $currentVersion);

		// Get the current version
		return (string)$update->version;
	}
}
