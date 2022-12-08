<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<div class="com-dpcalendar-list__actions dp-button-bar dp-print-hide">
	<div class="dp-button-bar__navigation">
		<button type="button" class="dp-button dp-button-action dp-button-prev" data-href="<?php echo $this->prevLink; ?>"
				aria-label="<?php echo $this->translate('COM_DPCALENDAR_PREVIOUS'); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::BACK]); ?>
		</button>
		<button type="button" class="dp-button dp-button-action dp-button-next" data-href="<?php echo $this->nextLink; ?>"
				aria-label="<?php echo $this->translate('COM_DPCALENDAR_NEXT'); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::NEXT]); ?>
		</button>
	</div>
	<div class="dp-button-bar__title dp-title">
		<span class="dp-title__start"><?php echo $this->startDate->format($this->params->get('list_title_format', 'j.n.Y')); ?></span>
		<span class="dp-title__separator"> - </span>
		<span class="dp-title__end"><?php echo $this->endDate->format($this->params->get('list_title_format', 'j.n.Y')); ?></span>
	</div>
	<div class="dp-button-bar__actions">
		<?php if (DPCalendarHelper::canCreateEvent()) { ?>
			<button type="button" class="dp-button dp-button-action dp-button-create"
					data-href="<?php echo $this->router->getEventFormRoute(0, $this->returnPage); ?>">
				<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::PLUS]); ?>
				<?php echo $this->translate('JACTION_CREATE'); ?>
			</button>
		<?php } ?>
		<?php if ($this->params->get('list_show_print', 1)) { ?>
			<button type="button" class="dp-button dp-button-print" data-selector=".com-dpcalendar-list">
				<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::PRINTING]); ?>
				<?php echo $this->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT'); ?>
			</button>
		<?php } ?>
		<?php if ($this->params->get('list_manage_search_form', 1)) { ?>
			<button type="button" class="dp-button dp-button-search">
				<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::SEARCH]); ?>
				<?php echo $this->translate('JSEARCH_FILTER'); ?>
			</button>
		<?php } ?>
	</div>
</div>
