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
<meta itemprop="performer" content="<?php echo htmlentities($authorName); ?>">
