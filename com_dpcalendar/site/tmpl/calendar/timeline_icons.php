<?php
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<div class="com-dpcalendar-calendar-timeline__icons">
	<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::DELETE]); ?>
	<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::EDIT]); ?>
	<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::PLUS]); ?>
	<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::PRINTING]); ?>
	<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::CALENDAR]); ?>
	<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::NEXT]); ?>
	<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::BACK]); ?>
	<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::USERS]); ?>
	<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::MONEY]); ?>
</div>
