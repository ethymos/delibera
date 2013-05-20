<?php
/*
Plugin Name: Easy Comment Uploads
Plugin URI: http://wordpress.org/extend/plugins/easy-comment-uploads/
Description: Allow your users to easily upload images and files with their comments.
Author: Tom Wright
Version: 1.01
Author URI: http://gplus.to/twright/
License: GPLv3
*/

// Take a image url and return a url to a thumbnail for height $h, width $w
// and using zoom/crop mode $zc
function ecu_thumbnail($url, $h='null', $w='null', $zc=3) {
	return ecu_plugin_url() . "timthumb.php?src=$url&zc=$zc&h=$h&w=$w";
}

// Replaces [tags] with correct html
// Accepts either [img]image.png[/img] or [file]file.ext[/file] for other files
// Thanks to Trevor Fitzgerald's plugin (http://www.trevorfitzgerald.com/) for
// prompting the format used.
function ecu_insert_links($comment) {
	// Extract contents of tags
	preg_match_all('/\[(img|file)\]([^\]]*)\[\/\\1\]/i', $comment,
		$matches, PREG_SET_ORDER);
	foreach($matches as $match) {
		// Validate tags contain links of the correct format
		if (filter_var($match[2], FILTER_VALIDATE_URL)) {
			// Insert correct code based on tag
			preg_match('/[^\/]*$/', $match[2], $filename);
			$name = get_option('ecu_show_full_file_path') ? $match[2]
				: $filename[0];
			if ($match[1] == 'img') {
				$w = ecu_thumbnail_width(); $h = ecu_thumbnail_height();
				$thumbnail = ecu_thumbnail($match[2], $w, $h);
				$html = "<a href='$match[2]' rel='lightbox[comments]'>"
					. (get_option('ecu_display_images_as_links')
					? __('Image', 'easy-comment-uploads') . ": $name"
					: "<img onerror='this.src = \"$match[2]\"' "
					. "style='max-width: $w, max-height: $h' "
					. "class='ecu_images' src='$thumbnail' />")
					. '</a>';
			} elseif ($match[1] == 'file') {
				$html = sprintf('<a href="%s">%s: %s', $match[2],
					__('File', 'easy-comment-uploads'), $name);
			}
			
			$comment = str_replace($match[0], $html, $comment);
		}
	}

	return $comment;
}

// Retrieve either a user created file extension blacklist or a default list of
// harmful extensions. This function allows the blacklist to be updated with
// the plugin if it has not been edited by the user.
function ecu_get_blacklist() {
	$default_blacklist = array('htm', 'html', 'shtml', 'mhtm', 'mhtml',
		'js', 'php', 'php3', 'php4', 'php5', 'php6', 'phtml', 'cgi',
		'fcgi', 'pl', 'perl', 'p6', 'asp', 'aspx', 'htaccess', 'py',
		'python', 'exe', 'bat',  'sh', 'run', 'bin', 'vb', 'vbe',
		'vbs');
	return get_option('ecu_file_extension_blacklist', $default_blacklist);
}

// A list of file extensions which should not be harmful
function ecu_get_whitelist() {
	$default_whitelist = array('odt', 'ods', 'odp', 'doc', 'docx', 'xls',
		'xlsx', 'ppt', 'pptx', 'pdf', 'bmp', 'gif', 'jpg', 'jpeg',
		'webp', 'png', 'mp3', 'ogg', 'wav', 'webm', 'avi', 'mkv', 
		'mov', 'mp4', 'txt', 'psd', 'xcf', 'rtf', 'zip', '7z', 'xz',
		'tar', 'gz', 'bz2', 'tgz', 'tbz', 'tbz2', 'txz', 'lzma');
	return get_option('ecu_file_extension_whitelist',
		$default_whitelist);
}

// Get user ip address
function ecu_user_ip_address() {
	if ($_SERVER['HTTP_X_FORWARD_FOR'])
		return $_SERVER['HTTP_X_FORWARD_FOR'];
	else
		return $_SERVER['REMOTE_ADDR'];
}

// Record upload time in user metadata or ip based array
function ecu_user_record_upload_time() {
	$time = time();
	if (is_user_logged_in()) {
		$times = get_user_meta(get_current_user_id(), 'ecu_upload_times',
			true);
		update_user_meta(get_current_user_id(), 'ecu_upload_times',
			($times ? array_merge(array($time), $times) : array($time)));
	} else {
		$ip_upload_times = get_option('ecu_ip_upload_times');
		$ip = ecu_user_ip_address();

		if (array_key_exists($ip, $ip_upload_times)) {
			array_push($ip_upload_times[$ip], $time);
		} else {
			$ip_upload_times[$ip] = array($time);
		}
		update_option('ecu_ip_upload_times', $ip_upload_times);
	}
}

