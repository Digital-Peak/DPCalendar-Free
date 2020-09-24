<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewLocationForm extends \DPCalendar\View\BaseView
{
	public function display($tpl = null)
	{
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');
		$model = JModelLegacy::getInstance('Location', 'DPCalendarModel');
		$this->setModel($model, true);

		return parent::display($tpl);
	}

	public function init()
	{
		JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/forms');
		$this->app->getLanguage()->load('', JPATH_ADMINISTRATOR);

		$this->location   = $this->get('Item');
		$this->form       = $this->get('Form');
		$this->returnPage = $this->get('ReturnPage');

		if ($this->location->id && $this->location->params->get('access-edit')) {
			return;
		}

		if (!$this->user->authorise('core.create', 'com_dpcalendar')) {
			return $this->handleNoAccess();
		}
	}
}
