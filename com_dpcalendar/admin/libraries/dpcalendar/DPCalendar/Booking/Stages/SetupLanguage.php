<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use League\Pipeline\StageInterface;

class SetupLanguage implements StageInterface
{
	/**
	 * @var CMSApplication
	 */
	private $application = null;

	public function __construct(CMSApplication $application)
	{
		$this->application = $application;
	}

	public function __invoke($payload)
	{
		$payload->language = $this->application->getLanguage();

		$siteLanguage = ComponentHelper::getParams('com_languages')->get('site', $this->application->get('language', 'en-GB'));
		$userLanguage = Factory::getUser($payload->item->user_id)->getParam('language', $siteLanguage);
		if ($siteLanguage == $userLanguage) {
			return $payload;
		}

		$language = Language::getInstance($userLanguage);
		$language->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		$payload->language = $language;

		return $payload;
	}
}