// Get the users hourly upload quota
function ecu_user_uploads_per_hour() {
	$uploads_per_hour = get_option('ecu_uploads_per_hour');
	foreach (get_option('ecu_uploads_per_hour') as $role => $x) {
		if ($role == 'none' || current_user_can($role))
			return $x;
	}
}

// Calculate the number of times which occured during the last hour
function ecu_user_uploads_in_last_hour() {
	// Get times either for current user or ip as available
	$ip_upload_times = get_option('ecu_ip_upload_times');
	$times = (is_user_logged_in()
		? get_user_meta(get_current_user_id(), 'ecu_upload_times', true)
		: $ip_upload_times[ecu_user_ip_address()]);
	$i = 0; // Counter for uploads
	$now = time();
	foreach($times as $time) {
		// If time passed less than or equal to 3600 s (1 hour), increment i
		if ($now - $time <= 3600)
			$i++;
	}
	return $i;
}

// Get url of plugin
function ecu_plugin_url() {
	return plugins_url('easy-comment-uploads/');
}

// Get the full path to the wordpress root directory
function ecu_wordpress_root_path() {
	$path = dirname(__FILE__);
	
	while (!file_exists($path . '/wp-config.php'))
		$path = dirname($path);

	return str_replace("\\", "/", $path) . '/';
}

// Placeholder for preview of uploaded files
function ecu_upload_form_preview($display=true) {
	?>
	<p id='ecu_preview' <?php ($display ? "" : "style='display:none'") ?> />
	<?php
}

// An iframe containing the upload form
function ecu_upload_form_iframe() {
	?>
	<iframe id='ecu_upload_frame' scrolling='no' frameborder='0'
		allowTransparency='true' height='0px'
		src='<?php echo ecu_plugin_url() ?>upload-form.php' name='upload_form'>
	</iframe>
	<?php
}

// Complete upload form
function ecu_upload_form($title, $msg, $check=true) {
	if ( !ecu_allow_upload() && $check ) return;

	?>
	<!-- Easy Comment Uploads for Wordpress by Tom Wright: http://wordpress.org/extend/plugins/easy-comment-uploads/ -->

	<div id='ecu_uploadform'>
	<h3 class='title'><?php echo $title ?></h3>
	<div class='message'><?php echo $msg ?></div>
	
	<?php
	ecu_upload_form_iframe();
	ecu_upload_form_preview();
	?>
	</div>
	
	<?php if (get_option('ecu_upload_form_position') != 'default') { ?>
	<script type="text/javascript">
		var submit = document.getElementById('submit');
		if (submit) {
			var uploadform = document.getElementById('ecu_uploadform');
			var position = "<?php echo get_option
				('ecu_upload_form_position') ?>";
			switch (position) {
				case 'above':
					uploadform.insertBefore(submit);
					break;
				case 'below':
					uploadform.insertBefore(submit.nextSibling);
					break;
			}
		}
	</script>
	<?php } ?>

	<!-- End of Easy Comment Uploads -->
	<?php
}

// Default comment form
function ecu_upload_form_default($check=true) {
	ecu_upload_form (
		ecu_upload_form_heading(), // $title
		'<p>' . ecu_message_text() . '</p>', // $msg
		$check // $check
	);
}

// Upload form heading
function ecu_upload_form_heading() {
	if (get_option('ecu_upload_form_heading'))
		return wp_kses(get_option('ecu_upload_form_heading'), array());
	else
		return __('Upload Files', 'easy-comment-uploads');
}

// Get message text
function ecu_message_text() {
	if (get_option('ecu_message_text'))
		return wp_kses_data(get_option('ecu_message_text'));
	else
		return __('You can include images or files in your comment by selecting them below. Once you select a file, it will be uploaded and a link to it added to your comment. You can upload as many images or files as you like and they will all be added to your comment.', 'easy-comment-uploads');
}

// Add options menu item (restricted to level_10 users)
function ecu_options_menu() {
	if (current_user_can("level_10"))
		add_plugins_page('Easy Comment Uploads options',
			'Easy Comment Uploads', 8, __FILE__, 'ecu_options_page');
}

// Add an image to the media library
function ecu_insert_attachment($filename) {
	$wp_filetype = wp_check_filetype(basename($filename), null);
	$attachment = array(
		'post_mime_type' => $wp_filetype['type'],
		'post_title' => preg_replace('/\.[^.]+$/', '',
			basename($filename)),
		'post_content' => '',
		'post_status' => 'inherit'
	);
	$attachment_id = wp_insert_attachment($attachment, $filename);
	require_once(ABSPATH . 'wp-admin/includes/image.php');
	$attachment_data = wp_generate_attachment_metadata($attachment_id,
		$filename);
	wp_update_attachment_metadata($attachment_id, $attachment_data);
}

