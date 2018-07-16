<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<?php if (!empty($displayData['event']->images->image_intro)) { ?>
	<meta itemprop="image" content="<?php echo trim(JUri::base(), '/') . '/' . $displayData['event']->images->image_intro; ?>">
<?php } ?>
<?php if (!empty($displayData['event']->images->image_full)) { ?>
	<meta itemprop="image" content="<?php echo trim(JUri::base(), '/') . '/' . $displayData['event']->images->image_full; ?>">
<?php } ?>
