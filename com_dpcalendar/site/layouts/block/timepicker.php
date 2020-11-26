<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<input type="text"
	   id="<?php echo $displayData['id']; ?>"
	   name="<?php echo $displayData['name']; ?>"
	   class="dp-timepicker dp-input dp-input-text form-control"
	   autocomplete="off"
	   data-format="<?php echo $displayData['dateHelper']->convertPHPDateToJS($displayData['format']); ?>"
	   data-time="<?php echo $displayData['date']->format('H:i:s', true); ?>"
	   data-pair="<?php echo $displayData['pair']; ?>"
	   data-min-time="<?php echo $displayData['min']; ?>"
	   data-max-time="<?php echo $displayData['max']; ?>"
	   data-step="<?php echo $displayData['step']; ?>"
>
