<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$authorName = $displayData['event']->created_by_alias ?: $displayData['userHelper']->getUser($displayData['event']->created_by)->name;
?>
<div itemprop="organizer" itemscope itemtype="http://schema.org/Organization">
	<meta itemprop="name" content="<?php echo htmlentities($authorName); ?>">
	<meta itemprop="url" content="<?php echo $displayData['event']->url ?: JUri::getInstance()->toString(); ?>">
</div>
