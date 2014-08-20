<?php
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * MR_Rating_Results_Table class
 * 
 * @author dpowney
 *
 */
class MR_Rating_Results_Table extends WP_List_Table {

	const
	CHECKBOX_COLUMN = 'cb',
	POST_ID_COLUMN = 'post_id',
	TITLE_COLUMN = 'title',
	RATING_RESULT_COLUMN = 'rating_result',
	SHORTCODE_COLUMN = 'shortcode',
	ENTRIES_COUNT_COLUMN = 'entries_count',
	ACTION_COLUMN = 'action',
	DELETE_CHECKBOX = 'delete[]';

	/**
	 * Constructor
	 */
	function __construct() {
		
		parent::__construct( array(
				'singular'=> __( 'Rating Results', 'multi-rating' ),
				'plural' => __( 'Rating Results', 'multi-rating' ),
				'ajax'	=> false
		) );
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::extra_tablenav()
	 */
	function extra_tablenav( $which ) {
		
		if ( $which == "top" ){
			
			$post_id = '';
			if ( isset( $_REQUEST['post-id'] ) ) {
				$post_id = $_REQUEST['post-id'];
			}
			
			global $wpdb;
			?>
			
			<div class="alignleft filters">							
				<select name="post-id" id="post-id">
					<option value=""><?php _e( 'All posts / pages', 'multi-rating' ); ?></option>
					<?php	
					global $wpdb;
					$query = 'SELECT DISTINCT post_id FROM ' . $wpdb->prefix . Multi_Rating::RATING_ITEM_ENTRY_TBL_NAME;
					
					$rows = $wpdb->get_results( $query, ARRAY_A );
					foreach ( $rows as $row ) {
						$post = get_post( $row['post_id'] );
						
						$selected = '';
						if ( intval( $row['post_id'] ) == intval( $post_id ) ) {
							$selected = ' selected="selected"';
						}
						?>
						<option value="<?php echo $post->ID; ?>" <?php echo $selected; ?>>
							<?php echo get_the_title( $post->ID ); ?>
						</option>
					<?php } ?>
				</select>
				
				<input type="submit" class="button" value="<?php _e( 'Filter', 'multi-rating' ); ?>"/>
			</div>
						
			<?php
		}
		
		if ( $which == "bottom" ){
			
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_columns()
	 */
	function get_columns() {
		
		return $columns= array(
				MR_Rating_Results_Table::CHECKBOX_COLUMN => '<input type="checkbox" />',
				MR_Rating_Results_Table::POST_ID_COLUMN => __( 'Post Id', 'multi-rating' ),
				MR_Rating_Results_Table::TITLE_COLUMN => __( 'Title', 'multi-rating' ),
				MR_Rating_Results_Table::RATING_RESULT_COLUMN => __( 'Rating Result', 'multi-rating' ),
				MR_Rating_Results_Table::ENTRIES_COUNT_COLUMN => __( 'Entries', 'multi-rating' ),
				MR_Rating_Results_Table::ACTION_COLUMN => __( 'Action', 'multi-rating' ),
				MR_Rating_Results_Table::SHORTCODE_COLUMN => __( 'Shortcode', 'multi-rating' )
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::prepare_items()
	 */
	function prepare_items() {
		global $wpdb;
		
		// Process any bulk actions first
		$this->process_bulk_action();

		// Register the columns
		$columns = $this->get_columns();
		$hidden = array( );
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		$post_id = isset( $_REQUEST['post-id'] ) ? $_REQUEST['post-id'] : null;
		
		// get table data
		$query = 'SELECT * FROM ' . $wpdb->prefix . Multi_Rating::RATING_ITEM_ENTRY_TBL_NAME . ' as rie';
		
		$added_to_query = false;
		if ( $post_id ) {
			$query .= ' WHERE';
		}
		
		if ( $post_id ) {
			if ($added_to_query) {
				$query .= ' AND';
			}
		
			$query .= ' rie.post_id = "' . $post_id . '"';
			$added_to_query = true;
		}
		
		$query .= ' GROUP BY rie.post_id';
		
		// pagination
		$item_count = $wpdb->query( $query ); //return the total number of affected rows
		$items_per_page = 10;
		$page_num = ! empty( $_GET["paged"] ) ? mysql_real_escape_string( $_GET["paged"] ) : '';
		if ( empty( $page_num ) || ! is_numeric( $page_num ) || $page_num <= 0 ) {
			$page_num = 1;
		}
		$total_pages = ceil( $item_count / $items_per_page );
		// adjust the query to take pagination into account
		if ( ! empty( $page_num ) && ! empty( $items_per_page ) ) {
			$offset = ( $page_num -1 ) * $items_per_page;
			$query .= ' LIMIT ' . ( int ) $offset. ',' . ( int ) $items_per_page;
		}
		
		$this->set_pagination_args( array( 
				'total_items' => $item_count,
				'total_pages' => $total_pages,
				'per_page' => $items_per_page
		) );
		
		$this->items = $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Default column
	 * @param unknown_type $item
	 * @param unknown_type $column_name
	 * @return unknown|mixed
	 */
	function column_default( $item, $column_name ) {
		
		$post_id =  $item[MR_Rating_Results_Table::POST_ID_COLUMN];
		
		switch( $column_name ) {
			case MR_Rating_Results_Table::SHORTCODE_COLUMN : {
				
				echo '[display_rating_result post_id="' . $post_id . '"]';
				break;
			}
			
			case MR_Rating_Results_Table::POST_ID_COLUMN : {
				echo $post_id;
				break;
			}
			
			case MR_Rating_Results_Table::TITLE_COLUMN : {
				echo '<a href="' . get_permalink( $post_id ) . '">' . get_the_title( $post_id ) . '</a>';
				break;
			}
			
			case MR_Rating_Results_Table::ACTION_COLUMN : {
							
				?>
				<a class="view-rating-result-entries-anchor" href="?page=<?php echo Multi_Rating::RATING_RESULTS_PAGE_SLUG; ?>&tab=<?php 
						echo Multi_Rating::ENTRIES_TAB; ?>&post-id=<?php echo $post_id ?>"><?php 
						_e( 'View Entries', 'multi-rating' ); ?></a>
				<?php
				break;
			}
			
			case MR_Rating_Results_Table::ENTRIES_COUNT_COLUMN : {
				global $wpdb;
				
				$query = $query = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . Multi_Rating::RATING_ITEM_ENTRY_TBL_NAME . ' WHERE post_id = "' 
						. $post_id . '"';
				$rows = $wpdb->get_col( $query, 0 );
				
				echo $rows[0];
				
				break;
			}
			
			case MR_Rating_Results_Table::RATING_RESULT_COLUMN : {
				
				$rating_items = Multi_Rating_API::get_rating_items( array( 'post_id' => $post_id ) );
				$rating_result = Multi_Rating_API::calculate_rating_result( array( 'post_id' => $post_id, 'rating_items' => $rating_items ) );
				
				$entries = $rating_result['count'];
				$html = '';
				if ($entries != 0) {
					
					echo __( 'Star Rating: ', 'multi-rating' ) . $rating_result['adjusted_star_result'] . '/5<br />'
					. __( 'Score: ', 'multi-rating' ) . $rating_result['adjusted_score_result'] . '/' . $rating_result['total_max_option_value'] . '<br />'
					. __( 'Percentage: ', 'multi-rating' ) . $rating_result['adjusted_percentage_result'] . '%';
					
				} else {
					echo 'None';	
				}
				
				echo $html;
				break;
			}
			
			case Rating_Item_Entry_Table::CHECKBOX_COLUMN :
				return $item[ $column_name ];
				break;
			default:
				return print_r( $item, true ) ;
		}
	}
	
	/**
	 * checkbox column
	 * @param unknown_type $item
	 * @return string
	 */
	function column_cb($item) {
		
		return sprintf(
				'<input type="checkbox" name="' . MR_Rating_Results_Table::DELETE_CHECKBOX . '" value="%s" />', $item[MR_Rating_Results_Table::POST_ID_COLUMN]
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_bulk_actions()
	 */
	function get_bulk_actions() {
		
		$bulk_actions = array(
				'delete'    => __( 'Delete', 'multi-rating' )
		);
		
		return $bulk_actions;
	}
	
	/**
	 * Handles bulk actions
	 */
	function process_bulk_action() {
		
		if ( $this->current_action() ==='delete' ) {
			global $wpdb;
				
			$checked = ( is_array( $_REQUEST['delete'] ) ) ? $_REQUEST['delete'] : array( $_REQUEST['delete'] );
				
			foreach( $checked as $post_id ) {
				
				/*
				 * delete rating item entry values as well
				 */ 
				$entries = Multi_Rating_API::get_rating_item_entries( array( 
						'post_id' => $post_id
				) );
				
				foreach ( $entries as $entry ) {
					$rating_item_entry_id = $entry['rating_item_entry_id'];
					
					$entry_values_query = 'DELETE FROM '. $wpdb->prefix.Multi_Rating::RATING_ITEM_ENTRY_VALUE_TBL_NAME . '  WHERE ' .  MR_Rating_Entry_Value_Table::RATING_ITEM_ENTRY_ID_COLUMN . ' = "' . $rating_item_entry_id . '"';
					$results = $wpdb->query($entry_values_query);
					
					$entries_query = 'DELETE FROM '. $wpdb->prefix.Multi_Rating::RATING_ITEM_ENTRY_TBL_NAME . '  WHERE ' .  MR_Rating_Entry_Table::RATING_ITEM_ENTRY_ID_COLUMN . ' = "' . $rating_item_entry_id . '"';	
					$results = $wpdb->query($entries_query);
				}
				
				/* 
				 * delete rating results cache in WordPress postmeta table
				 */
				delete_post_meta( $post_id, Multi_Rating::RATING_RESULTS_POST_META_KEY );
			}
				
			echo '<div class="updated"><p>' . __( 'Rating results deleted successfully.', 'multi-rating' ) . '</p></div>';
		}
	}
}