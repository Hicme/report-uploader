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

    add_action( 'init', [ __CLASS__, 'download_report' ] );
    add_action( 'before_delete_post', [ __CLASS__, 'delete_report_file' ], 10, 1 );
  }



  public static function generate_download_link( $id )
  {
    $post = get_post( $id );

    if (
      $post &&
      current_user_can( 'administrator' ) ||
      ( current_user_can( 'agent' ) &&
      $post->post_author == get_current_user_id() )
    ) {
      $link = add_query_arg( [ 'download' => $id, 'nonce' => wp_create_nonce( 'download-action' ) ], get_site_url() );
      return $link;
    }

    return false;
  }

  public static function get_report_content( $id )
  {
    $file_path = self::get_report_src( $id );

    if ( $file_path ) {
      $spreadsheet = IOFactory::load( $file_path );
      return $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
    }

    return false;
  }

  public static function get_report_src( $id )
  {
    $post = get_post( $id );
    $src = false;

    if ( 'agent-reports' !== $post->post_type ) {
      return false;
    }

    if ( $file = get_post_meta( $post->ID, '_attached_file', true ) ) {
      if ( ( $uploads = wp_get_upload_dir() ) && false === $uploads['error'] ) {
        $src = $uploads['basedir'] . "/$file";
      }
    }

    return $src;
  }

  public static function get_report_url( $id )
  {
    $post = get_post( $id );
    $url = false;

    if ( 'agent-reports' !== $post->post_type ) {
      return false;
    }

    if ( $file = get_post_meta( $post->ID, '_attached_file', true ) ) {
      if ( ( $uploads = wp_get_upload_dir() ) && false === $uploads['error'] ) {
        $url = $uploads['baseurl'] . "/$file";
      }
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

  public static function delete_report_file( $post_id )
  {
    $post = get_post( $post_id );

    if( ! $post || $post->post_type !== 'agent-reports' ) {
      return;
    }

    $file_path = self::get_report_src( $post->ID );

    if ( file_exists( $file_path ) ) {
      unlink( $file_path );
    }
  }

  public static function download_report()
  {
    if (
      isset( $_REQUEST['download'] ) &&
      !empty( $_REQUEST['download'] ) &&
      wp_verify_nonce( $_REQUEST['nonce'], 'download-action' ) &&
      is_user_logged_in()
    ) {
      $id = intval( $_REQUEST['download'] );

      if ( $file = self::get_report_src( $id ) ) {
        $post = get_post( $id );
        $ext = pathinfo( $file, PATHINFO_EXTENSION );
        $name = $post->post_title . ".$ext";

        header( "Content-Description: File Transfer" ); 
        header( "Content-Type: application/octet-stream" ); 
        header( "Content-Disposition: attachment; filename={$name}" ); 

        readfile( $file );
        exit(); 
      }
    }

    return false;
  }
}
