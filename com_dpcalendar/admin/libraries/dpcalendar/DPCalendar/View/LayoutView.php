<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

namespace DPCalendar\View;

defined('_JEXEC') or die();


class LayoutView extends BaseView
{
	protected $layoutName = null;

	public function loadTemplate($tpl = null)
	{
		if (!$this->layoutName) {
			return parent::loadTemplate($tpl);
		}

		if (!isset($this->returnPage)) {
			$this->returnPage = '';
		}

		return \DPCalendarHelper::renderLayout($this->layoutName, $this->getLayoutData());
	}

	protected function getLayoutData()
	{
		$data = [];

		foreach (get_object_vars($this) as $name => $var) {
			if (strpos($name, '_') === 0) {
				continue;
			}
			$data[$name] = $var;
		}

		return $data;
	}
}
