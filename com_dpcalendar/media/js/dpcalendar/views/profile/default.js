jQuery(document).ready(function () {
    jQuery('#dp-profile-form-read-users, #dp-profile-form-write-users').chosen({
        placeholder_text_multiple: Joomla.JText._('COM_DPCALENDAR_VIEW_DAVCALENDAR_NONE_SELECTED_LABEL')
    }).change(function (event, obj) {
        var data = obj;
        data['action'] = jQuery(event.target).attr('id') == 'dp-profile-form-write-users' ? 'write' : 'read';
        data[jQuery('#dp-profile-form input[name="token"]').val()] = 1;
        jQuery.ajax({
            type: 'post',
            url: 'index.php?option=com_dpcalendar&view=profile&task=profile.change',
            data: data,
            success: function (response) {
            }
        });
    });

    jQuery('.dp-profile-delete-action').click(function(e){
	    return confirm(Joomla.JText._('COM_DPCALENDAR_CONFIRM_DELETE'));
    });
});
