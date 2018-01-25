<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.view');

class DPCalendarViewForm extends JViewLegacy
{

	protected $form;

	protected $item;

	protected $return_page;

	protected $state;

	public function display ($tpl = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication();
		$user = JFactory::getUser();

		// Get model data.
		$this->state = $this->get('State');
		$this->item = $this->get('Item');
		$this->form = $this->get('Form');
		$this->return_page = $this->get('ReturnPage');

		if (empty($this->item->id))
		{
			$authorised = DPCalendarHelper::canCreateEvent();
		}
		else
		{
			$authorised = $user->authorise('core.edit', 'com_dpcalendar.event.' . $this->item->id);
		}

		if ($authorised !== true)
		{
			JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
			return false;
		}

		if (! empty($this->item))
		{
			$this->form->bind($this->item);
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseWarning(500, implode("\n", $errors));
			return false;
		}

		// Create a shortcut to the parameters.
		$params = &$this->state->params;

		$this->params = $params;
		$this->user = $user;

		parent::display($tpl);
	}
}
