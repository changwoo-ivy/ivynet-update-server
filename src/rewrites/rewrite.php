<?php
/**
 * rewrite.php
 *
 * handle custom plugin update check request.
 */

add_action( 'init', 'ius_rewrite_rules' );

function ius_rewrite_rules() {
    add_rewrite_rule( '^plugins/update-check/(.+)/?$', 'index.php?ius-action=check-update&ius-version=$matches[1]',
        'top' );
}


add_filter( 'query_vars', 'ius_query_vars' );

function ius_query_vars( $query_vars ) {
    $query_vars[] = 'ius-action';
    $query_vars[] = 'ius-version';
    return $query_vars;
}


add_action( 'template_redirect', 'ius_template_redirect' );

function ius_template_redirect() {

    global $wp;

    switch ( ius_from_assoc( $wp->query_vars, 'ius-action', '' ) ) {
        case 'check-update':
            $version = ius_from_assoc( $wp->query_vars, 'ius-version', '1.0' );
            ius_check_update( $_POST, $version );
            exit;
    }
}
