<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$status = $displayData['event']->state != '3' ? 'Scheduled' : 'Cancelled';
?>
<meta itemprop="eventStatus" content="http://schema.org/Event<?php echo $status; ?>">
