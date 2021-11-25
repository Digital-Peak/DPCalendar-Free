<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;

if (!key_exists('field', $displayData)) {
	return;
}

$field = $displayData['field'];
?>
<dl class="dp-description dp-field-<?php echo $field->name; ?> <?php echo $field->params->get('render_class'); ?>">
	<?php if ($field->params->get('showlabel', 1)) { ?>
		<dt class="dp-description__label <?php echo $field->params->get('label_render_class'); ?>">
			<?php echo Text::_($field->label); ?>
		</dt>
	<?php } ?>
	<dd class="dp-description__description <?php echo $field->params->get('value_render_class'); ?>">
		<?php echo $field->value; ?>
	</dd>
</dl>
