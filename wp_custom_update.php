<?php


class WP_CustomUpdate extends GithubUpdatePlugin
{
	public $settings_name;

	function __construct ($slug, $repo, $plugin = true )
	{
		$this->slug = $slug; // plugin-directory/plugin.php
		$slug_parts = explode("/",$this->slug);
		$slug = $slug_parts[0];

		$this->name = ucfirst(str_replace('-',' ',$slug));
		$this->dir =  $slug_parts[0]; // used to name the options and settings
		$this->file = $slug_parts[1];
		// name option to store version
		$this->option_name = $this->dir.'-version';

		$this->host = get_option($this->repo_setting);
		$this->url = $this->host . $repo;
		$this->package =  $this->url.'/repository/master/archive.zip';
		$this->is_plugin = $plugin; // plugin == true && theme == false

		$this->api = $this->host.'api/v4/projects/'.str_replace('/','%2F',$repo).'/repository/tags';
		$this->token = get_option($this->token_setting); // set in parent

		if (false === empty($this->token)) {
			$this->api .= '?private_token='.$this->token;
		}
		// register the token setting for the hooked theme or plugin
		add_action( 'admin_init', array($this, 'checkUpdates'));
		add_action( 'admin_init', array($this, 'create_token_setting'));


	}

	public function checkUpdates(){
		add_filter ('pre_set_site_transient_update_plugins', array($this,'checkForPluginUpdates'));
		add_filter ('pre_set_site_transient_update_themes', array($this,'checkForThemeUpdates'));
	}

	public function create_token_setting()
	{
		// custom setting
		register_setting(
			$this->option_group, // setting group --> set in github-updater.php
			$this->option_name // option_name
		);

		add_settings_field(
			$this->option_name, // setting name
			$this->name, // setting title
			array($this, 'token_option_html'), // html callback
			$this->setting_page, // admin page
			$this->setting_section // section
		);
	}

	function token_option_html()
	{
		printf(
			'<input name="%s" class="plugin-version" type="hidden" value="%s"/><span id="%s">v. %s</span>',
			$this->option_name,
			$this->api,
			$this->option_name,
			$this->getLastVersion()
		);
	}

	function checkForPluginUpdates($transient)
	{
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		if( false === $this->is_plugin ) return $transient;
		$last_version = $this->getLastVersion();
		$plugin = get_plugin_data( ABSPATH.'wp-content/plugins/'.$this->slug);

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
		if ( true === $this->is_plugin ) return $updates;

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
		$option = get_option($this->option_name);
		// create a new option if no token it not exits
		if (false === is_array($option)) {
			return null;
		} else{
			return $option['version'];
		}
	}



}


