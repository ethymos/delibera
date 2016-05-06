jQuery(document).ready(function() {

	jQuery(".delibera-like").click(function() {
		var container = jQuery(this);
		console.log(container);
		if(jQuery(this).children('input[name="object_id"]').val() > 0)
		{
			jQuery.post(
				delibera.ajax_url,
				{
					action : "delibera_curtir",
					like_id : jQuery(this).children('input[name="object_id"]').val(),
					type : jQuery(this).children('input[name="type"]').val(),
				},
				function(response) {
					container.toggleClass('.active');
					jQuery(container).children('.delibera-like-count').text(response);
					jQuery(container).children('.delibera-like-count').show();
				}
			);
		}
	});

	jQuery(".delibera-unlike").click(function() {
		var container = jQuery(this);
		if (container.hasClass('.active'))
		{
			jQuery.post(
				delibera.ajax_url,
				{
					action : "delibera_desdiscordar",
					like_id : jQuery(this).children('input[name="object_id"]').val(),
					type : jQuery(this).children('input[name="type"]').val(),
				},
				function(response) {
					container.removeClass('active');
					jQuery(container).parent().children('.delibera-unlike-count').text(response);
				}
			);
		}
		else if(jQuery(this).children('input[name="object_id"]').val() > 0)
		{
			jQuery.post(
				delibera.ajax_url,
				{
					action : "delibera_discordar",
					like_id : jQuery(this).children('input[name="object_id"]').val(),
					type : jQuery(this).children('input[name="type"]').val(),
				},
				function(response) {
					jQuery(container).children('.delibera-unlike-count').text(response);
					jQuery(container).children('.delibera-unlike-count').show();
					container.addClass('active');
				}
			);
		}
	});
});
