<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Heading;
use CCL\Content\Element\Basic\Image;
use CCL\Content\Element\Basic\Element;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
<link rel='stylesheet' type='text/css' href='<?php echo JUri::base()?>/media/com_dpcalendar/css/fullcalendar/fullcalendar.min.css' />
<link rel='stylesheet' type='text/css' href='<?php echo JUri::base()?>/media/com_dpcalendar/css/dpcalendar/views/calendar/default.css' />
<link rel='stylesheet' type='text/css' href='<?php echo JUri::base()?>/media/com_dpcalendar/css/jquery/themes/bootstrap/jquery-ui.custom.css' />

<style type='text/css'>
body {
	text-align: center;
	font-size: 14px;
	font-family: "Lucida Grande",Helvetica,Arial,Verdana,sans-serif;
	-webkit-print-color-adjust:exact;
}
#dp-calendar-calendar, #dp-calendar-map {
	width: 900px !important;
	margin: 0 auto;
}
#dp-calendar-calendar {
	margin-bottom: 10px;
}
#dpcalendar_view_toggle_status {
	margin-bottom: 15px;
}
</style>

<script src="<?php echo JUri::base()?>/media/system/js/core.js" type="text/javascript"></script>
<script src="<?php echo JUri::base()?>/media/jui/js/jquery.min.js" type="text/javascript"></script>
<script src="<?php echo JUri::base()?>/media/jui/js/jquery-noconflict.js" type="text/javascript"></script>
<script src="<?php echo JUri::base()?>/media/jui/js/jquery-migrate.min.js" type="text/javascript"></script>
<script type='text/javascript' src='<?php echo JUri::base()?>/media/com_dpcalendar/js/dpcalendar/dpcalendar.js'></script>
<script type='text/javascript' src='<?php echo JUri::base()?>/media/com_dpcalendar/js/dpcalendar/map.js'></script>
<script type='text/javascript' src='<?php echo JUri::base()?>/media/com_dpcalendar/js/dpcalendar/calendar.js'></script>
<script type='text/javascript' src='<?php echo JUri::base()?>/media/com_dpcalendar/js//fullcalendar/moment.min.js'></script>
<script type='text/javascript' src='<?php echo JUri::base()?>/media/com_dpcalendar/js//fullcalendar/fullcalendar.min.js'></script>
<script type='text/javascript' src='<?php echo JUri::base()?>/media/com_dpcalendar/js//jquery/ui/jquery-ui.custom.min.js'></script>

<?php
$params = $this->params;

// Load the map when needed
if ($params->get('show_map', 1) == 1)
{
	$key = trim(DPCalendarHelper::getComponentParameter('map_api_google_jskey', ''));
	if ($key)
	{
		$key = '&key=' . $key;
	}
?>
<script type='text/javascript' src='<?php echo (JFactory::getApplication()->isSSLConnection() ? "https" : "http")?>://maps.googleapis.com/maps/api/js?language=<?php echo DPCalendarHelper::getGoogleLanguage() . $key?>'></script>
<?php
}

// Loading the strings for javascript
$texts = array();
$texts['COM_DPCALENDAR_VIEW_CALENDAR_ALL_DAY'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_ALL_DAY', true);
$texts['COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_TODAY'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_TODAY', true);
$texts['COM_DPCALENDAR_VIEW_CALENDAR_VIEW_MONTH'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_MONTH', true);
$texts['COM_DPCALENDAR_VIEW_CALENDAR_VIEW_WEEK'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_WEEK', true);
$texts['COM_DPCALENDAR_VIEW_CALENDAR_VIEW_DAY'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_DAY', true);
$texts['COM_DPCALENDAR_VIEW_CALENDAR_VIEW_LIST'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_LIST', true);
$texts['COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_UNTIL'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_UNTIL', true);
$texts['COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_PAST'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_PAST', true);
$texts['COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_TODAY'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_TODAY', true);
$texts['COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_TOMORROW'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_TOMORROW', true);
$texts['COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_THIS_WEEK'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_THIS_WEEK', true);
$texts['COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_NEXT_WEEK'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_NEXT_WEEK', true);
$texts['COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_THIS_MONTH'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_THIS_MONTH', true);
$texts['COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_NEXT_MONTH'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_NEXT_MONTH', true);
$texts['COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_FUTURE'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_FUTURE', true);
$texts['COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_WEEK'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_WEEK', true);

$texts['COM_DPCALENDAR_VIEW_CALENDAR_SHOW_DATEPICKER'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_SHOW_DATEPICKER', true);
$texts['COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT', true);

$texts['JCANCEL'] = JText::_('JCANCEL', true);
$texts['COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_TODAY'] = JText::_('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_TODAY', true);

// The root container
$this->root = new Container('dp-calendar');

if ($params->get('show_page_heading', 1))
{
	// The heading
	$h = $this->root->addChild(new Heading('heading', 1, array('dp-event-header')));
	$h->setProtectedClass('dp-event-header');
	$h->setContent($params->get('page_heading'));
}

// The text before content
$this->root->addChild(new Container('text-before'))->setContent(JHtml::_('content.prepare', $params->get('textbefore')));

$this->params->set('use_hash', true);
$this->params->set('echo_js_code', true);
$this->params->set('header_show_datepicker', false);
$this->params->set('header_show_print', false);
$this->params->set('event_create_form', 0);

// Load the calendar layout
$js = DPCalendarHelper::renderLayout(
	'calendar.calendar',
	array(
		'params'            => $this->params,
		'root'              => $this->root,
		'calendars'         => $this->doNotListCalendars,
		'selectedCalendars' => $this->selectedCalendars
	)
);

// The text after content
$this->root->addChild(new Container('text-after'))->setContent(JHtml::_('content.prepare', $params->get('textafter')));
?>
<script type='text/javascript'><?php echo $js; ?></script>
<?php
echo '<script type="application/json" class="joomla-script-options new">{"joomla.jtext": ' . json_encode($texts) . '}</script>';
?>
</head>
<body>
<?php
// Render the tree
echo DPCalendarHelper::renderElement($this->root, $params);
?>
</body>
</html>
