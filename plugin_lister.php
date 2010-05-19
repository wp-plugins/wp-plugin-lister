<?php
/*
Plugin Name: Plugin Lister
Plugin URI: http://wordpress.org/extend/plugins/wp-plugin-lister/
Description: Outputs all of your active plugins via PHP for use in page templates & more.  Just call: &LT;?php wp_list_all_active_plugins(); ?&GT;
Version: 2.0.0
Author: Paul G Petty
Author URI: http://paulgriffinpetty.com/
*/

define("PluginListerVersion", "2.0.0");

$PluginListerOptions = array(
    'title' => '',
    'description' => ''
);

define("PluginListerOptions", serialize($PluginListerOptions));

function wp_list_all_active_plugins() {

    if(!function_exists('get_plugin_data')){

    	function get_plugin_data( $plugin_file, $markup = true, $translate = true ) {
    		// We don't need to write to the file, so just open for reading.
    		$fp = fopen($plugin_file, 'r');
    	
    		// Pull only the first 8kiB of the file in.
    		$plugin_data = fread( $fp, 8192 );
    	
    		// PHP will close file handle, but we are good citizens.
    		fclose($fp);
    	
    		preg_match( '|Plugin Name:(.*)$|mi', $plugin_data, $name );
    		preg_match( '|Plugin URI:(.*)$|mi', $plugin_data, $uri );
    		preg_match( '|Version:(.*)|i', $plugin_data, $version );
    		preg_match( '|Description:(.*)$|mi', $plugin_data, $description );
    		preg_match( '|Author:(.*)$|mi', $plugin_data, $author_name );
    		preg_match( '|Author URI:(.*)$|mi', $plugin_data, $author_uri );
    		preg_match( '|Text Domain:(.*)$|mi', $plugin_data, $text_domain );
    		preg_match( '|Domain Path:(.*)$|mi', $plugin_data, $domain_path );
    	
    		foreach ( array( 'name', 'uri', 'version', 'description', 'author_name', 'author_uri', 'text_domain', 'domain_path' ) as $field ) {
    			if ( !empty( ${$field} ) )
    				${$field} = trim(${$field}[1]);
    			else
    				${$field} = '';
    		}
    	
    		$plugin_data = array(
    					'Name' => $name, 'Title' => $name, 'PluginURI' => $uri, 'Description' => $description,
    					'Author' => $author_name, 'AuthorURI' => $author_uri, 'Version' => $version,
    					'TextDomain' => $text_domain, 'DomainPath' => $domain_path
    					);
    		if ( $markup || $translate )
    			$plugin_data = _get_plugin_data_markup_translate($plugin_data, $markup, $translate);
    		return $plugin_data;
    	}
    	
    }
    
    if(!function_exists('_get_plugin_data_markup_translate')){
	
    	function _get_plugin_data_markup_translate($plugin_data, $markup = true, $translate = true) {
    	
    		//Translate fields
    		if( $translate && ! empty($plugin_data['TextDomain']) ) {
    			if( ! empty( $plugin_data['DomainPath'] ) )
    				load_plugin_textdomain($plugin_data['TextDomain'], dirname($plugin_file). $plugin_data['DomainPath']);
    			else
    				load_plugin_textdomain($plugin_data['TextDomain'], dirname($plugin_file));
    	
    			foreach ( array('Name', 'PluginURI', 'Description', 'Author', 'AuthorURI', 'Version') as $field )
    				$plugin_data[ $field ] = translate($plugin_data[ $field ], $plugin_data['TextDomain']);
    		}
    	
    		//Apply Markup
    		if ( $markup ) {
    			if ( ! empty($plugin_data['PluginURI']) && ! empty($plugin_data['Name']) )
    				$plugin_data['Title'] = '<a href="' . $plugin_data['PluginURI'] . '" title="' . __( 'Visit plugin homepage' ) . '">' . $plugin_data['Name'] . '</a>';
    			else
    				$plugin_data['Title'] = $plugin_data['Name'];
    	
    			if ( ! empty($plugin_data['AuthorURI']) )
    				$plugin_data['Author'] = '<a href="' . $plugin_data['AuthorURI'] . '" title="' . __( 'Visit author homepage' ) . '">' . $plugin_data['Author'] . '</a>';
    	
    			$plugin_data['Description'] = wptexturize( $plugin_data['Description'] );
    			if( ! empty($plugin_data['Author']) )
    				$plugin_data['Description'] .= ' <cite>' . sprintf( __('By %s'), $plugin_data['Author'] ) . '.</cite>';
    		}
    	
    		$plugins_allowedtags = array('a' => array('href' => array(),'title' => array()),'abbr' => array('title' => array()),'acronym' => array('title' => array()),'code' => array(),'em' => array(),'strong' => array());
    	
    		// Sanitize all displayed data
    		$plugin_data['Title']       = wp_kses($plugin_data['Title'], $plugins_allowedtags);
    		$plugin_data['Version']     = wp_kses($plugin_data['Version'], $plugins_allowedtags);
    		$plugin_data['Description'] = wp_kses($plugin_data['Description'], $plugins_allowedtags);
    		$plugin_data['Author']      = wp_kses($plugin_data['Author'], $plugins_allowedtags);
    	
    		return $plugin_data;
    	}

    }
	
	$p = get_option('active_plugins');

    $plugin_list = "";
    
    $PluginListerOptionsStorage = getAdminOptions();
    
    if ($PluginListerOptionsStorage['title'] != "") {
        $plugin_list .= "<h3>".$PluginListerOptionsStorage['title']."</h3>";
    }
    
    if ($PluginListerOptionsStorage['description'] != "") {
        $plugin_list .= "<p>".$PluginListerOptionsStorage['description']."</p>";
    }
    
	$plugin_list .= "<ul>";
	
	foreach ($p as $q) {
	
		$d = get_plugin_data( WP_PLUGIN_DIR."/".$q , false , false );
		
		$plugin_list .= "<li>";
		$plugin_list .= "<h4><a href='".$d['PluginURI']."' target='_new'>".$d['Title']."</a> (Version: ".$d['Version'].")</h4>";
		$plugin_list .= "<p>";
		
		$plugin_list .= $d['Description'];
				
		if ($d['AuthorURI'] != "") { 
		  $plugin_list .= "<br /><em>Created by:</em> <a href='".$d['AuthorURI']."' target='_new'>".$d['Author']."</a>";
		} else {
    		$plugin_list .= "<br /><em>Created by:</em> ".$d['Author'];
		}
		
		$plugin_list .= "</p>";
		$plugin_list .= "</li>";
	
	}
	
    $plugin_list .= "</ul>";
    
    echo $plugin_list;

}

