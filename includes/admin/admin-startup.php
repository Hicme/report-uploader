<?php

namespace admin;

class Admin_Startup
{

  public function __construct()
  {
    add_action( 'init', [ $this, 'admin_inits' ] );
    add_action( 'admin_menu', [ $this, 'admin_menus' ], 9 );
    add_action( 'admin_head', [ $this, 'admin_menus_reorder' ] );
  }

  public function admin_inits()
  {
    add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
  }

  public function enqueue_assets()
  {
    wp_register_style( 'select2css', '//cdnjs.cloudflare.com/ajax/libs/select2/3.4.8/select2.css', false, P_VERSION, 'all' );
    wp_enqueue_style( 'select2css' );
    wp_enqueue_style( 'pstyles-admin', P_URL_FOLDER . 'assets/css/admin_styles.css', [], P_VERSION, 'all' );

    wp_register_script( 'select2', '//cdnjs.cloudflare.com/ajax/libs/select2/3.4.8/select2.js', array( 'jquery' ), P_VERSION, true );
    wp_enqueue_script( 'select2' );
    wp_enqueue_script( 'pscripts-admin', P_URL_FOLDER . 'assets/js/admin_js.js', [], P_VERSION, true );

    wp_localize_script('pscripts-admin', 'FUPAJAX',
      [
        'url' => admin_url('admin-ajax.php'),
        'ajax_nonce' => wp_create_nonce('qmw-admin-ajax-nonce'),
      ]
    );
  }

  public function admin_menus_reorder()
  {
    global $submenu;

    if( isset( $submenu['agent-reports'] ) ){
      unset( $submenu['agent-reports'][0] );
    }
  }

  public function admin_menus()
  {
    add_menu_page( __( 'Agent Reports', 'report_uploader' ), __( 'Agent Reports', 'report_uploader' ), 'manage_areports', 'agent-reports', null, 'dashicons-book-alt', '45' );
    add_submenu_page( 'agent-reports', __( 'Upload Reports', 'report_uploader' ), __( 'Upload', 'report_uploader' ), 'manage_areports', 'upload-reports', [ $this, 'upload_reports_page' ] );
  }

  public function upload_reports_page()
  {
    wp_enqueue_script( 'plupload-handlers' );
    echo get_template_html( P_PATH . 'templates/admin/upload-reports.php' );
  }
}
