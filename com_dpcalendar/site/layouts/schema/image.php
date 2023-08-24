<?php
use Joomla\CMS\Uri\Uri;
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<?php if (!empty($displayData['event']->images->image_intro)) { ?>
	<meta itemprop="image" content="<?php echo trim(Uri::base(), '/') . '/' . $displayData['event']->images->image_intro; ?>">
<?php } ?>
<?php if (!empty($displayData['event']->images->image_full)) { ?>
	<meta itemprop="image" content="<?php echo trim(Uri::base(), '/') . '/' . $displayData['event']->images->image_full; ?>">
<?php } ?>
