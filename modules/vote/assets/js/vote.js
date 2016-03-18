function delibera_add_comment_input(element)
{
	var panel = jQuery(element).parent();
	panel.find('.delibera_comment_add_current').append(jQuery('<p><textarea name="delibera_comment_add_list[]" ></textarea><a href="#" class="delibera_comment_input_bt_remove delibera-icon-cancel"></a></p>').val(panel.find('.delibera_comment_input_list').val()));
	panel.find('.delibera_comment_input_list').val('');
}

jQuery(document).ready(function() {
	jQuery('.delibera_comment_input_bt_remove').live('click', function() {
		var sel = confirm('Do you want to delete this vote option?');
		if (sel) {
			jQuery(this).parents('p').remove();
		}
	    return false;
	});
});