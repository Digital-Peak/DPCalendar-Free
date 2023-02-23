<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\Transifex;
use Joomla\CMS\Language\Text;

$this->dpdocument->loadStyleFile('dpcalendar/views/tools/translate.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/tools/translate.js');

$this->app->enqueueMessage($this->translate('COM_DPCALENDAR_VIEW_TOOLS_TRANSLATE_TEXT'), 'warning');
?>
<div class="com-dpcalendar-tools-translate">
	<?php if ($this->sidebar) { ?>
		<div id="j-sidebar-container"><?php echo $this->sidebar; ?></div>
	<?php } ?>
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
						<a href="https://www.transifex.com/digital-peak/DPCalendar/<?php echo $resource->slug; ?>" target="_blank">
							<?php echo $resource->name; ?>
						</a>
					</td>
					<?php foreach ($this->languages as $language) { ?>
						<?php $hash = Transifex::getLangCode($language['tag'], true) . '/' . $resource->slug; ?>
						<td class="dp-resource__language" data-language="<?php echo $language['tag']; ?>">
							<a href="https://www.transifex.com/digital-peak/DPCalendar/translate/#<?php echo $hash; ?>"
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
			<?php echo Text::sprintf('COM_DPCALENDAR_FOOTER', $this->input->getString('DPCALENDAR_VERSION')); ?>
		</div>
	</div>
</div>
