<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();
?>
<meta itemprop="performer" content="<?php echo htmlentities($displayData['userHelper']->getUser($displayData['event']->created_by)->name); ?>">
