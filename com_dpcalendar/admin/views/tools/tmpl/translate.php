<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\Transifex;

DPCalendarHelper::loadLibrary(array('jquery' => true));

JFactory::getApplication()->enqueueMessage(JText::_('COM_DPCALENDAR_VIEW_TOOLS_TRANSLATE_TEXT'), 'warning');
?>
<script type="text/javascript">
(function($) {

	// jQuery on an empty object, we are going to use this as our Queue
	var ajaxQueue = new Array();

	$.ajaxQueue = function( ajaxOpts, name) {
	    var jqXHR,
	        dfd = $.Deferred(),
	        promise = dfd.promise();

	    // run the actual query
	    function doRequest( next ) {
	        jqXHR = $.ajax( ajaxOpts );
	        jqXHR.done( dfd.resolve )
	            .fail( dfd.reject )
	            .then( next, next );
	    }

	    if (!(name in ajaxQueue)) {
	    	ajaxQueue[name] = $({});
	    }

	    // queue our ajax request
		ajaxQueue[name].queue(doRequest);

	    // add the abort method
	    promise.abort = function( statusText ) {

	        // proxy abort to the jqXHR if it is active
	        if ( jqXHR ) {
	            return jqXHR.abort( statusText );
	        }

	        // if there wasn't already a jqXHR we need to remove from queue
	        var queue = ajaxQueue.queue(),
	            index = $.inArray( doRequest, queue );

	        if ( index > -1 ) {
	            queue.splice( index, 1 );
	        }

	        // and then reject the deferred
	        dfd.rejectWith( ajaxOpts.context || ajaxOpts, [ promise, statusText, "" ] );
	        return promise;
	    };

	    return promise;
	};
	})(jQuery);

	Joomla.submitbutton = function(task) {
		if (task == 'translate.update') {
			jQuery('#resources tbody .resource').each(function( index ) {
				var el = jQuery(this);
				jQuery.ajaxQueue({
					type: 'POST',
					url: 'index.php?option=com_dpcalendar&task=translate.update',
					data: {resource: el.attr('id')},
					beforeSend: function () {
						el.prev().find('i').attr('class', 'icon-loop');
					},
					success: function (data) {
						var json = jQuery.parseJSON(data);
						Joomla.renderMessages(json.messages);

						var el = jQuery('#' + json.data.resource);
						el.prev().find('i').attr('class', 'icon-checkmark-circle');
					}
				}, 'update');
			});
		}
		return true;
	}

	jQuery(document).ready(function(){
		jQuery('#resources tbody .resource').each(function( index ) {
			var el = jQuery(this);
			jQuery.ajaxQueue({
				type: 'POST',
				url: 'index.php?option=com_dpcalendar&task=translate.fetch',
				data: {resource: el.attr('id')},
				success: function (data) {
					var json = jQuery.parseJSON(data);

					for (var i in json.languages) {
						var language = json.languages[i];
						var el = jQuery('#' + json.resource + '-' + language.tag).find('span');
						el.html(language.percent + '%');
						var label = 'success';
						if (language.percent < 30 ) label = 'important';
						else if (language.percent < 50 ) label = 'warning';
						else if (language.percent < 100 ) label = 'info';
						el.attr('class', 'label label-' + label);
					}
				}
			}, 'fetch');
		});
	});
</script>

<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
	<table id="resources" class="table">
		<thead><tr>
			<th></th>
			<th></th>
			<?php
			$languages = JFactory::getLanguage()->getKnownLanguages();
			foreach ($languages as $language) {
				if($language['tag'] == 'en-GB')
				{
					unset($languages[$language['tag']]);
					continue;
				}?>
			<th id="<?php echo $language['tag']?>" class="left"><?php echo $language['name']?></th>
			<?php }?>
		</tr></thead>
		<tbody>
		<?php
		foreach ($this->resources as $data) {
			$name = str_replace(array('-', '_'), ' ', $data->name);
			$name = ucwords($name);
			$name = str_replace('Plg', 'Plugin', $name);
			$name = str_replace('Mod', 'Module', $name);
			$name = str_replace('Com', 'Component', $name);
			$name = str_replace('Dpc', 'DPC', $name);?>
			<tr>
				<td><i class="icon-minus"></i></td>
				<td id="<?php echo $data->slug;?>" class="resource">
					<a href ="https://www.transifex.com/projects/p/DPCalendar/resource/<?php echo $data->slug?>" target="_blank">
						<?php echo $name?>
					</a>
				</td>
				<?php foreach ($languages as $language) { ?>
					<td id="<?php echo $data->slug . '-' . $language['tag']?>" class="left">
						<a href="http://transifex.com/projects/p/DPCalendar/translate/#<?php echo Transifex::getLangCode($language['tag'], true) . '/' . $data->slug?>"
							class="btn" target="_blank">
						<?php echo JText::_('COM_DPCALENDAR_VIEW_TOOLS_TRANSLATE_TRANSLATE')?>
						<span></span>
					</a>
					</td>
				<?php }?>
			</tr>
		<?php }?>
		</tbody>
	</table>
</div>

<div align="center" style="clear: both">
	<?php echo sprintf(JText::_('COM_DPCALENDAR_FOOTER'), JFactory::getApplication()->input->getVar('DPCALENDAR_VERSION'));?>
</div>
