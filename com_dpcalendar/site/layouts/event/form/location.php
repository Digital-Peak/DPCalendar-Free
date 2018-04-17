<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Button;
use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Element;
use CCL\Content\Element\Basic\Form\Input;
use CCL\Content\Element\Basic\Heading;
use CCL\Content\Element\Basic\TextBlock;
use CCL\Content\Element\Component\Icon;

/**
 * Layout variables
 * -----------------
 * @var object $event
 * @var object $form
 * @var object $user
 * @var object $input
 * @var object $params
 * @var string $returnPage
 **/
extract($displayData);

// Load the maps scripts when required
if (!$params->get('event_form_change_location', 1)) {
	return;
}

// Add the maps library
DPCalendarHelper::loadLibrary(array('maps' => true));

$c = $root->addChild(new Container('map'));

// Add the map element
$map = $c->addChild(
	new Element(
		'frame',
		array('dpcalendar-map', 'dpcalendar-fixed-map'),
		array(
			'data-zoom'      => $params->get('map_zoom', 6),
			'data-latitude'  => $params->get('map_lat', 47),
			'data-longitude' => $params->get('map_long', 4)
		)
	)
);
$map->setProtectedClass('dpcalendar-map');
$map->setProtectedClass('dpcalendar-fixed-map');

// Add the heading
$h = $c->addChild(new Heading('title', 3));
$h->addChild(new TextBlock('text'))->setContent(JText::_('COM_DPCALENDAR_VIEW_EVENT_FORM_CREATE_LOCATION'));

// The toggle icons
$t = $h->addChild(new TextBlock('toggle'));
$t->addChild(new Icon('up', Icon::UP, array(), array('data-direction' => 'up')));
$t->addChild(new Icon('down', Icon::DOWN, array(), array('data-direction' => 'down')));

$fc = $c->addChild(new Container('form'));
$fc->addClass('dp-actions-container');

$fc->addChild(
	new Button(
		'save',
		$app->isClient('site') ? JText::_('JAPPLY') : JText::_('JSAVE'),
		new Icon('save', Icon::OK)
	)
)->addClass('dp-button', true);

JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
$locationForm = JModelLegacy::getInstance('Location', 'DPCalendarModel', ['ignore_request' => true])->getForm([], false, 'location');
$locationForm->setFieldAttribute('title', 'required', false);
$locationForm->setFieldAttribute('rooms', 'label', 'COM_DPCALENDAR_ROOMS');

// Load the form from the layout
DPCalendarHelper::renderLayout(
	'content.form',
	array(
		'root'            => $fc,
		'jform'           => $locationForm,
		'flat'            => true,
		'fieldsToHide'    => array('geocomplete', 'latitude', 'longitude'),
		'fieldsetsToHide' => array('description', 'rooms', 'publishing', 'optional'),
		'hideTask'        => true
	)
);
$fc->addChild(new Input('token', 'hidden', 'location_token', JSession::getFormToken()));

