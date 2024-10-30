<?php
/* 
Plugin Name: Manage Admin Menus
Description: Tired of searching for an admin menu manager? This plugin will display a list of ALL active menus and allow Super Admin to select which ones should show depending on user capability. Super admin sees all menus, Administrators and lower dont.
Author: Laurence Tuck
Version: 0.1
Author URI: http://www.innate.co.za

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 
*/

/// Manage Admin Menus
class manage_admin_menus {
	
		/****************************************************************************************************************************************************
		 * setup() 
		 * setup plugin 
		 * 
		 ****************************************************************************************************************************************************/
		
		// setup plugin - add options
		function setup(){
			// db and version	
			global $wpdb, $wp_version;	    
		    //if this is less than wp 3.0, just get out of here.
			if ( version_compare( $wp_version, '3.0', '<' ) ) {
				return;		
			}	
			
			// defaults
			/* TODO: add defaults at some point */
			$saved_checked_menus_administrator = '';
			$saved_checked_menus_editor = '';
			$saved_checked_menus_author = '';
			$saved_checked_menus_contributor = '';
			$saved_checked_menus_subscriber = '';
			
			// network options
			add_site_option( 'saved_checked_network_settings', 1 );
				
			// Add a site options 
			add_option( 'saved_checked_menus_setup', 1 );			
			add_option( 'saved_checked_menus_administrator', $saved_checked_menus_administrator);
			add_option( 'saved_checked_menus_editor', $saved_checked_menus_editor);
			add_option( 'saved_checked_menus_author', $saved_checked_menus_author);
			add_option( 'saved_checked_menus_contributor', $saved_checked_menus_contributor);
			add_option( 'saved_checked_menus_subscriber', $saved_checked_menus_subscriber);	
		}
			
		
		/****************************************************************************************************************************************************
		 * manage_admin_menus_page()
		 * manage_admin_menus_network_page()
		 * management page
		 * network management page
		 * 
		 ****************************************************************************************************************************************************/
		
