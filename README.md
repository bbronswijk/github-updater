# Custom Wordpress Updater

This plugin allows WordPress to update plugins and themes directly from gitlab or github.

### Getting Started

Install this plugin in your wordpress admin. 

#### Activating custom updates in your own plugin/theme
Copy the code below and paste it in your custom theme or plugin. 
```
function activate_custom_updates()
{
    if( !class_exists (WP_CustomUpdate) ) return false;

    $slug       = 'theme-directory/style.css';
    $repo        = 'user/repo';

    if ($slug && $repo) {
        new WP_CustomUpdate($slug, $repo, false );
    }
} add_action ('init', 'activate_custom_updates');
```

If you hosting your plugin or theme in a private repository, provide an access token in the update setting page. 

## How does it work?
This plugin checks the version defined in the main file uploaded to the repository. 
It compares this version with the version of your plugin/theme installed on your wordpress website.
If there is an update available an admin notice is shown. 

Unfortunately, when downloading an update from Gitlab or Github the plugin folder gets renamed to 
something like *your-plugin-master-48597252901578*. The Custom Wordpress Updater hooks into the updating process 
and renames the folder to its orignal name.  
