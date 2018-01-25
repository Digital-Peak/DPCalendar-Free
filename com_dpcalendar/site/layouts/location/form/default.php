<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

use CCL\Content\Element\Basic\Form;
use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Element;

/**
 * Layout variables
 * -----------------
 * @var object $location
 * @var object $form
 * @var object $input
 * @var object $params
 * @var string $returnPage
 **/
extract($displayData);

defined('_JEXEC') or die();

// Load the needed JS libraries
DPCalendarHelper::loadLibrary(array('jquery' => true, 'chosen' => true, 'maps' => true, 'dpcalendar' => true));
JHtml::_('script', 'com_dpcalendar/dpcalendar/layouts/location/form/default.js', ['relative' => true], ['defer' => true]);

// Add some CSS rules
JFactory::getDocument()->addStyleDeclaration('.map_canvas{width:100%;height:200px;} #dp-locationform-actions {margin-bottom:10px}');

// The form element
$tmpl = $input->getCmd('tmpl') ? '&tmpl=' . $input->getCmd('tmpl') : '';
$root = new Form(
	'dp-locationform',
	JRoute::_('index.php?option=com_dpcalendar&view=locationform&l_id=' . (int)$location->id . $tmpl, false),
	'adminForm',
	'POST',
	array('form-validate'),
	array('ccl-prefix' => $root->getPrefix())
);

if ($app->isSite()) {
	$displayData['root'] = $root;

	// Load the header template
	DPCalendarHelper::renderLayout('location.form.toolbar', $displayData);
}

// Load the form from the layout
DPCalendarHelper::renderLayout('content.form', array(
	'root'            => $root,
	'jform'           => $form,
	'fieldsToHide'    => array('id', 'ordering', 'revision'),
	'fieldsetsToHide' => array('optional'),
	'return'          => $returnPage
));

// Add the map element
$root->addChild(new Container('map'))->addChild(new Element('map-canvas'))->addClass('map_canvas', true);

// Render the tree
echo DPCalendarHelper::renderElement($root, $params);
