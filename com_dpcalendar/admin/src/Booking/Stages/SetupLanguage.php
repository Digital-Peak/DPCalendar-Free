<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages;

defined('_JEXEC') or die();

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Language;
use Joomla\CMS\User\UserFactoryAwareInterface;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\CMS\User\UserFactoryInterface;
use League\Pipeline\StageInterface;

class SetupLanguage implements StageInterface, UserFactoryAwareInterface
{
	use UserFactoryAwareTrait;

	public function __construct(private readonly CMSApplicationInterface $application, UserFactoryInterface $factory)
	{
		$this->setUserFactory($factory);
	}

	public function __invoke($payload)
	{
		$payload->language = $this->application->getLanguage();

		$siteLanguage = ComponentHelper::getParams('com_languages')->get('site', $this->application->get('language', 'en-GB'));
		$userLanguage = $this->getUserFactory()->loadUserById($payload->item->user_id)->getParam('language', $siteLanguage);
		if ($siteLanguage == $userLanguage) {
			return $payload;
		}

		// @phpstan-ignore-next-line
		$language = Language::getInstance($userLanguage);
		$language->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		$payload->language = $language;

		return $payload;
	}
}
