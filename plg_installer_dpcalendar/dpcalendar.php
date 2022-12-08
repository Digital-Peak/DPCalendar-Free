<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;

class PlgInstallerDPCalendar extends CMSPlugin
{
	public function onInstallerBeforePackageDownload(&$url, &$headers)
	{
		if (strpos($url, '/download/dpcalendar/') === false) {
			return;
		}

		$params = ComponentHelper::getParams('com_dpcalendar');
		if (!$downloadId = $params->get('downloadid')) {
			return;
		}

		$uri = Uri::getInstance($url);
		$uri->setVar('dlid', $downloadId);

		$url = $uri->toString();
	}
}
