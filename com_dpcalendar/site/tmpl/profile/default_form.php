<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
use Joomla\CMS\HTML\HTMLHelper;

$action = $this->router->route('index.php?option=com_dpcalendar&view=profile');
?>
<form class="com-dpcalendar-profile__form dp-form form-validate" method="post" name="adminForm" id="adminForm" action="<?php echo $action; ?>">
	<div class="dp-form__search">
		<input type="text" name="filter-search" placeholder="<?php echo $this->translate('JGLOBAL_FILTER_LABEL'); ?>"
			   value="<?php echo $this->state->get('filter.search'); ?>" class="dp-input dp-input-text">
		<button type="button" class="dp-button dp-button-save">
			<?php echo $this->layoutHelper->renderLayout(
				'block.icon',
				['icon' => Icon::SEARCH, 'title' => $this->translate('JSEARCH_FILTER')]
			); ?>
		</button>
		<button type="button" class="dp-button dp-button-clear">
			<?php echo $this->layoutHelper->renderLayout(
				'block.icon',
				['icon' => Icon::DELETE, 'title' => $this->translate('COM_DPCALENDAR_CLEAR')]
			); ?>
		</button>
	</div>
	<div class="dp-form__limit">
		<span class="dp-text"><?php echo $this->translate('JGLOBAL_DISPLAY_NUM'); ?></span>
		<span class="dp-text"><?php echo $this->pagination->getLimitBox(); ?></span>
	</div>
	<input type="hidden" name="task" class="dp-input dp-input-hidden">
	<input type="hidden" name="limitstart" class="dp-input dp-input-hidden">
	<input type="hidden" name="filter_order" class="dp-input dp-input-hidden">
	<input type="hidden" name="filter_order_Dir" class="dp-input dp-input-hidden">
	<input type="hidden" name="Itemid" value="<?php echo $this->input->getInt('Itemid', 0); ?>" class="dp-input dp-input-hidden">
	<input type="hidden" name="return" value="<?php echo $this->input->get('return', null, 'base64'); ?>" class="dp-input dp-input-hidden">
	<input type="hidden" name="tmpl" value="<?php echo $this->input->get('tmpl'); ?>" class="dp-input dp-input-hidden">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
