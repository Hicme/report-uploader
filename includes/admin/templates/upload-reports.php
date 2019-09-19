<?php
  $form_class = 'media-upload-form type-form validate';

  if ( get_user_setting( 'uploader' ) || isset( $_GET['browser-uploader'] ) ) {
    $form_class .= ' html-uploader';
  }
?>

<div class="wrap">
    <h1><?php echo get_admin_page_title(); ?></h1>
    <form enctype="multipart/form-data" method="post" action="<?php echo admin_url( 'admin-ajax.php' ); ?>" class="<?php echo esc_attr( $form_class ); ?>" id="file-form">
      <?php media_upload_form(); ?>

      <script type="text/javascript">
        var post_id = 0, shortform = 3;
      </script>

      <input type="hidden" name="post_id" id="post_id" value="0" />
      <?php wp_nonce_field( 'reports-upload' ); ?>
      <div id="media-items" class="hide-if-no-js"></div>
    </form>
</div>
