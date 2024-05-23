<?php
use Joomla\CMS\Session\Session;
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('profile_show_sharing', '1')) {
	return;
}
?>
<div class="com-dpcalendar-profile__share">
	<h2 class="dp-heading"><?php echo $this->translate('COM_DPCALENDAR_VIEW_PROFILE_SHARING'); ?></h2>
	<div class="dp-sharing-users">
		<div class="dp-sharing-users__read">
			<p class="dp-sharing-users__title"><?php echo $this->translate('COM_DPCALENDAR_VIEW_PROFILE_READ_USERS_LABEL'); ?>: </p>
			<select name="read-users" class="dp-select" data-token="<?php echo Session::getFormToken(); ?>" multiple>
				<?php foreach ($this->users as $user) { ?>
					<?php $checked = in_array($user, $this->readMembers) ? ' selected' : ''; ?>
					<option value="<?php echo $user->value; ?>"<?php echo $checked; ?>><?php echo $user->text; ?></option>
				<?php } ?>
			</select>
		</div>
		<div class="dp-sharing-users__write">
			<p class="dp-sharing-users__title"><?php echo $this->translate('COM_DPCALENDAR_VIEW_PROFILE_WRITE_USERS_LABEL'); ?>: </p>
			<select name="write-users" class="dp-select" data-token="<?php echo Session::getFormToken(); ?>" multiple>
				<?php foreach ($this->users as $user) { ?>
					<?php $checked = in_array($user, $this->writeMembers) ? ' selected' : ''; ?>
					<option value="<?php echo $user->value; ?>"<?php echo $checked; ?>><?php echo $user->text; ?></option>
				<?php } ?>
			</select>
		</div>
	</div>
</div>
