<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
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

class Pkg_DPCalendarInstallerScript extends InstallerScript implements DatabaseAwareInterface
{
	use DatabaseAwareTrait;

	protected $minimumPhp      = '8.1.0';
	protected $minimumJoomla   = '4.4.4';
	protected $allowDowngrades = true;

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

		$version = null;
		$this->run("select * from `#__extensions` where element = 'pkg_dpcalendar'");
		$package = $this->getDatabase()->loadObject();
		if ($package) {
			$info = json_decode((string)$package->manifest_cache);
			if (isset($info->version)) {
				$version = $info->version;
			}
		}

		if ($version && $version != 'DP_DEPLOY_VERSION' && version_compare($version, '9.0.0') < 0) {
			$app = Factory::getApplication();
			if (!$app instanceof CMSWebApplicationInterface) {
				return false;
			}

			$app->enqueueMessage(
				'You have DPCalendar version ' . $version . ' installed. For this version is no automatic update available anymore, you need to have at least version 9.0.0 running. Please install the latest release from version 9 first.',
				'error'
			);

			if ($app->getInput()->get('task') !== 'ajax_upload') {
				$app->redirect('index.php?option=com_installer&view=install');
			}

			return false;
		}

		return true;
	}

	public function update(InstallerAdapter $parent): void
	{
		$file = $parent->getParent()->getPath('source') . '/deleted.php';
		if (file_exists($file)) {
			require $file;
		}

		$path    = JPATH_ADMINISTRATOR . '/manifests/packages/pkg_dpcalendar.xml';
		$version = null;

		if (file_exists($path)) {
			$manifest = simplexml_load_file($path);
			$version  = $manifest instanceof SimpleXMLElement ? (string)$manifest->version : null;
		}

		if ($version === null || $version === '' || $version === '0' || $version === 'DP_DEPLOY_VERSION') {
			return;
		}

		if (version_compare($version, '9.0.5') == -1) {
			$this->run(
				"UPDATE `#__update_sites` SET location=replace(location,'&ext=extension.xml','') where location like 'https://joomla.digital-peak.com/index.php?option=com_ars&view=update&task=stream&format=xml&id=%'"
			);
			$this->run(
				"UPDATE `#__update_sites` SET location=replace(location,'https://joomla.digital-peak.com/index.php?option=com_ars&view=update&task=stream&format=xml&id=','https://cdn.digital-peak.com/update/stream.php?id=') where location like 'https://joomla.digital-peak.com/index.php?option=com_ars&view=update&task=stream&format=xml&id=%'"
			);
		}

		if (version_compare($version, '10.3.1') == -1) {
			$this->run("UPDATE `#__update_sites` SET location = CONCAT(location, '&project=dpcalendar') WHERE name LIKE '%dpcalendar%' AND location NOT LIKE '%&project=dpcalendar%'");
		}
	}

	public function postflight(string $type): void
	{
		// Perform some post install tasks
		if ($type === 'install') {
			$this->run("update `#__extensions` set enabled = 1 where type = 'plugin' and element = 'dpcalendar'");
			$this->run("update `#__extensions` set enabled = 1 where type = 'plugin' and element = 'manual'");

			$this->run(
				"insert ignore into `#__modules_menu` (menuid, moduleid) select 0 as menuid, id as moduleid from `#__modules` where module like 'mod_dpcalendar%'"
			);
		}

		// Make sure the installer plugin is enabled
		$this->run("update `#__extensions` set enabled = 1 where name = 'plg_installer_dpcalendar'");
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
