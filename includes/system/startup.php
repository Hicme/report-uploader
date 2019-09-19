<?php

namespace system;

final class StartUp
{

  use \system\Instance;

  public function __get( $key )
  {
    if ( in_array( $key, array( 'upload', 'get_agent' ), true ) ) {
      return $this->$key();
    }
  }

  public function __construct()
  {

    $this->includes();
    do_action( 'p_loaded' );

  }



  public function is_request( $type )
  {
    switch ( $type ) {
      case 'admin':
        return is_admin();
      case 'ajax':
        return defined( 'DOING_AJAX' );
      case 'cron':
        return defined( 'DOING_CRON' );
      case 'frontend':
        return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
    }
  }

  private function includes()
  {
    \system\User::init();
    \system\Files::init();
    \system\Post_Types::init();

    if( $this->is_request( 'cron' ) ){
      new \system\Cron();
    }

    if( $this->is_request( 'ajax' ) ){
      new \system\Ajax();
    }

    if( $this->is_request( 'admin' ) ){
      new \admin\Admin_Startup();
    }
  }


  public function upload( $file_id )
  {
    return \system\Files::process_report_upload( $file_id );
  }

  public function get_agent( $isa_id )
  {
    return \system\User::find_agent_by_id( $isa_id );
  }

}
