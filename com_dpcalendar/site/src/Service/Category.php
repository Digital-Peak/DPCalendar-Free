<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Service;

\defined('_JEXEC') or die();

use Joomla\CMS\Categories\Categories;

class Category extends Categories
{
	public function __construct($options = [])
	{
		$options['table']      = '#__dpcalendar_events';
		$options['extension']  = 'com_dpcalendar';
		$options['countItems'] = false;
		parent::__construct($options);
	}
}
