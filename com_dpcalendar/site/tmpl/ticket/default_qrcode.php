<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

if (!$this->qrCodeString) {
	return;
}
?>
<div class="com-dpcalendar-ticket__qrcode dp-qrcode">
	<img src="<?php echo $this->qrCodeString; ?>" class="dp-qrcode__image" loading="lazy">
</div>
