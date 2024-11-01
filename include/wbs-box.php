<?php
if (! defined ( 'ABSPATH' )) die ();

class Box {
	// DB Var
	var $table_name = '';
	var $db_schema = array ('id','title', 'slug', 'box_content', 'user_id','date_created','state' );

	// File Var
	var $info = array();
	var $base_url = 'wp_box_simple';
	
	// Schema fields
	var $id = '';
	var $title = '';
	var $slug = '';
	var $box_content = ''; 
	var $date_created = '';
	var $user_id = '';
	
	
	

	/**
	 * Class constructor 
	 *
	 * @param string $field
	 * @param string|array $value
	 */	
	function Box($field = '', $value = '', $output = 'OBJECT'){

		// Init info
		$this->info ['plugin_url'] = WBS_PLUGIN_URL;
		$this->info ['siteurl'] = get_option('siteurl');

		// Set table name
		global $wpdb;
		$this->table_name = $wpdb->prefix . WBS_DB_NAME;
		
		// Load box in class var if constructor set
		if (! empty ( $field ) && ! empty ( $value ) ) {
			$this->loadBox( $field, $value, $output );
		}
	}

	function loadBox( $field = '', $value = '', $output = 'object' ) {
	
		// On recupère les données du wallpaper
		if ( $field == 'object') {
			$box_data = $value;
		} else {
			$box_data = $this->getBoxBy ( $field, $value, $output );
		}

		if( $box_data == false) {
			return false;
		}

		// On initialise les variables
		if ( $output == 'ARRAY_A' ) { // ARRAY_A
			
			foreach ( $this->db_schema as $row ) {
				$field = $row;
				$this->{$field} = $box_data[$field];
				
				unset($row, $field);
			}	
			//die('tpto');
		} else { // OBJECT
			foreach ( $this->db_schema as $row ) {
				$field = $row;
				$this->{$field} = $box_data->{$field};
				unset($row, $field);
			}
		}
	
		// Delete temp var
		unset ( $box_data );

		return true;
	}

	/**
	 * SQL Query for get ONE box 
	 *
	 * @param string $field
	 * @param string $value
	 * @return object|boolean
	 */
	function getBoxBy( $field = '', $value = '', $output = 'OBJECT' ) {
		if ( !in_array ( $field, $this->db_schema ) || empty ( $value ) || empty ( $field ) ) { // Field is valid ?
			return false;
		}	
		
		global $wpdb;
		
		$output = ( $output == 'ARRAY_A' ) ? ARRAY_A : OBJECT;
		
		return $wpdb->get_row ( $wpdb->prepare ( " SELECT * FROM {$this->table_name} WHERE $field = %s ", $value ), $output );
	}


	
	/**
	 * Build link box
	 *
	 * @return string string
	 */
	function getLink() {
		global $wp_box_simple;
		return clean_url ($this->getSlug () );
	}


	/**
	 * Getter picture slug
	 *
	 * @return string
	 */
	function getSlug() {
		return attribute_escape ( stripslashes ( $this->slug ) );
	}
	
	/**
	 * Getter  ID
	 *
	 * @return integer
	 */
	function getID() {
		return ( int ) $this->id;
	}

	/**
	 * Getter Title
	 *
	 * @return string
	 */
	function getTitle(){
		return wp_specialchars ( stripslashes ( $this->title ) );
	}

	/**
	 * Getter Description Long
	 *
	 * @return string
	 */
	function getBoxContent(){
		return  stripslashes ( $this->box_content  );
	}

	/**
	 * Getter State
	 *
	 * @return string
	 */
	function getState(){
		return wp_specialchars ( stripslashes ( $this->state ) );
	}

	/**
	 * Getter DateCreated
	 *
	 * @return string
	 */
	function getDateCreated(){
		return mysql2date(  $this->date_created);
	}

	/**
	 * Getter User_ID
	 *
	 * @return string
	 */
	function getUserID(){
		return (int)  $this->user_id ;
	}

}


?>