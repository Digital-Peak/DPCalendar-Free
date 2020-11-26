<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!key_exists('item', $displayData) || !key_exists('context', $displayData)) {
	return;
}

$item = $displayData['item'];
if (!$item) {
	return;
}

$context = $displayData['context'];
if (!$context) {
	return;
}

JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');

$parts     = explode('.', $context);
$component = $parts[0];
$fields    = [];

if (key_exists('fields', $displayData)) {
	$fields = $displayData['fields'];
} else {
	if (!empty($item->jcfields)) {
		$fields = $item->jcfields;
	}

	if (!$fields) {
		$fields = FieldsHelper::getFields($context, $item, true);
	}
}
if (!$fields) {
	return;
}
?>
<div class="dp-fields">
	<?php foreach ($fields as $field) { ?>
		<?php if (!isset($field->value) || $field->value == '') { ?>
			<?php continue; ?>
		<?php } ?>
		<dl class="dp-description dp-field-<?php echo $field->name; ?> <?php echo $field->params->get('render_class'); ?>">
			<?php if ($field->params->get('showlabel', 1)) { ?>
				<dt class="dp-description__label"><?php echo JText::_($field->label); ?></dt>
			<?php } ?>
			<dd class="dp-description__description"><?php echo $field->value; ?></dd>
		</dl>
	<?php } ?>
</div>