function PluginLister_init() {
		
    $PluginListerOptionsStorage = getAdminOptions();

    if (isset($_POST['title'])) {
        $PluginListerOptionsStorage['title'] = $_POST['title'];
    } 
    
    if (isset($_POST['description'])) {
        $PluginListerOptionsStorage['description'] = $_POST['description'];
    } 
        
    update_option(adminOptionsName, $PluginListerOptionsStorage);
               
    $PluginLister_title       = $PluginListerOptionsStorage['title'];
    $PluginLister_description = $PluginListerOptionsStorage['description'];

    // Admin UI starts
    
        ?>
    
        <style type="text/css">
        
        #PluginLister {
            margin:5px 15px;
        }
        
        #PluginListerDescription,
        #PluginListerForm,
        #PluginListerPreview {
            width:550px;
            margin:5px 0;
            padding:5px;
            background:#fff;
            border:1px solid #eee;
            -moz-border-radius:4px; 
            -webkit-border-radius:4px;
        }
        
        #PluginLister_preview_link {
            margin-left:10px;
        }
    
        #PluginListerPreview {
            display:none;
        }
    
        </style>
        
        <script type="text/javascript" src="http://www.google.com/jsapi"></script>
        <script type="text/javascript"> if (!jQuery) google.load( "jquery","1.3.2" ); </script>
        <script type="text/javascript">
    
        function PluginLister_preview() {
    
            jQuery("#PluginListerPreview").toggle();
            
            var show = "Show Preview";
            var hide = "Hide Preview";
            var labl = jQuery("#PluginLister_preview_link");
            
            (labl.text() == show) ? labl.text( hide ) : labl.text( show );
        
        }
                    
        </script>
    
        <h2>Plugin Lister, version: <?php echo PluginListerVersion; ?></h2>
    
        <div id="PluginLister">
        
            <p id="PluginListerDescription">
            
                This plugin lets you share a list of all the plugins your blog is using.<br />
                <br />
                Currently the only way to get the list to display is to use a PHP execution 
                plugin that allows PHP to work in your posts; or you can add the PHP code 
                below directly to a WordPress Theme template for your site.<br />
                <br />
                <code>&lt;?php wp_list_all_active_plugins(); ?&gt;</code>
                
            </p>
        
            <form id="PluginListerForm" method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>&time=<?php echo time(); ?>">
            
                <?php 
                //wp_nonce_field('PluginLister_nonce'); 
                ?>
            
                <fieldset class="PluginListerOptions">
        
                    <label for="title"><strong>Title</strong> (that appears above list &amp; is optional)</label> <br />
                    <input type="text" name="title" value="<?php echo $PluginLister_title; ?>" /> <br />
                    <br />
                    <br />
                    <label for="description"><strong>Description</strong> (that appears between title and list &amp; is optional)</label> <br />
                    <textarea name="description"><?php echo $PluginLister_description; ?></textarea> <br />

                </fieldset>    
        
                <div class="submit">
                    <input type="submit" value="Update Options" />
                </div>
                
            </form>
            
            <a href="javascript:void(0)" onclick="PluginLister_preview()" id="PluginLister_preview_link">Show Preview</a> of your list.
            
            <div id="PluginListerPreview">
            
                <?php 
                wp_list_all_active_plugins(); 
                ?>
            
            </div>
        
        </div>
    
        <?php
    
    // Admin UI ends

}