		// management page
		function manage_admin_menus_page() {
		
			// set current role edit - defaults to Administrator
			global $wp_roles, $blog_id; 
		    $roles = $wp_roles->roles;
			$current_role_selection = (isset($_POST['current_role_selection'])) ? $_POST['current_role_selection'] : 'administrator';		
			
			// show donate button
			$this->MAM_donate();
			
			// display main admin option page 
			?>
			<h2>Manage Admin Menus</h2>
			<hr size="1" />
			<? /// process form submission
			if( isset($_POST['manage_admin_menus']) && isset($_POST['action']) && $_POST['action'] == 'update' ) {          
			    
				// save options			    
				$this->update_checked_menus($_POST, $current_role_selection);
				$saved = "Options saved for ".ucwords($current_role_selection);
				
			} elseif(isset($_POST['manage_admin_menus_users']) && isset($_POST['action']) && $_POST['action'] == 'update_capabilities' ) {
					
				// show message		
				$saved = "Role changed to ".ucwords($current_role_selection);
				
			} ?>			
			<form action="" method="post" id="user_management">
				<select name="current_role_selection">
					<? // show selected role
					 foreach($roles as $role){
					 	$role_name = strtolower($role['name']);
					 	$selected = ($current_role_selection === $role_name) ? 'selected=' : '';
					 	echo '<option value="'.$role_name.'" '.$selected.'>'.$role['name'].'</option>';
					 }; ?>
				</select>
				<input type="hidden" name="action" value="update_capabilities" />
				<input type="submit" name="manage_admin_menus_users" value="Change Role" class="button-primary"/>
			</form>
			
			<br clear="all" />
			
			<? if(isset($saved)){ ?>
				<div class="updated">
			        <p><?php _e( $saved ); ?></p>
			    </div>
			<? } ?>
			
			<div id="options_form">
				<h3>You are now editing <?=ucwords($current_role_selection);?></h3>
				<h3><label><input type="checkbox" id="select_all"/>Select All</label></h3>		
				<form action="" method="post" id="options_management">
					<input type="hidden" name="current_site_id" value="<?=$blog_id;?>" />
					<input type="hidden" name="current_role_selection" value="<?=$current_role_selection;?>" /> 
					<? // loop through active options				
						
						// get required
						global $menu, $submenu;
						
						// set array
						$sub_menu_array = array();
						
						// get options
						$MAM_user_role = $current_role_selection;						
						$saved_checked_menus = $this->return_current_selection_options($MAM_user_role);
						
						// may help a bit
						ob_start();
						
						//////////////////// Submenus - get the submenus first so we can place them correctly
						
						$MAM_submenu = $submenu;
						//print_r($MAM_submenu);
						
						// menu exists
						if($MAM_submenu){
							
							// loop through menu
							foreach ($MAM_submenu as $MAM_skey => $MAM_svalue) {
								
								// loop through values				    	
						    	foreach( $MAM_svalue as $MAM_sub){
						    		
									// if slug exists				    	  			    	
							        if( isset($MAM_sub[0]) ){					        
										 
										 // create our own slug and title and get the file name for the parent
										 $menu_item_file = str_replace('.php','',$MAM_skey);
										 $menu_item_slug = strtolower( str_replace( array('-',' ','/','?','=') , '_', $MAM_sub[0].'_'.$menu_item_file) );
										 $menu_item_title = preg_replace('/[0-9]+/', '', $MAM_sub[0]);
										 
										 
										 // is this option checked?								 
										 $checked = (@array_key_exists($menu_item_slug, $saved_checked_menus)) ? "checked" : "";
										 
										 // create the checkbox
										 $output = '<div class="menu_option"><input class="submenu" type="checkbox" name="'.$menu_item_slug.'" '.$checked.' /> '.$menu_item_title.'</label></div>';
										 
										 // push the new output into our sub menu array
										 $sub_menu_array[] = array('page' => $MAM_skey, 'output' => $output);
										 
									 }
								}
						    }
						}
					    
					    
					    /////////////////// Menus
					    
					    $MAM_Menu = $menu;
						//print_r($MAM_Menu);		
					    
						// menu exists
					    if($MAM_Menu){
					    		
							// loop through menu	
					    	foreach ($MAM_Menu as $MAM_key => $MAM_value) {
					    		
								// slug exists				    			    	
						        if( isset($MAM_value[5]) ){					        
									 //print_r($MAM_value);
									 
									 // create our own slug and title and get the file name for the parent
									 $menu_item_slug = strtolower( str_replace( array('-',' ','/','?','=') , '_', $MAM_value[5]) ); // replace unwanted characters
									 $menu_item_title = preg_replace('/[0-9]+/', '', $MAM_value[0]);
									 $menu_item_file = $MAM_value[2];
									 
									 // is this option checked?							 
									 $checked = (@array_key_exists($menu_item_slug, $saved_checked_menus)) ? "checked" : "";
									 
									 
									 // create and output the checkbox
									 echo '<div class="menu_group"><input type="checkbox" class="parentmenu" name="'.$menu_item_slug.'" '.$checked.' /> <strong>'.$menu_item_title.'</strong>';
									 
									 // submenu array exists
									 if($sub_menu_array){
									 	
										echo '<div class="submenuboxes">';
										 	
									 	// each main menu item - loop through subs
										 foreach($sub_menu_array as $submenu){
										 		
											// check if this item nests under the parent								 	
										 	if( isset($submenu['page']) && $submenu['page'] == $menu_item_file ){
										 			// output menu item
													
										 		echo $submenu['output'];
										 	};
										 }
										 
										 echo '</div>';
									 }
									 
									 echo "</div>
									 ";								 
									 
								 }
						    }
					    }				    
						
						$all_checkboxes = ob_get_clean();
						echo $all_checkboxes;
						
					?>
					<input type="hidden" name="action" value="update" />
					<br clear="all" />
					<input type="submit" name="manage_admin_menus" value="Save changes" class="button-primary"/>
				</form>			     
				<br clear="all" />
			</div>
			<? 
		}