// Get max thumbnail width
function ecu_thumbnail_width() {
	return get_option('ecu_thumbnail_width')
		? get_option('ecu_thumbnail_width') : 360;
}

// Get max thumbnail height
function ecu_thumbnail_height() {
	return get_option('ecu_thumbnail_height')
		? get_option('ecu_thumbnail_height') : 250;
}

class InvalidInputException extends Exception {}
 
// Provide an options page in wp-admin
function ecu_options_page() {
	// Handle changed options
	if (isset($_POST['submitted'])) {
		check_admin_referer('easy-comment-uploads');
		$error = false;

		// Update options
		
		try {
			// Upload Form
			if (isset($_POST['upload_form_heading']))
				if (preg_match('/^(\w+( \w+)*|)$/',
					$_POST['upload_form_heading']))
					update_option('ecu_upload_form_heading',
						$_POST['upload_form_heading']);
				else throw new InvalidInputException();
			if (isset($_POST['upload_form_text']))
				update_option('ecu_message_text',
				$_POST['upload_form_text']);
			if (isset($_POST['upload_form_visibility']))
				if (in_array($_POST['upload_form_visibility'],
					array('all', 'category', 'pages', 'none')))
					update_option('ecu_upload_form_visibility',
						$_POST['upload_form_visibility']);
				else throw new InvalidInputException();
			if (isset($_POST['enabled_category']))
				if (preg_match('/^([1-9][0-9]*)?$/',
					$_POST['enabled_category']))
					update_option('ecu_enabled_category',
						$_POST['enabled_category']);
				else $error = true;
			if (isset($_POST['enabled_pages']))
				if (preg_match('/^[1-9][0-9]*(, [1-9][0-9]*)?|$/',
					$_POST['enabled_pages']))
					update_option('ecu_enabled_pages', explode(', ',
						$_POST['enabled_pages']));
				else throw new InvalidInputException();
			if (isset($_POST['upload_form_position']))
				if (in_array($_POST['upload_form_position'],
					array('default', 'above', 'below')))
					update_option('ecu_upload_form_position',
						$_POST['upload_form_position']);
				else throw new InvalidInputException();
					
			// Comments
			update_option ('ecu_show_full_file_path',
				(int) ($_POST['show_full_file_path'] != null));
			update_option ('ecu_display_images_as_links',
				(int) ($_POST['display_images_as_links'] != null));
			if (isset($_POST['thumbnail_width']))
				if (preg_match('/^([1-9][0-9]*|)$/',
					$_POST['thumbnail_width']))
					update_option('ecu_thumbnail_width',
						$_POST['thumbnail_width']);
				else throw new InvalidInputException();
			if (isset($_POST['thumbnail_height']))
				if (preg_match('/^([1-9][0-9]*|)$/',
					$_POST['thumbnail_height']))
					update_option('ecu_thumbnail_height',
						$_POST['thumbnail_height']);
				else throw new InvalidInputException();
				
			// Files
			update_option('ecu_images_only',
				$_POST['images_only'] != null);
			if (isset($_POST['max_file_size']))
				if (preg_match('/^[1-9][0-9]*|0$/',
					$_POST['max_file_size']))
					update_option('ecu_max_file_size',
						$_POST['max_file_size']);
				else throw new InvalidInputException();
			if (isset($_POST['file_extension_whitelist'])
				&& $_POST['file_extension_whitelist'] != implode(', ',
					ecu_get_whitelist()))
				if (preg_match('/^[a-z0-9]+([, ][ ]*[a-z0-9]+)*$/i',
					$_POST['file_extension_whitelist']))
					if ($_POST['file_extension_whitelist'] == 'default')
						delete_option('ecu_file_extension_whitelist');
					else if ($_POST['file_extension_whitelist']
						== 'ignore')
						update_option('ecu_file_extension_whitelist',
							array());
					else update_option('ecu_file_extension_whitelist',
						preg_split("/[, ][ ]*/",
							$_POST['file_extension_whitelist']));
				else throw new InvalidInputException();
			if (isset($_POST['file_extension_blacklist'])
				&& $_POST['file_extension_blacklist'] != implode(', ',
					ecu_get_blacklist()))
				if (preg_match('/^[a-z0-9]+([, ][ ]*[a-z0-9]+)*$/i',
					$_POST['file_extension_blacklist']))
					if ($_POST['file_extension_blacklist'] == 'default')
						delete_option('ecu_file_extension_blacklist');
					else if ($_POST['file_extension_blacklist']
						== 'none')
						update_option('ecu_file_extension_blacklist',
							array());
					else update_option('ecu_file_extension_blacklist',
						preg_split("/[, ][ ]*/",
						$_POST['file_extension_blacklist']));
				else throw new InvalidInputException();
			if (isset($_POST['per_filetype_upload_limits'])) {
				$limits = array();
				if ($_POST['per_filetype_upload_limits'] != '')
					foreach (explode("\n",
						$_POST['per_filetype_upload_limits']) as $line)
						if (preg_match('/([a-z0-9]+),\s([1-9][0-9]*)/i',
							$line, $matches))
							$limits[$matches[1]] = $matches[2];
						else throw new InvalidInputException();
				update_option('ecu_per_filetype_upload_limits', $limits);
			}
			if (isset($_POST['upload_dir_path']))
				if ($_POST['upload_dir_path'] == '')
					delete_option('ecu_upload_dir_path');
				else
					update_option('ecu_upload_dir_path',
						$_POST['upload_dir_path']);
			update_option('ecu_media_library_insertion',
				(int) ($_POST['media_library_insertion'] != null));
					
			// User Permissions
			if (isset($_POST['permission_required']))
				if (in_array($_POST['permission_required'],
					array('none', 'read', 'edit_posts', 'upload_files')))
					update_option('ecu_permission_required',
						$_POST['permission_required']);
				else throw new InvalidInputException();
			$uploads_per_hour = get_option('ecu_uploads_per_hour');
			if (isset($_POST['upload_files_uploads_per_hour']))
				if (preg_match ('/^[1-9][0-9]*|0|-1$/',
					$_POST['upload_files_uploads_per_hour'])) {
					$uploads_per_hour['upload_files']
						= $_POST['upload_files_uploads_per_hour'];
					update_option('ecu_uploads_per_hour',
						$uploads_per_hour);
				} else throw new InvalidInputException();
			if (isset($_POST['edit_posts_uploads_per_hour']))
				if (preg_match ('/^[1-9][0-9]*|0|-1$/',
					$_POST['edit_posts_uploads_per_hour'])) {
					$uploads_per_hour['edit_posts']
						= $_POST['edit_posts_uploads_per_hour'];
					update_option('ecu_uploads_per_hour',
						$uploads_per_hour);
				} else throw new InvalidInputException();
			if (isset($_POST['read_uploads_per_hour']))
				if (preg_match ('/^[1-9][0-9]*|0|-1$/',
					$_POST['read_uploads_per_hour'])) {
					$uploads_per_hour['read']
						= $_POST['read_uploads_per_hour'];
					update_option('ecu_uploads_per_hour',
						$uploads_per_hour);
				} else throw new InvalidInputException();
			if (isset($_POST['none_uploads_per_hour']))
				if (preg_match ('/^[1-9][0-9]*|0|-1$/',
					$_POST['none_uploads_per_hour'])) {
					$uploads_per_hour['none']
						= $_POST['none_uploads_per_hour'];
					update_option('ecu_uploads_per_hour',
						$uploads_per_hour);
				} else throw new InvalidInputException();
		} catch (InvalidInputException $e) {
			$alert = __('Option invalid: please use the specified format.', 'easy-comment-uploads');
		}
			
		// Inform user
		if (!isset($alert))
			$alert = __('Options updated.', 'easy-comment-uploads');
		?>	
		<div class="updated">
			<p><strong><?php echo $alert ?></strong></p>
		</div>
		<?php
	}

	// Store current values for fields
	$images_only = get_option('ecu_images_only') ? 'checked' : '';
	$hide_comment_form = get_option('ecu_hide_comment_form')
		? 'checked' : '';
	$show_full_file_path = get_option('ecu_show_full_file_path')
		? 'checked' : '';
	$display_images_as_links = get_option('ecu_display_images_as_links')
		? 'checked' : '';
	$premission_required = array();
	foreach (array('none', 'read', 'edit_posts', 'upload_files')
		as $elem)
		$permission_required[$elem]
			= (get_option('ecu_permission_required') == $elem)
			? 'checked' : '';
	$max_file_size = get_option('ecu_max_file_size');
	$enabled_pages = implode(', ', get_option('ecu_enabled_pages'));
	$enabled_category = get_option('ecu_enabled_category');
	$file_extension_blacklist = ecu_get_blacklist() ?
		implode(', ', ecu_get_blacklist()) : 'none';
	$file_extension_whitelist = ecu_get_whitelist() ?
		implode(', ', ecu_get_whitelist()) : 'ignore';
	$uploads_per_hour = get_option('ecu_uploads_per_hour');
	$upload_form_heading = ecu_upload_form_heading();
	$upload_form_text = ecu_message_text();
	$upload_dir_path = get_option('ecu_upload_dir_path');
	$per_filetype_upload_limits = '';
	foreach (get_option('ecu_per_filetype_upload_limits')
		as $extension => $limit)
		$per_filetype_upload_limits .= "\n$extension, $limit";
	$per_filetype_upload_limits = substr($per_filetype_upload_limits, 1);
	$upload_form_visibility_checked = array(
		get_option('ecu_upload_form_visibility') => 'checked="checked"'
	);
	$upload_form_position_checked = array(
		get_option('ecu_upload_form_position') => 'checked="checked"'
	);
	$media_library_insertion = get_option('ecu_media_library_insertion')
		? 'checked' : '';

	// Info for form
	$actionurl = $_SERVER['REQUEST_URI'];

	?>
	<div class="wrap" style="max-width:950px !important;">
	<h2>Easy Comment Uploads</h2>
	
	<a href='http://goo.gl/WFJP6' target='_blank'
		style='text-decoration: none'>
	<p id='ecu_donate' style='background-color: #757575; padding: 0.5em;
		color: white; font-weight: bold; text-align: center; font-size: 11pt;
		border-radius: 10px'>
		<?php
			_e('If you find this plugin useful and want to support its future development, please consider donating.', 'easy-comment-uploads');
		?>
		<input type="submit" class="button-primary"
		style="margin-left: 1em" name="donate"
		value="<?php _e('Donate', 'easy-comment-uploads') ?>" />
	</p>
	</a>

	<form name="ecuform" action="<?php echo $action_url ?>"
		method="post">
		<input type="hidden" name="submitted" value="1" />
		<?php wp_nonce_field('easy-comment-uploads') ?>

		<h3><?php _e('Upload Form', 'easy-comment-uploads') ?></h3>
		<table class="form-table" id="files-table">
		<tbody>
			<tr valign="top">
				<th scope="row">
					<label for="upload_form_heading">
						<?php _e('Title',
							'easy-comment-uploads') ?>
					</label>
				</th>
				<td>
					<input id="upload_form_heading" type="text"
						name="upload_form_heading" class='regular-text'
						pattern="^(\w+( \w+)*|)$"
						value="<?php echo $upload_form_heading ?>" />
					<span class="description">
						<?php _e('Title shown above upload form (leave blank for default text).', 'easy-comment-uploads') ?>
					</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="upload_form_text">
						<?php _e('Descriptive text',
							'easy-comment-uploads') ?>
					</label>
				</th>
				<td>
					<textarea id="upload_form_text"
						name="upload_form_text"
						style="width : 100%; height : 80pt"
						><?php echo $upload_form_text ?></textarea>
					<span class="description">
						<?php _e('Text explaining use of the upload form (leave blank for default text; basic html tags allowed).', 'easy-comment-uploads') ?>
					</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<?php _e('Visibility', 'easy-comment-uploads') ?>
				</th>
				<td>
					<fieldset>
						<label for="upload_form_visibility_all">
							<input type="radio"
								id="upload_form_visibility_all"
								name="upload_form_visibility"
								value="all"
								<?php echo $upload_form_visibility_checked['all'] ?> />
							<?php _e('Show in all comment forms', 'easy-comment-uploads') ?>
						</label>
						<br />
						<label for="upload_form_visibility_category">
							<input type="radio"
								id="upload_form_visibility_category"
								name="upload_form_visibility"
								value="category"
								<?php echo $upload_form_visibility_checked['category'] ?> />
							<?php _e('Show only in category', 'easy-comment-uploads') ?>
							<?php
							$args = array('hide_empty' => 0,
								'name' => 'enabled_category',
								'selected' => $enabled_category,
								'orderby' => 'name',
								'hierarchical' => true);
							wp_dropdown_categories($args); 
							?>
						</label>
						<br />
						<label for="upload_form_visibility_pages">
							<input type="radio"
								id="upload_form_visibility_pages"
								name="upload_form_visibility"
								value="pages"
								<?php echo $upload_form_visibility_checked['pages'] ?> />
							<?php _e('Show only for these <a href="http://www.techtrot.com/wordpress-page-id/">page/post IDs</a>', 'easy-comment-uploads') ?>
							<input id="enabled_pages" type="text"		   
								name="enabled_pages" class="large-text"
								placeholder="ID1, ID2, ID3, ..."
								pattern="^[1-9][0-9]*(, [1-9][0-9]*)?|$"
								value="<?php echo $enabled_pages ?>" />
						</label>
						<br />
						<label for="upload_form_visibility_none">
							<input type="radio"
								id="upload_form_visibility_none"
								name="upload_form_visibility"
								value="none"
								<?php echo $upload_form_visibility['none'] ?> />
							<?php _e('Hide from all comment forms',
								'easy-comment-uploads') ?>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<?php _e('Position', 'easy-comment-uploads') ?>
				</th>
				<td>
					<fieldset>
						<label for="upload_form_position_above">
							<input type="radio"
								id="upload_form_position_above"
								name="upload_form_position"
								value="above"
								<?php echo $upload_form_position_checked['above'] ?> />
							<?php _e('Place above the submit button', 
								'easy-comment-uploads') ?>
						</label>
						<br />
						<label for="upload_form_position_below">
							<input type="radio"
								id="upload_form_position_below"
								name="upload_form_position"
								value="below"
								<?php echo $upload_form_position_checked['below'] ?> />
							<?php _e('Place below the submit button',
								'easy-comment-uploads') ?>
						</label>
						<br />
						<label for="upload_form_position_default">
							<input type="radio"
								id="upload_form_position_default"
								name="upload_form_position"
								value="default"
								<?php echo $upload_form_position_checked['default'] ?> />
							<?php _e('Let the current theme determine position', 'easy-comment-uploads') ?>
						</label>
					</fieldset>
				</td>
			</tr>
		</tbody>
		</table>
		
		<h3><?php _e('Comments', 'easy-comment-uploads') ?></h3>
		<table class="form-table" id="files-table">
		<tbody>
			<tr valign="top">
				<th scope="row">
					<?php _e('Show full url', 'easy-comment-uploads') ?>
				</th>
				<td>
					<label for="show_full_file_path">
						<input id="show_full_file_path" type="checkbox"
							name="show_full_file_path"
							<?php echo $show_full_file_path ?> />
						<?php _e('Show full url in links to files',
							'easy-comment-uploads') ?>
					</label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<?php _e('Images as links',
						'easy-comment-uploads') ?>
				</th>
				<td>
					<label for="display_images_as_links">
						<input type="checkbox"
							id="display_images_as_links"
							name="display_images_as_links"
							<?php echo $show_full_file_path ?> />
						<?php _e('Replace images with links',
							'easy-comment-uploads') ?>
					</label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<?php _e('Thumbnail dimensions',
						'easy-comment-uploads') ?>
				</th>
				<td>
					<input id="thumbnail_width" type="text"
						name="thumbnail_width" class='small-text'
						pattern="^([1-9][0-9]*|)$"
						value="<?php echo ecu_thumbnail_width() ?>" />
					&times;
					<input id="thumbnail_height" type="text"
						name="thumbnail_height" class='small-text'
						pattern="^([1-9][0-9]*|)$"
						value="<?php echo ecu_thumbnail_height() ?>" />
					pixels
					<span class="description">
						<?php _e('Maximum size of image thumbnails (leave blank for default values).', 'easy-comment-uploads') ?>
					</span>
				</td>
			</tr>
		</tbody>
		</table>

		<h3><?php _e('Files', 'easy-comment-uploads') ?></h3>
		<table class="form-table" id="files-table">
		<tbody>
			<tr valign="top">
				<th scope="row">
					<?php _e('Images only', 'easy-comment-uploads') ?>
				</th>
				<td>
					<label for="images_only">
						<input id="images_only" type="checkbox"
							name="images_only"
							<?php echo $images_only ?> />
						<?php _e('Only allow images to be uploaded',
							'easy-comment-uploads') ?>
					</label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="max_file_size">
						<?php _e('Size limit',
							'easy-comment-uploads') ?>
					</label>
				</th>
				<td>
					<input id="max_file_size" type="text"
						name="max_file_size" class='small-text'
						pattern="^[1-9][0-9]*|0$"
						value="<?php echo $max_file_size ?>" />
					<span class="description">
						<?php _e('Limit the size of uploaded files (KiB, 0 = unlimited).', 'easy-comment-uploads') ?>
					</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="file_extension_whitelist">
						<?php _e('Allowed extensions',
							'easy-comment-uploads') ?>
					</label>
				</th>
				<td>
					<input id="file_extension_whitelist" type="text"
						value="<?php echo $file_extension_whitelist ?>"
						name="file_extension_whitelist"
						pattern="^[a-zA-Z0-9]+([, ][ ]*[a-zA-Z0-9]+)*$"
						class="large-text" />
					<span class="description">
						<?php _e('Only files with the following extensions will be allowed to be uploaded (extensions separated with spaces, \'ignore\' to disable the whitelist, or \'default\' to restore the default list).', 'easy-comment-uploads') ?>
					</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="file_extension_blacklist">
						<?php _e('Blacklisted extensions',
							'easy-comment-uploads') ?>
					</label>
				</th>
				<td>
					<input id="file_extension_blacklist" type="text"
						value="<?php echo $file_extension_blacklist ?>"
						name="file_extension_blacklist"
						pattern="^[a-zA-Z0-9]+([, ][ ]*[a-zA-Z0-9]+)*$"
						class="large-text" />
					<span class="description">
						<?php _e('File extensions which may not be uploaded (extensions separated with spaces, \'none\' to allow all (not recommended), or \'default\' to restore the default list).', 'easy-comment-uploads') ?>
					</span>
				</td>
			</tr> 
			<tr valign="top">
				<th scope="row">
					<label for="per_filetype_upload_limits">
						<?php _e('Filetype size limits',
							'easy-comment-uploads') ?>
					</label>
				</th>
				<td>
					<textarea id="per_filetype_upload_limits"
						name="per_filetype_upload_limits"
						style="width : 100%; height : 65pt"
						pattern="^([a-zA-Z0-9]+),\s([1-9][0-9]*)$"
						><?php echo $per_filetype_upload_limits ?></textarea>
					<span class="description">
						<?php _e('List of file extensions and size limits (an extenstion and a limit in KiB per line, separated by a comma e.g. png, 2000).', 'easy-comment-uploads') ?>
					</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="upload_dir_path">
						<?php _e('Upload directory',
							'easy-comment-uploads') ?>
					</label>
				</th>
				<td>
					<input id="upload_dir_path" type="text"
						name="upload_dir_path" class="large-text"
						value="<?php echo $upload_dir_path ?>" />
					<span class="description">
						<?php _e('Directory used for storing uploaded files (path relative to the Wordpress installation directory or leave blank for default location).', 'easy-comment-uploads') ?>
					</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<?php _e('Media library integration',
						'easy-comment-uploads') ?>
				</th>
				<td>
					<label for="media_library_insertion">
						<input id="media_library_insertion"
							type="checkbox"
							name="media_library_insertion"
							<?php echo $media_library_insertion ?> />
						<?php _e('Insert uploaded files into Wordpress media library', 'easy-comment-uploads') ?>
					</label>
				</td>
			</tr>
		</tbody>
		</table>

		<h3><?php _e('User Permissions',
			'easy-comment-uploads') ?></h3>
		
		<table class="widefat">
		<col />
		<col align="left" />
		<thead>
			<tr>
				<th scope="col" class="manage-column">
					<?php _e('User class') ?>
				</th>
				<th scope="col" class="manage-column">
					<?php _e('Permission required to upload images') ?>
				</th>
				<th scope="col">
					<?php _e('Uploads allowed per hour ',
						'easy-comment-uploads') ?>
					(-1 = <?php _e('unlimited',
						'easy-comment-uploads') ?>)
				</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th scope="row">
				<?php _e('Users with upload rights',
					'easy-comment-uploads') ?>
				<br />
				<em>(<?php _e('e.g. only admin, editors and authors',
					'easy-comment-uploads') ?>)</em>
				</th>
				<td>
					<input id="upload_rights_only" type="radio"
						name="permission_required" value="upload_files"
						<?php echo $permission_required['upload_files'] ?>
						/>
				</td>
				<td>
				<input id="upload_files_uploads_per_hour" type="text"
					name="upload_files_uploads_per_hour"
					class="small-text" pattern="^[1-9][0-9]*|0|-1$"
					value="<?php echo $uploads_per_hour['upload_files'] ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row">
				<?php _e('Contributors', 'easy-comment-uploads') ?>
				</th>
				<td>
					<input id="edit_rights_only" type="radio"
						name="permission_required" value="edit_posts"
						<?php echo $permission_required['edit_posts'] ?> />
				</td>
				<td>
				<input id="edit_posts_uploads_per_hour" type="text"
					name="edit_posts_uploads_per_hour" 
					class="small-text" pattern="^[1-9][0-9]*|0|-1$"
					value="<?php echo $uploads_per_hour['edit_posts'] ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row">
				<?php _e('Registered users', 'easy-comment-uploads') ?>
				</th>
				<td>
					<input id="registered_users_only" type="radio"
						name="permission_required" value="read"
						<?php echo $permission_required['read'] ?> />
				</td>
				<td>
				<input id="read_uploads_per_hour" type="text"
					name="read_uploads_per_hour" class="small-text"
					pattern="^[1-9][0-9]*|0|-1$"
					value="<?php echo $uploads_per_hour['read'] ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row">
				<?php _e('Unregistered users',
					'easy-comment-uploads') ?>
				</th>
				<td>
					<input id="all_users" type="radio"
						name="permission_required" value="none"
						<?php echo $permission_required['none'] ?> />
				</td>
				<td>
				<input id="none_uploads_per_hour" type="text"
					name="none_uploads_per_hour" class="small-text"
					pattern="^[1-9][0-9]*|0|-1$"
					value="<?php echo $uploads_per_hour['none'] ?>" />
				</td>
			</tr>
		</tbody>
		</table>

		<p class="submit">
			<input type="submit" class="button-primary"
				name="Submit" value="<?php _e('Save Changes',
				'easy-comment-uploads') ?>" />
		</p>
	</form>
	<?php

	// Sample upload form
	?>
	<div style='margin : auto auto auto 2em; width : 40em;
		background-color : ghostwhite; border : 1px dashed gray;
		padding : 0 1em 0em 1em'>
		<?php ecu_upload_form_default(false) ?>
	</div>
	<?php
}

