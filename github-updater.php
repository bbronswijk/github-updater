<?php
/*
 Plugin Name: Github and Gitlab Wordpress plugin updater
 Plugin URI: https://github.com/bbronswijk/github-updater
 Description: This plugin allows wordpress to use the plugins which were downloaded directly from gitlab or github.
 Author: B. Bronswijk, LYCEO
 Version: 1.0
 */

$githubUpdatePlugin = new GithubUpdatePlugin();
if (!class_exists('WP_Custom_Plugin_Update')) {
	require_once 'wp_custom_plugin_update.php';
}

class GithubUpdatePlugin
{
	public $setting_page  = 'custom_updater_settings';
	public $setting_section = 'update_access_token_section';
	public $option_group  = 'updater_token_group';

	function __construct()
	{
		add_action( 'upgrader_process_complete', array( $this, 'rename_plugin_dir', 10, 2 ) );
		add_action( 'admin_menu', array( $this, 'create_admin_page' ) );
		add_action( 'admin_init', array( $this, 'add_setting_section' ) );
	}


	function rename_plugin_dir ( $data )
	{
		// get the data of the updated plugins
		$updated_plugins = $data['plugins'];

		if( empty($updated_plugins) ) return false;

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
	}

	public function create_admin_page()
	{
		add_options_page(
			'Custom Updater', //
			__('Settings updates'), // menu item
			'publish_posts',
			$this->setting_page,
			array($this, 'admin_token_page')
		);
	}

	function admin_token_page()
	{
		if ( !current_user_can( 'publish_posts' ) ) wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

		require_once 'admin_page.php';
	}

	public function section_description_html()
	{
		echo '<p>Voer hieronder uw accestokens in voor de plugin die gebruik maken van de Custom Update Plugin</p>';
	}

	public function add_setting_section()
	{
		add_settings_section(
			$this->setting_section, // section id
			false, // section title
			array($this, 'section_description_html'), // hmtl callback
			$this->setting_page // setting page id
		);
	}

}




