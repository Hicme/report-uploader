<?php

namespace system;

class Front
{
  private static $paged = 0;
  private static $query;

  public static function init()
  {
    add_filter( 'template_include', [ __CLASS__, 'secure_reports_page' ], 99 );

    add_action( 'init', [ __CLASS__, 'setup_pagination' ] );

    add_action( 'wp', [ __CLASS__, 'setup_front_reports' ] );

    add_action( 'print_front_table', [ __CLASS__, 'front_table' ] );
    add_action( 'print_front_table_pagination', [ __CLASS__, 'front_pagination' ] );
  }

  public static function secure_reports_page( $original_template )
  {
    if ( is_page( 'reports' ) ) {
      if ( is_user_logged_in() ) {
        if ( current_user_can('administrator') || current_user_can('agent') ) {
          $original_template = P_PATH . 'templates/front-report-page.php';
        } else {
          $original_template = P_PATH . 'templates/no-permission-page.php';
        }
      } else {
        $original_template = P_PATH . 'templates/not-logged-page.php';
      }
    }


    return $original_template;
  }

  public static function setup_pagination()
  {
    if ( is_user_logged_in() ) {
      if ( isset( $_GET['paginated'] ) && ! empty( $_GET['paginated'] ) ) {
        self::$paged = intval( $_GET['paginated'] );
      }
    }
  }

  public static function setup_front_reports()
  {
    if ( is_user_logged_in() && ( current_user_can('administrator') || current_user_can('agent') ) ) {
      $args = array(
        'posts_per_page' => 20,
        'order' => 'DESC',
        'orderby' => 'date',
        'post_type' => 'agent-reports',
        'post_status' => 'publish',
      );

      if ( current_user_can('agent') ) {
        $args['author'] = get_current_user_id();
      }

      self::$query = new \WP_Query( $args );
    } else {
      self::$query = false;
    }
  }

  public static function front_table()
  {
    if ( self::$query ) {
      foreach ( self::$query->posts as $report ) {
        ?>
          <tr>
            <td><?php echo get_post_meta( $report->ID, '_platform', true ); ?></td>
            <td><?php echo get_post_meta( $report->ID, '_isa_name', true ); ?></td>
            <td><?php echo get_post_meta( $report->ID, '_isa_id', true ); ?></td>
            <td><?php echo get_post_meta( $report->ID, '_date', true ); ?></td>
            <td><a href="<?php echo report_uploader()->download_report( $report->ID ); ?>">Download</a></td>
          </tr>
        <?php
      }
    }
  }

  public static function front_pagination()
  {

  }

}
