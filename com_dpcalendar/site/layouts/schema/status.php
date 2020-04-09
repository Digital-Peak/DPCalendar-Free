<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$status = $displayData['event']->state != '3' ? 'Scheduled' : 'Cancelled';
?>
<meta itemprop="eventStatus" content="http://schema.org/Event<?php echo $status; ?>">
