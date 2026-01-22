<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\ExternalCalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

/**
 * @var array $displayData
 * @var array $calendars
 * @var array $hidden_calendars
 * @var \DigitalPeak\Component\DPCalendar\Administrator\Translator\Translator $translator
 * @var \DigitalPeak\Component\DPCalendar\Administrator\Router\Router $router
 * @var \DigitalPeak\Component\DPCalendar\Administrator\Model\LayoutModel $layoutHelper
 * @var \Joomla\CMS\User\User $user
 * @var \Joomla\CMS\Form\Form $form
 * @var \Joomla\Registry\Registry $params
 */
extract($displayData);

if (in_array('calendars', $params->get('hidden_fields', []))) {
	$hidden_calendars = $calendars;
	$calendars        = [];
}
$hidden_calendars = empty($hidden_calendars) ? [-2] : array_map(fn($c): string => $c->getId(), $hidden_calendars);
?>
<div class="dp-filter<?php echo $params->get('form_state', 1) == 2 ? '' : ' dp-filter_hidden'; ?>">
	<?php if (count($calendars ?: []) >= 10) { ?>
		<div class="dp-filter-toggle">
			<input type="checkbox" id="calendars-toggle" class="dp-input dp-input-checkbox dp-filter-toggle-input" checked>
			<label for="calendars-toggle" class="dp-input-label"><?php echo $translator->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOGGLE'); ?></label>
		</div>
	<?php } ?>
	<form class="dp-filter__calendars dp-form form-validate dp-print-hide" method="post" action="<?php echo $action ?? ''; ?>">
		<?php foreach ($calendars as $calendar) { ?>
			<?php $icalRoute = $router->getCalendarIcalRoute($calendar->getId()); ?>
			<div class="dp-calendar">
				<label for="cal-<?php echo $calendar->getId(); ?>" class="dp-input-label dp-calendar__label">
					<input type="checkbox" name="filter[calendars][]" value="<?php echo $calendar->getId(); ?>"
						<?php echo !$form->getValue('calendars', 'filter', []) || in_array($calendar->getId(), $form->getValue('calendars', 'filter', [])) ? 'checked' : ''; ?>
						id="cal-<?php echo $calendar->getId(); ?>" class="dp-input dp-input-checkbox dp-calendar__input">
					<span class="dp-calendar__title-dot" style="background-color: #<?php echo $calendar->getColor(); ?>"></span>
					<div class="dp-calendar__title">
						<?php echo str_pad(' ' . $calendar->getTitle(), strlen(' ' . $calendar->getTitle()) + $calendar->level - 1, '-', STR_PAD_LEFT); ?>
					</div>
					<div class="dp-calendar__event-text"><?php echo $calendar->event->afterDisplayTitle; ?></div>
				</label>
				<div class="dp-calendar__links">
					<?php if ((!empty($calendar->getIcalUrl()) || !$calendar instanceof ExternalCalendarInterface) && $params->get('show_export_links', 1)) { ?>
						<a href="<?php echo str_replace(['http://', 'https://'], 'webcal://', (string)$icalRoute); ?>"
						   class="dp-link dp-link-subscribe" title="<?php echo $translator->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_SUBSCRIBE'); ?>">
							[<?php echo $translator->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_SUBSCRIBE'); ?>]
						</a>
						<a href="<?php echo $icalRoute; ?>" class="dp-link" title="<?php echo $translator->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_ICAL'); ?>">
							[<?php echo $translator->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_ICAL'); ?>]
						</a>
						<?php if (!$user->guest && $token = (new Registry($user->params))->get('token')) { ?>
							<a href="<?php echo $router->getCalendarIcalRoute($calendar->getId(), $token); ?>" class="dp-link dp-link-ical" title="<?php echo $translator->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRIVATE_ICAL'); ?>">
								[<?php echo $translator->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRIVATE_ICAL'); ?>]
							</a>
						<?php } ?>
						<?php if (!$calendar instanceof ExternalCalendarInterface && !DPCalendarHelper::isFree() && !$user->guest) { ?>
							<?php $url = '/components/com_dpcalendar/caldav.php/calendars/' . $user->username . '/dp-' . $calendar->getId(); ?>
							<a href="<?php echo trim(Uri::base(), '/') . $url; ?>" class="dp-link dp-link-caldav"title="<?php echo $translator->translate('COM_DPCALENDAR_VIEW_PROFILE_TABLE_CALDAV_URL_LABEL'); ?>">
								[<?php echo $translator->translate('COM_DPCALENDAR_VIEW_PROFILE_TABLE_CALDAV_URL_LABEL'); ?>]
							</a>
						<?php } ?>
					<?php } ?>
				</div>
				<div class="dp-calendar__description">
					<div class="dp-calendar__event-text"><?php echo $calendar->event->beforeDisplayContent; ?></div>
					<div class="dp-calendar__description-text"><?php echo $calendar->getDescription(); ?></div>
					<div class="dp-calendar__event-text"><?php echo $calendar->event->afterDisplayContent; ?></div>
				</div>
			</div>
		<?php } ?>
		<div class="dp-filter__form-container">
			<?php foreach ($form->getFieldset() as $field) { ?>
				<?php echo str_replace('com_fields[','filter[com_fields][', $field->renderField(['hiddenLabel' => true, 'class' => 'dp-form-control ' . DPCalendarHelper::getFieldName($field, true)])); ?>
			<?php } ?>
		</div>
		<div class="dp-filter__button-bar dp-button-bar">
			<?php if (!in_array('location', $params->get('hidden_fields', [])) && $params->get('map_provider', 'openstreetmap') != 'none') { ?>
				<button class="dp-button dp-button-current-location" type="button">
					<?php echo $layoutHelper->renderLayout('block.icon', ['icon' => Icon::LOCATION]); ?>
					<?php echo $translator->translate('COM_DPCALENDAR_VIEW_MAP_LABEL_CURRENT_LOCATION'); ?>
				</button>
			<?php } ?>
			<button class="dp-button dp-button-search" type="submit">
				<?php echo $layoutHelper->renderLayout('block.icon', ['icon' => Icon::OK]); ?>
				<?php echo $translator->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_FILTER_APPLY'); ?>
			</button>
			<button class="dp-button dp-button-clear" type="button">
				<?php echo $layoutHelper->renderLayout('block.icon', ['icon' => Icon::CANCEL]); ?>
				<?php echo $translator->translate('JCLEAR'); ?>
			</button>
			<button class="dp-button dp-button-close" type="button">
				<?php echo $translator->translate('JCLOSE'); ?>
			</button>
			<?php echo $displayData['buttons']?? ''; ?>
		</div>
		<input type="hidden" name="Itemid" value="<?php echo $displayData['input']->getInt('Itemid', 0); ?>" class="dp-input dp-input-hidden">
		<?php foreach ($hidden_calendars as $calendar) { ?>
			<input type="hidden" id="cal-<?php echo $calendar; ?>" class="dp-input dp-input-hidden" name="filter[calendars][]" value="<?php echo $calendar; ?>">
		<?php } ?>
	</form>
</div>
