
<div class="wrap">
	<h1>My Settings</h1>
	<form method="post" action="options.php">
		<?php settings_fields($this->option_group); ?>
		<?php do_settings_sections( $this->setting_page ); ?>
		<?php submit_button(); ?>
	</form>
</div>

