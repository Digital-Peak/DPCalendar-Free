<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;

if (!$this->booking->price) {
	return;
}
?>
<div class="com-dpcalendar-booking__payment">
	<div class="com-dpcalendar-booking__payment-info dp-info-box"<?php echo (is_countable($this->paymentProviders) ? count($this->paymentProviders) : 0) < 2 ? ' style="display:none"' : ''; ?>>
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_CONFIRM_CHOOSE_PAYMENT_OPTION'); ?>
	</div>
	<div class="com-dpcalendar-booking__payment-options">
		<?php foreach ($this->paymentProviders as $provider) { ?>
			<label class="dp-payment-option <?php echo (is_countable($this->paymentProviders) ? count($this->paymentProviders) : 0) == 1 ? 'dp-payment-option_selected ' : ''; ?>dp-payment-option-<?php echo $provider->id; ?>">
				<input class="dp-payment-option__input dp-input dp-input-radio" <?php echo (is_countable($this->paymentProviders) ? count($this->paymentProviders) : 0) == 1 ? 'checked' : ''; ?>
					   name="payment_provider" value="<?php echo $provider->id; ?>" type="radio">
				<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::NEXT]); ?>
				<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::OK]); ?>
				<?php if (strpos((string) $provider->icon, '.svg') > 0 && file_exists($provider->icon)) { ?>
					<span class="dp-payment-option__image"><?php echo file_get_contents($provider->icon); ?></span>
				<?php } else { ?>
					<img src="<?php echo $provider->icon; ?>" class="dp-payment-option__image"/>
				<?php } ?>
				<p class="dp-payment-option__text">
					<?php echo $this->translate($provider->description ?: 'PLG_' . strtoupper($provider->plugin_type . '_' . $provider->plugin_name) . '_PAY_BUTTON_DESC'); ?>
				</p>
			</label>
		<?php } ?>
	</div>
</div>
