<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if (!class_exists('WPMUDEVChat_Session_Logs_Table')) {
	class WPMUDEVChat_Session_Logs_Table extends WP_List_Table {

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
			return array( 'widefat', 'fixed', 'wpmudev-chat-session-logs-table' );
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
			$chat_details_value = strtotime($item->start) .'-'. $item->chat_id;
			?><input type="checkbox" name="delete-bulk[]" value="<?php echo $chat_details_value; ?>" /><?php
		}

	    function get_columns() {
			global $wpmudev_chat;

			$columns = array();

			$columns['cb'] 			= 	'<input type="checkbox" />';

			$columns['chat_id']		=	__('Session', 			$wpmudev_chat->translation_domain);

			$columns['type']		=	__('Type', 			$wpmudev_chat->translation_domain);
			$columns['time']		= 	__('Time', 			$wpmudev_chat->translation_domain);


			if (is_multisite())
				$columns['blog']	=	__('Blog', 			$wpmudev_chat->translation_domain);

	        return $columns;
	    }

		function column_chat_id($item) {
			global $wpmudev_chat;

			$chat_details_href = '?page=chat_session_logs&action=details&amp;start_time='. strtotime($item->start) .'&amp;end_time='. strtotime($item->end).'&amp;chat_id='. $item->chat_id.'&amp;log_id='. $item->id;
			?>
			<a href="<?php echo $chat_details_href; ?>"><?php
				echo $item->chat_id ?></a>

			<div class="row-actions" style="margin:0; padding:0;">
				<span class="details"><a href="<?php echo $chat_details_href; ?>"><?php
					_e('details', $wpmudev_chat->translation_domain); ?></a></span> | <span class="delete"><a
						href="?page=chat_session_logs&amp;action=delete&amp;log_id=<?php echo $item->id; ?>&amp;chat-noonce-field=<?php echo wp_create_nonce( 'chat-delete-item' ); ?>"><?php
							_e('delete', $wpmudev_chat->translation_domain); ?></a></span>
			</div>
			<?php
		}

		function column_type($item) {
			//echo "item<pre>"; print_r($item); echo "</pre>";
			echo $item->session_type;
		}

		function column_time($item) {
			echo $item->start .' - '. $item->end;
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

//		function column_chat_id($item) {
//			echo $item->chat_id;
//		}

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
			$sortable_columns['session'];
			$sortable_columns['type'];
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

			//$screen = get_current_screen();
			//echo "screen<pre>"; print_r($screen); echo "</pre>";
			$per_page = 20;
			$per_page = get_user_meta(get_current_user_id(), 'chat_page_chat_session_logs_per_page', true);
			if ((!$per_page) || ($per_page < 1)) {
				$per_page = 20;
			}

			$current_page = $this->get_pagenum();
			$page_offset = ($current_page - 1) * intval($per_page);

			$sql_str = "SELECT count(*) as total_items FROM ". WPMUDEV_Chat::tablename('log');
			//echo "sql_str=[". $sql_str ."]<br />";
			$result = $wpdb->get_row( $sql_str );
			if ($result->total_items) {
				$total_items = $result->total_items;
			} else {
				$total_items = 0;
			}

			$sql_str = "SELECT log.*, message.* FROM ".
				WPMUDEV_Chat::tablename('log') ." as log LEFT JOIN ". WPMUDEV_Chat::tablename('message') ." as message ON log.chat_id=message.chat_id";

			$sql_str .= " WHERE 1=1 AND message.session_type != 'private'";

			if (is_multisite())
				$sql_str .= " AND blog_id=". $wpdb->blogid;
			if (isset($_GET['chat_id']))
				$sql_str .= " AND log.chat_id='". esc_attr($_GET['chat_id']) ."'";

			$sql_str .= " ORDER BY start DESC LIMIT ". $page_offset .", ". $per_page;
			echo "sql_str=[". $sql_str ."]<br />";

			$items = $wpdb->get_results( $sql_str );
			//echo "items<pre>"; print_r($items); echo "</pre>";

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