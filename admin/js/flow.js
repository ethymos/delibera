/**
 * 
 */

function deliberaFlowToggle(element)
{
	jQuery(element).parent().siblings('.dragbox-content').toggle();
}

function deliberaFlowRemove(element)
{
	var sel = confirm('do you want to delete this step?');
	if (sel) {
		jQuery(element).parent().parent().remove();
	}
}

var sortorder = '';

jQuery(document).ready(function() {
	jQuery('a.maxmin').click(function() {
		deliberaFlowToggle(this);
	});

	jQuery('a.delete').click(function() {
		deliberaFlowRemove(this);
	});

	jQuery('.column').sortable({
		connectWith : '.column',
		handle : 'h2',
		cursor : 'move',
		placeholder : 'placeholder',
		forcePlaceholderSize : true,
		opacity : 0.4,
		helper : function(e, div) {
			copyHelper = div.clone().insertAfter(div);
			jQuery(copyHelper).find('a.maxmin').click(function() {
				deliberaFlowToggle(this);
			});
			jQuery(copyHelper).find('a.delete').click(function() {
				deliberaFlowRemove(this);
			});
			div_copy = div.clone();
			return div_copy;
		},
		stop : function(event, ui) {
			copyHelper && copyHelper.remove();
			jQuery(ui.item).addClass('clone');
			jQuery(ui.item).find('h2').click();
			sortorder = '';
			jQuery('#column2').each(function() {
				var itemorder = jQuery(this).sortable('toArray');
				var columnId = jQuery(this).attr('id');
				sortorder += itemorder.toString();
			});
		}
	});
	jQuery("#column2").sortable({
		receive : function(e, ui) {
			copyHelper = null;
		}
	});
	jQuery("#column1").sortable({
		receive : function(e, ui) {
			if(ui.item.hasClass("clone"))
			{
				ui.sender.sortable("cancel");
	        }
		}
	});
	jQuery(".dragbox-bt-save").click(function(){
		jQuery('#column2').each(function() {
			var itemorder = jQuery(this).sortable('toArray');
			var columnId = jQuery(this).attr('id');
			sortorder += itemorder.toString();
		});
		var data = {
            action : "delibera_save_flow",
            flow: sortorder,
            post_id: jQuery('#delibera-flow-postid').val(),
            nonce: jQuery('#_delibera-flow-nonce').val()
        };
		if(delibera_admin_flow.post_id == '')
		{
			jQuery('#column1').find('input:not(input[type=button], input[type=submit], input[type=reset]), textarea, select').each(function(){
				data[this.name] = this.value;
			});
		}
		else
		{
			jQuery('#column2').find('input:not(input[type=button], input[type=submit], input[type=reset]), textarea, select').each(function(){
				data[this.name] = this.value;
			});
		}
		
		jQuery.post(
			delibera_admin_flow.ajax_url, 
            data,
            function(response) {
            	if(response == 'ok')
            		alert("OK");
            	else
            		alert("Errors");
            }
        );
	});
});