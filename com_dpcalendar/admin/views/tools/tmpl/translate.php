<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\Transifex;

$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_DPCORE);
$this->dpdocument->loadStyleFile('dpcalendar/views/tools/translate.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/tools/translate.js');

$this->app->enqueueMessage($this->translate('COM_DPCALENDAR_VIEW_TOOLS_TRANSLATE_TEXT'), 'warning');
?>
<div class="com-dpcalendar-translate">
	<div id="j-sidebar-container" class="com-dpcalendar-translate__sidebar span2"><?php echo $this->sidebar; ?></div>
	<div id="j-main-container" class="com-dpcalendar-translate__content span10">
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
						<a href="https://www.transifex.com/projects/p/DPCalendar/resource/<?php echo $resource->slug; ?>" target="_blank">
							<?php echo $resource->name; ?>
						</a>
					</td>
					<?php foreach ($this->languages as $language) { ?>
						<?php $hash = Transifex::getLangCode($language['tag'], true) . '/' . $resource->slug; ?>
						<td class="dp-resource__language" data-language="<?php echo $language['tag']; ?>">
							<a href="http://transifex.com/projects/p/DPCalendar/translate/#<?php echo $hash; ?>"
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
	</div>
	<div class="com-dpcalendar-translate__footer">
		<?php echo JText::sprintf('COM_DPCALENDAR_FOOTER', $this->input->getString('DPCALENDAR_VERSION')); ?>
	</div>
</div>
