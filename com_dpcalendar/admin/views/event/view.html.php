<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\View\BaseView;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Toolbar\ToolbarHelper;

class DPCalendarViewEvent extends BaseView
{
	public function init()
	{
		// Set the default model
		$this->setModel(BaseDatabaseModel::getInstance('AdminEvent', 'DPCalendarModel'), true);
		$this->setModel(BaseDatabaseModel::getInstance('AdminEvents', 'DPCalendarModel'));

		$this->event        = $this->get('Item');
		$this->form         = $this->get('Form');
		$this->returnPage   = $this->get('ReturnPage');
		$this->seriesEvents = [];

		if ($this->event->original_id == -1) {
			$model = $this->getModel('Adminevents');
			$model->getState();
			$model->setState('filter.children', $this->event->id);
			$model->setState('filter.modified', $this->event->modified ?: '0000-00-00');
			$model->setState('filter.state', null);
			$model->setState('filter.search_start', null);

			foreach ($model->getItems() as $event) {
				$e                 = new stdClass();
				$e->title          = $event->title;
				$e->formatted_date = $this->dateHelper->getDateStringFromEvent(
					$event,
					$this->params->get('event_form_date_format', 'd.m.Y'),
					$this->params->get('event_form_time_format', 'H:i')
				);
				$e->edit_link = $this->router->route('index.php?option=com_dpcalendar&view=event&e_id=' . $event->id);

				$this->seriesEvents[] = $e;
			}
		}

		$this->form->setFieldAttribute('user_id', 'type', 'hidden');
		$this->form->setFieldAttribute('start_date', 'format', DPCalendarHelper::getComponentParameter('event_form_date_format', 'd.m.Y'));
		$this->form->setFieldAttribute('start_date', 'formatTime', DPCalendarHelper::getComponentParameter('event_form_time_format', 'H:i'));
		$this->form->setFieldAttribute('end_date', 'format', DPCalendarHelper::getComponentParameter('event_form_date_format', 'd.m.Y'));
		$this->form->setFieldAttribute('end_date', 'formatTime', DPCalendarHelper::getComponentParameter('event_form_time_format', 'H:i'));
		$this->form->setFieldAttribute('scheduling_end_date', 'format', DPCalendarHelper::getComponentParameter('event_form_date_format', 'd.m.Y'));

		if ($this->event->original_id > '0') {
			// Hide the scheduling fields
			$this->form->removeField('rrule');
			$this->form->removeField('exdates');
			$this->form->removeField('scheduling');
			$this->form->removeField('scheduling_end_date');
			$this->form->removeField('scheduling_interval');
			$this->form->removeField('scheduling_repeat_count');
			$this->form->removeField('scheduling_daily_weekdays');
			$this->form->removeField('scheduling_weekly_days');
			$this->form->removeField('scheduling_monthly_options');
			$this->form->removeField('scheduling_monthly_week');
			$this->form->removeField('scheduling_monthly_week_days');
			$this->form->removeField('scheduling_monthly_days');
		}
		$this->canDo = DPCalendarHelper::getActions($this->state->get('filter.category_id'));
	}

	protected function addToolbar()
	{
		$this->input->set('hidemainmenu', true);

		$isNew      = ($this->event->id == 0);
		$checkedOut = !($this->event->checked_out == 0 || $this->event->checked_out == $this->user->id);
		$canDo      = DPCalendarHelper::getActions($this->event->catid);

		if (!$checkedOut && ($canDo->get('core.edit') || (is_countable($this->user->getAuthorisedCategories('com_dpcalendar', 'core.create')) ? count($this->user->getAuthorisedCategories('com_dpcalendar', 'core.create')) : 0))) {
			ToolbarHelper::apply('event.apply');
			ToolbarHelper::save('event.save');
		}
		if (!$checkedOut && (is_countable($this->user->getAuthorisedCategories('com_dpcalendar', 'core.create')) ? count($this->user->getAuthorisedCategories('com_dpcalendar', 'core.create')) : 0)) {
			ToolbarHelper::save2new('event.save2new');
		}
		if (!$isNew && ((is_countable($this->user->getAuthorisedCategories('com_dpcalendar', 'core.create')) ? count($this->user->getAuthorisedCategories('com_dpcalendar', 'core.create')) : 0) > 0)) {
			ToolbarHelper::save2copy('event.save2copy');
		}
		if ($this->state->params->get('save_history', 1) && $this->user->authorise('core.edit')) {
			ToolbarHelper::versions('com_dpcalendar.event', $this->event->id);
		}
		if (empty($this->event->id)) {
			ToolbarHelper::cancel('event.cancel');
		} else {
			ToolbarHelper::cancel('event.cancel', 'JTOOLBAR_CLOSE');
		}
		parent::addToolbar();
	}
}
