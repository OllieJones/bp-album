<?php

/**
 * This function should include all classes and functions that access the database.
 * In most BuddyPress components the database access classes are treated like a model,
 * where each table has a class that can be used to create an object populated with a row
 * from the corresponding database table.
 * 
 * By doing this you can easily save, update and delete records using the class, you're also
 * abstracting database access.
 */

class BP_Album_Picture {
    
	var $id;
	var $owner_type;
	var $owner_id;
	var $date_uploaded;
	var $title;
	var $description;
	var $privacy;
	var $pic_org_url;
	var $pic_org_path;
	var $pic_mid_url;
	var $pic_mid_path;
	var $pic_thumb_url;
	var $pic_thumb_path;

	/**
	 * bp_album_picture()
	 *
	 * This is the constructor, it is auto run when the class is instantiated.
	 * It will either create a new empty object if no ID is set, or fill the object
	 * with a row from the table if an ID is provided.
	 */
	function BP_Album_Picture( $id = null ) {
		$this->__construct( $id );
	}
	
	function __construct( $id = null ) {
		global $wpdb, $bp;	
		
		if ( $id ) {	
			$this->populate( $id );
		}
	}
	
	/**
	 * populate()
	 *
	 * This method will populate the object with a row from the database, based on the
	 * ID passed to the constructor.
	 */
	function populate($id) {
		global $wpdb,$bp;
		
		$sql = $wpdb->prepare( "SELECT * FROM {$bp->album->table_name} WHERE id = %d", $id );
		$picture = $wpdb->get_row( $sql );
		
		if ( $picture ) {
			$this->owner_type = $picture->owner_type;
			$this->owner_id = $picture->owner_id;
			$this->id = $picture->id;
	        $this->date_uploaded = $picture->date_uploaded;
	        $this->title = $picture->title;
	        $this->description = $picture->description;
	        $this->privacy = $picture->privacy;
	        $this->pic_org_path = $picture->pic_org_path;
	        $this->pic_org_url = $picture->pic_org_url;
	        $this->pic_mid_path = $picture->pic_mid_path;
	        $this->pic_mid_url = $picture->pic_mid_url;
	        $this->pic_thumb_path = $picture->pic_thumb_path;
	        $this->pic_thumb_url = $picture->pic_thumb_url;
		}
	}
	
	/**
	 * save()
	 *
	 * This method will save an object to the database. It will dynamically switch between
	 * INSERT and UPDATE depending on whether or not the object already exists in the database.
	 */
	
	function save() {
		global $wpdb, $bp;
		
		/***
		 * In this save() method, you should add pre-save filters to all the values you are saving to the
		 * database. This helps with two things -
		 * 
		 * 1. Blanket filtering of values by plugins (for example if a plugin wanted to force a specific 
		 *	  value for all saves)
		 * 
		 * 2. Security - attaching a wp_filter_kses() call to all filters, so you are not saving
		 *	  potentially dangerous values to the database.
		 *
		 * It's very important that for number 2 above, you add a call like this for each filter to
		 * 'bp-album-filters.php'
		 *
		 *   add_filter( 'example_data_fieldname1_before_save', 'wp_filter_kses' );
		 */	
		
		$this->title = apply_filters( 'bp_album_title_before_save', $this->title );
		$this->description = apply_filters( 'bp_album_description_before_save', $this->description, $this->id );
		
		/* Call a before save action here */
		do_action( 'bp_album_data_before_save', $this );

		// Don't try and save if there is no user ID.
		if ( !$this->owner_id)
			return false;

		$this->title = esc_attr( strip_tags($this->title) );
		$this->description = wp_filter_kses($this->description);

        if ( $this->id ) {
			// Update
			$sql = $wpdb->prepare(
				"UPDATE {$bp->album->table_name} SET
					owner_type = %s,
					owner_id = %d,
					date_uploaded = %s,
					title = %s,
					description = %s,
					privacy = %d,
					pic_org_url = %s,
					pic_org_path =%s,
					pic_mid_url = %s,
					pic_mid_path =%s,
					pic_thumb_url = %s,
					pic_thumb_path =%s
				WHERE id = %d",
					$this->owner_type,
					$this->owner_id,
					$this->date_uploaded,
					$this->title,
					$this->description,
					$this->privacy,
					$this->pic_org_url,
					$this->pic_org_path,
					$this->pic_mid_url,
					$this->pic_mid_path,
					$this->pic_thumb_url,
					$this->pic_thumb_path,
					$this->id
				);
		} else {
			// Save
			$sql = $wpdb->prepare(
					"INSERT INTO {$bp->album->table_name} (
						owner_type,
						owner_id,
						date_uploaded,
						title,
						description,
						privacy,
						pic_org_url,
						pic_org_path,
						pic_mid_url,
						pic_mid_path,
						pic_thumb_url,
						pic_thumb_path
					) VALUES (
						%s, %d, %s, %s, %s, %d, %s, %s, %s, %s, %s, %s
					)",
						$this->owner_type,
						$this->owner_id,
						$this->date_uploaded,
						$this->title,
						$this->description,
						$this->privacy,
						$this->pic_org_url,
						$this->pic_org_path,
						$this->pic_mid_url,
						$this->pic_mid_path,
						$this->pic_thumb_url,
						$this->pic_thumb_path
					);
		}
		
		$result = $wpdb->query( $sql );
	    
		if ( !$result )
			return false;
		
		if ( !$this->id ) {
			$this->id = $wpdb->insert_id;
		}	
		
		/* Add an after save action here */
		do_action( 'bp_album_data_after_save', $this ); 
		
