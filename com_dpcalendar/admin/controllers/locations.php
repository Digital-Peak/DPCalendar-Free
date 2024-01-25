<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.controlleradmin');

class DPCalendarControllerLocations extends AdminController
{
	public $message;
	protected $text_prefix = 'COM_DPCALENDAR_LOCATION';

	public function getModel($name = 'Location', $prefix = 'DPCalendarModel', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function publish()
	{
		$return = parent::publish();

		if (Factory::getApplication()->input->get('ajax') != 0) {
			$text = Text::plural($this->text_prefix . '_N_ITEMS_TRASHED', is_countable(Factory::getApplication()->input->get('cid', [], 'array')) ? count(Factory::getApplication()->input->get('cid', [], 'array')) : 0);
			if ($this->message == $text) {
				DPCalendarHelper::sendMessage($this->message, false);
			} else {
				DPCalendarHelper::sendMessage($this->message, true);
			}
		}
		return $return;
	}
}
