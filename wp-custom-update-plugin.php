<?php
/*
 Plugin Name: Custom Wordpress Updater
 Plugin URI: https://github.com/bbronswijk/github-updater
 Description: This plugin allows WordPress to update plugins and themes directly from gitlab or github.
 Author: B. Bronswijk
 Version: 2.2
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
	public $menu_page;

	function __construct()
	{
		add_action ('upgrader_process_complete', array($this, 'rename_plugin_dir'), 10, 2);
		add_action ('upgrader_process_complete', array($this, 'rename_theme_dir'), 10, 2);

		add_action ('admin_menu', array($this, 'create_admin_page'));
		add_action( 'admin_init', array($this, 'create_token_setting'));

		add_filter ('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));

		add_action ('wp_ajax_set_repo_versions', array($this,'set_repo_versions'));
		add_action ('wp_ajax_set_save_token', array($this,'save_token'));
	}

	function loadScripts() {
		wp_enqueue_script ('update-api-request', '/wp-content/plugins/wp-custom-update/api-request.js', array('jquery'), '1.0.0', true);
		wp_localize_script ('update-api-request', ajax, array('url' => admin_url('admin-ajax.php')));
	}



	//Creates an admin setting page
	public function create_admin_page()
	{
		$menu_page = add_submenu_page(
			'plugins.php', // parent menu item
			__('Custom Updater'), // title
			__('Update settings'), // menu item
			'publish_posts', // capabilities
			$this->setting_page,
			array($this, 'admin_token_page')
		);

		// only on admin page
		add_action ('load-'.$menu_page, array($this,'loadScripts'));
	}

	// Renders the admin setting page and save the form
	function admin_token_page()
	{
		// check if the users capabilities
		if (false === current_user_can('publish_posts')){
			wp_die(__('You do not have sufficient permissions to access this page.') );
		}

		// if the form is submitted save the repo base url
		if($_POST[$this->repo_setting]){
			update_option($this->repo_setting,$_POST[$this->repo_setting]);
		}

		// if the form is submitted save the accesstoken
		if($_POST[$this->token_setting]){
			update_option($this->token_setting,$_POST[$this->token_setting]);
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

		// custom option group
		register_setting(
			$this->option_group, // setting group 
			$this->repo_setting // option_name
		);

		register_setting(
			$this->option_group, // setting group 
			$this->token_setting // option_name
		);

		add_settings_field(
			$this->repo_setting, // setting name
			'Repository base url', // setting title
			array($this, 'repo_option_html'), // html callback
			$this->setting_page, // admin page
			$this->setting_section // section
		);

		add_settings_field(
			$this->token_setting, // setting name
			'GitLab Access token', // setting title
			array($this, 'token_option_html'), // html callback
			$this->setting_page, // admin page
			$this->setting_section // section
		);
	}


	// output the input field for the repository base url
	function repo_option_html()
	{
		printf(
			'<input type="text" name="%s" value="%s" placeholder="Repo Base Url" size="50" />',
			$this->repo_setting, get_option($this->repo_setting)
		);
	}

	// outputs the input field for the access token field
	function token_option_html()
	{
		printf(
			'<input type="text" name="%s" value="%s" placeholder="Access Token" size="50" />',
			$this->token_setting, get_option($this->token_setting)
		);
	}

	// stores the latest versions in a wordpress option
	// called by js
	// returns the current date
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


	// Renames the plugin directory
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

	// Renames the theme directory after the update
	// Updates the right options
	function rename_theme_dir($upgrader_object, $data )
	{
		// get the data of the updated plugins
		$updated_themes = $data['themes'];

		if( !empty($updated_themes) ) {

			foreach ($updated_themes as $path) {

				$path_parts      = explode('/', $path);
				$theme_directory = $path_parts[0]; // prezzence

				// loop through plugin directories and look for the current updated plugin folder
				$dirs = glob(ABSPATH . 'wp-content/themes/*');
				foreach ( $dirs as $dir ) {
					// check if this is the folder we need
					if (is_dir( $dir ) && false !== strpos( $dir, '-master' ) && strpos( $dir, $theme_directory )) {

						//explode the directory path
						$parts = explode( '-master', $dir );

						// rename the directory and use only the part before the -master part
						rename( $dir, $parts[0] );

						$my_theme = wp_get_theme( $theme_directory );

						$cur_theme = explode( 'wp-content/themes/', $dir ); // prezzence-master-sdjklgjh

						// if is child theeme just update stylesheet and not template
						if ( !empty( $my_theme->get( Template ) ) ){
							update_option('stylesheet',$updated_themes[0] );
						} else if( $cur_theme[1]  === get_option('stylesheet')  ){
							update_option('template',$updated_themes[0] );
						} 				
						
					}
				}
			}
		}
	}


	// Adds a setting link to the plugin item in the plugin list
	function add_action_links ( $links ) {
		$mylinks = array('<a href="' . admin_url( 'options-general.php?page='.$this->setting_page ) . '">Settings</a>');
		return array_merge( $links, $mylinks );
	}


}




