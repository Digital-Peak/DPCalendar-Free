<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Filesystem\File;

class Com_DPCalendarInstallerScript extends InstallerScript implements DatabaseAwareInterface
{
	use DatabaseAwareTrait;

	protected $minimumPhp = '8.1.0';

	protected $minimumJoomla = '4.4.4';

	protected $allowDowngrades = true;

	public function update(): void
	{
		$path    = JPATH_ADMINISTRATOR . '/components/com_dpcalendar/dpcalendar.xml';
		$version = null;

		if (file_exists($path)) {
			$manifest = simplexml_load_file($path);
			$version  = $manifest instanceof SimpleXMLElement ? (string)$manifest->version : '';
		}

		if (\in_array($version, [null, '', '0', 'DP_DEPLOY_VERSION'], true)) {
		}
	}

	public function preflight($type, $parent): bool
	{
		if (!parent::preflight($type, $parent)) {
			return false;
		}

		// Check for the minimum Joomla 5 version before continuing
		if (version_compare(JVERSION, '5.0.0', '>=') && version_compare(JVERSION, '5.1.0', '<')) {
			Log::add(Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', '5.1.0'), Log::WARNING, 'jerror');

			return false;
		}

		$app = Factory::getApplication();

		$path    = JPATH_ADMINISTRATOR . '/components/com_dpcalendar/dpcalendar.xml';
		$version = null;
		if (file_exists($path)) {
			$manifest = simplexml_load_file($path);
			$version  = $manifest instanceof SimpleXMLElement ? (string)$manifest->version : '';
		}

		if (!\in_array($version, [null, '', '0', 'DP_DEPLOY_VERSION'], true) && version_compare($version, '10.6.0', '<')) {
			$app->enqueueMessage(
				'You have DPCalendar version ' . $version . ' installed. Please install version 10.6.0 first before you upgrade to this package.',
				'error'
			);

			if ($app instanceof CMSWebApplicationInterface) {
				$app->redirect('index.php?option=com_installer&view=install');
			}

			return false;
		}

		return true;
	}

	public function postflight(string $type, InstallerAdapter $parent): void
	{
		if ($parent->getElement() != 'com_dpcalendar') {
			return;
		}

		if (is_dir(JPATH_ADMINISTRATOR . '/components/com_falang/contentelements')) {
			File::copy(
				JPATH_ADMINISTRATOR . '/components/com_dpcalendar/config/falang/dpcalendar_events.xml',
				JPATH_ADMINISTRATOR . '/components/com_falang/contentelements/dpcalendar_events.xml'
			);
			File::copy(
				JPATH_ADMINISTRATOR . '/components/com_dpcalendar/config/falang/dpcalendar_locations.xml',
				JPATH_ADMINISTRATOR . '/components/com_falang/contentelements/dpcalendar_locations.xml'
			);
		}

		if ($type === 'install' || $type === 'discover_install') {
			// Create default calendar
			$category              = Factory::getApplication()->bootComponent('categories')->getMVCFactory()->createTable('Category', 'Administrator');
			$category->extension   = 'com_dpcalendar';
			$category->title       = 'Uncategorised';
			$category->alias       = 'uncategorised';
			$category->description = '';
			$category->published   = 1;
			$category->access      = 1;
			$category->params      = '{"category_layout":"","image":"","color":"3366CC"}';
			$category->metadata    = '{"author":"","robots":""}';
			$category->language    = '*';
			$category->setLocation(1, 'last-child');
			$category->store(true);
			$category->rebuildPath($category->id);

			$this->run('update #__extensions set params = \'{"providers":{"providers0":{"id":1,"title":"Default","description":"PLG_DPCALENDARPAY_MANUAL_PAY_BUTTON_DESC","booking_state": 4,"payment_statement":"PLG_DPCALENDARPAY_MANUAL_PAYMENT_STATEMENT_TEXT"}}}\' where name like \'plg_dpcalendarpay_manual\'');
		}
	}

	private function run(string $query): void
	{
		try {
			$db = $this->getDatabase();
			$db->setQuery($query);
			$db->execute();
		} catch (Exception $exception) {
			Factory::getApplication()->enqueueMessage($exception->getMessage(), 'error');
		}
	}
}
