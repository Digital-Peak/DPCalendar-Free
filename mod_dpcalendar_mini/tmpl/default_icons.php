<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<div class="mod-dpcalendar-mini__icons">
	<?php echo $layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::DELETE]); ?>
	<?php echo $layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::EDIT]); ?>
	<?php echo $layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::PLUS]); ?>
	<?php echo $layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::PRINTING]); ?>
	<?php echo $layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::CALENDAR]); ?>
	<?php echo $layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::NEXT]); ?>
	<?php echo $layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::BACK]); ?>
</div>