		return $result;
	}

	/**
	 * delete()
	 *
	 * This method will delete the corresponding row for an object from the database.
	 */	
	function delete() {
		global $wpdb, $bp;
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->album->table_name} WHERE id = %d", $this->id ) );
	}

	/* Static Functions */

	/**
	 * Static functions can be used to bulk delete items in a table, or do something that
	 * doesn't necessarily warrant the instantiation of the class.
	 *
	 * Look at bp-core-classes.php for examples of mass delete.
	 */

	/*function delete_all() {

	}*/
	
	public static function query_pictures($args = '',$count=false,$adjacent=false) {
		global $bp, $wpdb;
		
		$defaults = bp_album_default_query_args();
		
		$r = apply_filters('bp_album_query_args',wp_parse_args( $args, $defaults ));
		extract( $r , EXTR_SKIP);
		
		$where = "1 = 1";
		
		if ($owner_id){
			$where .= $wpdb->prepare(' AND owner_id = %d',$owner_id);	
		}
		if ($id && $adjacent != 'next' && $adjacent != 'prev' && !$count){
			$where .= $wpdb->prepare(' AND id = %d',$id);
		}
		
		switch ( $privacy ) {
			case 'public':
			case 0 === $privacy:
			case '0':
				$where .= " AND privacy = 0";
				break;
				
			case 'members':
			case 2:
				if (bp_album_privacy_level_permitted()>=2 || $priv_override)
					$where .= " AND privacy = 2";
				else
					return $count ? 0 : array();
				break;
				
			case 'friends':
			case 4:
				if (bp_album_privacy_level_permitted()>=4 || $priv_override)
					$where .= " AND privacy = 4";
				else
					return $count ? 0 : array();
				break;
				
			case 'private':
			case 6:
				if (bp_album_privacy_level_permitted()>=6 || $priv_override)
					$where .= " AND privacy = 6";
				else
					return $count ? 0 : array();
				break;
				
			case 'admin':
			case 10:
				if (bp_album_privacy_level_permitted()>=10 || $priv_override)
					$where .= " AND privacy = 10";
				else
					return $count ? 0 : array();
				break;
				
			case 'all':
				if ( $priv_override )
					break;
				
			case 'permitted':
			default:
				$where .= " AND privacy <= ".bp_album_privacy_level_permitted();
				break;
		}
		if(!$count){	
		$order = "";	
		$limits = "";
			if($adjacent == 'next'){
				$where .= $wpdb->prepare(' AND id > %d',$id);	
				$order = "ORDER BY id ASC";	
				$limits = "LIMIT 0, 1";
			}elseif($adjacent == 'prev'){
				$where .= $wpdb->prepare(' AND id < %d',$id);	
				$order = "ORDER BY id DESC";	
				$limits = "LIMIT 0, 1";
			}elseif(!$id){
				if ($orderkey != 'id' && $orderkey != 'user_id' && $orderkey != 'status') $orderkey = 'id';
				if ($ordersort != 'ASC' && $ordersort != 'DESC') $ordersort = 'DESC';
				$order = "ORDER BY $orderkey $ordersort";
				if ($per_page){
					if ( empty($offset) ) {
						$limits = $wpdb->prepare('LIMIT %d, %d', ($page-1)*$per_page , $per_page);
					} else { // we're ignoring $page and using 'offset'
						$limits = $wpdb->prepare('LIMIT %d, %d', $offset , $per_page);
					}
				}
			}
			
			$sql = $wpdb->prepare( "SELECT * FROM {$bp->album->table_name} WHERE $where $order $limits") ;
			$result = $wpdb->get_results( $sql );
			
		} else {
			$select='';
			$group='';
			if ($groupby=='privacy'){
				$select='privacy,';
				$group='GROUP BY privacy';
			}
			
			$sql =  $wpdb->prepare( "SELECT DISTINCT $select COUNT(id) AS count FROM {$bp->album->table_name} WHERE $where $group") ;
			if ($group)
				$result = $wpdb->get_results( $sql );
			else
				$result = $wpdb->get_var( $sql );
		}

		return $result;	
	}

	public static function delete_by_owner($owner_id,$owner_type ) {
		global $bp, $wpdb;
		
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->album->table_name} WHERE owner_type = %d AND owner_id = %d ", $owner_type, $owner_id ) );
	}

	public static function delete_by_user_id($user_id) {
		return BP_Album_Picture::delete_by_owner($user_id,'user');
	}
	
}

function bp_album_default_query_args(){
	global $bp;
	$args = array();
	
	$args['owner_id'] = $bp->displayed_user->id ? $bp->displayed_user->id : false;
	$args['id'] = false;
	$args['page']=1;
	$args['per_page']=$bp->album->bp_album_per_page;
	$args['max']=false;
	$args['privacy']='permitted';
	$args['priv_override']=false;
	$args['ordersort']='ASC';
	$args['orderkey']='id';
	$args['groupby']=false;
	
	if($bp->album->single_slug == $bp->current_action){
		$args['id'] = ( isset($bp->action_variables[0]) && (string)(int) $bp->action_variables[0] === (string) $bp->action_variables[0] ) ? (int) $bp->action_variables[0] : false ;
		$args['per_page']=1;
	}
	if($bp->album->pictures_slug == $bp->current_action){
		$args['page'] = ( isset($bp->action_variables[0]) && (string)(int) $bp->action_variables[0] === (string) $bp->action_variables[0] ) ? (int) $bp->action_variables[0] : 1 ;
	}
	return $args;
}

?>
