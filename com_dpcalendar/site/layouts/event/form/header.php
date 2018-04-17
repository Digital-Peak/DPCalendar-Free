<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Component\Icon;
use CCL\Content\Element\Basic\Heading;
use CCL\Content\Element\Component\Alert;
use CCL\Content\Element\Basic\TextBlock;

/**
 * Layout variables
 * -----------------
 * @var Container $root
 * @var object    $event
 * @var object    $form
 * @var object    $params
 * @var string    $returnPage
 **/
extract($displayData);

/** @var Container $root * */
$root = $root->addChild(new Container('actions'));
$root->addClass('noprint', true);
$root->addClass('dp-actions-container', true);

$calendar = DPCalendarHelper::getCalendar($form->getValue('catid'));
if (!$event->id || !$calendar || $calendar->canEdit || ($calendar->canEditOwn && $event->created_by == JFactory::getUser()->id)) {
	// Create the save button
	DPCalendarHelper::renderLayout(
		'content.button',
		array(
			'id'      => 'apply',
			'type'    => Icon::OK,
			'root'    => $root,
			'text'    => 'JAPPLY',
			'onclick' => "Joomla.submitbutton('event.apply')"
		)
	);
}

// Create the save and close button
DPCalendarHelper::renderLayout(
	'content.button',
	array(
		'id'      => 'save',
		'type'    => Icon::OK,
		'root'    => $root,
		'text'    => 'JSAVE',
		'onclick' => "Joomla.submitbutton('event.save')"
	)
);

// Create the save and new button
DPCalendarHelper::renderLayout(
	'content.button',
	array(
		'id'      => 'save2new',
		'type'    => Icon::OK,
		'root'    => $root,
		'text'    => 'JTOOLBAR_SAVE_AND_NEW',
		'onclick' => "Joomla.submitbutton('event.save2new')"
	)
);

// Create the save as copy button
DPCalendarHelper::renderLayout(
	'content.button',
	array(
		'id'      => 'save2copy',
		'type'    => Icon::OK,
		'root'    => $root,
		'text'    => 'JTOOLBAR_SAVE_AS_COPY',
		'onclick' => "Joomla.submitbutton('event.save2copy')"
	)
);

if ($params->get('save_history', 0)) {
	$root->addChild(new TextBlock('history'))->setContent($form->getInput('contenthistory'));
}

// Create the cancel button
DPCalendarHelper::renderLayout(
	'content.button',
	array(
		'type'    => Icon::CANCEL,
		'root'    => $root,
		'text'    => 'JCANCEL',
		'onclick' => "Joomla.submitbutton('event.cancel')"
	)
);

// Create the delete button
DPCalendarHelper::renderLayout(
	'content.button',
	array(
		'type'    => Icon::DELETE,
		'root'    => $root,
		'text'    => 'JACTION_DELETE',
		'onclick' => "Joomla.submitbutton('event.delete')"
	)
);
