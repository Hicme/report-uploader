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

    if ( $_REQUEST['short'] ) {
      // Short form response - attachment ID only.
      echo $id;
    }
  }

}
