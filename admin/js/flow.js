/**
 * 
 */

function deliberaFlowToggle(element)
{
	jQuery(element).parent().siblings('.dragbox-content').toggle();
}

function deliberaFlowRemove(element)
{
	var sel = confirm('do you want to delete the widget?');
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
			
			jQuery(ui.item).find('h2').click();
			sortorder = '';
			jQuery('#column2').each(function() {
				var itemorder = jQuery(this).sortable('toArray');
				var columnId = jQuery(this).attr('id');
				sortorder += itemorder.toString();
			});
			sortorder = sortorder.substring(1, sortorder.length)
		}
	});
	jQuery(".column").sortable({
		receive : function(e, ui) {
			copyHelper = null;
		}
	});
	jQuery(".dragbox-bt-save").click(function(){
		var data = {
            action : "delibera_save_flow",
            flow: sortorder,
            post_id: jQuery('#delibera-flow-postid').val(),
            nonce: jQuery('#_delibera-flow-nonce').val()
        };
		
		jQuery('#column2').find('input:not(input[type=button], input[type=submit], input[type=reset]), textarea, select').each(function(){
			data[this.name] = this.value;
		});
		
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