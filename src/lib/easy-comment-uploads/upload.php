<?php require('../../../wp-load.php'); ?>
<!doctype html5>
<html>
	<head>
		<script type="text/javascript">
		// Write txt to comment field
		function write_comment (text) {
			// Handle commentMCE
			if (parent.parent.tinyMCE
				&& parent.parent.tinyMCE.get('comment')) {
				editor = parent.parent.tinyMCE.get('comment');
				editor.setContent(editor.getContent()
					+ '\n<p>' + text + '</p>');
				return;
			}

			// Handle nicEdit
			if (parent.parent.nicEditors
				&& parent.parent.nicEditors.findEditor('comment')) {
				editor = parent.parent.nicEditors.findEditor('comment');
				editor.setContent((editor.getContent() != '<br>' ?
					editor.getContent().replace(/(<(p|div)><br><\/(p|div)>)+$/,
					'') : '') + '<p>' + text + '</p>');
				return;
			}

			// Handle standard comment forms
			if (parent.parent.document.getElementById("comment")
				|| parent.parent.document.getElementById("comment-p1")
				|| parent.parent.document.forms["commentform"]
				&& parent.parent.document.forms["commentform"].comment) {
				comment = parent.parent.document.getElementById("comment")
					|| parent.parent.document.getElementById("comment-p1")
					|| parent.parent.document.forms["commentform"].comment;
				comment.value = comment.value.replace(/[\n]+$/, '')
					+ (comment.value.length > 0 ? '\n' : '') + text + '\n';
				return;
			}
		}
		
		function upload_end() {
			parent.document.getElementById('uploadform').style.display = 'block';
			parent.document.getElementById('loading').style.display = 'none';
		}
		</script>
	</head>

	<body>
		<?php
		// Get needed info
		$target_dir = ecu_upload_dir_path();
		$target_url = ecu_upload_dir_url();
		$images_only = get_option('ecu_images_only');
		$max_post_size = (int)ini_get('post_max_size');
		$max_upload_size = (int)ini_get('upload_max_filesize');

		if (!file_exists($target_dir))
			mkdir ($target_dir);

		$target_path = find_unique_target($target_dir
			. basename($_FILES['file']['name']));
		$target_name = basename($target_path);

		/* Debugging info */
		#$error = (int) $_FILES['file']['error'];
		//$file = file_within_size($ext, $lim);
		//write_js("alert('$file $ext $lim')");
		// sleep(2);

		// Default values
		$filecode = "";
		$filelink = "";

		// Detect whether the uploaded file is an image
		$is_image = preg_match('/(jpeg|png|gif)/i',
			$_FILES['file']['type'])
			&& preg_match('/^[^\\.]+\\.(jpeg|png|jpg|gif)$/i',
			$_FILES['file']['name']);
		$type = ($is_image) ? "img" : "file";

		if (!is_writable($target_dir)) {
			$alert = sprintf(__('Files can not be written to %s.\nPlease make sure that the permissions are set correctly (mode 666).'), $target_dir);
		} else if (empty($_FILES) && empty($_POST)
			&& isset($_SERVER['REQUEST_METHOD'])
			&& strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
			$alert = sprintf(__('Your file has exceeded the PHP max_post_size (%u MiB).\nPlease choose a smaller file or ask the website administrator to update this setting in the php.ini configuration file.',
				'easy-comment-uploads'), $max_post_size);
		} else if ($_FILES['file']['error'] == UPLOAD_ERR_INI_SIZE) {
			$alert = sprintf(__('Your file has exceeded the PHP max_upload_size (%u MiB).\n'
				. 'Please choose a smaller file or ask the website administrator to update this setting in the php.ini configuration file.',
				'easy-comment-uploads'), $max_upload_size);
		} else if ($FILES['file']['error'] == UPLOAD_ERR_PARTIAL) {
			$alert = __('Your file was only partially uploaded. Please try again.', 'easy-comment-uploads');
		} else if (!$is_image && $images_only) {
			$alert = __('Sorry, you can only upload images.',
				'easy-comment-uploads');
		} else if (filetype_blacklisted()) {
			$alert = __('You are attempting to upload a file with a disallowed/unsafe filetype!',
				'easy-comment-uploads');
		} else if (!filetype_whitelisted() && ecu_get_whitelist()) {
			$alert = __('You may only upload files with the following extensions: ', 'easy-comment-uploads')
				. implode(', ', ecu_get_whitelist());
		} else if (!file_within_size($extension, $limit)) {
			$alert = sprintf(__('The file you have uploaded is too large (%u KiB).\nPlease choose a smaller file and try again (uploads are limited to %u KiB%s).', 'easy-comment-uploads'),
				round($_FILES['file']['size']/1024, 1),
				$limit,
				$extension ? sprintf(__(' for .%s files',
					'easy-comment-uploads'), $extension) : '');
		} else if (ecu_user_uploads_per_hour() != -1
			&& ecu_user_uploads_in_last_hour()
			>= ecu_user_uploads_per_hour()) {
			$alert = sprintf(__('You are only permitted to upload %u files per hour.', 'easy-comment-uploads'),
				ecu_user_uploads_per_hour());
		} else if (!wp_verify_nonce($_REQUEST['_wpnonce'],
			'ecu_upload_form')) {
			// Check referer
			$alert = __('Invalid Referrer!');
		} else if (move_uploaded_file($_FILES['file']['tmp_name'],
			$target_path)) {
			$filelink = $target_url . $target_name;
			$filecode = "[$type]$filelink\[/$type]";

			// Add the filecode to the comment form
			write_js("write_comment('$filecode');");

			// Post info below upload form
			write_html_form("<div class='ecu_preview_file'>"
				. "<a href='$filelink'>$target_name</a><br />$filecode</div>");

			if ($is_image) {
				$thumbnail = ecu_thumbnail($filelink, 300);
				write_html_form("<a href='$filelink' rel='lightbox[new]'>"
					. "<img class='ecu_preview_img' src='$thumbnail' /></a>"
					. '<br />');
			}

			ecu_user_record_upload_time();
			if (get_option('ecu_media_library_insertion'))
				ecu_insert_attachment($target_path);
		} else {
			$alert = __('There was an error uploading the file, '
				. 'please try again!', 'easy-comment-uploads');
		}
		
		write_js('upload_end()');

		// Alert the user of any errors
		if (isset($alert))
			js_alert($alert);

		// Check upload against blacklist and return true unless it matches
		function filetype_blacklisted() {
			$blacklist = ecu_get_blacklist();
			return preg_match('/\\.((' . implode('|', $blacklist)
				. ')|~)([^a-z0-9]|$)/i', $_FILES['file']['name']);
		}

		// Check upload against whitelist and return true if it matches
		function filetype_whitelisted() {
			$whitelist = ecu_get_whitelist();
			return preg_match('/^[^\\.]+\\.(' . implode('|', $whitelist)
				. ')$/i', $_FILES['file']['name']);
		}
		
		// Check whether file in within size
		function file_within_size(&$extension, &$limit) {
			$extension = '';
			$limits = get_option('ecu_per_filetype_upload_limits');
			$limit = get_option('ecu_max_file_size');
			if (preg_match('/(?<=\.)[a-z0-9]+$/i',
				$_FILES['file']['name'], $matches)
				&& array_key_exists($matches[0], $limits)) {
				$extension = $matches[0];
				$limit = $limits[$extension];
			}
			return $limit == 0 || $_FILES['file']['size'] < $limit*1024;
		}

		// Write script as js to the page
		function write_js($script) {
			echo "<script type='text/javascript'>$script\n</script>\n";
		}

		// Send message to user in an alert
		function js_alert($msg) {
			write_js("alert('$msg');");
		}

		// Write html to the preview iframe
		function write_html_form ($html) {
			write_js("parent.parent.document.getElementById('ecu_preview')"
				. ".innerHTML = \"$html\""
				. "+ parent.parent.document.getElementById('ecu_preview')"
				. ".innerHTML");
		}

		// Find a unique filename similar to $prototype
		function find_unique_target ($prototype) {
			$prototype_parts = pathinfo ($prototype);
			$ext = $prototype_parts['extension'];
			$dir = $prototype_parts['dirname'];
			$name = sanitize_file_name(filter_var($prototype_parts['filename'],
				FILTER_SANITIZE_URL));
			$dot = $ext == '' ? '' : '.';
			if (!file_exists("$dir/$name.$ext")) {
				return "$dir/$name$dot$ext";
			} else {
				$i = 1;
				while (file_exists("$dir/$name-$i$dot$ext")) { ++$i; }
				return "$dir/$name-$i$dot$ext";
			}
		}

		?>
	</body>
</html>
