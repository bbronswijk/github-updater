<?php


class WP_CustomUpdate extends GithubUpdatePlugin
{
	public $settings_name;

	function __construct ($name, $dir, $file, $url, $raw, $package, $plugin = true )
	{
		$this->is_plugin = $plugin; // plugin == true && theme == false
		$this->name = $name;
		$this->dir = $dir;
		$this->main_file = $file; // style.css or plugin.php
		$this->url = $url;
		$this->raw_file = $raw;
		$this->package = $package;
		$this->slug = $this->dir.'/'.$this->main_file;
		$this->api = "https://gitlab.com/api/v4/projects/bbronswijk%2Ffresh-insights/repository/tags?private_token=uWRE_GPzYvk7wiU6qk54";
		$slug = $this->dir; // used to name the options and settings

		$this->settings_id = 'token_'.$slug;
		$this->settings_name = $slug.'token_setting';
		$this->option_name = 'token_'.$slug;
		$option = get_option($this->option_name);
		
		$this->token = $option['token'];

		// check for updates
		// register the token setting for the hooked theme or plugin
		add_action( 'admin_init', array($this, 'create_token_setting'));
		add_action( 'admin_init', array($this, 'checkUpdates'));
	}

	public function checkUpdates(){
		add_filter ('site_transient_update_themes', array($this,'checkForThemeUpdates'));
		add_filter ('pre_set_site_transient_update_plugins', array($this,'checkForPluginUpdates'));
	}

	public function create_token_setting()
	{
		// custom setting
		register_setting(
			$this->option_group, // setting group --> set in github-updater.php
			$this->option_name // option_name
		);

		add_settings_field(
			$this->settings_name, // setting name
			$this->name, // setting title
			array($this, 'token_option_html'), // html callback
			$this->setting_page, // admin page
			$this->setting_section // section
		);
	}

	function token_option_html()
	{


		printf(
			'<input type="text" name="%s" id="%s" value="%s" placeholder="Access Token" size="50" api="%s"/>',
			$this->option_name, $this->settings_name, $this->token, $this->api
		);
	}

	function checkForPluginUpdates($transient)
	{
		if( false === $this->is_plugin ) return false;

		$last_version = $this->getLastVersion();

		$plugin = get_plugin_data( ABSPATH.'wp-content/plugins/'.$this->slug);

		//wp_die(version_compare ( $last_version, $plugin['Version'],'>' ));

		if (version_compare ( $last_version, $plugin['Version'],'>' )) {

			$obj = new stdClass();
			$obj->slug = $this->slug;
			$obj->new_version = $last_version;
			$obj->plugin = $this->slug;

			if ( !empty($this->token) ) {
				$obj->url = $this->url.'?private_token=' . $this->token;
			} else {
				$obj->url = $this->url; // zip file??
			}

			if ( !empty($this->token) ) {
				$obj->package = $this->package.'?private_token=' . $this->token;
			} else {
				$obj->package = $this->package; // zip file??
			}

			$transient->response[$this->slug] = $obj;
		}
		return $transient;
	}

	function checkForThemeUpdates($updates)
	{
		if ( true === $this->is_plugin ) return false;

		$last_version = $this->getLastVersion();

		$theme = wp_get_theme($this->dir, WP_CONTENT_DIR . '/themes');
		$cur_version = $theme->get( 'Version' );

		if (version_compare ( $last_version, $cur_version ,'>' )){
			$update = array(
				'new_version' => $last_version ,
				'url' => $this->url,
				'package' => $this->package
			);


			$updates->response[$this->dir] = $update;
		}

		return $updates;
	}


	function getLastVersion()
	{
		if ( !empty($this->token) ) {
			if (file_exists($this->raw_file.'?private_token=' . $this->token)) {
				$handle = fopen($this->raw_file.'?private_token=' . $this->token, "r");	
			}			
		} else {
			if (file_exists($this->raw_file)) {
				$handle = fopen($this->raw_file, "r");
			}
		}

		if ($handle) {
			while (($line = fgets($handle)) !== false) {
				if( stripos($line, 'version') !== FALSE ){
					$words = $parts = explode (':', $line);
					$version = trim($words[1]);
					fclose($handle);

					$check = preg_match("/^(?:(\d+)\.)?(?:(\d+)\.)?(\*|\d+)$/", $version);

					if( $check ){
						return $version;
					} else{
						return 'Error: No AccessToken provided';
					}
				}
			}
			fclose($handle);
			return 'Error: No version provided';
		} else {
			return 'Error: file not found';
		}
	}



}


