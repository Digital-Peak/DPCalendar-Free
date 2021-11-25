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
		<?php echo FieldsHelper::render($context, 'field.' . $field->params->get('layout', 'render'), ['field' => $field]); ?>
	<?php } ?>
</div>
