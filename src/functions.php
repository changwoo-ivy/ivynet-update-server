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
function ius_from_assoc( &$assoc, $key, $default = '' ) {
    if ( is_array( $assoc ) ) {
        return isset( $assoc[ $key ] ) ? $assoc[ $key ] : $default;
    } elseif ( is_object( $assoc ) ) {
        return isset( $assoc->{$key} ) ? $assoc->{$key} : $default;
    }

    return $default;
}


function &ius_from_assoc_ref( &$assoc, $key, $default = '' ) {
    $val = $default;
    if ( is_array( $assoc ) && isset( $assoc[ $key ] ) ) {
        $val = &$assoc[ $key ];
    } elseif ( is_object( $assoc ) && isset( $assoc->{$key} ) ) {
        $val = &$assoc->{$key};
    }

    return $val;
}


function ius_from_get( $key, $default = '' ) {
    return ius_from_assoc( $_GET, $key, $default );
}


function ius_from_post( $key, $default = '' ) {
    return ius_from_assoc( $_POST, $key, $default );
}


function ius_from_request( $key, $default = '' ) {
    return ius_from_assoc( $_REQUEST, $key, $default );
}


/**
 * 업데이트 체크 요청 함수
 *
 * @see   ius_check_update_10()
 *
 * @param array  $request $_POST, $_REQUEST 같은 배열.
 * @param string $version 버전. 기본 1.0
 *
 * @return array
 */
function ius_check_update( &$request, $version ) {
    switch ( $version ) {
        case '1.0':
        default:
            return ius_check_update_10( $request );
    }
}


/**
 * @param array  $request     get, request
 * @param string $name        가져올 키 이름
 * @param bool   $assoc_array true 면 associative array 형태로 리턴.
 *
 * @return array|mixed|object
 */
function ius_get_request_param( &$request, $name, $assoc_array = FALSE ) {
    return json_decode( stripslashes( ius_from_assoc( $request, $name ) ), $assoc_array );
}


/**
 * @param $request
 *
 * @return array (
 *           array $response 업데이트 가능한 목록 (
 *             string $id           플러그인 아이디. 임의로 ivynet.co.kr/plugins/{slug} 로 결정
 *             string $slug         플러그인 슬러그. 주로 디렉토리 부분.
 *             string $plugin       메인 파일
 *             string $new_version  새 버전
 *             string $url          플러그인의 소개 페이지
 *             string $package      새 파일
 *             array  $icons        사용하지 않음. 빈 배열.
 *             array  $banners      사용하지 않음. 빈 배열.
 *             array  $banners_rtl  사용하지 않음. 빈 배열.
 *           )
 *           array $no_update    업데이트 하지 않는 플러그인의 페인 파일 목록. 원래의 워드프레스 업데이트 체크 요청과 다름에 유의.
 *           array $translations 사용하지 않음. 빈 배열.
 *         )
 */
function ius_check_update_10( &$request ) {

    $output = array(
        'response'     => array(),
        'translations' => array(),
        'no_update'    => array(),
    );

    $request_plugins = ius_get_request_param( $request, 'plugins', TRUE );
    if ( ! isset( $request_plugins['plugins'] ) ) {
        return $output;
    }

    $plugins = &$request_plugins['plugins'];

    if ( $plugins ) {
        $project_query = new WP_Query( array(
            'post_type'   => 'ius_project',
            'post_status' => 'publish',
            'nopaging'    => TRUE,
            'tax_query'   => array(
                array(
                    'taxonomy' => 'project-status',
                    'field'    => 'slug',
                    'terms'    => 'active',
                ),
            ),
            'meta_key'    => 'ius_plugin_main',
            'orderby'     => 'meta_value',
            'order'       => 'ASC',
            'fields'      => 'ids',
        ) );

        $avail_projects = array();

        foreach ( $project_query->posts as $project_id ) {
            $plugin_main    = get_post_meta( $project_id, 'ius_plugin_main', TRUE );
            $latest_version = get_post_meta( $project_id, 'ius_latest_version', TRUE );

            if ( $plugin_main && $latest_version ) {
                $release                        = get_posts(
                    array(
                        'post_type'   => 'ius_release',
                        'post_status' => 'publish',
                        'post_parent' => $project_id,
                        'meta_key'    => 'ius_release_version',
                        'meta_value'  => $latest_version,
                        'fields'      => 'ids',
                    )
                );
                $avail_projects[ $plugin_main ] = array(
                    'version'    => $latest_version,
                    'project_id' => $project_id,
                    'release_id' => count( $release ) ? $release[0] : 0,
                );
            }
        }

        $targeted = array_flip( array_intersect( array_keys( $plugins ), array_keys( $avail_projects ) ) );

        // compare server's latest, and plugins data.
        foreach ( $plugins as $main_file => $plugin ) {

            if ( ! isset( $targeted[ $main_file ] ) ) {
                $output['no_update'][] = $main_file;
                continue;
            }

            $latest_version = $avail_projects[ $main_file ]['version'];
            $plugin_version = $plugins[ $main_file ]['Version'];
            $slug           = ius_get_plugin_identifier( $main_file );

            if ( version_compare( $latest_version, $plugin_version, '>' ) ) {

                $attachments = get_attached_media( 'application/zip', $avail_projects[ $main_file ]['release_id'] );

                if ( ! $attachments ) {
                    continue;
                }

                $attach_ids = array_keys( $attachments );
                $package    = wp_get_attachment_url( $attach_ids[0] );

                $output['response'][ $main_file ] = (object) array(
                    'id'          => "ivynet.co.kr/plugins/{$slug}",
                    'slug'        => $slug,
                    'plugin'      => $main_file,
                    'new_version' => $latest_version,
                    'url'         => get_permalink( $avail_projects[ $main_file ]['project_id'] ),
                    'package'     => $package,
                    'icons'       => array(),
                    'banners'     => array(),
                    'banners_rtl' => array(),
                );
            } else {
                $output['no_update'][] = $main_file;
            }
        }
    }

    return $output;
}


