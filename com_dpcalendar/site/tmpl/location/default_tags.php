<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$tagsContent = $this->layoutHelper->renderLayout('joomla.content.tags', $this->location->tags->itemTags);
if (!$tagsContent) {
	return;
}
?>
<div class="com-dpcalendar-location__tags-text"><?php echo $tagsContent; ?></div>
