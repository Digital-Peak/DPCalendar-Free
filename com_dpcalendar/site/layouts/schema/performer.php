<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

$authorName = $displayData['event']->created_by_alias ?: $displayData['userHelper']->getUser($displayData['event']->created_by)->name;
?>
<meta itemprop="performer" content="<?php echo htmlentities($authorName ?: '', ENT_COMPAT, 'UTF-8'); ?>">
