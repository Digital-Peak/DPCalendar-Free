<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
namespace DPCalendar\Helper;

defined('_JEXEC') or die();

class UserHelper
{
	public function getUser($id = null)
	{
		return \JFactory::getUser($id);
	}
}
