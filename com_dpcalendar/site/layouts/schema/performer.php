<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

$authorName = $displayData['event']->created_by_alias ?: $displayData['event']->author;
?>
<meta itemprop="performer" content="<?php echo htmlentities((string) ($authorName ?: ''), ENT_COMPAT, 'UTF-8'); ?>">
