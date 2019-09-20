<?php

namespace system;

class Ajax
{

  public function __construct()
  {
    add_action( 'wp_ajax_process_upload_report', [ $this, 'process_upload' ] );
  }



  public function process_upload()
  {
    if ( ! current_user_can( 'manage_areports' ) ) {
      wp_die( __( 'Sorry, you are not allowed to upload files.' ) );
    }

    check_admin_referer( 'media-form' );

    // $id = media_handle_upload( 'async-upload', 0 ); // - this need to be inspect

    $id = report_uploader()->upload( 'async-upload' );

    if ( is_wp_error( $id ) ) {
      echo '<div class="error-div error">
      <button type="button" class="dismiss button-link" onclick="jQuery(this).parents(\'div.media-item\').slideUp(200, function(){jQuery(this).remove();});">' . __( 'Dismiss' ) . '</button>
      <strong>' . sprintf( __( '&#8220;%s&#8221; has failed to upload.' ), esc_html( $_FILES['async-upload']['name'] ) ) . '</strong><br />' .
      esc_html( $id->get_error_message() ) . '</div>';
      exit;
    }

    echo '<div class="error-div report-success">';
    echo '<img class="pinkynail" src="/wp-includes/images/media/text.png" alt="">';
    echo '<a class="edit-attachment" href="' . esc_url( get_edit_post_link( $id ) ) . '" target="_blank">' . _x( 'Edit', 'media item' ) . '</a>';

    $post  = get_post( $id );
    $file  = get_attached_file( $post->ID );
    $title = $post->post_title ? $post->post_title : wp_basename( $file );
    echo '<div class="filename new"><span class="title">' . esc_html( wp_html_excerpt( $title, 60, '&hellip;' ) ) . '</span></div></div>';
    exit();
  }

}
