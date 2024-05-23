<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
use Joomla\CMS\Uri\Uri;
?>
<div class="com-dpcalendar-tickets__actions dp-button-bar dp-print-hide">
	<form class="dp-form" action="<?php echo Uri::getInstance()->toString(); ?>" method="post">
		<div class="dp-form__search">
			<input type="text" name="filter[search]" value="<?php echo $this->state->get('filter.search'); ?>" class="dp-input dp-input-text"
				placeholder="<?php echo $this->translate('JGLOBAL_FILTER_LABEL'); ?>">
		</div>
		<div class="dp-form__buttons">
			<button type="button" class="dp-button dp-button-search">
				<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::SEARCH]); ?>
			</button>
			<button type="button" class="dp-button dp-button-clear">
				<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::CANCEL]); ?>
			</button>
			<button type="button" class="dp-button dp-button-print" data-selector=".com-dpcalendar-tickets">
				<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::PRINTING]); ?>
			</button>
			<?php echo $this->afterButtonEventOutput; ?>
			<?php if (!$this->event && !$this->booking) { ?>
				<input type="checkbox" name="filter[future]" value="1" class="dp-input dp-input-checkbox"
					<?php echo $this->state->get('filter.future') == 1 ? 'checked="checked"' : ''; ?>>
				<label for="filter[future]" class="dp-label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_FUTURE'); ?></label>
			<?php } ?>
		</div>
		<div class="dp-form__limit"><?php echo $this->pagination->getLimitBox(); ?></div>
		<input type="hidden" name="limitstart" class="dp-input dp-input-hidden">
	</form>
</div>
