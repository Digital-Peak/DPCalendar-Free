<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use League\Pipeline\StageInterface;

class SetupForUpdate implements StageInterface
{
	/**
	 * @var \JApplicationCms
	 */
	private $application = null;

	/**
	 * @var \JUser
	 */
	private $user = null;

	public function __construct(\JApplicationCms $application, \JUser $user)
	{
		$this->application = $application;
		$this->user        = $user;
	}

	public function __invoke($payload)
	{
		// Unset the price, that it can't be changed afterwards through some form hacking
		if ($this->application->isClient('site') && !$this->user->authorise('core.admin', 'com_dpcalendar')) {
			unset($payload->data['price']);
		}

		return $payload;
	}
}
