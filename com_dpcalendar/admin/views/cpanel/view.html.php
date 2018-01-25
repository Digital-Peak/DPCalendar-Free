<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.libraries.dpcalendar.view', JPATH_ADMINISTRATOR);

class DPCalendarViewCpanel extends \DPCalendar\View\BaseView
{

	protected $icon = 'dpcalendar';

	protected $title = 'COM_DPCALENDAR_VIEW_CPANEL';

	protected function init ()
	{
		if (! DPCalendarHelper::getComponentParameter('downloadid') && ! DPCalendarHelper::isFree())
		{
			JFactory::getApplication()->enqueueMessage(
					'Please define the download ID in the <a href="index.php?option=com_config&view=component&component=com_dpcalendar">component parameters</a> to enable DPCalendar updates trough the Joomla updater.
							You can get the download ID from <a href="https://joomla.digital-peak.com/my-account/download-id" target="_blank">your account at joomla.digital.peak</a>.');
		}
		$this->getModel()->refreshUpdateSite();
		parent::init();
	}
}
