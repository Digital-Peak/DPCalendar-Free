<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Uri\Uri;

$authorName = $displayData['event']->created_by_alias ?: $displayData['userHelper']->getUser($displayData['event']->created_by)->name;
?>
<div itemprop="organizer" itemscope itemtype="http://schema.org/Organization">
	<meta itemprop="name" content="<?php echo htmlentities($authorName ?: '', ENT_COMPAT, 'UTF-8'); ?>">
	<meta itemprop="url" content="<?php echo $displayData['event']->url ?: Uri::getInstance()->toString(); ?>">
</div>
