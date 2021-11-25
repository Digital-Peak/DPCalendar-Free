<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

if (DPCalendarHelper::getComponentParameter('enable_tz_switcher', '0') == '0') {
	return;
}

$displayData['document']->loadScriptFile('dpcalendar/layouts/block/timezone.js');

$displayData['translator']->translateJS('COM_DPCALENDAR_OPTIONS');

$regions = [
	'Africa'     => DateTimeZone::AFRICA,
	'America'    => DateTimeZone::AMERICA,
	'Antarctica' => DateTimeZone::ANTARCTICA,
	'Asia'       => DateTimeZone::ASIA,
	'Atlantic'   => DateTimeZone::ATLANTIC,
	'Australia'  => DateTimeZone::AUSTRALIA,
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

$actualTimezone = Factory::getSession()->get('user-timezone', $displayData['dateHelper']->getDate()->getTimezone()->getName(), 'DPCalendar');
?>
<form action="<?php echo Uri::base(); ?>" method="get" class="dp-timezone dp-form">
	<span class="dp-timezone__label">
		<?php echo $displayData['translator']->translate('COM_DPCALENDAR_CHOOSE_TIMEZONE'); ?>:
	</span>
	<select name="tz" class="dp-select dp-timezone__select dp-select-container_unstyled">
		<option value="UTC"<?php $actualTimezone == 'UTC' ? ' selected' : ''; ?>>
			<?php echo $displayData['translator']->translate('JLIB_FORM_VALUE_TIMEZONE_UTC'); ?>
		</option>
		<?php foreach ($timezones as $region => $list) { ?>
			<optgroup label="<?php echo $displayData['translator']->translate('COM_DPCALENDAR_LAYOUT_TIMEZONE_REGION_' . $region); ?>">
			<?php foreach ($list as $timezone => $name) { ?>
				<option value="<?php echo $timezone; ?>"<?php echo $actualTimezone == $timezone ? ' selected' : ''; ?>>
					<?php echo $displayData['translator']->translate($name); ?>
				</option>
			<?php } ?>
			</optgroup>
		<?php } ?>
	</select>
	<input type="hidden" name="task" value="profile.tz" class="dp-input dp-input-hidden">
	<input type="hidden" name="option" value="com_dpcalendar" class="dp-input dp-input-hidden">
	<input type="hidden" name="view" value="profile" class="dp-input dp-input-hidden">
	<input type="hidden" name="return" value="<?php echo base64_encode(Uri::getInstance()->toString()); ?>" class="dp-input dp-input-hidden">
</form>
