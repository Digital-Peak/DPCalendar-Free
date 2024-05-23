<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Controller;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;

class LocationsController extends AdminController
{
	protected $text_prefix = 'COM_DPCALENDAR_LOCATION';

	public function getModel($name = 'Location', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function publish(): void
	{
		parent::publish();

		if ($this->app->getInput()->get('ajax') != 0) {
			$text = Text::plural($this->text_prefix . '_N_ITEMS_TRASHED', is_countable($this->app->getInput()->get('cid', [], 'array')) ? count($this->app->getInput()->get('cid', [], 'array')) : 0);
			if ($this->message == $text) {
				DPCalendarHelper::sendMessage($this->message, false);
			} else {
				DPCalendarHelper::sendMessage($this->message, true);
			}
		}

	}
}
