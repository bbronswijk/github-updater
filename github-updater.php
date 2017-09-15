<?php
/*
 Plugin Name: Github and Gitlab Wordpress plugin updater
 Plugin URI: http://brambronswijk.com
 Description: This plugin allows wordpress to use the plugins which were downloaded directly from gitlab or github.
 Author: B. Bronswijk, LYCEO
 Version: 1.0
 */

function rename_plugin_dir ( $this, $data )
{
	// get the data of the updated plugins
	$updated_plugins = $data['plugins'];

	foreach ($updated_plugins as $path) {

		$path_parts = explode ('/', $path);
		$plugin_directory = $path_parts[0];

		// loop through plugin directories and look for the current updated plugin folder
		$dirs = glob(ABSPATH.'wp-content/plugins/*');
		foreach ($dirs as $dir) {
			// check if this is the folder we need
			if (is_dir($dir) && strpos ($dir, '-master') !== false && strpos($dir, $plugin_directory)) {

				//explode the directory path
				$parts = explode ('-master', $dir);

				// rename the directory and use only the part before the -master part
				rename ( $dir , $parts[0] );
			}
		}
	}

} add_action('upgrader_process_complete','rename_plugin_dir', 10, 2 );