function getAdminOptions() {

    $PluginListerOptions = unserialize(PluginListerOptions);
        
    $PluginListerOptionsStorage = get_option(adminOptionsName);
    
    if (!empty($PluginListerOptionsStorage)) {
        foreach ($PluginListerOptionsStorage as $key => $option) {
            $PluginListerOptions[$key] = $option;
        }    
    }  
              
    update_option(adminOptionsName, $PluginListerOptions);
    
    return $PluginListerOptions;
    
} 

function PluginLister_deactivate() {

    $PluginListerOptions = unserialize(PluginListerOptions);
        
    foreach ($PluginListerOptions as $key => $option) {
        delete_option( $PluginListerOptions[$key] );
    }  

}	

function PluginLister_plugin_actions( $links, $file ) {
    $settings_link = '<a href="' . admin_url( 'options-general.php?page=' . "PluginListerCore.php4" ) . '">' . __('Settings') . '</a>';
    array_unshift( $links, $settings_link ); 
    return $links;
}

function PluginLister_ap() {
    if (function_exists('add_options_page')) {
        add_options_page('Plugin Lister', 'Plugin Lister', 9, basename(__FILE__), 'PluginLister_init');
    }
}	

add_action('admin_menu', 'PluginLister_ap');

add_action('wp-plugin-lister/plugin_lister.php', 'PluginLister_init');
    
register_deactivation_hook('wp-plugin-lister/plugin_lister.php', 'PluginLister_deactivate');

add_action('activate_plugin_lister.php', 'wp_list_all_active_plugins');

?>
