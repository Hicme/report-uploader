<?php

namespace system;

class Post_Types
{
  public static function init()
  {
    add_action( 'setup_theme', [ __CLASS__, 'register_post_type' ], 5 );

    add_filter( 'manage_posts_columns', [ __CLASS__, 'reports_column' ], 10, 2 );
    add_action( 'manage_posts_custom_column', [ __CLASS__, 'reports_column_content' ], 10, 2);
    add_action( 'restrict_manage_posts', [ __CLASS__, 'posts_filters' ] );

    add_filter( 'parse_query', [ __CLASS__, 'catch_filtering' ] );

    add_action('add_meta_boxes', [ __CLASS__, 'preview_report' ]);
  }



  public static function register_post_type()
  {
    if( ! is_blog_installed() || post_type_exists( 'agent-reports' ) ){
        return;
    }

    $supports   = [ 'title' ];

    register_post_type(
      'agent-reports',
      [
        'labels' => [
          'name'                  => __( 'Reports', 'report_uploader' ),
          'singular_name'         => __( 'Agent Reports', 'report_uploader' ),
          'all_items'             => __( 'All Agent Reports', 'report_uploader' ),
          'menu_name'             => _x( 'Agent Reports', 'Admin menu name', 'report_uploader' ),
          'add_new'               => __( 'Add New', 'report_uploader' ),
          'add_new_item'          => __( 'Add new report', 'report_uploader' ),
          'edit'                  => __( 'Edit', 'report_uploader' ),
          'edit_item'             => __( 'Edit report', 'report_uploader' ),
          'new_item'              => __( 'New report', 'report_uploader' ),
          'view_item'             => __( 'View report', 'report_uploader' ),
          'view_items'            => __( 'View reports', 'report_uploader' ),
          'search_items'          => __( 'Search reports', 'report_uploader' ),
          'not_found'             => __( 'No reports found', 'report_uploader' ),
          'not_found_in_trash'    => __( 'No reports found in trash', 'report_uploader' ),
          'parent'                => __( 'Parent report', 'report_uploader' ),
          'featured_image'        => __( 'report image', 'report_uploader' ),
          'set_featured_image'    => __( 'Set report image', 'report_uploader' ),
          'remove_featured_image' => __( 'Remove report image', 'report_uploader' ),
          'use_featured_image'    => __( 'Use as report image', 'report_uploader' ),
          'insert_into_item'      => __( 'Insert into report', 'report_uploader' ),
          'uploaded_to_this_item' => __( 'Uploaded to this report', 'report_uploader' ),
          'filter_items_list'     => __( 'Filter reports', 'report_uploader' ),
          'items_list_navigation' => __( 'reports navigation', 'report_uploader' ),
          'items_list'            => __( 'reports list', 'report_uploader' ),
        ],
        'description'         => '',
        'public'              => true,
        'show_ui'             => true,
        'capability_type'     => 'agent_report',
        'map_meta_cap'        => true,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'hierarchical'        => false,
        'query_var'           => true,
        'rewrite'             => false,
        'supports'            => $supports,
        'has_archive'         => false,
        'show_in_nav_menus'   => false,
        'show_in_rest'        => false,
        'show_in_menu'        => 'agent-reports',
      ]
    );
  }

  public static function reports_column( $columns, $post_type )
  {
    if ( $post_type === 'agent-reports' ) {
      $save_date = $columns['date'];
      unset( $columns['date'] );

      $columns['file_format'] = __( 'Format', 'report_uploader' );
      $columns['agent_id'] = __( 'Agent', 'report_uploader' );
      $columns['download_link'] = __( 'Download', 'report_uploader' );
      $columns['date'] = $save_date;
    }

    return $columns;
  }

  public static function reports_column_content( $column_name, $post_id )
  {
    global $post;
    if ( ! $post ) {
      $post = get_post( $post_id );
    }

    if( $column_name === 'file_format' ){
      if( $post ){
        echo '<span class="report-format">'. $post->post_mime_type .'</span>';
      }
    }

    if( $column_name === 'agent_id' ){
      if( $post && ( $user = get_user_by( 'ID', $post->post_author ) ) ){
        echo '<span class="report-agent-link"><a href="/wp-admin/user-edit.php?user_id='. $post->post_author .'">'. $user->display_name .'</a></span>';
      }else{
        echo '<span class="not_loaded_datas"><span class="dashicons dashicons-no"></span></span>';
      }
    }

    if( $column_name === 'download_link' ){
      if( $download_link = report_uploader()->download_report( $post_id ) ){
        $download = __('Download', 'report_uploader');
        echo "<span class=\"report-download-link\"><a href=\"{$download_link}\"><span class=\"dashicons dashicons-download\"></span>{$download}</a></span>";
      }else{
        echo '<span class="not_loaded_datas"><span class="dashicons dashicons-no"></span></span>';
      }
    }
  }

  public static function posts_filters( $post_type )
  {
    if ( 'agent-reports' !== $post_type ) {
      return;
    }
    $args = [
      'role'    => 'agent',
      'orderby' => 'display_name',
      'order'   => 'ASC',
    ];

    $users = get_users( $args );
    ?>
    <label for="filter-by-date" class="screen-reader-text">
      <?php _e( 'Filter by Agent', 'report_uploader' ); ?>
    </label>
    <select name="filter_agent" id="filter-by-agent" class="select2">
      <option value="">
        <?php _e( 'All Agents', 'report_uploader' ); ?>
      </option>
      <?php
        if ( ! empty( $users ) ) {
          foreach ( $users as $user ) {
            if ( isset( $_GET['filter_agent'] ) && $_GET['filter_agent'] == $user->ID ) {
              ?>
              <option selected="selected" value="<?php echo $user->ID; ?>">
                <?php echo $user->display_name; ?>
              </option>
              <?php
            } else {
              ?>
              <option value="<?php echo $user->ID; ?>">
                <?php echo $user->display_name; ?>
              </option>
              <?php
            }
          }
        }
      ?>
    </select>
    <?php
  }

  public static function catch_filtering( $query )
  {
    global $post_type, $pagenow;

    if (
      is_admin() &&
      $post_type === 'agent-reports' &&
      $pagenow === 'edit.php' &&
      isset( $_GET['filter_agent'] ) &&
      $_GET['filter_agent'] != ''
    ) {
      $query->set( 'author__in', $_GET['filter_agent'] );
    }
  }

  public static function preview_report()
  {
    add_meta_box( 'excel_viewer', __( 'XML/CSV Preview', 'report_uploader' ), [ __CLASS__, 'preview_report_callback' ], 'agent-reports' );
  }

  public static function preview_report_callback( $post, $meta )
  {
    if ( $datas = report_uploader()->view_report( $post->ID ) ) {
      print_table( $datas );
    } else {
      echo '<h3>' . __( 'An error occurred while loading the file.', 'report_uploader' ) . '</h3>';
    }

  }
}
