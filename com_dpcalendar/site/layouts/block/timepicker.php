<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$displayData['document']->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_AUTOCOMPLETE);
$displayData['document']->loadScriptFile('dpcalendar/layouts/block/timepicker.js');
?>
<input type="text"
	   id="<?php echo $displayData['id']; ?>"
	   name="<?php echo $displayData['name']; ?>"
	   class="dp-timepicker dp-input dp-input-text"
	   autocomplete="off"
	   data-format="<?php echo $displayData['dateHelper']->convertPHPDateToMoment($displayData['format']); ?>"
	   data-time="<?php echo $displayData['date']->format('H:i:s', true); ?>"
	   data-pair="<?php echo $displayData['pair']; ?>"
	   data-min="<?php echo $displayData['min']; ?>"
	   data-max="<?php echo $displayData['max']; ?>"
	   data-step="<?php echo $displayData['step']; ?>"
>
