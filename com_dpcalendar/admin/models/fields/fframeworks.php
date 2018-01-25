<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JFormHelper::loadFieldClass('filelist');

class JFormFieldFFrameworks extends JFormFieldFileList
{

	public $type = 'FFrameworks';

	public function getOptions()
	{
		$this->directory = JPATH_SITE . '/components/com_dpcalendar/layouts/fframework';
		$options = parent::getOptions();

		foreach (JFolder::folders(JPATH_SITE . '/templates/') as $folder)
		{
			$this->directory = JPATH_SITE . '/templates/' . $folder . '/html/layouts/com_dpcalendar/fframework';
			if (JFolder::exists($this->directory))
			{
				$options = array_merge(parent::getOptions(), $options);
			}
		}
		$options = array_unique($options, SORT_REGULAR);
		foreach ($options as $option)
		{
			$key = $this->element->attributes()->translatekey . strtoupper($option->value);
			if (JFactory::getLanguage()->hasKey($key))
			{
				$option->text = JText::_($key);
			}
			else
			{
				$option->text = ucfirst($option->text);
			}
		}
		return $options;
	}
}
