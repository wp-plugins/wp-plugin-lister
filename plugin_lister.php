<?php
/*
Plugin Name: Plugin Lister
Plugin URI: http://wordpress.org/extend/plugins/plugin-lister/
Description: Outputs all of your active plugins via PHP for use in page templates & more.  Just call: &LT;?php wp_list_all_active_plugins(); ?&GT;
Version: 1.0
Author: Paul G Petty
Author URI: http://paulgriffinpetty.com/
*/

	function wp_list_all_active_plugins() {
	
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
		
		$p = get_option('active_plugins');
		
		echo "<ul>";
		
		foreach ($p as $q) {
		
			$d = get_plugin_data( WP_PLUGIN_DIR."/".$q , false , false );
			
			echo "<li>";
			echo "<h4><a href='".$d['PluginURI']."' target='_new'>".$d['Title']."</a> (Version: ".$d['Version'].")</h4>";
			echo "<p>";
			
			echo $d['Description'];
					
			if ($d['AuthorURI'] != "") { 
				echo "<br /><em>Created by:</em> <a href='".$d['AuthorURI']."' target='_new'>".$d['Author']."</a>";
			} else {
				echo "<br /><em>Created by:</em> ".$d['Author'];
			}
			
			echo "</p>";
			echo "</li>";
		
		}
		
		echo "</ul>";
	
	}

	add_action('activate_plugin_lister.php', 'wp_list_all_active_plugins');

?>
