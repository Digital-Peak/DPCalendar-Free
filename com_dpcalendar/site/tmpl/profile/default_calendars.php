<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

$itemId = '&Itemid=' . $this->input->getInt('Itemid', 0);
$return = base64_encode(Uri::getInstance());
?>
<div class="com-dpcalendar-profile__calendars">
	<ul class="dp-davcalendars dp-list">
		<?php foreach ($this->calendars as $url => $calendar) { ?>
			<li class="dp-davcalendar">
				<span class="dp-davcalendar__color" style="background-color: #<?php echo str_replace('#', '', (string) $calendar->calendarcolor); ?>"></span>
				<?php if (empty($calendar->member_principal_access)) { ?>
					<?php $link = 'index.php?option=com_dpcalendar&task=davcalendar.delete&return='
						. $return . '&c_id=' . (int)$calendar->id; ?>
					<a href="<?php echo $this->router->route($link); ?>" class="dp-link dp-davcalendar__delete"
						aria-label="<?php echo $this->translate('COM_DPCALENDAR_VIEW_PROFILE_DELETE_PROFILE_CALENDAR'); ?>">
						<?php echo $this->layoutHelper->renderLayout('block.icon', [
							'icon'  => Icon::DELETE,
							'title' => $this->translate('COM_DPCALENDAR_VIEW_PROFILE_DELETE_PROFILE_CALENDAR')
						]); ?>
					</a>
					<a href="<?php echo $this->router->getEventFormRoute(0, Uri::getInstance(), 'catid=cd-' . (int)$calendar->id); ?>"
					   class="dp-link">
						<?php echo $this->layoutHelper->renderLayout('block.icon', [
							'icon'  => Icon::PLUS,
							'title' => $this->translate('COM_DPCALENDAR_VIEW_PROFILE_CREATE_EVENT_IN_CALENDAR')
						]); ?>
					</a>
					<?php $link = 'index.php?option=com_dpcalendar&task=davcalendar.edit&c_id=' . (int)$calendar->id . $itemId . '&return=' . $return; ?>
					<a href="<?php echo $this->router->route($link); ?>" class="dp-link dp-davcalendar__title">
						<?php echo $calendar->displayname; ?>
					</a>
				<?php } else { ?>
					<?php $text = Text::sprintf(
						'COM_DPCALENDAR_VIEW_PROFILE_SHARED_CALENDAR',
						$calendar->member_principal_name,
						$this->translate(
							'COM_DPCALENDAR_VIEW_PROFILE_SHARED_CALENDAR_ACCESS_' . (str_contains(
								(string) $calendar->member_principal_access,
								'/calendar-proxy-read'
							) ? 'READ' : 'WRITE')
						)
					); ?>
					<span class="dp-davcalendar__lock">
					<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::LOCK, 'title' => $text]); ?>
					</span>
					<span class="dp-davcalendar__name"><?php echo $calendar->displayname; ?></span>
				<?php } ?>
				<div class="dp-davcalendar__url">
					<span class="dp-text"><?php echo $this->translate('COM_DPCALENDAR_VIEW_PROFILE_TABLE_CALDAV_URL_LABEL'); ?></span>
					<a href="<?php echo Uri::base() . 'components/com_dpcalendar/caldav.php/' . $url; ?>" class="dp-link">
						<?php echo $calendar->uri; ?>
					</a>
				</div>
			</li>
		<?php } ?>
	</ul>
</div>
