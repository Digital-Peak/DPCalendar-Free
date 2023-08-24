<?php
use Joomla\CMS\Language\Text;
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$url = 'http://extensions.joomla.org/extensions/extension/calendars-a-events/events/dpcalendar' . (DPCalendarHelper::isFree() ? '-lite' : '');
?>
<div class="com-dpcalendar-cpanel__footer dp-footer">
	<div class="dp-footer__version">
		<?php echo Text::sprintf('COM_DPCALENDAR_FOOTER', $this->input->getString('DPCALENDAR_VERSION')); ?>
	</div>
	<div class="dp-footer__jed">
		<span class="small">If you like DPCalendar, please post a positive review at the</span>
		<a href="<?php echo $url; ?>" target="_blank">Joomla! Extensions Directory</a>
	</div>
</div>
