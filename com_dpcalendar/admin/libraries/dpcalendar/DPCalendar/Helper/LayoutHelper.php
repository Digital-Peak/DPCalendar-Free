<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
namespace DPCalendar\Helper;

defined('_JEXEC') or die();

class LayoutHelper
{
	public function renderLayout($layout, $data = [])
	{
		return \JLayoutHelper::render($layout, $data, null, array('component' => 'com_dpcalendar', 'client' => 0));
	}
}
