<?php


class WP_CustomPluginUpdate extends GithubUpdatePlugin
{

	private $current_version;
	private $update_path;
	private $plugin_slug;
	private $access_token;
	public $settings_name;

	function __construct ($name, $dir, $file, $url, $raw, $package)
	{
		$this->plugin_name = $name;
		$this->plugin_dir = $dir;
		$this->plugin_file = $file;
		$this->plugin_url = $url;
		$this->raw_plugin_file = $raw;
		$this->plugin_package = $package;

		$this->plugin_slug = $this->plugin_dir; // used to name the options and settings

		$this->settings_id = 'token_'.$this->plugin_slug;
		$this->settings_name = $this->plugin_slug.'token_setting';
		$this->option_name = 'token_'.$this->plugin_slug;
		$this->token = get_option($this->option_name);

		add_filter ('pre_set_site_transient_update_plugins', array($this,'checkForUpdate') );
		add_action( 'admin_init', array( $this, 'create_api_setting' ) ); // register the token setting for the hooked plugin

	}

	public function create_api_setting()
	{
		// custom setting
		register_setting(
			$this->option_group, // setting group --> set in github-updater.php
			$this->option_name // option_name
		);

		add_settings_field(
			$this->settings_name, // setting name
			'Access Token '.$this->plugin_name, // setting title
			array($this, 'plugin_options_page_html'), // html callback
			$this->setting_page, // admin page
			$this->setting_section // section
		);
	}

	function plugin_options_page_html()
	{
		printf(
			'<input type="text" name="%s" id="%s" value="%s" />',
			$this->option_name, $this->settings_name, $this->token
		);
	}


	function checkForUpdate()
	{
		$last_version = $this->getPluginVersionGithub();

		$plugin = get_plugin_data( ABSPATH.$this->plugin_dir.'/'.$this->plugin_file);

		if ($plugin['Version'] !== $last_version ){
			$obj = new stdClass();
			$obj->slug = $this->plugin_file;
			$obj->new_version = $last_version; /// github value which should be higher
			$obj->package = $this->plugin_package; // zip file??

			if ( !empty($this->option) ) {
				$obj->url = $this->plugin_url.'?private_token=' . $this->token; // ?private_token=gV1y2bG9nfS_6zz9drQy
			} else {
				$obj->url = $this->plugin_url;
			}

			$transient->response[plugin_basename( __FILE__ )] = $obj;
		}
	}


	function getPluginVersionGithub()
	{
		$handle = fopen($this->raw_plugin_file, "r");

		if ($handle) {
			while (($line = fgets($handle)) !== false) {
				if( stripos($line, 'version') !== FALSE ){
					$words = $parts = explode (':', $line);
					$version = trim($words[1]);
					fclose($handle);
					return $version;
				}
			}
			fclose($handle);
			return 'Error: No version provided';
		} else {
			return 'Error: file not found';
		}
	}
}

