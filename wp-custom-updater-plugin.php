<?php
/*
 Plugin Name: Custom Wordpress Updater
 Plugin URI: https://github.com/bbronswijk/github-updater
 Description: This plugin allows WordPress to update plugins and themes directly from gitlab or github.
 Author: B. Bronswijk
 Version: 2.0.2
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
	public $token_setting = 'custom_update_access_token';
	public $repo_setting  = 'custom_update_repo';
	public $option_group  = 'updater_token_group';
	

	function __construct()
	{

		add_action ('upgrader_process_complete', array($this, 'rename_plugin_dir'), 10, 2 );
		add_action ('upgrader_process_complete', array($this, 'rename_theme_dir'), 10, 2 );

		add_action ('admin_menu', array($this, 'create_admin_page'));
		add_action( 'admin_init', array($this, 'create_token_setting'));

		add_filter ('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
		// only on admin page
		add_action ('admin_enqueue_scripts', array($this,'loadScripts'));
		add_action ('wp_ajax_set_repo_versions', array($this,'set_repo_versions'));
		add_action ('wp_ajax_set_save_token', array($this,'save_token'));
	}

	function loadScripts() {
		wp_enqueue_script( 'update-api-request', '/wp-content/plugins/wp-custom-updater/api-request.js', array('jquery'), '1.0.0', true );
		wp_localize_script( 'update-api-request', ajax, array( 'url' => admin_url( 'admin-ajax.php' ) ) );
	}

	function set_repo_versions() {
		// first check if data is being sent and that it is the data we want
	  	if ( isset( $_POST["version"] ) ) {
	  		$date = date("j F Y, H:i:s");
			$option = array(
			    'version' => $_POST["version"],
			    'date' => $date
			);

			update_option($_POST["name"], $option);

			echo $date;
			die();
		}
		die('no data provided');
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

	//Creates an admin setting page
	public function create_admin_page()
	{
		add_submenu_page(
			'plugins.php',
			__('Custom Updater'), // title
			__('Update settings'), // menu item
			'publish_posts',
			$this->setting_page,
			array($this, 'admin_token_page')
		);
	}

	// Renders the admin setting page
	function admin_token_page()
	{
		// check if the users capabilities
		if (false === current_user_can('publish_posts')){
			wp_die(__('You do not have sufficient permissions to access this page.') );
		}

		if($_POST[$this->token_setting]){
			update_option($this->token_setting,$_POST[$this->token_setting]);
		}

		if($_POST[$this->repo_setting]){
			update_option($this->repo_setting,$_POST[$this->repo_setting]);
		}

		require_once 'admin_page.php';
	}

	public function create_token_setting()
	{
		add_settings_section(
			$this->setting_section, // section id
			false, // section title
			false, // html output callback
			$this->setting_page // setting page id
		);

		// custom setting
		register_setting(
			$this->option_group, // setting group 
			'updater_repo_group' // option_name
		);


		register_setting(
			$this->option_group, // setting group 
			$this->settings_name // option_name
		);

		add_settings_field(
			'updater_repo_group', // setting name
			'Repo url', // setting title
			array($this, 'repo_option_html'), // html callback
			$this->setting_page, // admin page
			$this->setting_section // section
		);

		add_settings_field(
			$this->settings_name, // setting name
			'GitLab Access token', // setting title
			array($this, 'token_option_html'), // html callback
			$this->setting_page, // admin page
			$this->setting_section // section
		);
	}

	function repo_option_html()
	{
		printf(
			'<input type="text" name="%s" value="%s" placeholder="Access Token" size="50" />',
			$this->repo_setting, get_option($this->repo_setting)
		);
	}

	function token_option_html()
	{
		printf(
			'<input type="text" name="%s" value="%s" placeholder="Access Token" size="50" />',
			$this->token_setting, get_option($this->token_setting)
		);
	}

	// Adds a setting link to the plugin item in the plugin list
	function add_action_links ( $links ) {
		$mylinks = array('<a href="' . admin_url( 'options-general.php?page='.$this->setting_page ) . '">Settings</a>');
		return array_merge( $links, $mylinks );
	}

}




