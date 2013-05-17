<?php require ('../../../wp-load.php'); ?>
<!doctype html>
<head>
	<link rel='stylesheet' href='<?php echo get_stylesheet_uri() ?>' />

	<style>
		body {
			background : transparent !important;
			text-align: center !important;
			margin: 0 !important;
		}
	</style>
	
	<script type='text/javascript'>
		function resize_iframe() {
			parent.document.getElementById('ecu_upload_frame').height
				= document.body.scrollHeight;
			parent.document.getElementById('ecu_upload_frame').width
				= document.body.scrollWidth;
		}
		
		function upload_start() {
			document.getElementById('uploadform').style.display
			= 'none';
			document.getElementById('loading').style.display = 'block';
			document.uploadform.submit();
			document.uploadform.file.value = ''
		}
	</script>
</head>
<body style='min-width: 0 !important;'>
	<form target='hiddenframe' enctype='multipart/form-data'
	action='<?php echo ecu_plugin_url() . 'upload.php' ?>'
	method='POST' name='uploadform' id='uploadform'>
		<?php wp_nonce_field('ecu_upload_form') ?>
		<label for='file' name='prompt'>
		<?php _e('Select File', 'easy-comment-uploads') ?>:
		</label>
		<input type='file' name='file' id='file'
			onchange='upload_start()' />
	</form>

	<div align='center'>
		<img src='loading.gif' style='display: none' id='loading' />
	</div>

	<iframe name='hiddenframe' style='display : none' frameborder='0'></iframe>
	
	<script type='text/javascript'>
		resize_iframe()
	</script>
</body>
