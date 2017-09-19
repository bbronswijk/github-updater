Deze plugin maakt het mogelijk om wordpress plugins direct vanaf github of gitlab te updaten naar wordpress

function activate_updates()
	{
		if( !class_exists (WP_CustomUpdate) ) return false;

		$name       = 'Banner promo button';
		$dir        = 'banner-promo-button';
		$file       = 'banner-promo-button.php';
		$url        = 'https://techniek-team.githost.io/label-sites/banner-promo-button';
		$raw        = 'https://techniek-team.githost.io/label-sites/banner-promo-button/raw/master/banner-promo-button.php';
		$package    = 'https://techniek-team.githost.io/label-sites/banner-promo-button/repository/master/archive.zip';

		// only perform Auto-Update call if a license_user and license_key is given
		if ( $name && $dir  && $file && $url && $raw && $package ) {
			new WP_CustomUpdate($name, $dir, $file, $url, $raw, $package, true );
		}
	}
