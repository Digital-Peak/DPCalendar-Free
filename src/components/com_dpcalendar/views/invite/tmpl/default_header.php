<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Component\Icon;

// The params
$params = $this->params;

/** @var Container $root **/
$root = $this->root->addChild(new Container('actions', array('noprint')));
$root->setProtectedClass('noprint');

// Create the invite button
DPCalendarHelper::renderLayout(
	'content.button',
	array(
		'type'    => Icon::OK,
		'root'    => $root,
		'text'    => 'COM_DPCALENDAR_VIEW_INVITE_SEND_BUTTON',
		'onclick' => "Joomla.submitbutton('event.invite')"
	)
);

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
