<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Application\CMSWebApplicationInterface;
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

$app = Factory::getApplication();
$actualTimezone = $app instanceof CMSWebApplicationInterface ? $app->get('DPCalendar.user-timezone', $displayData['dateHelper']->getDate()->getTimezone()->getName()) : 'UTC';
?>
<form action="<?php
echo Uri::base();
?>" method="get" class="dp-timezone dp-form">
	<label class="dp-timezone__label" for="dp-timezone__select">
		<?php
echo $displayData['translator']->translate('COM_DPCALENDAR_LAYOUT_TIMEZONE_CHOOSE_TIMEZONE');
?>:
	</label>
	<select name="tz" class="dp-select dp-timezone__select dp-select-container_unstyled" id="dp-timezone__select">
		<option value="UTC"<?php
?>>
			<?php
echo $displayData['translator']->translate('JLIB_FORM_VALUE_TIMEZONE_UTC');
?>
		</option>
		<?php
foreach ($timezones as $region => $list) { ?>
			<optgroup label="<?php echo $displayData['translator']->translate('COM_DPCALENDAR_LAYOUT_TIMEZONE_REGION_' . $region); ?>">
			<?php foreach ($list as $timezone => $name) { ?>
				<option value="<?php echo $timezone; ?>"<?php echo $actualTimezone == $timezone ? ' selected' : ''; ?>>
					<?php echo $displayData['translator']->translate($name); ?>
				</option>
			<?php } ?>
			</optgroup>
		<?php }
?>
	</select>
	<span class="dp-timezone__info dp-timezone__info_hidden">
		<?php
echo $displayData['translator']->translate('COM_DPCALENDAR_LAYOUT_TIMEZONE_DIFFERENT_TIMEZONE');
?>
		<a href="/" class="dp-link dp-link_confirm"><?php
echo $displayData['translator']->translate('JYES');
?></a>
		<a href="/" class="dp-link dp-link_close"><?php
echo $displayData['translator']->translate('COM_DPCALENDAR_CLOSE');
?></a>
	</span>
	<input type="hidden" name="task" value="profile.tz" class="dp-input dp-input-hidden">
	<input type="hidden" name="option" value="com_dpcalendar" class="dp-input dp-input-hidden">
	<input type="hidden" name="view" value="profile" class="dp-input dp-input-hidden">
	<input type="hidden" name="return" value="<?php
echo base64_encode(Uri::getInstance()->toString());
?>" class="dp-input dp-input-hidden">
</form>
<?php