		// network management page
		function manage_admin_menus_network_page(){
			
			
			// save network settings
			if( isset($_POST['manage_admin_menus_network']) && isset($_POST['action']) && $_POST['action'] == 'update_network_settings' ) {          
			    
				// save options
				$this->update_network_settings($_POST);
				$saved = "Network options saved!";
				//print_r($_POST);
							
			}

			// get saved option ?
			$saved_network_settings = $this->is_network_forced();				
			$network_checked = ( isset($saved_network_settings) && $saved_network_settings == 1) ? 'checked="checked"' : ""; 
			
			// show donate button
			$this->MAM_donate(); ?>
			
			<h2>Manage Admin Menus - Network Settings</h2>
			<hr size="1" />
			<? if(isset($saved)){ ?>
				<div class="updated">
			        <p><?php _e( $saved ); ?></p>
			    </div>
			<? } ?>
			<form action="" method="post" id="network_management">
				<p>Use this option to force the same settings on the entire network.</p>
				<ul>
					<li>- The settings pages per site will be disabled if this option is ticked.</li>
					<li>- The menu settings from your main site will be used.</li>
					<li>- Please edit the settings on the main site.</li>					
				</ul>
				<p><label><input type="checkbox" name="force_settings" <?=$network_checked;?> /> Use Network Settings on all sites?</label></p>
				<input type="hidden" name="action" value="update_network_settings" />
				<input type="submit" name="manage_admin_menus_network" value="Save Settings" class="button-primary"/>
			</form>			
			<?
		}
		
		function update_network_settings( $args ){
			// args exists
			if( $args ) {
				if(isset($args['force_settings'])){
					$saved_network_settings = update_site_option('saved_checked_network_settings', 1);
				} else {
					delete_site_option('saved_checked_network_settings');
				}
			} 
		}
		
		// create function - returns 1 = forced | 0 - per site settings
		function is_network_forced(){
			$nf = get_site_option('saved_checked_network_settings');
			$nfis = (isset($nf) && $nf == 1) ? 1 : 0; 
			return $nfis;
		}
		
		function is_network_bool(){
			// lets check if network settings are forced	
			$is_forced = $this->is_network_forced();
			if( isset($is_forced) && $is_forced === 0 ){
				// save to each site
				$forced = false;
			} else {
				// save to network
				$forced = true;
			}
			return $forced;
		}
		
			
		/****************************************************************************************************************************************************
		 * update_checked_menus()
		 * update check selections
		 * 
		 ****************************************************************************************************************************************************/
		
		// update check selections
		function update_checked_menus( $args, $role ){
			
			// set array	
			$MAM_saved_checked_menus = array();	
			
			// args exists
			if( $args ) {
				
				$current_site_id = $args['current_site_id'];
				
				// loop through args
				foreach($args as $key => $value){
					
					// not unused form fields
					if( !in_array( $key , array('action','manage_admin_menus','current_site_id','current_role_selection') ) ) {
							
						// push item							
						$MAM_saved_checked_menus[$key] = $value;
					}				
				}
			}		
			
			// override the site option			
			$saved_checked_menus = $this->save_current_selection_options($MAM_saved_checked_menus, $role);
			
			// return new items
			return $saved_checked_menus;
		}
		
		
		/****************************************************************************************************************************************************
		 * add_manage_menu_admin_page() 
		 * add network menu page
		 * 
		 ****************************************************************************************************************************************************/
	 
		// add network menu page
		function add_manage_menu_admin_network_page(){
			add_submenu_page('settings.php', 'Manage Admin Menus', 'Manage Admin Menus', 'manage_sites', 'manage_admin_menus_page', array(&$this, 'manage_admin_menus_network_page'));
		}
		
		function add_manage_menu_admin_page() {
		    add_options_page('Manage Admin Menus', 'Manage Admin Menus', 'manage_network', 'manage_admin_menus_page', array(&$this, 'manage_admin_menus_page'));
		}

		
		/****************************************************************************************************************************************************
		 * manage_admin_menus_kick() 
		 * remove menus that don't exist in selection
		 * 
		 ****************************************************************************************************************************************************/
	 
