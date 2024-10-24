<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
use Joomla\CMS\Uri\Uri;

$params = $this->params;
?>
<div class="com-dpcalendar-profile__events">
	<h2 class="dp-heading"><?php echo $this->translate('COM_DPCALENDAR_VIEW_PROFILE_UPCOMING_EVENTS'); ?></h2>
	<ul class="dp-events dp-list dp-list_unordered">
		<?php foreach ($this->events as $event) { ?>
			<?php $this->displayData['event'] = $event; ?>
			<?php $calendar = \Joomla\CMS\Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($event->catid); ?>
			<li class="dp-list-unordered__item dp-event">
				<?php if ($event->state == 0) { ?>
					<span class="dp-event__state dp-event__state_unpublished"><?php echo $this->translate('JUNPUBLISHED'); ?></span>
				<?php } ?>
				<?php if (Booking::openForBooking($event)) { ?>
					<a href="<?php echo $this->router->getBookingFormRouteFromEvent($event, Uri::getInstance()); ?>" class="dp-link"
						aria-label="<?php echo $this->translate('COM_DPCALENDAR_BOOK'); ?>">
						<?php echo $this->layoutHelper->renderLayout(
							'block.icon',
							['icon' => Icon::BOOK, 'title' => $this->translate('COM_DPCALENDAR_BOOK')]
						); ?>
					</a>
				<?php } ?>
				<?php if ($calendar->canEdit() || ($calendar->canEditOwn() && $event->created_by == $user->id)) { ?>
					<a href="<?php echo $this->router->getEventFormRoute($event->id, Uri::getInstance()); ?>" class="dp-link"
						aria-label="<?php echo $this->translate('JACTION_EDIT'); ?>">
						<?php echo $this->layoutHelper->renderLayout(
							'block.icon',
							['icon' => Icon::EDIT, 'title' => $this->translate('JACTION_EDIT')]
						); ?>
					</a>
				<?php } ?>
				<?php if ($calendar->canDelete() || ($calendar->canEditOwn() && $event->created_by == $user->id)) { ?>
					<a href="<?php echo $this->router->getEventDeleteRoute($event->id, Uri::getInstance()); ?>" class="dp-link"
						aria-label="<?php echo $this->translate('JACTION_DELETE'); ?>">
						<?php echo $this->layoutHelper->renderLayout(
							'block.icon',
							['icon' => Icon::DELETE, 'title' => $this->translate('JACTION_DELETE')]
						); ?>
					</a>
				<?php } ?>
				<a href="<?php echo $this->router->getEventRoute($event->id, $event->catid); ?>" class="dp-event__link dp-link">
					<?php echo $event->title; ?>
				</a>
				<span class="dp-event__date">
					<?php $date = $this->dateHelper->getDateStringFromEvent($event, $params->get('date_format'), $params->get('time_format')); ?>
					(<?php echo $this->translate('COM_DPCALENDAR_DATE') . ': ' . $date; ?>)
				</span>
				<span class="dp-event__calendar">
					<?php echo $this->translate('COM_DPCALENDAR_CALENDAR'); ?>:
					<?php echo $calendar != null ? $calendar->getTitle() : $event->catid; ?>
				</span>
				<?php if (!empty($event->locations)) { ?>
					<span class="dp-event__locations dp-locations">
						<?php foreach ($event->locations as $location) { ?>
							<span class="dp-event__location dp-location">
								<span class="dp-location__details"
									  data-latitude="<?php echo $location->latitude; ?>"
									  data-longitude="<?php echo $location->longitude; ?>"
									  data-title="<?php echo $location->title; ?>"
									  data-color="<?php echo $event->color; ?>"></span>
								<a href="<?php echo $this->router->getLocationRoute($location); ?>" class="dp-location__url dp-link">
									<?php echo $location->title; ?>
								</a>
							</span>
						<?php } ?>
					</span>
				<?php } ?>
			</li>
		<?php } ?>
	</ul>
</div>
