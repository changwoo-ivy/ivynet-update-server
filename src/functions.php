<?php
/**
 * functions.php
 *
 * common functions for the plugin.
 */

/**
 * @param string $template_name
 * @param array  $contexts
 * @param bool   $echo
 *
 * @return string|null
 */
function ius_render_template( $template_name, $contexts = array(), $echo = TRUE ) {

    $template_path = IUS_DIR . '/src/templates/' . $template_name;

    if ( ! $echo ) {
        ob_start();
    }

    if ( $contexts && is_array( $contexts ) ) {
        extract( $contexts );
    }

    /** @noinspection PhpIncludeInspection */
    include( $template_path );

    if ( ! $echo ) {
        return ob_get_clean();
    }

    return NULL;
}


/**
 * @param object|array $assoc
 * @param string       $key
 * @param mixed        $default
 *
 * @return mixed
 */
function from_assoc( &$assoc, $key, $default = '' ) {
    if ( is_array( $assoc ) ) {
        return isset( $assoc[ $key ] ) ? $assoc[ $key ] : $default;
    } elseif ( is_object( $assoc ) ) {
        return isset( $assoc->{$key} ) ? $assoc->{$key} : $default;
    }

    return $default;
}


function from_get( $key, $default = '' ) {
    return from_assoc( $_GET, $key, $default );
}


function from_post( $key, $default = '' ) {
    return from_assoc( $_POST, $key, $default );
}


function from_request( $key, $default = '' ) {
    return from_assoc( $_REQUEST, $key, $default );
}