function ecu_upload_dir_path() {
	if (get_option('ecu_upload_dir_path')) {
		return ecu_wordpress_root_path()
			. get_option('ecu_upload_dir_path')  . '/';
	} else {
		$upload_dir = wp_upload_dir();
		return $upload_dir['path'] . '/';				
	}
}

function ecu_upload_dir_url() {
	if (get_option('ecu_upload_dir_path')) {
		return get_option('siteurl') . '/'
			. get_option('ecu_upload_dir_path') . '/';
	} else {
		$upload_dir = wp_upload_dir();
		return $upload_dir['url'] . '/';		
	}
}

// Seperate function as closures were not supported before 5.3.0
function ecu_extract_cat_ID($category) {
	return $category->cat_ID;
}

// Are uploads allowed?
function ecu_allow_upload() {
	global $post;
	$permission_required = get_option('ecu_permission_required');
	$upload_form_visibility = get_option('ecu_upload_form_visibility');
	$enabled_pages = get_option('ecu_enabled_pages');
	$enabled_category = get_option('ecu_enabled_category');
	$categories = array_map('ecu_extract_cat_ID', get_the_category());
	
	// If the current user does not pocess the permissions required to upload
	// files, return false
	if (!($permission_required == 'none'
		|| current_user_can($permission_required)))
		return false;
	
	// Perform the appropriate check for the selected level of visibility
	switch ($upload_form_visibility) {
		case 'all':
			return true;
		case 'category':
			return in_array($enabled_category, $categories);
		case 'pages':
			return in_array($post->ID, $enabled_pages);
		case 'none':
			return false;
	}
}

