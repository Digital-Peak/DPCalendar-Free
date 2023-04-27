<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\HTML\Block\Icon;

$displayData['document']->addScriptOptions('calendar.names', $displayData['dateHelper']->getNames());

$displayData['localFormat'] = !empty($displayData['localFormat']) ? $displayData['localFormat'] : false;
?>
<div class="dp-datepicker dp-datepicker-<?php echo $displayData['name']; ?>">
	<input type="text"
		<?php echo !empty($displayData['id']) ? 'id="' . $displayData['id'] . '"' : ''; ?>
		name="<?php echo $displayData['name']; ?>"
		class="dp-datepicker__input dp-input dp-input-text"
		title="<?php echo !empty($displayData['title']) ? $displayData['title'] : ''; ?>"
		placeholder="<?php echo !empty($displayData['title']) ? $displayData['title'] : ''; ?>"
		autocomplete="off"
		data-format="<?php echo $displayData['dateHelper']->convertPHPDateToJS($displayData['format']); ?>"
		data-date="<?php echo !empty($displayData['date']) ? $displayData['date']->format('Y-m-d', $displayData['localFormat']) : ''; ?>"
		data-first-day="<?php echo !empty($displayData['firstDay']) ? $displayData['firstDay'] : ''; ?>"
		data-pair="<?php echo !empty($displayData['pair']) ? $displayData['pair'] : ''; ?>">
	<button type="button" class="dp-datepicker__button dp-button">
		<?php echo $displayData['layoutHelper']->renderLayout('block.icon', ['icon' => Icon::CALENDAR]); ?>
	</button>
</div>
