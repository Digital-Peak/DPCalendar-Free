<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Link;
use CCL\Content\Element\Basic\Element;
use CCL\Content\Element\Basic\TextBlock;

if ($item == null) {
	return;
}

// Load the required JS libraries
DPCalendarHelper::loadLibrary(array('dpcalendar' => true, 'url' => true));
if ($params->get('show_as_popup')) {
	DPCalendarHelper::loadLibrary(array('modal' => true));
}

// Load the counter library
JHtml::_('script', 'com_dpcalendar/moment/moment.min.js', ['relative' => true], ['defer' => true]);
JHtml::_('script', 'mod_dpcalendar_counter/default.min.js', ['relative' => true], ['defer' => true]);

// Load the module stylesheet
JHtml::_('stylesheet', 'mod_dpcalendar_counter/default.min.css', ['relative' => true]);

$labelsPlural = array(
	JText::script('MOD_DPCALENDAR_COUNTER_LABEL_YEARS'),
	JText::script('MOD_DPCALENDAR_COUNTER_LABEL_MONTHS'),
	JText::script('MOD_DPCALENDAR_COUNTER_LABEL_WEEKS'),
	JText::script('MOD_DPCALENDAR_COUNTER_LABEL_DAYS'),
	JText::script('MOD_DPCALENDAR_COUNTER_LABEL_HOURS'),
	JText::script('MOD_DPCALENDAR_COUNTER_LABEL_MINUTES'),
	JText::script('MOD_DPCALENDAR_COUNTER_LABEL_SECONDS')
);
$labels       = array(
	JText::script('MOD_DPCALENDAR_COUNTER_LABEL_YEAR'),
	JText::script('MOD_DPCALENDAR_COUNTER_LABEL_MONTH'),
	JText::script('MOD_DPCALENDAR_COUNTER_LABEL_WEEK'),
	JText::script('MOD_DPCALENDAR_COUNTER_LABEL_DAY'),
	JText::script('MOD_DPCALENDAR_COUNTER_LABEL_HOUR'),
	JText::script('MOD_DPCALENDAR_COUNTER_LABEL_MINUTE'),
	JText::script('MOD_DPCALENDAR_COUNTER_LABEL_SECOND')
);

// The root container
$root = new Container('dp-module-counter-' . $module->id, array('root'), array('ccl-prefix' => 'dp-module-counter-'));
$root->addAttribute('data-date', DPCalendarHelper::getDate($item->start_date, $item->all_day)->format('c', true));
$root->addAttribute('data-modal', $params->get('show_as_popup'));
$root->addAttribute('data-counting', !$params->get('disable_counting'));

$cc = $root->addChild(new Container('container', ['container']));

// Add the soon text
$cc->addChild(new Container('soon', ['soon']))->setContent(JText::_('MOD_DPCALENDAR_COUNTER_SOON_OUTPUT'));

$c = $cc->addChild(new TextBlock('year', ['cell', 'year']));
$c->addChild(new TextBlock('number', ['year-number', 'number']));
$c->addChild(new TextBlock('content', ['year-content', 'content']));
$c = $cc->addChild(new TextBlock('month', ['cell', 'month']));
$c->addChild(new TextBlock('number', ['month-number', 'number']));
$c->addChild(new TextBlock('content', ['month-content', 'content']));
$c = $cc->addChild(new TextBlock('week', ['cell', 'week']));
$c->addChild(new TextBlock('number', ['week-number', 'number']));
$c->addChild(new TextBlock('content', ['week-content', 'content']));
$c = $cc->addChild(new TextBlock('day', ['cell', 'day']));
$c->addChild(new TextBlock('number', ['day-number', 'number']));
$c->addChild(new TextBlock('content', ['day-content', 'content']));
$c = $cc->addChild(new TextBlock('hour', ['cell', 'hour']));
$c->addChild(new TextBlock('number', ['hour-number', 'number']));
$c->addChild(new TextBlock('content', ['hour-content', 'content']));
$c = $cc->addChild(new TextBlock('minute', ['cell', 'minute']));
$c->addChild(new TextBlock('number', ['minute-number', 'number']));
$c->addChild(new TextBlock('content', ['minute-content', 'content']));
$c = $cc->addChild(new TextBlock('second', ['cell', 'second']));
$c->addChild(new TextBlock('number', ['second-number', 'number']));
$c->addChild(new TextBlock('content', ['second-content', 'content']));

// Add the ongoing text
$root->addChild(new Container('ongoing', ['ongoing']))->setContent(JText::_('MOD_DPCALENDAR_COUNTER_ONGOING_OUTPUT'));

// The body of the module
$body = $root->addChild(new Container('content', ['content']));

// The event content
$l = $body->addChild(new Link('link', DPCalendarHelperRoute::getEventRoute($item->id, $item->catid)));
$l->setContent($item->title);

// Add description only if it is different than 0
if ($params->get('description_length') > 0 || $params->get('description_length') === null) {
	$desc = JHtml::_('string.truncate', $item->description, $params->get('description_length'));
	$desc = JHTML::_('content.prepare', $desc);
	$desc = \DPCalendar\Helper\DPCalendarHelper::fixImageLinks($desc);
	$body->addChild(new Element('description'))->setContent($desc);
}

// Render the root element
echo DPCalendarHelper::renderElement($root, $params);
