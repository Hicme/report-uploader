<?php

namespace system;

class User
{
  public static function init()
  {
    add_action( 'personal_options_update', [ __CLASS__, 'save_isa' ] );
    add_action( 'edit_user_profile_update', [ __CLASS__, 'save_isa' ] );

    add_action( 'show_user_profile', [ __CLASS__, 'show_isa' ] );
    add_action( 'edit_user_profile', [ __CLASS__, 'show_isa' ] );
  }

  public static function save_isa( $user_id )
  {
    if ( ! current_user_can( 'edit_user', $user_id ) ) { 
      return false; 
    }

    if ( isset( $_POST['_isa_id'] ) ) {
      update_user_meta( $user_id, '_isa_id', sanitize_text_field( $_POST['_isa_id'] ) );
    }
  }

  public static function show_isa( $user )
  {
    if ( ! in_array( 'agent', $user->roles ) ) {
      return false;
    }
    ?>
      <h3><?php _e( 'Agent Information', 'report_uploader' ); ?></h3>
      <table class="form-table" role="isa-id">
        <tbody>
          <tr class="user-rich-editing-wrap">
            <th scope="row">
              ISA ID
            </th>
            <td>
              <?php
                render_input([
                  'id'    => 'isa_id',
                  'label' => '',
                  'type'  => 'text',
                  'name'  => '_isa_id',
                  'class' => 'regular-text',
                  'value' => get_user_meta( $user->ID, '_isa_id', true ),
                ]);
              ?>
            </td>
          </tr>
        </tbody>
      </table>
    <?php
  }
}
