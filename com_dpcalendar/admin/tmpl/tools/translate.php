<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;

$this->dpdocument->loadStyleFile('dpcalendar/views/tools/translate.css');
$this->dpdocument->loadScriptFile('views/tools/translate.js');

$this->app->enqueueMessage($this->translate('COM_DPCALENDAR_VIEW_TOOLS_TRANSLATE_TEXT'), 'warning');
?>
<div class="com-dpcalendar-tools-translate">
	<div id="j-main-container">
		<div class="com-dpcalendar-tools-translate__loader">
			<?php echo $this->layoutHelper->renderLayout('block.loader', $this->displayData); ?>
		</div>
		<table class="dp-resource-table dp-table">
			<thead>
			<tr>
				<th></th>
				<th></th>
				<?php foreach ($this->languages as $language) { ?>
					<th id="<?php echo $language['tag']; ?>" class="left"><?php echo $language['name']; ?></th>
				<?php } ?>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($this->resources as $resource) { ?>
				<tr class="dp-resource" data-slug="<?php echo $resource->slug; ?>">
					<td class="dp-resource__icon"><i class="icon-minus"></i></td>
					<td class="dp-resource__name">
						<a href="<?php echo $resource->web_url; ?>" target="_blank">
							<?php echo $resource->name; ?>
						</a>
					</td>
					<?php foreach ($this->languages as $language) { ?>
						<td class="dp-resource__language" data-language="<?php echo $language['tag']; ?>">
							<a href="https://translate.digital-peak.com/translate/dpcalendar/<?php echo $resource->slug . '/' . $language['tag']; ?>/?q=state:empty"
								class="dp-button" target="_blank">
								<?php echo $this->translate('COM_DPCALENDAR_VIEW_TOOLS_TRANSLATE_TRANSLATE'); ?>
								<span class="dp-resource__percentage"></span>
							</a>
						</td>
					<?php } ?>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<div class="com-dpcalendar-tools-translate__footer">
			<?php echo Text::sprintf('COM_DPCALENDAR_FOOTER', $this->input->getString('DPCALENDAR_VERSION', '')); ?>
		</div>
	</div>
</div>
