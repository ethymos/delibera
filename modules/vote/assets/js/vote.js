jQuery(document).ready(function() {
	jQuery('.delibera-voto-modal-close').click(function (){
		jQuery(this).parent().parent().hide();
	});
	jQuery('.label-voto').click(function () {
		var id = jQuery(this).attr('id').replace('delibera-label-voto-', '');
		jQuery('#delibera-voto-modal-' + id).show();
	});
});