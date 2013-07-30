<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if (!class_exists('WPMUDEVChat_Session_Messages_Table')) {
	class WPMUDEVChat_Session_Messages_Table extends WP_List_Table {

		//var $_parent;	// The parent Snapshot instance
		var $item;
		var $file_kb;

	    function __construct( ) {
	        global $status, $page;

	        //Set parent defaults
	        parent::__construct( array(
	            'singular'  => 'Archive',     //singular name of the listed records
	            'plural'    => 'Archive',    //plural name of the listed records
	            'ajax'      => false        //does this table support ajax?
	        ) );
	    }

		function get_table_classes() {
			return array( 'widefat', 'fixed', 'wpmudev-chat-session-messages-table' );
		}

	    function get_bulk_actions() {
	        $actions = array(
	            'delete'    => 'Delete'
	        );
	        return $actions;
	    }

		function check_table_filters() {

			$filters = array();

			if ( (isset($_POST['chat-filter'])) && (isset($_POST['chat-filter-blog-id'])) ) {
	 			$filters['blog-id'] = intval($_POST['chat-filter-blog-id']);
			} else {
				$filters['blog-id'] = '';
			}

			if ( (isset($_POST['chat-filter'])) && (isset($_POST['chat-filter-chat-id'])) ) {
	 			$filters['chat-id'] = esc_attr($_POST['chat-filter-chat-id']);
			}
			return $filters;
		}


	    function column_default($item, $column_name){
			//echo "column_name=[". $column_name ."]<br />";
			//echo "item<pre>"; print_r($item); echo "</pre>";
			echo "&nbsp;";
	  	}

		function column_cb($item) {
			?><input type="checkbox" name="delete-bulk[]" value="<?php echo $item->id; ?>" /><?php
		}

	    function get_columns() {
			global $wpmudev_chat;

			$columns = array();

			$columns['cb'] 			= 	'<input type="checkbox" />';
			$columns['timestamp']	=	__('Time', 			$wpmudev_chat->translation_domain);
			$columns['user']		=	__('User', 			$wpmudev_chat->translation_domain);
			$columns['message']		=	__('Message', 		$wpmudev_chat->translation_domain);

			if (is_multisite())
				$columns['blog']	=	__('Blog', 			$wpmudev_chat->translation_domain);

	        return $columns;
	    }


		function column_timestamp($item) {
			echo $item->timestamp;
		}

		function column_user($item) {
			echo '<span class="chat-user-avatar">'. get_avatar($item->avatar, 32) . '</span><span class="chat-user-name">' .$item->name .'</span>';
		}

		function column_message($item) {
			echo $item->message;
		}

		function column_blog($item) {

			if (isset($item['blog-id'])) {
				$blog = get_blog_details($item['blog-id']);
				if ($blog) {
					echo $blog->blogname ."<br /> (". $blog->domain .")";
				} else {
					echo "&nbsp;";
				}
			} else {
				echo "&nbsp;";
			}
		}

		function get_hidden_columns() {
			$screen 	= get_current_screen();
			$hidden 	= get_hidden_columns( $screen );

			// Don't want the user to hide the 'File' column
			$file_idx = array_search('file', $hidden);
			if ($file_idx !== false) {
				unset($hidden[$file_idx]);
			}

			return $hidden;
		}

	    function get_sortable_columns() {

			$sortable_columns = array();
	        return $sortable_columns;
	    }

		function display() {
			extract( $this->_args );
			$this->display_tablenav( 'top' );
			?>
			<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>
			<tbody id="the-list"<?php if ( $singular ) echo " class='list:$singular'"; ?>>
				<?php $this->display_rows_or_placeholder(); ?>
			</tbody>
			<tfoot>
			<tr>
				<?php $this->print_column_headers( false ); ?>
			</tr>
			</tfoot>
			</table>
			<?php
			$this->display_tablenav( 'bottom' );
		}


	    function prepare_items() {
			global $wpdb;

	        $columns 	= $this->get_columns();
			$hidden 	= $this->get_hidden_columns();
	        $sortable 	= $this->get_sortable_columns();

	        $this->_column_headers = array($columns, $hidden, $sortable);

			$filters = $this->check_table_filters();
			//echo "filters<pre>"; print_r($filters); echo "</pre>";
			if ((isset($filters['blog-id'])) && (intval($filters['blog-id']))) {
				if (count($items)) {
					$filtered_items = array();
					foreach($items as $timestamp => $item) {
						if ($item['blog-id'] == $filters['blog-id']) {
							$filtered_items[$timestamp] = $item;
						}
					}
					$items = $filtered_items;
				}
			}

			$per_page = get_user_meta(get_current_user_id(), 'chat_page_chat_session_messages_per_page', true);
			if ((!$per_page) || ($per_page < 1)) {
				$per_page = 20;
			}

			$current_page = $this->get_pagenum();
			$page_offset = ($current_page - 1) * intval($per_page);

			if ((isset($_GET['chat_id'])) && (isset($_GET['start_time'])) && (isset($_GET['end_time']))) {
				$chat_id = intval($_GET['chat_id']);
				$start_time = date('Y-m-d H:i:s', intval($_GET['start_time']) );
				$end_time = date('Y-m-d H:i:s', intval($_GET['end_time']) );

				$sql_str = "SELECT count(*) as total_items FROM ".
					WPMUDEV_Chat::tablename('message') ." WHERE chat_id=". $chat_id ." AND timestamp BETWEEN '". $start_time ."' AND '". $end_time ."'";
				//echo "sql_str=[". $sql_str ."]<br />";
				$result = $wpdb->get_row( $sql_str );
				if ($result->total_items) {
					$total_items = $result->total_items;
				} else {
					$total_items = 0;
				}
				//echo "total_items=[". $total_items ."]<br />";

				$sql_str = "SELECT id, blog_id, chat_id, timestamp, name, avatar, message, moderator, archived FROM ".
					WPMUDEV_Chat::tablename('message') ." WHERE 1=1 AND chat_id=". $chat_id ." AND timestamp BETWEEN '". $start_time ."' AND '". $end_time ."'" ;
				if (is_multisite())
					$sql_str .= " AND blog_id=". $wpdb->blogid;
			 	$sql_str .= " ORDER BY timestamp ASC LIMIT ". $page_offset .", ". $per_page;
				//echo "sql_str=[". $sql_str ."]<br />";

				$items = $wpdb->get_results( $sql_str );
				//echo "items<pre>"; print_r($items); echo "</pre>";
			}


			if (count($items)) {

				$this->items = $items;

				$this->set_pagination_args( array(
					'total_items' => $total_items,                  			// WE have to calculate the total number of items
					'per_page'    => intval( $per_page ),                     			// WE have to determine how many items to show on a page
					'total_pages' => ceil(intval($total_items) / intval( $per_page ))   	// WE have to calculate the total number of pages
					)
				);
			}
	    }
	}
}