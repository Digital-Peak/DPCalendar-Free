<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();
?>
<div itemprop="event" itemtype="http://schema.org/Event" itemscope>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.name', $displayData); ?>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.date', $displayData); ?>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.url', $displayData); ?>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.offer', $displayData); ?>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.performer', $displayData); ?>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.image', $displayData); ?>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.description', $displayData); ?>
	<?php echo $displayData['layoutHelper']->renderLayout('schema.location', $displayData); ?>
</div>
