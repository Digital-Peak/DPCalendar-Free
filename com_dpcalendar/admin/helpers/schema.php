<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarHelperSchema
{

	public static function offer($event)
	{
		if (!$event->price)
		{
			return '';
		}
		$buffer = '';

		foreach ($event->price->value as $key => $value)
		{
			$label = $event->price->label[$key];
			$desc = $event->price->description[$key];

			$buffer .= '<div ' . JMicrodata::htmlScope('Offer') . ' ' . JMicrodata::htmlProperty('offers') . '>';
			$buffer .= JMicrodata::htmlMeta(htmlspecialchars($value, ENT_QUOTES), 'price');
			if ($label)
			{
				$buffer .= JMicrodata::htmlMeta(htmlspecialchars($label, ENT_QUOTES), 'name');
			}
			if ($desc)
			{
				$buffer .= JMicrodata::htmlMeta(htmlspecialchars($desc, ENT_QUOTES), 'description');
			}
			$buffer .= JMicrodata::htmlMeta(htmlspecialchars(JText::_('COM_DPCALENDAR_FIELD_CAPACITY_LABEL') . ': ' . $event->capacity, ENT_QUOTES),
					'availability');
			$buffer .= JMicrodata::htmlMeta(htmlspecialchars(DPCalendarHelperRoute::getEventRoute($event->id, $event->catid, true), ENT_QUOTES),
					'url');
			$buffer .= '</div>';
		}
		return $buffer;
	}

	public static function location($locations, $elementName = 'div')
	{
		$buffer = '';

		foreach ($locations as $location)
		{
			$buffer .= '<' . $elementName . ' ' . JMicrodata::htmlScope('Place') . ' ' . JMicrodata::htmlProperty('location') . '>';
			$buffer .= JMicrodata::htmlMeta(htmlspecialchars(DPCalendarHelperLocation::format($location), ENT_QUOTES), 'name');
			$buffer .= '<' . $elementName . ' ' . JMicrodata::htmlScope('PostalAddress') . ' ' . JMicrodata::htmlProperty('address') . '>';
			if (isset($location->city))
			{
				$buffer .= JMicrodata::htmlMeta(htmlspecialchars($location->city, ENT_QUOTES), 'addressLocality');
			}
			if (isset($location->province))
			{
				$buffer .= JMicrodata::htmlMeta(htmlspecialchars($location->province, ENT_QUOTES), 'addressRegion');
			}
			if (isset($location->zip))
			{
				$buffer .= JMicrodata::htmlMeta(htmlspecialchars($location->zip, ENT_QUOTES), 'postalCode');
			}
			if (isset($location->street))
			{
				$buffer .= JMicrodata::htmlMeta(htmlspecialchars($location->street . ' ' . $location->number, ENT_QUOTES), 'streetAddress');
			}
			if (isset($location->country))
			{
				$buffer .= JMicrodata::htmlMeta(htmlspecialchars($location->country, ENT_QUOTES), 'addressCountry');
			}
			$buffer .= '</' . $elementName . '>';
			$buffer .= '</' . $elementName . '>';
		}

		return $buffer;
	}
}