		// remove menus that dont exist in selection
		function manage_admin_menus_kick() {
			
			// everyone but super admin
			if( !current_user_can('manage_network') ) {
					
			    // get users role, why isnt there a built in function for this?
				$role = $this->MAM_get_user_role();
					
				// remove menu options
				global $menu, $blog_id;
				
				// get saved options
				$saved_checked_menus = $this->return_current_selection_options($role);
				//print_r($saved_checked_menus);	
				
				// menu 		
				$MAM_Menu = $menu;				
				//print_r($MAM_Menu);
				
				// menu exists
				if($MAM_Menu){
						
					// loop and cut	
					foreach ($MAM_Menu as $MAM_key => $MAM_value) {
					    
						// slug exists					    	
					    if(isset($MAM_value[5])){
					    	 // menu slug	
							 $menu_item_slug = strtolower( str_replace( array('-',' ','/','?','=') , '_', $MAM_value[5]) );						 
							 
							 
							 // check if the item is in the options
							 if (@array_key_exists($menu_item_slug, $saved_checked_menus)) {
							 	continue;
							 }	
							 // remove item from menu						
							 unset($menu[$MAM_key]);
						 }
					}
				}	
				
				
				// submenu
				global $submenu;
				$MAM_submenu = $submenu;
				//print_r($saved_checked_menus);
				//print_r($MAM_submenu);
				
				
				if($MAM_submenu){
					
					// loop and cut	    
					foreach ($MAM_submenu as $MAM_skey => $MAM_svalue) {
					    	
					    // loop again for subs	
					    foreach( $MAM_svalue as $MAM_sub){
					    	//print_r($MAM_svalue);					    		
					    	// each sub  			    	
					        if( isset($MAM_sub[0]) ){
								 						        
								 // menu slug
								 $smenu_item_file = str_replace('.php','',$MAM_skey);
								 $smenu_item_slug = strtolower( str_replace( array('-',' ','/','?','=') , '_', $MAM_sub[0].'_'.$smenu_item_file) );	
								 $smenu_rm = $MAM_sub[2];							 						 
								 
								 // check if the item is in the options
								 if (@array_key_exists($smenu_item_slug, $saved_checked_menus)) {
								 	//echo "<p>".$smenu_rm." - ".$smenu_item_slug." - ".$MAM_skey."</p>";							 	
								 	continue;								 	
								 }	
								 
								 // remove item from menu
								 remove_submenu_page($MAM_skey, $smenu_rm);									 
								 //if( strpos($_SERVER['REQUEST_URI'], $smenu_rm) ) { $this->manage_menu_admin_denied(); exit(); }						 

							}
						}
					}	
				}
				
				//print_r($submenu);				
				
			}
		}

		
		/****************************************************************************************************************************************************
		 * other necessary functions 
		 * save options, get options, deny page access, add scripts to admin page
		 * 
		 ****************************************************************************************************************************************************/
		 
		// get current users role
		function MAM_get_user_role() {
			global $current_user;		
			$user_roles = $current_user->roles;
			$user_role = array_shift($user_roles);		
			return $user_role;
		}
		
		// save current selection options based on capability
		function save_current_selection_options($saved_checked_menus, $role){						
			if(isset($saved_checked_menus) && isset($role)){
				$forced = $this->is_network_bool();
				if($forced) {
					switch($role){							
						case "administrator" : 
							// get saved options						
							$saved_checked_menus = update_site_option('saved_checked_menus_administrator', $saved_checked_menus);
							break;
						case "editor" :
							// get saved options
							$saved_checked_menus = update_site_option('saved_checked_menus_editor', $saved_checked_menus);
							break;
						case "author" :
							// get saved options
							$saved_checked_menus = update_site_option('saved_checked_menus_author', $saved_checked_menus);
							break;
						case "contributor" :
							// get saved options
							$saved_checked_menus = update_site_option('saved_checked_menus_contributor', $saved_checked_menus);
							break;
						case "subscriber" :
							// get saved options
							$saved_checked_menus = update_site_option('saved_checked_menus_subscriber', $saved_checked_menus);
							break;				
					}	
				} else {
					switch($role){							
						case "administrator" : 
							// get saved options						
							$saved_checked_menus = update_option('saved_checked_menus_administrator', $saved_checked_menus);
							break;
						case "editor" :
							// get saved options
							$saved_checked_menus = update_option('saved_checked_menus_editor', $saved_checked_menus);
							break;
						case "author" :
							// get saved options
							$saved_checked_menus = update_option('saved_checked_menus_author', $saved_checked_menus);
							break;
						case "contributor" :
							// get saved options
							$saved_checked_menus = update_option('saved_checked_menus_contributor', $saved_checked_menus);
							break;
						case "subscriber" :
							// get saved options
							$saved_checked_menus = update_option('saved_checked_menus_subscriber', $saved_checked_menus);
							break;				
					}
				}				
				return $saved_checked_menus;
			}
			return false;			
		}

