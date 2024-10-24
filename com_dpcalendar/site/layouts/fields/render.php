<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Registry\Registry;

if (!array_key_exists('item', $displayData) || !array_key_exists('context', $displayData)) {
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

$parts     = explode('.', (string) $context);
$component = $parts[0];
$fields    = [];

if (array_key_exists('fields', $displayData)) {
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
		<?php if (empty($field->value) || empty($field->params)) { continue; } ?>
		<?php echo FieldsHelper::render($context, 'field.' . $field->params->get('layout', 'render'), ['field' => $field]); ?>
	<?php } ?>
</div>
