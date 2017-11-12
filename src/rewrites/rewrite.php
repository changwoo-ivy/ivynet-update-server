<?php
/**
 * rewrite.php
 *
 * handle custom plugin update check request.
 */

add_action( 'init', 'ius_rewrite_rules' );

function ius_rewrite_rules() {
   add_rewrite_rule( '^check-update/?$', 'index.php?ius=check-update', 'top' );
}


add_filter( 'query_vars', 'ius_query_vars' );

function ius_query_vars( $query_vars ) {
    $query_vars[] = 'ius';
    return $query_vars;
}


add_action( 'template_redirect', 'ius_template_redirect' );

function ius_template_redirect() {

    global $wp;

    switch ( from_assoc( $wp->query_vars, 'ius', '' ) ) {
        case 'check-update':
            exit;
    }
}
