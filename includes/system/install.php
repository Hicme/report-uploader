<?php

namespace system;

class Install{

  public static function install_depencies()
  {

    if ( ! is_blog_installed() ) {
      return;
    }

    self::create_roles();
    self::create_capability();
    self::create_cron();
  }



  private static function create_capability()
  {
    global $wp_roles;

    if ( ! class_exists( 'WP_Roles' ) ) {
      return;
    }

    if ( ! isset( $wp_roles ) ) {
      $wp_roles = new \WP_Roles();
    }

    $capabilities = self::get_capabilities();

    foreach ( $capabilities as $cap_group ) {
      foreach ( $cap_group as $cap ) {
        $wp_roles->add_cap( 'administrator', $cap );
      }
    }
  }

  private static function create_roles()
  {
    global $wp_roles;

    if ( ! class_exists( 'WP_Roles' ) ) {
      return;
    }
    
    if ( ! isset( $wp_roles ) ) {
      $wp_roles = new WP_Roles();
    }

    add_role(
      'agent',
      'Agent',
      array(
        'read' => true,
      )
    );

  }

  private static function get_capabilities()
  {
    $capabilities = [];

    $capabilities[ 'core' ] = [
        'manage_areports',
    ];

    $capability_types = [ 'agent_report' ];

    foreach ( $capability_types as $capability ){
      $capabilities[ $capability ] = [
        "edit_{$capability}",
        "read_{$capability}",
        "delete_{$capability}",
        "edit_{$capability}s",
        "edit_others_{$capability}s",
        "publish_{$capability}s",
        "read_private_{$capability}s",
        "delete_{$capability}s",
        "delete_private_{$capability}s",
        "delete_published_{$capability}s",
        "delete_others_{$capability}s",
        "edit_private_{$capability}s",
        "edit_published_{$capability}s",
        
        "manage_{$capability}_terms",
        "edit_{$capability}_terms",
        "delete_{$capability}_terms",
        "assign_{$capability}_terms",
      ];
    }

    return $capabilities;
  }

  private static function create_cron()
  {
    wp_clear_scheduled_hook( 'p_try_update_system' );

    wp_schedule_event( time(), 'daily', 'p_try_update_system' );
  }

}
