<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if ($event == null) {
	return;
}

$translator->translateJS('MOD_DPCALENDAR_COUNTER_LABEL_YEARS');
$translator->translateJS('MOD_DPCALENDAR_COUNTER_LABEL_MONTHS');
$translator->translateJS('MOD_DPCALENDAR_COUNTER_LABEL_WEEKS');
$translator->translateJS('MOD_DPCALENDAR_COUNTER_LABEL_DAYS');
$translator->translateJS('MOD_DPCALENDAR_COUNTER_LABEL_HOURS');
$translator->translateJS('MOD_DPCALENDAR_COUNTER_LABEL_MINUTES');
$translator->translateJS('MOD_DPCALENDAR_COUNTER_LABEL_SECONDS');
$translator->translateJS('MOD_DPCALENDAR_COUNTER_LABEL_YEAR');
$translator->translateJS('MOD_DPCALENDAR_COUNTER_LABEL_MONTH');
$translator->translateJS('MOD_DPCALENDAR_COUNTER_LABEL_WEEK');
$translator->translateJS('MOD_DPCALENDAR_COUNTER_LABEL_DAY');
$translator->translateJS('MOD_DPCALENDAR_COUNTER_LABEL_HOUR');
$translator->translateJS('MOD_DPCALENDAR_COUNTER_LABEL_MINUTE');
$translator->translateJS('MOD_DPCALENDAR_COUNTER_LABEL_SECOND');

$document->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_DPCORE);
$document->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_URL);
$document->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_MOMENT);
$document->loadScriptFile('default.js', 'mod_dpcalendar_counter');
$document->loadStyleFile('default.css', 'mod_dpcalendar_counter');

if ($params->get('show_as_popup')) {
	$document->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_MODAL);
}
?>
<div class="mod-dpcalendar-counter mod-dpcalendar-counter-<?php echo $module->id; ?>"
     data-date="<?php echo $dateHelper->getDate($event->start_date, $event->all_day)->format('c', true); ?>"
     data-modal="<?php echo $params->get('show_as_popup'); ?>"
     data-counting="<?php echo !$params->get('disable_counting'); ?>">
	<div class="mod-dpcalendar-counter__upcoming">
		<div class="mod-dpcalendar-counter__intro-text">
			<?php echo $translator->translate('MOD_DPCALENDAR_COUNTER_SOON_OUTPUT'); ?>
		</div>
		<span class="mod-dpcalendar-counter__year dp-counter-block">
			<span class="dp-counter-block__number"></span>
			<span class="dp-counter-block__content"></span>
		</span>
		<span class="mod-dpcalendar-counter__month dp-counter-block">
			<span class="dp-counter-block__number"></span>
			<span class="dp-counter-block__content"></span>
		</span>
		<span class="mod-dpcalendar-counter__week dp-counter-block">
			<span class="dp-counter-block__number"></span>
			<span class="dp-counter-block__content"></span>
		</span>
		<span class="mod-dpcalendar-counter__day dp-counter-block">
			<span class="dp-counter-block__number"></span>
			<span class="dp-counter-block__content"></span>
		</span>
		<span class="mod-dpcalendar-counter__hour dp-counter-block">
			<span class="dp-counter-block__number"></span>
			<span class="dp-counter-block__content"></span>
		</span>
		<span class="mod-dpcalendar-counter__minute dp-counter-block">
			<span class="dp-counter-block__number"></span>
			<span class="dp-counter-block__content"></span>
		</span>
		<span class="mod-dpcalendar-counter__second dp-counter-block">
			<span class="dp-counter-block__number"></span>
			<span class="dp-counter-block__content"></span>
		</span>
	</div>
	<div class="mod-dpcalendar-counter__ongoing">
		<div class="mod-dpcalendar-counter__intro-text">
			<?php echo $translator->translate('MOD_DPCALENDAR_COUNTER_ONGOING_OUTPUT'); ?>
		</div>
		<a href="<?php echo $router->getEventRoute($event->id, $event->catid); ?>" class="mod-dpcalendar-counter__link dp-link">
			<?php echo $event->title; ?>
		</a>
		<?php if ($event->images->image_intro) { ?>
			<div class="mod-dpcalendar-upcoming-counter__image">
				<figure class="dp-figure">
					<img class="dp-image" src="<?php echo $event->images->image_intro; ?>" alt="<?php echo $event->images->image_intro_alt; ?>">
					<?php if ($event->images->image_intro_caption) { ?>
						<figcaption class="dp-figure__caption"><?php echo $event->images->image_intro_caption; ?></figcaption>
					<?php } ?>
				</figure>
			</div>
		<?php } ?>
		<?php if ($truncatedDescription) { ?>
			<div class="mod-dpcalendar-counter__description">
				<?php echo $truncatedDescription; ?>
			</div>
		<?php } ?>
	</div>
</div>
