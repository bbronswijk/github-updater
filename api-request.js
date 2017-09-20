jQuery(document).ready(function($){

	if( $('.wrap#custom_updater_settings-page').length === 0 ) return false;
		
	$('#getRepoVersions').on('click',getRepoVersions);

	$loader = $('.spinner');

	$input = $('.wrap#custom_updater_settings-page').find('input[type="text"]');

	getRepoVersions();

	function getRepoVersions(){
		$loader.css('visibility','visible');

		$.each($input,function(i){

			// get url from plugin
			var url = $input.eq(i).attr('api');
			var option_name = $input.eq(i).attr('name');
			var token = $input.eq(i).val();

			// check for each plugin the online version
			var settings = {
			  "async": true,
			  "crossDomain": true,
			  "url": url,
			  "method": "GET",
			  "headers": {
			    "x-api-key": "a0B1c2D34D5c6b7a8",
			    "accept": "application/hal+json"
			  }
			}

			$.ajax(settings).done(function (response) {

				var data = {
					action 	: 'set_repo_versions',
				    version : response[0].name,
				    token 	: token,
				    name 	: option_name
				};

				// the_ajax_script.ajaxurl is a variable that will contain the url to the ajax processing file
				$.post(ajax.url, data, function(response) {
					if( i === $input.length - 1 ){
						$loader.css('visibility','hidden');
						$('span#last-updated').text(response);
					} 
				});
			});

		});
	}

	

	

	

});