		// return current selected options to edit
		// returns option
		function return_current_selection_options($role){
			/* TODO : $current_site_id */
			if(isset($role)){				
				switch($role){							
					case "administrator" : 
						// get saved options
						$saved_checked_menus = get_option('saved_checked_menus_administrator');
						$saved_checked_menus_network = get_site_option('saved_checked_menus_administrator');
						break;
					case "editor" :
						// get saved options
						$saved_checked_menus = get_option('saved_checked_menus_editor');
						$saved_checked_menus_network = get_site_option('saved_checked_menus_editor');
						break;
					case "author" :
						// get saved options
						$saved_checked_menus = get_option('saved_checked_menus_author');
						$saved_checked_menus_network = get_site_option('saved_checked_menus_author');
						break;
					case "contributor" :
						// get saved options
						$saved_checked_menus = get_option('saved_checked_menus_contributor');
						$saved_checked_menus_network = get_site_option('saved_checked_menus_contributor');
						break;
					case "subscriber" :
						// get saved options
						$saved_checked_menus = get_option('saved_checked_menus_subscriber');
						$saved_checked_menus_network = get_site_option('saved_checked_menus_subscriber');
						break;				
				}
				
				
				// lets check if network settings are forced	
				$is_forced = $this->is_network_forced();
				if( isset($is_forced) && $is_forced === 0 ){
					return $saved_checked_menus;
				} else {
					return $saved_checked_menus_network;
				}					
			}
			return false;
		}

		// redirect users when trying to access hidden pages
		function manage_menu_admin_denied() {
			$user_id = get_current_user_id();
			$redirect = get_dashboard_url( $user_id );
			wp_redirect( $redirect );
			exit;
		}
		
		/// load necessary scripts and styles
		function add_manage_menu_admin_script() {    
		    if ( isset( $_GET['page'] ) && $_GET['page'] == 'manage_admin_menus_page' ) {		       
		        // load toggle function
		        wp_enqueue_script( 'mamscripts', plugin_dir_url( __FILE__ ) . 'js/mam-js.js' );
				wp_enqueue_style( 'mamstyle', plugin_dir_url( __FILE__ ) . 'css/mam-style.css' );		         
		    }
		}
		
		// MAM construct
		function construct_MAM(){
			if( current_user_can('manage_network') == true ) {
				$this->setup();		
			}
		}
		
		// create MAM
		function MAM_create(){
			
			// get current blog id				
			global $blog_id;
			
			// call set up if there's not option set yet
			if( get_option( 'saved_checked_menus_setup' ) == null OR (isset($_GET['reset']) && $_GET['reset'] == 1 && $_GET['page'] == 'manage_admin_menus_page')) {
					
				// construct the plugin when we have everything we need loaded - ie wp functions
				add_action('plugins_loaded',array(&$this, 'construct_MAM'));
								
			}
			
			// Add the site admin config page
			if ( function_exists('is_network_admin') ) {
					
				// add scripts	
				add_action( 'admin_enqueue_scripts', array(&$this, 'add_manage_menu_admin_script') );				
				
				// show settings page on main site
				$is_main_site = is_main_site($blog_id);
				if( $is_main_site ) {
						
					// always enable the settings page on the main site	 
					add_action('admin_menu', array(&$this, 'add_manage_menu_admin_page') );
					
				} else {
						
					// lets check if network settings are forced	
					$is_forced = $this->is_network_forced();
					if( isset($is_forced) && $is_forced === 0 ){
						add_action('admin_menu', array(&$this, 'add_manage_menu_admin_page') );
					}
				}
				add_action('network_admin_menu', array(&$this, 'add_manage_menu_admin_network_page') );
			}		
				
			add_action('admin_menu', array(&$this, 'manage_admin_menus_kick'), 9999 ); /// seriously?!
			add_action( 'admin_page_access_denied',  array(&$this, 'manage_menu_admin_denied'), 99 );
		}

		// create donate link
		function MAM_donate(){
			?>
			<div class="mam_donate">
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="KF6VJKRA7HHEQ">
					<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
					<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
				</form>
			</div><?
		}

		
				
} /// Manage Admin Menus

// Load Manage Admin Menus
$MAM_spinner = new manage_admin_menus();
$MAM_spinner->MAM_create();
