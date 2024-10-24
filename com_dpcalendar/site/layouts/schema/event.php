<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

?>
<div itemtype="http://schema.org/Event" itemscope>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.name', $displayData); ?>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.date', $displayData); ?>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.status', $displayData); ?>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.url', $displayData); ?>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.offer', $displayData); ?>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.performer', $displayData); ?>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.organizer', $displayData); ?>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.image', $displayData); ?>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.description', $displayData); ?>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.location', $displayData); ?>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.attendancemode', $displayData); ?>
</div>
