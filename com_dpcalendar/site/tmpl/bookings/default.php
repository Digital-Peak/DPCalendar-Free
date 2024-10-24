<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;

$this->dpdocument->loadStyleFile('dpcalendar/views/bookings/default.css');
$this->dpdocument->loadScriptFile('views/bookings/default.js');
$this->dpdocument->addStyle($this->params->get('bookings_custom_css', ''));
?>
<div class="com-dpcalendar-bookings<?php echo $this->pageclass_sfx ? ' com-dpcalendar-bookings-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<div class="com-dpcalendar-bookings__custom-text">
		<?php echo HTMLHelper::_('content.prepare', $this->translate($this->params->get('bookings_textbefore', ''))); ?>
	</div>
	<?php echo $this->loadTemplate('form'); ?>
	<?php echo $this->loadTemplate('content'); ?>
	<?php echo $this->loadTemplate('footer'); ?>
	<div class="com-dpcalendar-bookings__custom-text">
		<?php echo HTMLHelper::_('content.prepare', $this->translate($this->params->get('bookings_textafter', ''))); ?>
	</div>
</div>
