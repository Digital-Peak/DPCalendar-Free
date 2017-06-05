<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_weblinks
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


class InstallDpcalendarCest
{
	public function installJoomla(\AcceptanceTester $I)
	{
		$I->am('Administrator');
		$I->installJoomlaRemovingInstallationFolder();
		$I->doAdministratorLogin();
		$I->disableStatistics();
		$I->setErrorReportingToDevelopment();
	}

	/**
	 * @depends installJoomla
	 */
	public function installDpcalendar(\AcceptanceTester $I)
	{
		$I->doAdministratorLogin();
		$I->comment('get Dpcalendar repository folder from acceptance.suite.yml (see _support/AcceptanceHelper.php)');

		// URL where the package file to install is located (mostly the same as joomla-cms)
		$url = $I->getConfiguration('url');
		$I->installExtensionFromUrl($url . "/pkg-current.zip");
		$I->doAdministratorLogout();
	}

}
