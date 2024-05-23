<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;

$this->dpdocument->loadStyleFile('dpcalendar/views/booking/review.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/booking/review.js');
$this->dpdocument->addStyle($this->params->get('booking_custom_css', ''));
?>
<div class="com-dpcalendar-booking com-dpcalendar-booking-review<?php echo $this->pageclass_sfx ? ' com-dpcalendar-booking-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->loadTemplate('heading'); ?>
	<?php echo $this->loadTemplate('steps'); ?>
	<?php echo $this->loadTemplate('title'); ?>
	<?php echo $this->loadTemplate('message'); ?>
	<form class="com-dpcalendar-booking__form dp-form form-validate" method="post" name="adminForm" id="adminForm"
		  action="<?php echo $this->router->route('index.php?option=com_dpcalendar&b_id=' . (int)$this->booking->id . $this->tmpl); ?>">
		<?php echo $this->loadTemplate('tickets'); ?>
		<?php echo $this->loadTemplate('actions'); ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<input type="hidden" name="return" value="<?php echo base64_encode(\DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper::getBookingRoute($this->booking)); ?>"
			   class="dp-input dp-input-hidden">
		<input type="hidden" name="tmpl" value="<?php echo $this->input->get('tmpl'); ?>" class="dp-input dp-input-hidden">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
