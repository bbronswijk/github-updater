
<div class="wrap" id="<?= $this->setting_page; ?>-page">
	<h1>Accesstokens repositories</h1>
	<p>		
		<a href="#" id="getRepoVersions" class="button">Check updates</a>
		<span class="spinner" style="visibility: hidden; float: none;"></span>
		<span id="last-updated"></span>
	</p>
	<hr>
	<form method="post" action="options.php">
		<?php settings_fields($this->option_group); ?>
		<?php do_settings_sections( $this->setting_page ); ?>
		<?php submit_button(); ?>
	</form>
</div>