/**
 * Version compare function
 *
 * @used-by ius_get_project_releases()
 *
 * @param string $l
 * @param string $r
 *
 * @return int
 */
function ius_version_cmp_desc( $l, $r ) {
    return version_compare( $r, $l );
}


/**
 * Retrieve releases from a project
 *
 * @param int $project_id
 *
 * @return array (
 *               * string $version
 *               * int    $post_id
 *               )
 */
function ius_get_project_releases( $project_id ) {

    $output = array();

    $query = new WP_Query( array(
        'post_type'   => 'ius_release',
        'post_status' => array( 'publish', 'draft' ),
        'post_parent' => $project_id,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'nopaging'    => TRUE,
    ) );

    if ( $query->have_posts() ) {

        $versions = array();

        foreach ( $query->posts as $post ) {
            $versions[] = get_post_meta( $post->ID, 'ius_release_version', TRUE );
        }

        uasort( $versions, 'ius_version_cmp_desc' );

        foreach ( $versions as $idx => $version ) {
            $output[] = array(
                'version' => $version,
                'post_id' => $query->posts[ $idx ]->ID,
                'status'  => $query->posts[ $idx ]->post_status,
            );
        }
    }

    return $output;
}


/**
 * Get slug of the plugin
 *
 * @param $plugin_main
 *
 * @return null|string
 */
function ius_get_plugin_identifier( $plugin_main ) {

    if ( ! $plugin_main ) {
        return NULL;
    }

    $exploded = explode( '/', $plugin_main );
    if ( count( $exploded ) == 0 ) {
        return NULL;
    } elseif ( count( $exploded ) == 1 ) {
        $dot_pos   = strrpos( $exploded[0], '.' );
        $file_name = substr( $exploded[0], 0, $dot_pos === FALSE ? NULL : $dot_pos );
        return sanitize_key( $file_name );
    }

    return sanitize_key( $exploded[0] );
}


/**
 * @param $hook_identifier
 *
 * @return void
 */
function ius_handle_webhook( $hook_identifier ) {
    switch ( $hook_identifier ) {
        case 'github':
            require_once( __DIR__ . '/webhook/class-ius-github-webhook.php' );

            try {
                $github = new IUS_Github_Webhook();
                $result = $github->handle_webhook();
            } catch ( Exception $e ) {
                $result = new WP_Error( $e->getCode(), $e->getMessage() );
            }

            if ( is_wp_error( $result ) ) {
                error_log( 'Error occurred: ' . $result->get_error_message() );
                wp_send_json_error( $result );
            }

            wp_send_json_success( $result );
            break;

        case 'default':
            die( 0 );

    }
}


/**
 * @param string $repo_name
 *
 * @return WP_Post|NULL
 */
function ius_get_project_by_repo_name( $repo_name ) {
    $posts = get_posts(
        array(
            'post_type'   => 'ius_project',
            'post_status' => array( 'publish' ),
            'meta_key'    => 'ius_github_repository',
            'meta_value'  => $repo_name,
        )
    );

    if ( ! $posts ) {
        return NULL;
    }

    if ( count( $posts ) > 1 ) {
        trigger_error( "Repository name '{$repo_name}' is duplicated. Please check the project posts!" );
    }

    return $posts[0];
}


function ius_save_released( $project_id, $version, $url ) {

    $slug = ius_get_plugin_identifier( get_post_meta( $project_id, 'ius_plugin_main', TRUE ) );

    if ( ! $slug ) {
        return new WP_Error( 'wrong_project',
            'Project ID ' . $project_id . ' has no meta value of key \'ius_plugin_main\'' );
    }

    if ( ! function_exists( 'download_url' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
    }

    error_log( "Download a file from: {$url}" );

    /** @var string|WP_Error $temp_file */
    $temp_file = download_url( $url );

    if ( is_wp_error( $temp_file ) ) {
        return $temp_file;
    }

    $upload_dir  = wp_upload_dir();
    $release_dir = $upload_dir['basedir'] . '/ius/' . $slug;
    $file_name   = "{$release_dir}/{$slug}-{$version}.zip";

    if ( ! file_exists( $release_dir ) ) {
        mkdir( $release_dir, 0777, TRUE );
    }

    if ( ! copy( $temp_file, $file_name ) ) {
        return new WP_Error( 'move_fail', "Failed to copy file from '$temp_file' to '$file_name'." );
    }

    @unlink( $temp_file );

    return $file_name;


}
