<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_weblinks
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


class CreateEventCest
{
	public function createEvent(\AcceptanceTester $I)
	{
		$I->amGoingTo('Create an event for Allon');
		$I->doAdministratorLogin();
		$I->amOnPage('/administrator/index.php?option=com_dpcalendar&view=events');
		$I->waitForText('DPCalendar Manager: Events');
		$I->click('New');
		$I->waitForText('DPCalendar Manager: Event', 30);

		$I->fillField(['id' => 'jform_title'], 'JOscar for Allon');
		$I->click('Save & Close');

		$I->waitForText('DPCalendar Manager: Events');
		$I->see('Event succesfully saved');
	}
}
