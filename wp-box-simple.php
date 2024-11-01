<?php
/*
Plugin Name: WP Box Simple
Plugin URI: http://funandprog.fr
Description: Cette extension permet de créer une box et de l'afficher rapidement sur son theme , 
			un peu comme le widget Texte sauf qu'on c'est couc qui choissiez donné les droit de qui peu ou ne peu pas les modifier ou encore créer 
Author: Becuwe Adrien
Author URI: http://funandprog.fr
Version: 1.1
*/
define( 'WBS_PLUGIN_URL', WP_CONTENT_URL . '/plugins/wp_box_simple' );
define( 'WBS_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins/wp_box_simple' );
define( 'WBS_DB_NAME', 'wp_box_simple' );

require (dirname ( __FILE__ ) . '/include/wbs-box.php');

Class WPBoxSimple{
	// Table DB
	var $table_name = '';

	// File var
	var $current_box = '';

	// Generic
	var $home = '';
	var $info;

	// Team view
	var $base = 'box';
	var $base_url = '';
	

	//  Options
	var $options_name = array ('wbs-widget','wbs-tinymce');
	var $options;

	/**
	 * 
	 * WPTeamBoxs() - Class constructor
	 * 
	 * @return wp_box_simple
	 */
	function WPBoxSimple(){
		// Init options
		foreach ( $this->options_name as $name ) {
			$this->options [$name] = get_option ( $name );
		}

		// Init info

		$this->info ['plugin_url'] = WBS_PLUGIN_URL;
		$this->info ['siteurl'] = get_option('siteurl');

		 // Localization
       $locale = get_locale();
       if ( !empty( $locale ) ) {
           $load = load_textdomain('wpboxsimple', WBS_PLUGIN_DIR .'/languages/wpboxsimple-'.$locale.'.mo') ;
       }
		//installation
		global $wpdb;
		$this->table_name = $wpdb->prefix . WBS_DB_NAME;

		//header
		add_action('wp_head',  array( &$this, 'addHead') );

		//redirect
		//add_action('init', array( &$this, 'checkExternalLink') );

		// Short code
		add_shortcode('box',array( &$this, 'shortcodeBox') );

		// Rewriting
		//add_action('init', array( &$this, 'initRewrite'));
		
		// Query
		add_filter('query_vars', array( &$this, 'addQueryVar') );
		add_action('parse_query', array( &$this, 'parseQuery') );

		if (is_admin ()) {
			// Admin Init
			add_action ( 'admin_init', array ( &$this, 'checkAction' ) );
			add_action ( 'admin_init', array ( &$this, 'checkForm' ) );
			add_action ( 'admin_init', array ( &$this, 'helperAdminJS' ) );

			// Admin Menu
			add_action('admin_menu', array( &$this, 'addMenu') );

			// Init Permissions
			add_action('init', array( &$this, 'initPermission') );
			
			// TinyMCE JS
			if( $_GET['page'] == 'box_form' && ( int ) get_option ( 'wbs-tinymce' ) === 1) {			
				add_action('admin_print_scripts',array( &$this,  'printTinyMCE') );
			}
		}
	}


	/**
	 * 
	 * init Permisssions
	 *
	 */
	function initPermission() {
		if ( function_exists('get_role') ) {
			// Admin
			$role = get_role('administrator');
			if( $role != null && !$role->has_cap('super_admin_box') ) {
				$role->add_cap('super_admin_box');
			}
			if( $role != null && !$role->has_cap('any_box') ) {
				$role->add_cap('any_box');
			}
			if( $role != null && !$role->has_cap('admin_box') ) {
				$role->add_cap('admin_box');
			}
			unset($role);

			// Editor
			$role = get_role('editor');
			if( $role != null && !$role->has_cap('admin_box') ) {
				$role->add_cap('admin_box');
			}
			if( $role != null && !$role->has_cap('any_box') ) {
				$role->add_cap('any_box');
			}
			unset($role);

			// Auteur
			$role = get_role('author');
			if( $role != null && !$role->has_cap('any_box') ) {
				$role->add_cap('any_box');
			}
			unset($role);

			// Contributeur
			$role = get_role('contributor');
			if( $role != null && !$role->has_cap('any_box') ) {
				$role->add_cap('any_box');
			}
			unset($role);

			// Abonné
			$role = get_role('subscriber');
			if( $role != null && !$role->has_cap('any_box') ) {
				$role->add_cap('any_box');
			}
			unset($role);
		}
	}

	function addHead(){
	?>

	<?php
	}




	function printTinyMCE() {

  	wp_admin_css('thickbox');
	wp_print_scripts('post');
	wp_print_scripts('editor');
	add_thickbox();
	wp_print_scripts('media-upload');
	wp_print_scripts('jquery');
	wp_print_scripts('jquery-ui-core');
	wp_print_scripts('jquery-ui-tabs');
	if (function_exists('wp_tiny_mce')) wp_tiny_mce();

	}

	/**
	 * 
	 * Add Menu Boxs
	 *
	 */
	function addMenu() {
		// Add a menu :
		add_menu_page('Box Simple', __('Box Simple','wpboxsimple'), 'any_box' , __FILE__, array( &$this, 'pageDashboard') );
		// Add a submenu Ajout:
		add_submenu_page(__FILE__, 'Box Simple: Add/Edit', __('Add/Edit','wpboxsimple'), 'any_box', 'box_form', array( &$this, 'pageForm') );
		// Add a submenu List:
		add_submenu_page(__FILE__, 'Box Simple: List', __('List','wpboxsimple'), 'any_box', 'box_manage', array( &$this, 'pageManageBox') );
		// Add a submenu Option:
		add_submenu_page(__FILE__, 'Box Simple: Options', __('Options','wpboxsimple'), 'admin_box', 'box_option', array( &$this, 'pageOption') );
	}

	function helperAdminJS() {
		if ( isset( $_GET['page']) && $_GET['page'] == 'box_manage' ) { // List
			wp_enqueue_script('admin-forms');
		}

		return true;
	}

	/**
	 * 
	 * page dashboard
	 *
	 */
	function pageDashboard() {
		?>
		<div class="wrap">
			<h2><?php _e('Index WP Box Simple','wpboxsimple') ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><?php _e("Number of Box",'wpboxsimple') ?></th>
					<td><?php echo $this->countBoxs (); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php _e("Number of Box Publish",'wpboxsimple') ?></th>
					<td><?php echo $this->countPublishBoxs (); ?></td>
				</tr>
			</table>
		</div>
		<?php
	}
	
	
	/**
	 *
	 *  checkForm()
	 *
	 */
	function checkForm() {
		if (isset ( $_POST ['save_box'] ) && ! empty ( $_POST ['title']) && ! empty ( $_POST ['box_content']) ) {
			if (current_user_can ( 'any_box' ) && wp_verify_nonce ( $_POST ['box_nonce'], 'add_or_update' ) ) {
				// ID ?
				$box_id  = ( int ) $_GET ['box_id'];
				
				// title
				$title = stripslashes ( $_POST ['title'] );
				$box_content = stripslashes ( $_POST ['box_content'] );
				$state = stripslashes ( $_POST ['state'] );
				$date_created = stripslashes ( $_POST ['date_created'] );
				$distance = stripslashes ( $_POST ['distance'] );
				$user_id = stripslashes ( $_POST ['user_id'] );

				// Name is unique ?
				$try_box = new Box();
				$try_box_data = $try_box->loadBox( 'title', $title, 'ARRAY_A' ); 
				
				//var_dump($try_box_data);
				if ( $try_box_data == true && (  $try_box->getID() != $box_id ) ) { // Verify edit 
					$num = 2;
					do {
						$alt_title = $title . "-$num";
						$num++;
						$title_check = $try_box->loadBox( 'title', $alt_title );
					} while ( $title_check );
					$title = $alt_title;
				}

				// Slug
				$slug = attribute_escape ( stripslashes ( $_POST ['slug'] ) );
				if (empty ( $slug )) {
					$slug = sanitize_title ( $title );
					
				}
				// Slug is unique ?
				$try_box_1 = new Box(); 
                $try_box_data_1 = $try_box_1->loadBox( 'slug', $slug, 'ARRAY_A' ); 
                //var_dump($try_box_data_1);
                if ( $try_box_data_1 == true && ( $try_box->getID() != $box_id ) ) { // Verify edit 
					$num = 2;
					do {
						$alt_slug = $slug . "-$num";
						$num++;
						$slug_check = $try_box_1->loadBox( 'slug', $alt_slug );
					} while ( $slug_check );
					$slug = $alt_slug;
				}
			
				if ($box_id  != 0) { // Edit

					$current_box = new Box ( 'id', $box_id  );
					
					//si getState != $state => état changer => si publish ou refused send email
					$user_member = new WP_User( $user_id );
					$past_state = $current_box->getState ();
					$new_state = $state;

					$this->updateBox ( $box_id ,$title, $state,  $user_id, $box_content, $slug);
					wp_redirect ( 'admin.php?page=box_form&message=updated&box_id='.$box_id);
					exit();

				} else { // Add
					
					$id = $this->addBox ($title, $state,  $user_id, $box_content, $slug);					
					wp_redirect ( 'admin.php?page=box_form&message=added&box_id='.$id );
					exit();

				}
			}

		}
		else {


				$title = stripslashes ( $_POST ['title'] );
				$box_content = stripslashes ( $_POST ['box_content'] );
				$state = stripslashes ( $_POST ['state'] );
				$date_created = stripslashes ( $_POST ['date_created'] );
				$distance = stripslashes ( $_POST ['distance'] );
				$user_id = stripslashes ( $_POST ['user_id'] );

			if (current_user_can ( 'any_box' ) && wp_verify_nonce ( $_POST ['box_nonce'], 'add_or_update' )){

				//si l'un des fields et vide je rediction en dissant en ajoutant &nom_fields=empty;

				$fields = array('title','box_content');
				$errors = '';
				foreach( $fields as $field ) {
					if(empty( $_POST[$field] ) ) {
						$errors .= '&'.$field.'=empty';
					}

				}

				if( !empty($errors) ) {
					wp_redirect ( 'admin.php?page=box_form&message=notadded'.$errors );
					exit();
				}


			}
		}
	}


	/**
	 *
	 *  pageForm()
	 *
	 */
	function pageForm() {
		global $current_user;

		if (isset ( $_GET ['message'] ) && $_GET ['message'] == 'added') {
			$this->displayWordpressAlert ( __ ( 'Box added with success !', 'wpboxsimple' ) );
		} elseif (isset ( $_GET ['message'] ) && $_GET ['message'] == 'updated') {
			$this->displayWordpressAlert ( __ ( 'Box updated with success !', 'wpboxsimple' ) );
		} elseif (isset ( $_GET ['message'] ) && $_GET ['message'] == 'notadded') {
			//si on reçoit le nom d'un field vide on affiche le message d'alerte
			$fields = array('title','box_content');
			foreach( $fields as $field ) {
				if( $_GET[$field] == 'empty')
				$this->displayWordpressAlert ( sprintf( __('%s field missing. !', 'wpboxsimple' ), $field ));
				
			}
		}

		$edit = (isset ( $_GET ['box_id'] ) && (( int ) $_GET ['box_id'] != 0)) ? true : false;
		if ($edit == true) {
			$box = new Box( 'id', ( int ) $_GET ['box_id'] );
			if ( $box ) {
				$title = $box->getTitle();
				$box_content = $box->getBoxContent();
				$state = $box->getState();
				$date_created = $box->getDateCreated();
				$user_id = $box->getUserId();
				$slug = $box->getSlug();
			
			}

			$h2_title = sprintf ( __ ( 'Edit the Box: %s', 'wpboxsimple' ), $box->getTitle() );

		} else {
			$h2_title = __ ( 'Add an Box', 'wpboxsimple' );
			$title = $name = $box_content = $state = $date_created = $user_id = '';
		}
	?>
	<style type="text/css">
	#media-buttons {
		display:none;
	}
	</style>
	<div class="wrap">
		<div id="icon-edit" class="icon32"><br /></div>
		<h2><?php echo $h2_title; ?></h2>
		<form id="addlink" method="post" enctype="multipart/form-data">
			<div id="poststuff" class="metabox-holder has-right-sidebar">
				<div id="side-info-column" class="inner-sidebar">
					<div id="side-sortables" class="meta-box-sortables">
						<div id="submitdiv" class="postbox " >
						<div class="handlediv" title="Cliquer pour inverser."><br /></div><h3 class='hndle'><span>Publier</span></h3>
						<div class="inside">
							<div class="submitbox" id="submitpost">
								<div id="minor-publishing">
									<div style="display:none;">
										<input type="hidden" name="box_nonce" value="<?php echo wp_create_nonce('add_or_update'); ?>" />
										<input type="submit" name="save_box" value="<?php _e('Save Box', 'wpboxsimple' ) ?>" tabindex="4" />
									</div>

								<div id="misc-publishing-actions">
									<div class="misc-pub-section"><label for="post_status"><?php _e('Publish Status','wpboxsimple') ?></label>
									<select name='state' id='state' style="display:block; width:100%; " >
										<option<?php selected($state, 'publish'); ?> value='publish' ><?php echo $this->getNameStatus( 'publish' ); ?></option>
										<option<?php selected($state, 'wait'); ?> value='wait'> <?php echo $this->getNameStatus( 'wait' ); ?></option>
										<option<?php selected($state, 'private'); ?> value='private'> <?php echo $this->getNameStatus( 'private' );?></option>	
										<option<?php selected($state, 'refused'); ?> value='refused'> <?php echo $this->getNameStatus( 'refused' ); ?></option>
									</select>
							<?php if($edit == true): ?>
									<div class="misc-pub-section curtime misc-pub-section-last">
									<span id="timestamp"><?php _e('Date Created','wpboxsimple') ?> : <?php echo $date_created ?></span></p>
									</div>
							<?php endif; ?>
						
						<?php if( $current_user->has_cap('administrator') ): ?> 
						<p><strong><?php _e('User','wpboxsimple') ?></strong></p>
						<select name='user_id' id='user_id' style="display:block; width:100%; " >
							<?php 
							$users = get_users_of_blog ();
							foreach( (array) $users as $user) { ?> 
 					        <option<?php selected($user_id, $user->user_id);?> value='<?php echo $user->user_id;?>' > <?php echo $user->display_name;?></option>
		 				<?php } ?>
						</select>
						
					<?php else: ?>
						<input type="hidden" name="user_id" style="width:97%;" tabindex="1" value="<?php echo $user_id; ?>" id="user_id" />
					<?php endif; ?>
						
						
					</div>
					<div id="save-action">
						<input type="hidden" name="box_nonce" value="<?php echo wp_create_nonce('add_or_update'); ?>" />
						<input type="submit" class="button button-highlighted" name="save_box" value="<?php _e('Save Box', 'wpboxsimple' ) ?>" tabindex="4" />
					</div>
					
					</div>
				</div>
				</div>
				</div>
				</div>
								
				<div class="clear"></div>
				</div>
				
				</div>
				
				<div id="post-body" class="has-sidebar">
					<div id="post-body-content" class="has-sidebar-content">
					<div id="titlediv">
						<div id="titlewrap">
						<div class="inside">
							<input type="text" name="title" style="width:97%;" tabindex="1" value="<?php echo $title; ?>" id="title" /><br />
						</div>
					</div>
						<div class="inside">
							<div id="edit-slug-box">
						</div>
					</div>
					</div>
					
					<div id="slugdiv" class="stuffbox">
						<h3><label for="slug"><?php _e('Slug', 'wpboxsimple') ?></label></h3>
						<div class="inside">
							<input type="text" name="slug" style="width:97%;" tabindex="1" value="<?php echo $slug; ?>" id="slug" /><br />
						</div>
					</div>

					<div id="Contentdiv" class="stuffbox">
						<h3><label for="description_matos"><?php _e('Box Content','wpboxsimple') ?></label></h3>
						<div class="inside">
							<?php 
							the_editor($box_content);
							_e("Text of your box",'wpboxsimple'); 
							?>
						</div>
					</div>
					
				</div>
			</div>
		</form>
		<div class="clear"></div>
	</div>
	<div class="clear"></div>
	<?php

	}

	function checkAction() {
		// Delete box
		if (isset ( $_GET ['box_id'] ) && $_GET ['action'] == 'delete' ) {
			if ( current_user_can ( 'admin_box' ) && wp_verify_nonce ( $_GET ['_wpnonce'], 'delete_boxs' )) {
				$this->deleteBox ( ( int ) $_GET ['box_id'] );
				wp_redirect ( 'admin.php?page=box_manage&message=deleted' );
				exit();
			}
		}


		// Mass delete box
		if ( isset( $_GET['delete-boxs'] ) && isset( $_GET['delete'] ) ) {
			check_admin_referer('bulk-box');

			foreach( (array) $_GET['delete'] as $id ) {
				$this->deleteBox( (int) $id );
			}

			wp_redirect( 'admin.php?page=box_manage&message=deleteds' );
			exit();
		}

	}

	

	/**
    * Page Mange box
    *
    */

	function pageManageBox() {

		if ( isset ( $_GET ['message'] ) && $_GET ['message'] == 'deleted' ) {
			$this->displayWordpressAlert ( __ ( 'Box deleted with success !', 'wpboxsimple' ) );
		} elseif (isset ( $_GET ['message'] ) && $_GET ['message'] == 'deleteds' ) {
			$this->displayWordpressAlert ( __ ( 'Boxs deleted with success !', 'wpboxsimple' ) );
		}

		$s = $_GET['s'];
		$boxs = $this->getBoxs( 99999 , $s, '' );
	?>
	<div class="wrap" style="position:relative;">
			<?php if ( !empty( $s ) ) : ?>
				<h2><?php printf( __('Search results for "%s"', 'wpboxsimple'), wp_specialchars($s) ); ?></h2>
			<?php elseif( $tag_id != 0 ) : ?>
				<h2><?php printf( __('Manage Boxs in Tag ID: %d', 'wpboxsimple'), $tag_id); ?></h2>
			<?php else : ?>
				<h2><?php _e('Manage Boxs', 'wpboxsimple'); ?></h2>
			<?php endif; ?>
										
			<form action="" method="get">
				<p id="post-search">
					<input type="hidden" id="page" name="page" value="box_manage" />
					<input type="text" id="post-search-input" name="s" value="<?php echo wp_specialchars($s); ?>" />
					<input type="submit" value="<?php _e('Search', 'wpboxsimple'); ?>" class="button" />
				</p>
			</form>
				
			<form action="" method="get">
				<input type="hidden" id="page" name="page" value="box_manage" />
				
				<div class="tablenav">
					<div class="alignleft">
						<input type="submit" value="<?php _e('Delete', 'wpboxsimple'); ?>" name="delete-boxs" class="button-secondary delete" />
						<?php wp_nonce_field('bulk-box'); ?>
					</div>
					<br class="clear"/>
				</div>
				<br class="clear"/>
				
			
				<table class="widefat">
					<thead>
					    <tr>
					    	<th scope="col" class="check-column"><input type="checkbox" /></th>
					    	<th scope="col">ID</th>
					    	<th scope="col"> <?php _e('User ID','wpboxsimple') ?></th>
							<th scope="col"> <?php _e('Title','wpboxsimple') ?> </th>
							<th scope="col"> <?php _e('State','wpboxsimple') ?> </th>
							<th scope="col" colspan="2"> <?php _e('Action','wpboxsimple') ?> </th>
					    </tr>
					</thead>
					<tbody>
					    <?php if( empty( $boxs) ) : ?> 
					    	<tr valign="top">
					    		<td colspan='14'><?php _e('No box', 'wpboxsimple'); ?></td>
					    	</tr>
					    <?php else:	   	
					    foreach ( (array) $boxs as $box ) :
					    $box = new Box('object', $box ); 

					    		?>
					    		<tr valign="top">
					    			<th scope="row" class="check-column"><input type="checkbox" name="delete[]" value="<?php echo $box->getID(); ?>" /></th>
					    			<td><?php echo $box->getID (); ?></td>
					    			<td><?php echo $box->getUserId (); ?></td>
					    			<td><?php echo $box->getTitle (); ?></td>
									<td><?php echo $this->getNameStatus( $box->getState() );?></td>
					    			<td><a href="<?php echo get_option('siteurl'); ?>/wp-admin/admin.php?page=box_form&amp;box_id=<?php echo $box->getID(); ?>"><?php _e('Edit', 'wpboxsimple')?></a></td>
					    			<td><a href="<?php echo wp_nonce_url( get_option('siteurl') . '/wp-admin/admin.php?page=box_manage&amp;action=delete&amp;box_id='.$box->getID (), 'delete_boxs'); ?>"><?php _e('Delete', 'wpboxsimple') ?></a></td>
					    		</tr>
					    		<?php
					    		endforeach;
					    		endif;
					    ?>
					</tbody>
				</table>
			</form>
		</div>
		<div class="clear"></div>

	
	
	<?php
	}
	
	function getNameStatus( $status = '' ) {

   		switch ( $status ) {
       		case 'publish' :
           		return __('Published','wpboxsimple');
      		break;
      		case 'wait' :
           		return __('Wait this validation','wpboxsimple');
      		break;
      		case 'private' :
           		return __('Private','wpboxsimple');
      		break;
      		case 'refused' :
           		return __('Refused','wpboxsimple');
      		break;
      	}
      }



	/**
    * Page Option
    *
    */
	function pageOption() {
		if (isset ( $_POST ['update_members_options'] ) ) {
			if ( current_user_can ( 'super_admin_box' ) && wp_verify_nonce ( $_POST ['box_nonce'], 'update_options' ) ) {
				// Save options
				foreach ( $this->options_name as $name ) {

					if (! update_option ( $name, ( int ) $_POST [$name] )) {
						add_option ( $name, ( int ) $_POST [$name] );
					}

					// Set new values in memory
					$this->options[$name] = ( int ) $_POST [$name];
				}

				$this->displayWordpressAlert ( __ ( 'Options updated with success !', 'wpboxsimple' ) );
			}
		}
		?>
		<div class="wrap">
			<h2><?php _e('Options Wp Team Boxs', 'wpboxsimple') ?></h2>
	
			<form method="post">
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e('Widget', 'wpboxsimple') ?></th>
						<td>
							<input type="checkbox" id="wbs-widget" name="wbs-widget" value="1" <?php checked( 1, $this->options['wbs-widget'] ); ?> />
							<label for="wbs-widget"><?php _e('Active widget', 'wpboxsimple') ?></label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Tinymce Editor', 'wpboxsimple') ?></th>
						<td>
							<input type="checkbox" id="wbs-tinymce" name="wbs-tinymce" value="1" <?php checked( 1, $this->options['wbs-tinymce'] ); ?> />
							<label for="wbs-widget"><?php _e('Activate the tinymce', 'wpboxsimple') ?></label>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="hidden" name="box_nonce" value="<?php echo wp_create_nonce('update_options'); ?>" />
					<input type="submit" name="update_members_options" value="<?php _e('Update options', 'wpboxsimple') ?>" />
				</p>
			</form>
		</div>
	<?php
	}

	

	/**
	 * Get boxs
	 * 
	 * @param integer $limit
	 * @param string $search
	 * @param string $order
	 * @param int $tag_id
	 * @return array
	 */
	function getBoxs( $limit = 20 ,$search='',$order = 'ASC') {
		global $wpdb;
		global $current_user;

		if($current_user->has_cap('subscriber'))
		{
			$where = "user_id = ".$current_user->id;
		}
		else
		{
			$where = "1 = 1";
		}
		// Clean value
		$limit = ( int ) $limit ;
		$search = $wpdb->escape ( $search );
		$wpdb->escape ( $order );
		$query=" SELECT * FROM {$this->table_name} WHERE ".$where." ";

		if(!empty($search))  // Search
		{
			$query.=" AND ( title LIKE '%{$search}%' OR box_content LIKE '%{$search}%' OR slug LIKE '%{$search}%' ) ";
		}  

		$query .= "ORDER BY ID {$order} LIMIT {$limit}";
		return $wpdb->get_results($query);
	}
	
	/**
	 * Get getBoxsPublish
	 * 
	 * @param integer $limit
	 * @param string $search
	 * @param string $order
	 * @param int $tag_id
	 * @return array
	 */
	function getBoxsPublish( $limit = 20 ,$search='',$order = 'ASC' ) {
		global $wpdb;
		global $current_user;

		if($current_user->has_cap('subscriber'))
		{
			$where = "user_id = ".$current_user->id;
		}
		else
		{
			$where = "1 = 1";
		}
		// Clean value
		$limit = ( int ) $limit ;
		$search = $wpdb->escape ( $search );
		$wpdb->escape ( $order );
		$query=" SELECT * FROM {$this->table_name} WHERE ".$where." And state= 'publish' ";

		if(!empty($search))  // Search
		{
			$query.=" AND ( title LIKE '%{$search}%' OR box_content LIKE '%{$search}%' OR slug LIKE '%{$search}%' ) ";
		} 
		$query .= "ORDER BY ID {$order} LIMIT {$limit}";
		return $wpdb->get_results($query);
	}

	/**
	 * SQL Insert Query
	 *
	 * @param string $title
	 * @param string $description_short
	 * @param string $description_long
	 * @param string $picture
	 * @param boolean $state
	 * @param string $location_author
	 * @param string $location_members
	 * @param string $name_author
	 * @param string $name_traductor
	 * @param int $versions
	 * @param string $translation
	 * @return integer
	 */
	function addBox ($title='', $state='',  $user_id='', $box_content='', $slug='') {
		global $wpdb;
		global $current_user;
		// Clean value
		$user_id = $current_user->id;
		$date = current_time('mysql');
		if($current_user->has_cap('subscriber') ) {
			$state = 'wait';
		}
		
		$wpdb->insert( $this->table_name, array('title' => $title,  'state'=>$state,'date_created'=>$date, 'box_content'=>$box_content, 'user_id'=>$user_id, 'slug'=>$slug) );
		//var_dump(array('title' => $title,  'state'=>$state,'date_created'=>$date, 'box_content'=>$box_content, 'user_id'=>$user_id, 'slug'=>$slug) );

		return $wpdb->insert_id;
	}

	/**
	 * SQL Query for update ONE member
	 *
	 * @param integer $id
	 * @param string $description_short
	 * @param string $description_long
	 * @param string $picture
	 * @param boolean $state
	 * @param string $location_author
	 * @param string $location_members
	 * @param string $name_author
	 * @param string $name_traductor
	 * @param string $versions
	 * @param string $translation
	 * @return object
	 */

	function updateBox( $box_id ,$title='', $state='',  $user_id='', $box_content='', $slug='') {
		global $wpdb;
		global $current_user;
		// On sécurise
		$id = (int) $box_id;
		$date = current_time('mysql');
		$wpdb->update(
			$this->table_name, 
			array('title' => $title,  'state'=>$state,'date_created'=>$date, 'box_content'=>$box_content, 'user_id'=>$user_id, 'slug'=>$slug),
			array( 'id'=> $id )  
			);
	}


	/**
	 * SQL Query for delete ONE member
	 *
	 * @param integer $id
	 */
	function deleteBox( $id ) {
		global $wpdb;
	//	echo "delete";

		$delete_box = new Box ( 'id', $id );
		if ( $delete_box ) {
			// Delete row
			$wpdb->query ( $wpdb->prepare ( "DELETE FROM {$this->table_name} WHERE id = %d", $id ) );

			// Delete terms
			wp_delete_object_term_relationships( $id, $this->base_tags );

			unset($delete_box);
			return true;
		}
		return false;
	}




	function getObjectsByTerm( $field = '', $slug = '', $taxonomy = '' ) {
		// Get Tag detail
		$term = get_term_by ( $field, $slug, $taxonomy );
		if (! $term ) {
			return false;
		}

		// Get objects ID
		return get_objects_in_term ( $term->term_id, $taxonomy );
	}


	/**
	 * countBoxs()
	 */
	function countBoxs(){
		global $wpdb;
		return $wpdb->get_var("SELECT COUNT(id) FROM {$this->table_name}");

	}
	/**
	 * countBoxs()
	 */
	function countPublishBoxs(){
		global $wpdb;
		return $wpdb->get_var("SELECT COUNT(id) FROM {$this->table_name} Where state = 'publish'");

	}

	/**
	 * displayWordpressAlert( $texte, $status = 'updated' ) 
	 * @param string $texte
	 * @param string $status
	 * 
	 * affichage des messages
	 */

	function displayWordpressAlert( $texte, $status = 'updated' ) {
		if ( $texte ) {
		?>
		<div id="message" class="<?php echo ($status != '') ? $status :'updated'; ?> fade">
			<p><strong><?php echo $texte; ?></strong></p>
		</div>
		<?php
		}
	}

	// Ajouter le marqueur member à WP
	function addQueryVar( $wp_query_var ) {

		// Ajout du mot clef member
		$wp_query_var[] = $this->base;

		return ($wp_query_var);
	}


	function parseQuery() {
		// Evenement
		$this->current_box = stripslashes( get_query_var( $this->base ) );
		if ( get_magic_quotes_gpc() ) {
			$this->current_box = stripslashes( $this->current_box );
		}


		if ( !empty( $this->current_box ) || !empty( $this->current_tags_member ) ) {
			// Remove all WP flags
			global $wp_query;
			$wp_query->init_query_flags();
			// Redirect to specific template
			add_action('template_redirect', array( &$this, 'templateRedirect' ) );
		}
	}

	/**
	 * Template redirect Wordpress
	 *
	 * @return boolean 
	 */

	function templateRedirect() {

		if (! empty ( $this->current_box ) || ! empty ( $this->current_tags_member )) {
			if (! empty ( $this->current_box )) {
				$tpl_file = 'box-detail.php';
			} 

			if (is_file ( TEMPLATEPATH . '/' . $tpl_file ) ) {
				load_template ( TEMPLATEPATH . '/' . $tpl_file );
				exit ();
			} else {
				wp_die ( 'The template file <code>' . $tpl_file . '</code> is required for this member' );
			}
		}
		return false;
	}


	/**
	 * redirect()
	 */
	function redirect(){
		$id= (int) $_GET['wbs_redirect_to'] ;
		$box = new Box( 'id', $id );
		$this->incrementBox( $id );
		// On sécurise
		wp_redirect( clean_url( $box->getWebsite() ) );
		exit();
	}



	// Rewrite
	function initRewrite() {
		global $wp_rewrite;
		// Detect permalink type & construct base URL for local links
		$this->home = get_settings('home') . '/';

		if ( isset( $wp_rewrite ) && $wp_rewrite->using_permalinks() ) {
			$is_rewriteon = true; // using rewrite rules
			$base_url .= ( substr($wp_rewrite->front, 0, 1) == '/' ) ? substr($wp_rewrite->front, 1, strlen($wp_rewrite->front)) : $wp_rewrite->front;
			$base_url .= $wp_rewrite->root; // set to "index.php/" if using that style

			$this->base_url = $base_url . $this->base . '/';
		} else {
			$this->base_url .= '?' . $this->base . '=';
		}
		// http://monblog.fr/
		$this->base_url = $this->home . $this->base_url;

		if ($is_rewriteon === true) {
			add_filter('search_rewrite_rules',array(&$this, 'createRewriteRules'));
		}

		// flush rules if requested
		$wp_rewrite->flush_rules();
	}

	function createRewriteRules( $rewrite ) {
		global $wp_rewrite;
		// member
		$wp_rewrite->add_rewrite_tag('%' . $this->base . '%', '(.+)', $this->base . '=');
		$box_rewrite = $wp_rewrite->generate_rewrite_rules($wp_rewrite->root . $this->base . "/%$this->base%/");

		return ( $rewrite + $box_rewrite  );
	}

	/**
	 * Short Codes
	 */
	function shortcodeBox( $atts ) {

		extract ( shortcode_atts ( array ('slug' => '', 'title' => '', 'id' => '' ), $atts ) );

		// Clean value
		$title = trim ( $title );
		$id = intval ( $id );
		$slug = trim ( $slug );

		// Get member ?
		if (! empty ( $slug )) {
			$box = new Box ( 'slug', $slug );
		} elseif ($id != 0) {
			$box = new Box ( 'id', $id );
		} elseif (! empty ( $title )) {
			$box = new Box ( 'title', $title );
		}

		if ($box) {
			return $box->getBoxContent ();
		}
		return '';
	}



}//fin de classe

