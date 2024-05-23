<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();
?>
<meta itemprop="description" content="<?php echo htmlentities(strip_tags((string) ($displayData['event']->description ?: '')), ENT_COMPAT, 'UTF-8'); ?>">
