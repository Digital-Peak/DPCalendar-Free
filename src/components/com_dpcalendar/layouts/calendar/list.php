<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\DescriptionList;
use CCL\Content\Element\Basic\Description\Term;
use CCL\Content\Element\Basic\Form\Input;
use CCL\Content\Element\Component\Icon;
use CCL\Content\Element\Basic\Font;
use CCL\Content\Element\Basic\Link;
use CCL\Content\Element\Basic\Description\Description;
use CCL\Content\Element\Basic\Form\Label;

// Set up the params
$params = $displayData['params'];

// The root element
$root = $displayData['root'];

// Check if the list should be shown
if ($params->get('show_selection', 1) != 1 && $params->get('show_selection', 1) != 3) {
	return;
}

// The container for the calendars
$c = $root->addChild(new Container('list'));
$c->addClass('dp-calendar-list', true);

// Loop over the calendars
foreach ($displayData['calendars'] as $calendar) {
	// The url for the source
	$value = html_entity_decode(
		JRoute::_(
			'index.php?option=com_dpcalendar&view=events&format=raw&limit=0&ids=' . $calendar->id . '&my=' .
			$params->get('show_my_only_calendar', '0') . '&Itemid=' . JFactory::getApplication()->input->getInt('Itemid', 0)
		)
	);

	/** @var DescriptionList $dl * */
	$dl = $c->addChild(new DescriptionList('calendar-' . $calendar->id));

	// The label for the checkbox
	$l = $dl->setTerm(new Term('term'))->addChild(new Label('label', $dl->getId() . '-term-label-id'));

	// Add the checkbox to the label
	$l->addChild(
		new Input(
			'id',
			'checkbox',
			$calendar->id,
			$value,
			array(),
			array('onclick' => 'updateDPCalendarFrame(jQuery(this), jQuery("#dp-calendar-calendar"))')
		)
	);

	// Add the calendar name to the title
	$f = $l->addChild(new Font('calendar', array(), array('color' => $calendar->color)));
	$f->setContent(str_pad(' ' . $calendar->title, strlen(' ' . $calendar->title) + $calendar->level - 1, '-', STR_PAD_LEFT));

	// When native calendars add sharing urls
	if (!$calendar->external) {
		// Add the ical link
		$link = $l->addChild(new Link('ical', DPCalendarHelperRoute::getCalendarIcalRoute($calendar->id)));
		$link->setContent(' [ ' . JText::_('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_ICAL') . ' ]');

		if (!DPCalendarHelper::isFree() && !JFactory::getUser()->guest) {
			// Add the CalDAV link
			$url = trim(JUri::base(), '/');
			$url .= '/components/com_dpcalendar/caldav.php/calendars/' . JFactory::getUser()->username . '/dp-' . $calendar->id;
			$l->addChild(new Link('caldav', $url))->setContent(' [ ' . JText::_('COM_DPCALENDAR_VIEW_PROFILE_TABLE_CALDAV_URL_LABEL') . ' ]');
		}
	}

	// Add the description
	$dl->setDescription(new Description('content'))->setContent($calendar->description);
};

// Add the toggle icon
$title = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_CALENDAR_LIST');
$t     = $root->addChild(new Container('toggle'));
$t->addClass('dp-calendar-toggle', true);
$t->addChild(new Icon('up', Icon::UP, array(), array('data-direction' => 'up', 'title' => $title)));
$t->addChild(new Icon('down', Icon::DOWN, array(), array('data-direction' => 'down', 'title' => $title)));

// Define the initial icon to show
JFactory::getDocument()->addStyleDeclaration('#' . $t->getId() . ($params->get('show_selection', 1) == 3 ? '-down' : '-up') . '{ display:none }');