// Init sat
global $wp_box_simple;
function wbs_init() {
	global $wp_box_simple;
	$wp_box_simple = new WPBoxSimple ();
	if ( ( int ) get_option ( 'wbs-widget' ) === 1 ) {
		@include  (dirname ( __FILE__ ) . '/include/wbs-widget.php' );
	}
}
add_action ( 'plugins_loaded', 'wbs_init' );

function activate_wbs(){
	global $wpdb;

	// installation
	$table_name = $wpdb->prefix . 'wp_box_simple';

	if ( $wpdb->get_var("show tables like '$table_name'" ) != $table_name ) {
		$sql = "CREATE TABLE " . $table_name . "  (
			`id` int(10) NOT NULL auto_increment,
			`slug` varchar(255) NOT NULL,
			`title` varchar(255) NOT NULL,
			`box_content` varchar(255) NOT NULL,
			`date_created` datetime NOT NULL,
			`state` varchar(255) NOT NULL,
			`user_id` int(10) NOT NULL,
			PRIMARY KEY  (`id`),
			UNIQUE KEY `title` (`title`),
			UNIQUE KEY `slug` (`slug`)
			)";

		require_once ( ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta ( $sql );
	}
	// Init first options
	add_option ( 'wbs-widget', 0 );
	add_option ( 'wbs-tinymce',1);


	// Widget options
	add_option ( 'wbs-widget-options', array( 'title' =>  __ ( 'Latest Boxs', 'wpboxsimple' ), 'quantity' => 20, 'show-thumb' => 1 ) );
}
register_activation_hook(__FILE__, 'activate_wbs' );
?>