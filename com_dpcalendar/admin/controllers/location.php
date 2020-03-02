<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.controllerform');

class DPCalendarControllerLocation extends JControllerForm
{

	protected $text_prefix = 'COM_DPCALENDAR_LOCATION';

	public function batch($model = null)
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Set the model
		$model = $this->getModel('Location', '', []);

		// Preset the redirect
		$this->setRedirect(JRoute::_('index.php?option=com_dpcalendar&view=locations' . $this->getRedirectToListAppend(), false));

		return parent::batch($model);
	}

	public function save($key = null, $urlVar = 'l_id')
	{
		if ($lookup = $this->input->getString('lookup')) {
			$location = \DPCalendar\Helper\Location::get($lookup, true, $this->input->getString('lookup_title'));

			$data = [];
			if ($location->id) {
				$data =
					[
						'id'      => $location->id,
						'display' => $location->title . ' [' . $location->latitude . ':' . $location->longitude . ']'
					];
			}
			DPCalendarHelper::sendMessage(
				null,
				empty($data),
				$data
			);
		}
		$return = parent::save($key, $urlVar);

		if ($this->input->get('ajax') != 0) {
			if (isset($this->id) && $this->id) {
				$table = $this->getModel()->getTable();
				$table->load($this->id);
				DPCalendarHelper::sendMessage(
					$this->message,
					false,
					[
						'id'      => $this->id,
						'display' => $table->title . ' [' . $table->latitude . ':' . $table->longitude . ']'
					]
				);
			} else {
				DPCalendarHelper::sendMessage($this->message, true, ['id' => 0]);
			}
		}

		return $return;
	}

	protected function postSaveHook(JModelLegacy $model, $validData = [])
	{
		$this->id    = $model->getState('location.id');
		$this->error = $model->getError();
	}

	public function loc()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$loc = \DPCalendar\Helper\Location::get($this->input->getString('loc'), false);

		$data             = (array)$loc;
		$data['formated'] = \DPCalendar\Helper\Location::format([$loc]);
		DPCalendarHelper::sendMessage(null, false, $data);
	}

	public function searchloc()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		DPCalendarHelper::sendMessage(null, false, \DPCalendar\Helper\Location::search(trim($this->input->getString('loc'))));
	}

	public function edit($key = null, $urlVar = 'l_id')
	{
		return parent::edit($key, $urlVar);
	}

	public function cancel($key = 'l_id')
	{
		return parent::cancel($key);
	}
}
