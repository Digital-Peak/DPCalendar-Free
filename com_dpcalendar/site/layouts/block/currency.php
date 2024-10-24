<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Uri\Uri;

$displayData['document']->loadScriptFile('layouts/block/currency.js');
$displayData['translator']->translateJS('COM_DPCALENDAR_CURRENCY');

$app = $displayData['app'];
if (!$app instanceof CMSWebApplicationInterface) {
	return;
}

$model = $app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Currency', 'Administrator');

$currencies = $model->getCurrencies();
if (count($currencies) < 2) {
	return;
}

$actualCurrency = $model->getActualCurrency();
?>
<div class="dp-currency">
	<form action="<?php echo Uri::base(); ?>" method="get" class="dp-currency__form dp-form">
		<label class="dp-currency__label" for="dp-currency__select">
			<?php echo $displayData['translator']->translate('COM_DPCALENDAR_LAYOUT_CURRENCY_CHOOSE_CURRENCY'); ?>:
		</label>
		<select name="currency" class="dp-select dp-currency__select dp-select-container_unstyled" id="dp-currency__select">
			<?php foreach ($currencies as $currency) { ?>
				<option value="<?php echo $currency->currency; ?>"<?php echo $actualCurrency->currency == $currency->currency ? ' selected' : ''; ?>>
					<?php echo $currency->currency . ($currency->currency !== $currency->symbol ?' ' . $currency->symbol : ''); ?>
				</option>
			<?php } ?>
		</select>
		<input type="hidden" name="task" value="profile.tz" class="dp-input dp-input-hidden">
		<input type="hidden" name="option" value="com_dpcalendar" class="dp-input dp-input-hidden">
		<input type="hidden" name="view" value="profile" class="dp-input dp-input-hidden">
		<input type="hidden" name="return" value="<?php echo base64_encode(Uri::getInstance()->toString());?>" class="dp-input dp-input-hidden">
	</form>
</div>
