<?php
/**
 *  custom user meta keys:
 *      ius_github_user
 *      ius_github_personal_access_token
 */

add_action( 'show_user_profile', 'ius_user_profile' );

add_action( 'edit_user_profile', 'ius_user_profile' );

function ius_user_profile( $profileuser ) {
    ius_render_template( 'profile/user-profile.php', array( 'profileuser' => &$profileuser ) );
}


add_action( 'personal_options_update', 'ius_update_profile' );

add_action( 'edit_user_profile_update', 'ius_update_profile' );

function ius_update_profile( $user_id ) {
    if ( wp_verify_nonce( ius_from_post( 'ius-nonce' ), 'ius_profile_98^25bse$#' ) ) {
        update_user_meta(
            $user_id,
            'ius_github_user',
            sanitize_text_field( ius_from_post( 'ius_github_user' ) )
        );

        update_user_meta(
            $user_id,
            'ius_github_personal_access_token',
            sanitize_text_field( ius_from_post( 'ius_github_personal_access_token' ) )
        );
    }
}