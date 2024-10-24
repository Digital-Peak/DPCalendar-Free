<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
?>
<div class="com-dpcalendar-booking__message">
	<?php echo Text::plural('COM_DPCALENDAR_VIEW_BOOKING_ORDER_EDIT_TICKETS_TEXT', is_countable($this->tickets) ? count($this->tickets) : 0); ?>
</div>
