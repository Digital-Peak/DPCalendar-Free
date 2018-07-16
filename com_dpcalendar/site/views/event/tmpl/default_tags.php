<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$tagsContent = $this->layoutHelper->renderLayout('joomla.content.tags', $this->event->tags->itemTags);

if (!$tagsContent) {
	return;
}
?>
<div class="com-dpcalendar-event__tags-text"><?php echo $tagsContent; ?></div>
