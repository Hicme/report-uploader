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

    $author = self::process_report_name( $file_id );

    if ( isset( $author['error'] ) ) {
      return new \WP_Error( 'upload_error', $author['error'] );
    }

    $file = wp_handle_upload( $_FILES[ $file_id ], $overrides, $time );

    if ( isset( $file['error'] ) ) {
      return new \WP_Error( 'upload_error', $file['error'] );
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

    $file_data = [
      'post_mime_type' => $type,
      'guid'           => $url,
      'post_title'     => $title,
      'post_content'   => $content,
      'post_author'    => $author['id'],
      'post_excerpt'   => $excerpt,
      'comment_status' => 'closed',
      'post_type'      => 'agent-reports',
      'post_status'    => 'inherit',
      'post_parent'    => 0,
    ];

    $id = wp_insert_post( $file_data, true );
    if ( ! is_wp_error( $id ) ) {
      update_post_meta( (int) $id, '_report_metadata', wp_generate_attachment_metadata( $id, $file ), true );
    }
  
    return $id;
  }

  private static function process_report_name( $file_id )
  {
    $name = $_FILES[ $file_id ]['name'];
    $exploded_name = explode( '-', $name );
    $return = [];

    if ( is_array( $exploded_name ) && isset( $exploded_name[2] ) && ! empty( trim( $exploded_name[2] ) ) ) {
      $return['id'] = intval( $exploded_name[2] );
    } else {
      $return['error'] = __( "Can't parse file name. Double check it.", 'report_uploader' );
    }

    if ( ! report_uploader()->get_agent( $return['id'] ) ) {
      $return['error'] = __( "Can't find agent by ISA ID => {$return['id']}.", 'report_uploader' );
    }

    return $return;
  }

  public static function unique_report_name( $dir, $name, $ext )
  {
    return md5( str_replace( $ext, '', $name ) ) . $ext;
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
