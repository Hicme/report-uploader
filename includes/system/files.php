<?php

namespace system;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Files
{
  public static function init()
  {
    add_filter( 'upload_mimes', [ __CLASS__, 'files_mine_types' ] );
    add_filter( 'wp_check_filetype_and_ext', [ __CLASS__, 'file_type_checks' ], 10, 4 );

    add_filter( 'plupload_init', [ __CLASS__, 'upload_destination' ] );
  }


  public static function get_report_content( $id )
  {
    $post = get_post( $id );
    $file_path = false;

    if ( 'agent-reports' !== $post->post_type ) {
      return false;
    }

    if ( current_user_can('administrator') || get_current_user_id() === $post->post_author ) {
      if ( $file = get_post_meta( $post->ID, '_attached_file', true ) ) {
        if ( ( $uploads = wp_get_upload_dir() ) && false === $uploads['error'] ) {
          $file_path = $uploads['basedir'] . "/$file";
        }
      }
    }

    if ( $file_path ) {
      $spreadsheet = IOFactory::load( $file_path );
      return $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
    }

    return false;
  }

  public static function get_download_link( $id )
  {
    $post = get_post( $id );
    $url = '';

    if ( 'agent-reports' !== $post->post_type ) {
      return false;
    }

    if ( current_user_can('administrator') || get_current_user_id() === $post->post_author ) {
      if ( $file = get_post_meta( $post->ID, '_attached_file', true ) ) {
        if ( ( $uploads = wp_get_upload_dir() ) && false === $uploads['error'] ) {
          $url = $uploads['baseurl'] . "/$file";
        }
      }
    }

    if ( empty( $url ) ) {
      return false;
    }
  
    return $url;
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
      'post_author'    => $author['user_id'],
      'post_excerpt'   => $excerpt,
      'comment_status' => 'closed',
      'post_type'      => 'agent-reports',
      'post_status'    => 'publish',
      'post_parent'    => 0,
      'post_date'      => "{$author['year']}-{$author['month']}-01"
    ];

    $id = wp_insert_post( $file_data, true );
    if ( ! is_wp_error( $id ) ) {
      update_post_meta( (int) $id, '_attached_file', _wp_relative_upload_path( $file ), true );
      update_post_meta( (int) $id, '_platform', $author['platform'], true );
      update_post_meta( (int) $id, '_isa_name', $author['isa_name'], true );
      update_post_meta( (int) $id, '_isa_id', $author['isa_id'], true );
      update_post_meta( (int) $id, '_date', "{$author['year']}-{$author['month']}", true );
    }
  
    return $id;
  }

  private static function process_report_name( $file_id )
  {
    $name = $_FILES[ $file_id ]['name'];
    $type = $_FILES[ $file_id ]['type'];
    $ext  = pathinfo( $name, PATHINFO_EXTENSION );
    $exploded_name = explode( '-', str_replace( '.' . $ext, '', $name ) );
    $return = [];

    if (
      $type != 'application/vnd.ms-excel' &&
      $type != 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' &&
      $type != 'text/csv'
    ) {
      return [ 'error' => __( "Wrong file type. Use WP Media library instead.", 'report_uploader' ) ];
    }

    if (
      is_array( $exploded_name ) &&
      isset( $exploded_name[2] ) &&
      !empty( trim( $exploded_name[2] ) ) &&
      isset( $exploded_name[3] ) &&
      !empty( trim( $exploded_name[3] ) ) &&
      isset( $exploded_name[4] ) &&
      !empty( trim( $exploded_name[4] ) )
    ) {
      $return['platform'] = trim( $exploded_name[0] );
      $return['isa_name'] = trim( $exploded_name[1] );
      $return['isa_id'] = trim( $exploded_name[2] );
      $return['year'] = trim( $exploded_name[3] );
      $return['month'] = trim( $exploded_name[4] );
    } else {
      return [ 'error' =>__( "Can't parse file name. Double check it.", 'report_uploader' ) ];
    }

    if ( $user_id = report_uploader()->get_agent( $return['isa_id'] ) ) {
      $return['user_id'] = $user_id;
    } else {
      return [ 'error' => __( "Can't find agent by ISA ID => {$return['isa_id']}.", 'report_uploader' ) ];
    }

    return $return;
  }

  public static function unique_report_name( $dir, $name, $ext )
  {
    $filename = md5( str_replace( $ext, '', sanitize_file_name( $name ) ) ) . $ext ;
    $ext  = pathinfo( $filename, PATHINFO_EXTENSION );
    $name = pathinfo( $filename, PATHINFO_BASENAME );
    $number = '';

    if ( $ext ) {
      $ext = '.' . $ext;
    }

    while ( file_exists( $dir . "/$filename" ) ) {
      $new_number = (int) $number + 1;
      if ( '' == "$number$ext" ) {
        $filename = "$filename-" . $new_number;
      } else {
        $filename = str_replace( array( "-$number$ext", "$number$ext" ), '-' . $new_number . $ext, $filename );
      }
      $number = $new_number;
    }

    return $filename;
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
