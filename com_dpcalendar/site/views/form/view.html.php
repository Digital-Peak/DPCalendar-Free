<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewForm extends \DPCalendar\View\LayoutView
{
	protected $layoutName = 'event.form.default';

	public function init()
	{
		$user = $this->user;
		if ($user->guest && count($user->getAuthorisedCategories('com_dpcalendar', 'core.create')) < 1) {
			$active = $this->app->getMenu()->getActive();
			$link   = new JUri(JRoute::_('index.php?option=com_users&view=login&Itemid=' . $active->id, false));
			$link->setVar('return', base64_encode('index.php?Itemid=' . $active->id));

			$this->app->redirect(JRoute::_($link), JText::_('COM_DPCALENDAR_NOT_LOGGED_IN'), 'warning');

			return false;
		}

		JPluginHelper::importPlugin('dpcalendar');

		$this->app->getLanguage()->load('', JPATH_ADMINISTRATOR);

		$this->event      = $this->get('Item');
		$this->form       = $this->get('Form');
		$this->returnPage = $this->get('ReturnPage');

		JForm::addFieldPath(JPATH_COMPONENT_ADMINISTRATOR . '/models/files/');

		$authorised = true;
		if (empty($this->event->id)) {
			$tmp        = $this->app->triggerEvent('onCalendarsFetch', array(null, 'cd'));
			$authorised = DPCalendarHelper::canCreateEvent() || !empty(array_filter($tmp));
		}

		if ($authorised !== true) {
			throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$requestParams = $this->input->getVar('jform', array());
		if (key_exists('start_date', $requestParams)) {
			$this->form->setFieldAttribute('start_date', 'filter', null);
			$this->form->setFieldAttribute('start_date', 'formated', true);
			$this->form->setValue('start_date', null,
				$requestParams['start_date'] . (key_exists('start_date_time', $requestParams) ? ' ' . $requestParams['start_date_time'] : ''));
		}

		if (key_exists('end_date', $requestParams)) {
			$this->form->setFieldAttribute('end_date', 'filter', null);
			$this->form->setFieldAttribute('end_date', 'formated', true);
			$this->form->setValue('end_date', null,
				$requestParams['end_date'] . (key_exists('end_date_time', $requestParams) ? ' ' . $requestParams['end_date_time'] : ''));
		}

		if (key_exists('title', $requestParams)) {
			$this->form->setValue('title', null, $requestParams['title']);
		}

		if (key_exists('catid', $requestParams)) {
			$this->form->setValue('catid', null, $requestParams['catid']);
		}

		if (key_exists('location_ids', $requestParams)) {
			$this->form->setValue('location_ids', null, $requestParams['location_ids']);
		}

		if (key_exists('rooms', $requestParams)) {
			$this->form->setValue('rooms', null, $requestParams['rooms']);
		}

		return parent::init();
	}
}
