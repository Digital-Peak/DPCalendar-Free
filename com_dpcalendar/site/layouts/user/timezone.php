<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
use CCL\Content\Element\Basic\Form;
use CCL\Content\Element\Basic\Form\Input;
use CCL\Content\Element\Basic\Form\Label;
use CCL\Content\Element\Basic\Form\Select;
use CCL\Content\Element\Basic\Paragraph;

defined('_JEXEC') or die();

// Check if the timezone switcher is enabled
if (DPCalendarHelper::getComponentParameter('enable_tz_switcher', '0') == '0') {
	return;
}

// Load chosen to make the list nicer
DPCalendarHelper::loadLibrary(array('chosen' => '.dp-timezone-switcher'));

// Load the language file
JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

// The regions of the timezones
$regions = array(
	'Africa'     => DateTimeZone::AFRICA,
	'America'    => DateTimeZone::AMERICA,
	'Antarctica' => DateTimeZone::ANTARCTICA,
	'Aisa'       => DateTimeZone::ASIA,
	'Atlantic'   => DateTimeZone::ATLANTIC,
	'Europe'     => DateTimeZone::EUROPE,
	'Indian'     => DateTimeZone::INDIAN,
	'Pacific'    => DateTimeZone::PACIFIC
);

// Compile the timezones later we do with with optgroups
$timezones = array();
foreach ($regions as $name => $mask) {
	$zones = DateTimeZone::listIdentifiers($mask);
	foreach ($zones as $timezone) {
		$timezones[$name][$timezone] = $timezone;
	}
}

// Get the actual timezone
$actualTimezone = JFactory::getSession()->get('user-timezone', DPCalendarHelper::getDate()->getTimezone()->getName(), 'DPCalendar');

// Set up the form
$form = $displayData['root']->addChild(new Form('user-timezone-form', JRoute::_(JUri::base()), 'tz-form', 'GET', array('form-validate')));
$form->addChild(new Label('text', 'timezone'))->setContent(JText::_('COM_DPCALENDAR_CHOOSE_TIMEZONE') . ': ');

// Define the select box
$select = $form->addChild(new Select('timezone', 'tz'));
$select->addClass('dp-timezone-switcher', true);
$select->addAttribute('onchange', 'this.form.submit()');

$select->addOption(JText::_('JLIB_FORM_VALUE_TIMEZONE_UTC'), 'UTC', $actualTimezone == 'UTC');
foreach ($timezones as $region => $list) {
	foreach ($list as $timezone => $name) {
		$select->addOption($name, $timezone, $name == $actualTimezone);
	}
}

$form->addChild(new Input('task', 'hidden', 'task', 'profile.tz'));
$form->addChild(new Input('return', 'hidden', 'return', base64_encode(JUri::getInstance()->toString())));
$form->addChild(new Input('option', 'hidden', 'option', 'com_dpcalendar'));
$form->addChild(new Input('view', 'hidden', 'view', 'profile'));

JFactory::getDocument()->addStyleDeclaration('#' . $form->getId() . ' label, #' . $form->getId() . ' div {float: left; margin: 5px}');
JFactory::getDocument()->addStyleDeclaration('#' . $form->getId() . ' {display: overlay; margin: 5px}');
