<?php

namespace system;

class Post_Types
{
  public static function init()
  {
    add_action( 'setup_theme', [ __CLASS__, 'register_post_type' ], 5 );
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
        'exclude_from_search' => false,
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
}
