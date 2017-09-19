<?php


class WP_CustomPluginUpdate extends GithubUpdatePlugin
{
	private $plugin_slug;
	public $settings_name;

	function __construct ($name, $dir, $file, $url, $raw, $package)
	{
		$this->plugin_name = $name;
		$this->plugin_dir = $dir;
		$this->plugin_file = $file;
		$this->plugin_url = $url;
		$this->raw_plugin_file = $raw;
		$this->plugin_package = $package;
		$this->plugin_slug = $this->plugin_dir.'/'.$this->plugin_file;
		$slug = $this->plugin_dir; // used to name the options and settings

		$this->settings_id = 'token_'.$slug;
		$this->settings_name = $slug.'token_setting';
		$this->option_name = 'token_'.$slug;
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
			$this->plugin_name, // setting title
			array($this, 'plugin_options_page_html'), // html callback
			$this->setting_page, // admin page
			$this->setting_section // section
		);
	}

	function plugin_options_page_html()
	{
		printf(
			'<input type="text" name="%s" id="%s" value="%s" placeholder="Access Token" size="50"/>',
			$this->option_name, $this->settings_name, $this->token
		);
	}


	function checkForUpdate()
	{
		$last_version = $this->getPluginVersionGithub();
		$plugin = get_plugin_data( ABSPATH.'wp-content/plugins/'.$this->plugin_slug);

		if ($plugin['Version'] !== $last_version ) {

			$obj = new stdClass();
			$obj->slug = $this->plugin_slug;
			$obj->new_version = $last_version;
			$obj->plugin = $this->plugin_slug;


			if ( !empty($this->token) ) {
				$obj->url = $this->plugin_url.'?private_token=' . $this->token;
			} else {
				$obj->url = $this->plugin_url; // zip file??
			}

			if ( !empty($this->token) ) {
				$obj->package = $this->plugin_package.'?private_token=' . $this->token;
			} else {
				$obj->package = $this->plugin_package; // zip file??
			}

			$transient->response[$this->plugin_dir.'/'.$this->plugin_file] = $obj;
		}
		return $transient;
	}


	function getPluginVersionGithub()
	{
		if ( !empty($this->token) ) {
			$handle = fopen($this->raw_plugin_file.'?private_token=' . $this->token, "r");
		} else {
			$handle = fopen($this->raw_plugin_file, "r");
		}

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