// Set options to defaults, if not already set
function ecu_initial_options() {
	ecu_textdomain();
	/*var_dump(ecu_thumbnail_width());
	var_dump(ecu_thumbnail_height());*/
	
	wp_enqueue_style('ecu', ecu_plugin_url () . 'style.css');
	if (get_option('ecu_permission_required') === false)
		update_option('ecu_permission_required', 'none');
	if (get_option('ecu_show_full_file_path') === false)
		update_option('ecu_show_full_file_path', 0);
	if (get_option('ecu_display_images_as_links') === false)
		update_option('ecu_display_images_as_links', 0);
	if (get_option('ecu_hide_comment_form') !== false) {
		if (get_option('ecu_hide_comment_form'))
			update_option('ecu_upload_form_visibility', 'none');
		delete_option('ecu_hide_comment_form');
	}
	if (get_option('ecu_images_only') === false)
		update_option('ecu_images_only', 0);
	if (get_option('ecu_max_file_size') === false)
		update_option('ecu_max_file_size', 0);
	if (get_option('ecu_enabled_pages') === false)
		update_option('ecu_enabled_pages', array());
	else if (get_option('ecu_enabled_pages') == 'all')
		update_option('ecu_enabled_pages', array());
	else if (is_string(get_option('ecu_enabled_pages')))
		update_option('ecu_enabled_pages', explode(', ',
			get_option('ecu_enabled_pages')));
	if (get_option('ecu_enabled_category') === false)
		update_option('ecu_enabled_category', '');
	if (get_option('ecu_ip_upload_times') === false)
		update_option('ecu_ip_upload_times', array());
	if (get_option('ecu_uploads_per_hour') === false)
		update_option('ecu_uploads_per_hour', array(
			'upload_files' => -1,
			'edit_posts' => 50,
			'read' => 10,
			'none' => 5,
		));
	if (get_option('ecu_upload_form_visibility') === false)
		update_option('ecu_upload_form_visibility', 'all');
	if (get_option('ecu_upload_form_position') === false)
		update_option('ecu_upload_form_position', 'above');
	if (get_option('ecu_per_filetype_upload_limits') === false)
		update_option('ecu_per_filetype_upload_limits', array());
	if (get_option('ecu_media_library_insertion') === false)
		update_option('ecu_media_library_insertion', false);
}

// Set textdomain for translations (i18n)
function ecu_textdomain() {
	load_plugin_textdomain('easy-comment-uploads', false,
		basename(dirname(__FILE__)) . '/languages');
}

// Add settings link to plugins
// http://www.whypad.com/posts/wordpress-add-settings-link-to-plugins-page/785/
function ecu_add_settings_link($links, $file) {
	$settings_link = '<a '
		. 'href="plugins.php?page=easy-comment-uploads/main.php">'
		. __('Settings', 'easy-comment-uploads') . '</a>';
	if ($file == plugin_basename(__FILE__))
		array_unshift($links, $settings_link);
	return $links;
}

// Register code with wordpress
add_action('admin_menu', 'ecu_options_menu');
add_filter('comment_text', 'ecu_insert_links');
if (!get_option('ecu_hide_comment_form'))
	add_action('comment_form', 'ecu_upload_form_default');
add_action('init', 'ecu_initial_options');
add_filter('plugin_action_links', 'ecu_add_settings_link', 10, 2 );
