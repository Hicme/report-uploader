<?php

namespace system;

class Files
{
  public static function init()
  {
    add_filter( 'upload_mimes', [ __CLASS__, 'files_mine_types' ] );
    add_filter( 'wp_check_filetype_and_ext', [ __CLASS__, 'file_type_checks' ], 10, 4 );

    add_filter( 'plupload_init', [ __CLASS__, 'upload_destination' ] );
  }



  public static function process_report_upload( string $file_id )
  {
    $time = current_time( 'mysql' );
    $overrides = [
      'test_form'                => false,
      'unique_filename_callback' => [ __CLASS__, 'unique_report_name' ],
    ];

    $file = wp_handle_upload( $_FILES[ $file_id ], $overrides, $time );

    if ( isset( $file['error'] ) ) {
      return new WP_Error( 'upload_error', $file['error'] );
    }

    $name = $_FILES[ $file_id ]['name'];
    $ext  = pathinfo( $name, PATHINFO_EXTENSION );
    $name = wp_basename( $name, ".$ext" );

    $url     = $file['url'];
    $type    = $file['type'];
    $file    = $file['file'];
    $title   = sanitize_text_field( $name );
    $content = '';
    $excerpt = '';

    $attachment = [
      'post_mime_type' => $type,
      'guid'           => $url,
      'post_parent'    => $post_id,
      'post_title'     => $title,
      'post_content'   => $content,
      'post_excerpt'   => $excerpt,
    ];
  
    // This should never be set as it would then overwrite an existing attachment.
    unset( $attachment['ID'] );
  
    // Save the data
    $id = wp_insert_attachment( $attachment, $file, $post_id, true );
    if ( ! is_wp_error( $id ) ) {
      wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
    }
  
    return $id;
  }

  public static function unique_report_name( $dir, $name, $ext )
  {

  }

  public static function files_mine_types( $types )
  {
    $types['xlsx'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    return $types;
  }

  public static function file_type_checks( $type_and_ext, $file, $filename, $mimes ){
    $file_type = wp_check_filetype( $filename, $mimes );

    if ( $file_type['type'] === 'application/vnd.ms-excel' ||
      $file_type['type'] === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ) {
      if ( current_user_can('manage_options') ) {
        $type_and_ext['ext']  = $file_type['ext'];
        $type_and_ext['type'] = $file_type['type'];
      }
    }

    return $type_and_ext;
  }

  public static function upload_destination( $defaults )
  {
    $screen = get_current_screen();
    if ( $screen->base === 'agent-reports_page_upload-reports' ) {
      $defaults['url'] = admin_url( 'admin-ajax.php?action=process_upload_report' );
    }

    return $defaults;
  }
}
