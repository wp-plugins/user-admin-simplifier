<?php
 /*
Plugin Name: User Admin Simplifier
Plugin URI: http://www.earthbound.com/plugins/user-admin-simplifier.php
Description: Lets any Administrator simplify the WordPress Admin interface, on a per-user basis, by turning specific menu sections off.
Version: 0.23
Author: Earthbound
Author URI: http://www.earthbound.com/plugins
License: GPLv2 or later
*/

	add_action( 'init', 'uas_init' );
 	
	function uas_init() {
		wp_enqueue_script( 'jquery' );
		add_action( 'admin_menu', 'add_admin_menu' );
  		add_action( 'admin_menu', 'edit_admin_menus' );  	
        add_action( 'admin_head', 'admin_js' );
        add_action( 'admin_head', 'admin_css' );
		add_filter( 'plugin_action_links', 'uas_plugin_action_links', 10, 2 );
  	}
 
	function edit_admin_menus() {
		global $menu; 
		global $current_user;
		$uas_options=get_admin_options();
		$newmenu=array();
		//rebuild menu based on saved options
		foreach ($menu as $menuitem){
			if  ( isset ( $menuitem[5] ) && isset( $uas_options[$current_user->user_login][$menuitem[5]] ) &&
			 1 == $uas_options[$current_user->user_login][$menuitem[5]] ) {
				//unset ($menu[key($menuitem)]);
 				remove_menu_page($menuitem[2]);
			}
 		}
 	}
	
	function uas_plugin_action_links( $links, $file ) {
 		if ( $file == plugin_basename( __FILE__ ) ) {
			$posk_links = '<a href="'.get_admin_url().'admin.php?page=useradminsimplifier/useradminsimplifier.php">'.__('Settings').'</a>';
			// make the 'Settings' link appear first
			array_unshift( $links, $posk_links );
		}
 		return $links;
	}
 	
	function add_admin_menu() {
         add_menu_page(	esc_html__( 'User Admin Simplifier', 'useradminsimplifier' ), 
						esc_html__( 'User Admin Simplifier', 'useradminsimplifier' ), 
						'manage_options', 
						'useradminsimplifier/useradminsimplifier.php',
						'useradminsimplifier_options_page' ); 
    }
	
	function get_admin_options(){
        $saved_options = get_option( 'useradminsimplifier_options' );
        return is_array( $saved_options ) ? $saved_options : array();
    }
	
    function save_admin_options( $uas_options ){
         update_option( 'useradminsimplifier_options', $uas_options );
    }
	
	function cleanmenuname($menuname){ //clean up menu names provided by WordPress
 		$menuname = preg_replace( '/<span (.*?)span>/' , '' , $menuname ); //strip the count appended to menus like the post count
		return ( $menuname ); 
	}
	
	function useradminsimplifier_options_page() {
		$uas_options=get_admin_options();
		$uas_selecteduser = isset( $_POST['uas_user_select'] ) ? $_POST['uas_user_select']: '';
 		global $menu;
		global $current_user;
		$nowselected = array ();
		$menusectionsubmitted=false;
  		if ( isset( $uas_options['selecteduser'] ) && $uas_options['selecteduser'] != $uas_selecteduser ) {
			//user was changed
			$uas_options['selecteduser'] = $uas_selecteduser;
		} 
		else {
			$uas_options['selecteduser']=$uas_selecteduser;
			//process submitted menu selections
			if (isset ( $_POST['menuselection'] ) && is_array($_POST['menuselection'])) {
				$menusectionsubmitted=true;
				foreach ($_POST['menuselection'] as $key => $value) {
 						$nowselected[$uas_selecteduser][$value]=1; //disable this menu for this user
				}
			}
		}
	
?>
<div class="wrap">
    <h2> 
		<?php esc_html_e( 'User Admin Simplifier', 'user_admin_simplifier'); ?>
    </h2>
    
    <form action="" method="post" id="uas_options_form" class="uas_options_form">
      	<div class="uas_container" id="chooseauser">
        <h3>
        	<?php esc_html_e( 'Choose a user', 'user_admin_simplifier'); ?>: 
        </h3>
        <select id="uas_user_select" name="uas_user_select" >
        <option>
<?php
			$blogusers = get_users('orderby=nicename');
			foreach ($blogusers as $user) {
				echo ('<option value="'. $user->user_nicename .'" '); 
				echo ( ( $user->user_nicename==$uas_selecteduser ) ? 'selected' : '' );
				echo ('>' . $user->user_nicename .  '</option>');
			}
?>
		 </select>
          </div>
         
<?php
	        if( isset( $_POST['uas_user_select'] ) ) {
 ?>        
    <div class="uas_container" id="choosemenus">
        <h3>
            <?php esc_html_e( 'Select menus to disable for this user', 'user_admin_simplifier'); ?>: <br />

        </h3>
        <input  style="display:none;" type="checkbox" checked="checked" value="uas_dummy" id="menuselection[]" name="menuselection[]">
<?php
				//lets start with top level menus stored in global $menu
				//will add submenu support if needed later
 				$rowcount=0; 
				foreach($menu as $menuitem){
					$menuuseroption=0;
					if ( !('wp-menu-separator' == $menuitem[4]) ){
						//reset							$uas_options[$uas_selecteduser][$menuitem[5]]=0;
						if ( $menusectionsubmitted ) {
							if ( isset( $nowselected[$uas_selecteduser][$menuitem[5]] ) ) { //any selected options for this user/menu
								 
								$menuuseroption=$uas_options[$uas_selecteduser][$menuitem[5]]= $nowselected[$uas_selecteduser][$menuitem[5]] ;
							} 
							else {
								$menuuseroption=$uas_options[$uas_selecteduser][$menuitem[5]]=0;
							}
						}
						
						if ( isset( $uas_options[$uas_selecteduser][$menuitem[5]] ) ) { //any saved options for this user/menu
							$menuuseroption = $uas_options[$uas_selecteduser][$menuitem[5]];
						} else {
							$menuuseroption=0;
							$uas_options[$uas_selecteduser][$menuitem[5]]=0;
						}
 						//check if selected user has capability for menu
						//user_can( $user, $capability )
						//echo ( implode ( " ~ ",$menuitem ) );
						//don't allow current user to diable their own access to the plugin
						echo 	'<p'. (( 0 == $rowcount++ %2 ) ? '' : ' class="alternate"' ) . '>'.
						'<input type="checkbox" name="menuselection[]" id="menuselection[]" '.
						'value="'. $menuitem[5] .'" ' . ( 1==$menuuseroption ? 'checked="checked"' : '') .
						//don't allow current user to diable their own access to the plugin
						( $uas_selecteduser==$current_user->user_nicename && "toplevel_page_useradminsimplifier/useradminsimplifier"== $menuitem[5]  ? ' disabled ' : '') .
						' /> ' . 
						cleanmenuname($menuitem[0]) . "</p>";
					} //menu separator
 				} 
?>
	<input name="uas_save" type="submit" id="uas_save" value="Save Changes" />
    </div>
 <?php
			}
?>
   </form>
</div>
 <?php
save_admin_options( $uas_options );
 	}
    
	 function admin_js(){
?>
<script type="text/javascript">
	jQuery(function() {
		jQuery('form#uas_options_form #uas_user_select').change( function() {
				jQuery('form#uas_options_form').submit();
			}) 
	});
</script>
<?php
    }
	function admin_css(){
?>
<style type="text/css">
	.uas_options_form {
		font-size:14px;
	}
	
	.uas_options_form p {
		margin:0 0;
		padding:.5em .5em;
	}
	.uas_options_form input {
		font-size:18px;
	}
	
	.uas_options_form select {
		min-width:200px;
		padding:5px;
		font-size:16px;
	}
 	#choosemenus{
		border-width:1px;
		border-color:#ccc;
		padding:10px;
		border-style:solid;
	}
</style>
<?php
    }
