<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarControllerImport extends JControllerLegacy
{

	public function add ($data = array())
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$model = $this->getModel('Import', '', array());
		$model->import();

		$this->setRedirect(JRoute::_('index.php?option=com_dpcalendar&view=tools&layout=import', false), implode('<br>', $model->get('messages')));
	}
}
