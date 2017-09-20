<?php
/*
 Plugin Name: Custom Wordpress Updater
 Plugin URI: https://github.com/bbronswijk/github-updater
 Description: This plugin allows WordPress to update plugins and themes directly from gitlab or github.
 Author: B. Bronswijk, LYCEO
 Version: 2.0.0
 */

$githubUpdatePlugin = new GithubUpdatePlugin();

// require the update class
require_once 'wp_custom_update.php';

/**
 * The main plugin class
 *
 * This class creates the admin settings pages
 * and renames the directories after a plugin or theme update
 *
 * @since 1.0.0
 */
class GithubUpdatePlugin
{
	// get accessed by the WP_CustomUpdate class
	public $setting_page  = 'custom_updater_settings';
	public $setting_section = 'update_access_token_section';
	public $option_group  = 'updater_token_group';

	function __construct()
	{
		add_action ('upgrader_process_complete', [$this, 'rename_plugin_dir'], 10, 2 );
		add_action ('upgrader_process_complete', [$this, 'rename_theme_dir'], 10, 2 );
		add_action ('admin_menu', [$this, 'create_admin_page']);
		add_action ('admin_init', [$this, 'add_setting_section']);
		add_filter ('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_action_links']);
	}

	/**
	 * Renames the plugin directory
	 *
	 * @param $upgrader_object
	 * @param $data
	 */
	function rename_plugin_dir ( $upgrader_object, $data ) {
		// get the data of the updated plugins
		$updated_plugins = $data['plugins'];

		if ( false === empty($updated_plugins)) {
			foreach ($updated_plugins as $path) {
				$path_parts       = explode( '/', $path );
				$plugin_directory = $path_parts[0];

				// loop through plugin directories and look for the current updated plugin folder
				$dirs = glob(ABSPATH . 'wp-content/plugins/*');
				foreach ( $dirs as $dir ) {
					// check if this is the folder we need
					if (is_dir( $dir ) && false !== strpos( $dir, '-master' )  && strpos( $dir, $plugin_directory )) {

						//explode the directory path
						$parts = explode( '-master', $dir );

						// rename the directory and use only the part before the -master part
						rename( $dir, $parts[0] );
					}
				}
			}
		}
	}

	/**
	 * Renames the theme directory
	 *
	 * @param $upgrader_object
	 * @param $data
	 */
	function rename_theme_dir($upgrader_object, $data )
	{
		// get the data of the updated plugins
		$updated_themes = $data['themes'];

		if( !empty($updated_themes) ) {

			foreach ($updated_themes as $path) {

				$path_parts      = explode('/', $path);
				$theme_directory = $path_parts[0];

				// loop through plugin directories and look for the current updated plugin folder
				$dirs = glob(ABSPATH . 'wp-content/themes/*');
				foreach ( $dirs as $dir ) {
					// check if this is the folder we need
					if (is_dir( $dir ) && false !== strpos( $dir, '-master' ) && strpos( $dir, $theme_directory )) {

						//explode the directory path
						$parts = explode( '-master', $dir );

						// rename the directory and use only the part before the -master part
						rename( $dir, $parts[0] );
					}
				}
			}
		}
	}

	/**
	 * Creates an admin setting page
	 */
	public function create_admin_page()
	{
		add_options_page(
			__('Custom Updater'), // title
			__('Update settings'), // menu item
			'publish_posts',
			$this->setting_page,
			array($this, 'admin_token_page')
		);
	}

	/**
	 * Renders the admin setting page
	 */
	function admin_token_page()
	{
		// check if the users capabilities
		if (false === current_user_can('publish_posts')){
			wp_die(__('You do not have sufficient permissions to access this page.') );
		}

		require_once 'admin_page.php';
	}

	/**
	 * Creates an section for settings
	 */
	public function add_setting_section()
	{
		add_settings_section(
			$this->setting_section, // section id
			false, // section title
			array($this, 'section_description_html'), // html output callback
			$this->setting_page // setting page id
		);
	}

	/**
	 * Outputs the html for the settings section
	 */
	public function section_description_html()
	{
		echo '<p>Voer hieronder uw accestokens in voor de themes & plugin die gebruik maken van de Custom Update Plugin</p>';
	}

	/**
	 * Adds a setting link to the plugin item in the plugin list
	 *
	 * @param $links
	 *
	 * @return array
	 */
	function add_action_links ( $links ) {
		$mylinks = array('<a href="' . admin_url( 'options-general.php?page='.$this->setting_page ) . '">Settings</a>');
		return array_merge( $links, $mylinks );
	}

}




