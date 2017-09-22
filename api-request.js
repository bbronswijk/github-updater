jQuery(document).ready(function($){

	if ( 0 === $('.wrap#custom_updater_settings-page').length){
	    return false;
    }

	// define elements
    var $input = $('.wrap#custom_updater_settings-page').find('input.plugin-version');
	var $last_update = $('span#last-updated');
    var $loader = $('.spinner');

	// trigger events
    $('#getRepoVersions').on('click',getRepoVersions);

	function getRepoVersions()
	{
		$loader.css('visibility','visible');

		$.each($input,function(i){
			// get url from plugin
			var url = $input.eq(i).val();
			var option_name = $input.eq(i).attr('name');
			var $version_container = $('span#'+option_name);

			// check for each plugin the online version
			var settings = {
			  'async': true,
			  'crossDomain': true,
			  'url': url,
			  'method': 'GET'
			}

			// get the version from the github api
			$.ajax(settings).done(function (response) {
				var version = response[0].name;
                var $version_info = 'v. '+version;

                $version_container.text($version_info);

                saveSetting(i, option_name, version);

			}).error(function(){
                $version_container.text('api request denied');
                $loader.css('visibility','hidden');
			});

		});
	}

	// saves the values as an array in a WordPress option
    function saveSetting(index,option_name,version)
    {
        var data = {
            action 	: 'set_repo_versions',
            version : version,
            name 	: option_name
        };

        // store the data in the WordPress option
        $.post(ajax.url, data, function(response) {
            if (index === $input.length - 1){
                $loader.css('visibility','hidden');
                $last_update.text('Laatste controle op '+response);
            }
        });
    }


});