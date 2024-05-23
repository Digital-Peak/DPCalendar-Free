<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;

if (!$this->params->get('list_manage_search_form', 1)) {
	return;
}

$action  = $this->router->route('index.php?option=com_dpcalendar&view=list&Itemid=' . $this->input->getInt('Itemid', 0) . $this->tmpl);
$visible = $this->activeFilters || $this->params->get('list_manage_search_form', 1) == 2;
?>
<form action="<?php echo $action; ?>" method="post"
	class="com-dpcalendar-list__form com-dpcalendar-list__form_<?php echo $visible ? '' : 'hidden'; ?> dp-form form-validate dp-print-hide">
	<div class="com-dpcalendar-list__form-container">
		<?php foreach ($this->filterForm->getFieldset() as $field) { ?>
			<?php echo $field->input; ?>
		<?php } ?>
	</div>
	<div class="com-dpcalendar-list__button-bar dp-button-bar">
		<?php if (!in_array('location', $this->params->get('list_search_form_hidden_fields', [])) && $this->params->get('map_provider', 'openstreetmap') != 'none') { ?>
			<button class="dp-button dp-button-current-location" type="button">
				<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::LOCATION]); ?>
				<?php echo $this->translate('COM_DPCALENDAR_VIEW_MAP_LABEL_CURRENT_LOCATION'); ?>
			</button>
		<?php } ?>
		<button class="dp-button dp-button-search" type="button">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::OK]); ?>
			<?php echo $this->translate('JSEARCH_FILTER'); ?>
		</button>
		<button class="dp-button dp-button-clear" type="button">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::CANCEL]); ?>
			<?php echo $this->translate('JCLEAR'); ?>
		</button>
	</div>
	<input type="hidden" name="Itemid" value="<?php echo $this->input->getInt('Itemid', 0); ?>" class="dp-input dp-input-hidden">
</form>
