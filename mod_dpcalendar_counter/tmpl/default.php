<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Link;
use CCL\Content\Element\Basic\Element;
use CCL\Content\Element\Basic\Frame;

if ($item == null)
{
	return;
}

// Load the required JS libraries
DPCalendarHelper::loadLibrary(array('jquery' => true, 'dpcalendar' => true));

// Load the counter library
JHtml::_('script', 'com_dpcalendar/jquery/ext/jquery.countdown.min.js', false, true);
JHtml::_('stylesheet', 'com_dpcalendar/jquery/ext/jquery.countdown.css', array(), true);

// Load the module stylesheet
JHtml::_('stylesheet', 'mod_dpcalendar_counter/default.css', array(), true);

// The layout for the counter
$layout = new Container('dp-module-counter-' . $module->id . '-layout', array('layout'), array('ccl-prefix' => 'dp-module-counter-'));
$layout->addClass('countdown-row', true);

// The counter element
$c = $layout->addChild(new Container('date'))->addClass('countdown-date', true);
$c->setContent('
{y<}<span class="countdown-section"><span class="countdown-amount">{yn}</span><br />{yl}</span>{y>}
{o<}<span class="countdown-section"><span class="countdown-amount">{on}</span><br />{ol}</span>{o>}
{w<}<span class="countdown-section"><span class="countdown-amount">{wn}</span><br />{wl}</span>{w>}
{d<}<span class="countdown-section"><span class="countdown-amount">{dn}</span><br />{dl}</span>{d>}
{h<}<span class="countdown-section"><span class="countdown-amount">{hn}</span><br />{hl}</span>{h>}
{m<}<span class="countdown-section"><span class="countdown-amount">{mn}</span><br />{ml}</span>{m>}
{s<}<span class="countdown-section"><span class="countdown-amount">{sn}</span><br />{sl}</span>{s>}
');

// The body of the module
$body = $layout->addChild(new Container('content'))->addClass('countdown-content', true);
$body->addChild(new Link('link', DPCalendarHelperRoute::getEventRoute($item->id, $item->catid), '', array('link')))->setContent($item->title);
$body->addChild(new Element('description', array('description')))->setContent(JHTML::_('content.prepare', JHtml::_('string.truncate', $item->description, $params->get('description_length'))));

// Date compiling
$d = DPCalendarHelper::getDate($item->start_date, $item->all_day);
$targetDate  = $d->format('Y', true) . ",";
$targetDate .= ($d->format('m', true) - 1) . ",";
$targetDate .= $d->format('d', true) . ",";
$targetDate .= $d->format('H', true) . ",";
$targetDate .= $d->format('i', true) . ",0";

// prepare the params
$tmp = clone JComponentHelper::getParams('com_dpcalendar');
$tmp->set('event_date_format', $params->get('date_format', 'm.d.Y'));
$tmp->set('event_time_format', $params->get('time_format', 'g:i a'));
$tmp->set('description_length', $params->get('description_length', $tmp->get('description_length')));

// The layout for the counter
$layout = preg_replace('#\r|\n#', '', DPCalendarHelper::renderElement($layout, $params));

// The output when the event is running
$expiryText = preg_replace('#\r|\n#', "", DPCalendarHelper::renderEvents(array($item), JText::_('MOD_DPCALENDAR_COUNTER_ONGOING_OUTPUT'), $tmp));

$labelsPlural = array(
	JText::_('MOD_DPCALENDAR_COUNTER_LABEL_YEARS'),
	JText::_('MOD_DPCALENDAR_COUNTER_LABEL_MONTHS'),
	JText::_('MOD_DPCALENDAR_COUNTER_LABEL_WEEKS'),
	JText::_('MOD_DPCALENDAR_COUNTER_LABEL_DAYS'),
	JText::_('MOD_DPCALENDAR_COUNTER_LABEL_HOURS'),
	JText::_('MOD_DPCALENDAR_COUNTER_LABEL_MINUTES'),
	JText::_('MOD_DPCALENDAR_COUNTER_LABEL_SECONDS')
);
$labels = array(
	JText::_('MOD_DPCALENDAR_COUNTER_LABEL_YEAR'),
	JText::_('MOD_DPCALENDAR_COUNTER_LABEL_MONTH'),
	JText::_('MOD_DPCALENDAR_COUNTER_LABEL_WEEK'),
	JText::_('MOD_DPCALENDAR_COUNTER_LABEL_DAY'),
	JText::_('MOD_DPCALENDAR_COUNTER_LABEL_HOUR'),
	JText::_('MOD_DPCALENDAR_COUNTER_LABEL_MINUTE'),
	JText::_('MOD_DPCALENDAR_COUNTER_LABEL_SECOND')
);

// The root container
$root = new Container('dp-module-counter-' . $module->id, array('root'), array('ccl-prefix' => 'dp-module-counter-'));

$counter = $root->addChild(new Container('counter'));
$counter->addClass('countdown', true);
$counter->setContent(JText::_("MOD_DPCALENDAR_COUNTER_JSERR"));

// The javascript code which starts the counter
$code  = "// <![CDATA[ \n";
$code .= "jQuery(document).ready(function() {\n";
$code .= "	jQuery('#" . $counter->getId() . "').countdown({until: new Date(" . $targetDate . "), \n";
$code .= "		description:  '" . str_replace('\'', '\\\'', $item->title) . "', \n";
$code .= "		expiryText:   '" . str_replace('\'', '\\\'', $expiryText)  . "', \n";
$code .= "		layout:       '" . str_replace('\'', '\\\'', $layout)      . "', \n";
$code .= "		labels:       ['" . implode("','", $labelsPlural) . "'], \n";
$code .= "		labels1:      ['" . implode("','", $labels)       . "'], \n";
$code .= "		alwaysExpire: true\n";
$code .= "	});\n";
if ($params->get('disable_counting'))
{
	$code .= "	jQuery('#" . $counter->getId() . "').countdown('pause');\n";
}
$code .= "});\n";
$code .= "// ]]>\n";
JFactory::getDocument()->addScriptDeclaration($code);

if ($params->get('show_as_popup'))
{
	// Load the required JS libraries
	JHtml::_('script', 'mod_dpcalendar_counter/default.js', false, true);
	JHtml::_('behavior.modal', '.dp-module-map-event-link-invalid');

	// The root container for the modal iframe
	$m = $root->addChild(new Container('modal', array('modal')));
	$m->addClass('dp-module-counter-modal', true);

	// Add the iframe which holds the content
	$m->addChild(new Frame('frame', ''));
}

// Render the root element
echo DPCalendarHelper::renderElement($root, $params);

return;

if ($params->get('show_as_popup', '0') == '1' || $params->get('show_as_popup', '0') == '3')
{
	$calCode = "jQuery(document).ready(function() {\n";
	$calCode .= "jQuery('body').on('click','.dpc-counter-event-link', function (event) {\n";
	$calCode .= "	event.stopPropagation();\n";

	if ($params->get('show_as_popup', '0') == '1')
	{
		$calCode .= "	var link = jQuery(this).attr('href');\n";
		$calCode .= "	jQuery('#" . $targetId . "-modal').on('show', function () {\n";
		$calCode .= "		var url = new Url(link);\n";
		$calCode .= "		url.query.tmpl = 'component';\n";
		$calCode .= "		jQuery('#" . $targetId . "-modal iframe').attr('src', url.toString());\n";
		$calCode .= "	});\n";
		$calCode .= "	jQuery('#" . $targetId . "-modal iframe').removeAttr('src');\n";
		$calCode .= "	jQuery('#" . $targetId . "-modal').modal();\n";
	}
	else if ($params->get('show_as_popup', '0') == '3')
	{
		JHtml::_('behavior.modal', '.dpc-counter-event-link-invalid');

		$calCode .= "	var modal = jQuery('#" . $targetId . "-modal');\n";
		$calCode .= "	var width = jQuery(window).width();\n";
		$calCode .= "	var url = new Url(jQuery(this).attr('href'));\n";
		$calCode .= "	url.query.tmpl = 'component';\n";
		$calCode .= "	SqueezeBox.open(url.toString(), {\n";
		$calCode .= "		handler : 'iframe',\n";
		$calCode .= "		size : {\n";
		$calCode .= "			x : (width < 650 ? width - (width * 0.10) : modal.width() < 650 ? 650 : modal.width()),\n";
		$calCode .= "			y : modal.height()\n";
		$calCode .= "		}\n";
		$calCode .= "	});\n";
	}
	$calCode .= "	return false;\n";
	$calCode .= "});\n";
	$calCode .= "});\n";
	$document->addScriptDeclaration($calCode);
	?>
<div id="<?php echo $targetId;?>-modal" class="modal hide" tabindex="-1" role="dialog" aria-hidden="true"
	style="height:500px">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
  	<iframe style="width:99.6%;height:95%;border:none;"></iframe>
</div>
<?php
}
?>
