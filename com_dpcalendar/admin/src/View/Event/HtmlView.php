<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\View\Event;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Form\Field\SubformField;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseView
{
	/** @var \stdClass */
	protected $event;

	/** @var Form */
	protected $form;

	/** @var array */
	protected $seriesEvents;

	protected function init(): void
	{
		// Set the default model
		$this->setModel($this->getDPCalendar()->getMVCFactory()->createModel('Event', 'Administrator'), true);
		$this->setModel($this->getDPCalendar()->getMVCFactory()->createModel('Events', 'Administrator'));

		$this->event        = $this->getModel()->getItem() ?: new \stdClass();
		$this->form         = $this->getModel()->getForm();
		$this->seriesEvents = [];

		if ($this->event->original_id == -1) {
			$model = $this->getModel('Events');
			$model->getState();
			$model->setState('filter.children', $this->event->id);
			$model->setState('filter.modified', $this->event->modified ?: '0000-00-00');
			$model->setState('filter.state', null);
			$model->setState('list.start-date', null);

			foreach ($model->getItems() as $event) {
				$e                 = new \stdClass();
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

		// Set the date format on existing subforms
		$exdates = $this->form->getField('exdates');
		if ($exdates instanceof SubformField) {
			$exdates->__get('input');
			$exdates->loadSubForm()->setFieldAttribute('date', 'format', DPCalendarHelper::getComponentParameter('event_form_date_format', 'd.m.Y'));
			foreach (array_keys(array_filter((array)$exdates->__get('value'))) as $key) {
				// @phpstan-ignore-next-line
				$form = Form::getInstance('subform.' . $key);
				$form->setFieldAttribute('date', 'format', DPCalendarHelper::getComponentParameter('event_form_date_format', 'd.m.Y'));
			}
		}

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
	}

	protected function addToolbar(): void
	{
		$this->input->set('hidemainmenu', true);

		$isNew      = ($this->event->id == 0);
		$checkedOut = $this->event->checked_out != 0 && $this->event->checked_out != $this->user->id;
		$canDo      = ContentHelper::getActions('com_dpcalendar', '', $this->event->catid);
		$cats       = $this->user->getAuthorisedCategories('com_dpcalendar', 'core.create') ?: [];

		if (!$checkedOut && ($canDo->get('core.edit') || $cats)) {
			ToolbarHelper::apply('event.apply');
			ToolbarHelper::save('event.save');
		}
		if (!$checkedOut && $cats) {
			ToolbarHelper::save2new('event.save2new');
		}
		if (!$isNew && $cats) {
			ToolbarHelper::save2copy('event.save2copy');
		}
		if ($this->params->get('save_history', 1) && $this->user->authorise('core.edit')) {
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
