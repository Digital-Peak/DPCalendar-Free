<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
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
$fields    = array();

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
		<dl class="dp-description <?php echo $field->params->get('render_class'); ?>">
			<dt class="dp-description__label"><?php echo $field->params->get('showlabel') ? $field->label : ''; ?></dt>
			<dd class="dp-description__description"><?php echo $field->value; ?></dd>
		</dl>
	<?php } ?>
</div>
