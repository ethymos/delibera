<?php

function delibera_et_builder_ready()
{
	$modules = array_filter(glob(dirname(__FILE__).'/*'), 'is_dir');
	foreach ($modules as $module)
	{
		$filename = $module.DIRECTORY_SEPARATOR.basename($module).'.php';
		if(file_exists($filename))
		{
			require_once $filename;
		}
	}
}
add_action('et_builder_ready', 'delibera_et_builder_ready');

