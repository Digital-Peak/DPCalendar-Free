<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\DescriptionListHorizontal;
use CCL\Content\Element\Basic\Description\Term;
use CCL\Content\Element\Basic\Description\Description;

// Global variables
$root       = $displayData['root'];
$id         = $displayData['id'];
$label      = $displayData['label'];
$content    = $displayData['content'];
$classes    = isset($displayData['classes']) ? $displayData['classes'] : array();
$attributes = isset($displayData['attributes']) ? $displayData['attributes'] : array();

// The description list
$dl = new DescriptionListHorizontal($id, $classes, $attributes);

// Add the term
$term = new Term('label', array('label', 'dpcalendar-label'));
$term->setProtectedClass('dpcalendar-label');
$term->setContent(JText::_($label));
$dl->setTerm($term);

// Add the description
$desc = new Description('content', array('content', 'dp-event-content'));
$desc->setProtectedClass('dp-event-content');
$desc->setContent($content);
$dl->setDescription($desc);

// Add it to the parent
$root->addChild($dl);
