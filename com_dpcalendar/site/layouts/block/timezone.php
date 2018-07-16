<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

if (DPCalendarHelper::getComponentParameter('enable_tz_switcher', '0') == '0') {
	return;
}

$displayData['document']->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_DPCORE);
$displayData['document']->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_SELECT);

$regions = [
	'Africa'     => DateTimeZone::AFRICA,
	'America'    => DateTimeZone::AMERICA,
	'Antarctica' => DateTimeZone::ANTARCTICA,
	'Aisa'       => DateTimeZone::ASIA,
	'Atlantic'   => DateTimeZone::ATLANTIC,
	'Europe'     => DateTimeZone::EUROPE,
	'Indian'     => DateTimeZone::INDIAN,
	'Pacific'    => DateTimeZone::PACIFIC
];

$timezones = [];
foreach ($regions as $name => $mask) {
	$zones = DateTimeZone::listIdentifiers($mask);
	foreach ($zones as $timezone) {
		$timezones[$name][$timezone] = $timezone;
	}
}

$actualTimezone = JFactory::getSession()->get('user-timezone', $displayData['dateHelper']->getDate()->getTimezone()->getName(), 'DPCalendar');
?>
<form action="<?php echo JUri::base(); ?>" method="get" class="dp-timezone dp-form">
	<span class="dp-timezone__label">
		<?php echo $displayData['translator']->translate('COM_DPCALENDAR_CHOOSE_TIMEZONE'); ?>:
	</span>
	<select name="tz" class="dp-select dp-timezone__select">
		<option value="UTC"<?php $actualTimezone == 'UTC' ? ' selected' : ''; ?>>
			<?php echo $displayData['translator']->translate('JLIB_FORM_VALUE_TIMEZONE_UTC'); ?>
		</option>
		<?php foreach ($timezones as $region => $list) { ?>
			<?php foreach ($list as $timezone => $name) { ?>
				<option value="<?php echo $timezone; ?>"<?php echo $actualTimezone == $timezone ? ' selected' : ''; ?>>
					<?php echo $displayData['translator']->translate($name); ?>
				</option>
			<?php } ?>
		<?php } ?>
	</select>
	<input type="hidden" name="task" value="profile.tz" class="dp-input dp-input-hidden">
	<input type="hidden" name="option" value="com_dpcalendar" class="dp-input dp-input-hidden">
	<input type="hidden" name="view" value="profile" class="dp-input dp-input-hidden">
	<input type="hidden" name="return" value="<?php echo base64_encode(JUri::getInstance()->toString()); ?>" class="dp-input dp-input-hidden">
</form>
