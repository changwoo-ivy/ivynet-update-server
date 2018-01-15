<?php

/**
 * Github 웹 훅 이벤트에 대응.
 *
 * 대응 가능한 events:
 * - create:  태그가 생성되었을 때, 릴리즈 예비로 생각하여 draft 포스트로 생성.
 * - delete:  해당 태그가 삭제되었을 때, 관련된 포스트를 삭제.
 * - release: 릴리즈 이벤트를 수신하면, 해당 포스트를 publish.
 *
 * Class IUS_Github_Webhook
 *
 * @see https://developer.github.com/webhooks/
 * @see https://developer.github.com/v3/repos/hooks/
 * @see https://developer.github.com/v3/activity/events/types/#releaseevent
 */
class IUS_Github_Webhook {

    private $payload;

    /**
     * IUS_Github_Webhook constructor.
     *
     * @throws Exception
     */
    public function __construct() {
        if ( ! extension_loaded( 'hash' ) ) {
            throw new Exception( 'Missing \'hash\' extension to check to secret code validity.' );
        }
    }

    private function get_event() {
        return ius_from_assoc( $_SERVER, 'HTTP_X_GITHUB_EVENT' );
    }

    private function get_payload() {
        return ius_get_request_param( $_POST, 'payload', TRUE );
    }

    /**
     * @param string $signature '{method}={hash}' string, like: sha1=665037bbb....
     * @param string $key
     *
     * @return bool
     * @throws Exception
     */
    private function verify_signature( $signature, $key ) {

        if ( ! $key ) {
            return TRUE;
        }

        $pos    = strpos( $signature, '=' );
        $method = substr( $signature, 0, $pos );
        $hash   = substr( $signature, $pos + 1 );

        if ( ! in_array( $method, hash_algos() ) ) {
            throw new Exception( "Hash algorithm {$method} is not supported" );
        }

        return hash_equals( $hash, hash_hmac( $method, file_get_contents( 'php://input' ), $key ) );
    }

    private function initialize() {

        $this->payload = NULL;
    }

    /**
     * @return false|int|null|WP_Error|WP_Post
     * @throws Exception
     */
    public function handle_webhook() {

        $this->initialize();
        $this->payload = $this->get_payload();

//        error_log( print_r( $_SERVER, TRUE ) );
//        error_log( print_r( $this->payload, TRUE ) );

        // check secret
        $project = ius_get_project_by_repo_name( $this->get_repo_full_name( $this->payload ) );

        if ( ! $project ) {
            return new WP_Error( 'handle_webhook', 'project now found' );
        }

        $secret    = get_post_meta( $project->ID, 'ius_github_webhook_secret', TRUE );
        $signature = ius_from_assoc( $_SERVER, 'HTTP_X_HUB_SIGNATURE' );

        if ( $signature && ! $this->verify_signature( $signature, $secret ) ) {
            return new WP_Error( 'signature_validation', 'Signature mismatch!' );
        }

        $event = $this->get_event();
//        error_log( "event: $event" );

        switch ( $event ) {
            case 'create':
                return $this->handle_tag_create();
                break;

            case 'delete':
                return $this->handle_tag_delete();
                break;

            default:
                return new WP_Error( 'handle_webhook', 'Unknown event: ' . $event );
        }
    }

    /**
     * @return int|null|WP_Error
     */
    private function handle_tag_create() {

        error_log( 'handle_tag_create() invoked' );

        $repo_full_name = $this->get_repo_full_name( $this->payload );
        $tag            = $this->get_tag_name( $this->payload );
        $ref_type       = ius_from_assoc( $this->payload, 'ref_type' );

        if ( $ref_type != 'tag' ) {
            return NULL;
        }

        if ( ! $repo_full_name || ! $tag ) {
            return new WP_Error(
                'handle_event',
                "Handle event \'create\' could not proceed because it is not a tag creation event. Repository: '{$repo_full_name}', Tag: '{$tag}'"
            );
        }

        $project = ius_get_project_by_repo_name( $repo_full_name );

        if ( ! $project ) {
            return new WP_Error(
                'handle_event',
                "Repository '{$repo_full_name}' is not a proper project. Did you forget to create a post of it?"
            );
        }

        $release = get_posts(
            array(
                'post_type'   => 'ius_release',
                'post_status' => 'publish',
                'post_parent' => $project->ID,
                'meta_key'    => 'ius_release_version',
                'meta_value'  => $tag,
            )
        );

        if ( $release ) {
            error_log( "Tag '{$tag}' of repository '{$repo_full_name}' is already created. Replace the post." );
            $attachments = get_attached_media( 'application/zip', $release[0]->ID );
            foreach ( $attachments as $attachment ) {
                wp_delete_attachment( $attachment->ID, TRUE );
            }
            wp_delete_post( $release[0]->ID, TRUE );
        }

        $new_release_id = wp_insert_post(
            array(
                'post_title'  => sprintf( __( '%s v%s', 'ius' ), $project->post_title, $tag ),
                'post_type'   => 'ius_release',
                'post_status' => 'publish',
                'post_parent' => $project->ID,
                'meta_input'  => array( 'ius_release_version' => $tag ),
            )
        );

        if ( is_wp_error( $new_release_id ) ) {
            return $new_release_id;
        }

        $is_private = (bool) $this->payload['repository']['private'];

        if ( $is_private ) {
            $user          = $this->get_user( $project->ID );
            $private_token = $this->get_private_token( $project->ID );
        } else {
            $user          = NULL;
            $private_token = NULL;
        }

        if ( $is_private && ! ( $private_token && $user ) ) {
            error_log( 'Github access information not provided for private repository. Skip downloading the zip archive.' );
        } else {
            $file_name = ius_save_released(
                $project->ID,
                $tag,
                "https://github.com/{$repo_full_name}/archive/{$tag}.zip",
                $user,
                $private_token
            );

            if ( $file_name ) {

                $plugin_main = get_post_meta( $project->ID, 'ius_plugin_main', TRUE );

                if ( ! $plugin_main ) {
                    return new WP_Error( 'handle_event', 'invalid meta value: ius_plugin_main' );
                }

                if ( ( $plugin_exploded = explode( '/', $plugin_main ) ) && ! ius_rename_zip_root_dir( $file_name,
                        $plugin_exploded[0],
                        $tag ) ) {
                    return new WP_Error( 'handle_event', 'zip folder renaming failed' );
                }

                $attach_id = wp_insert_attachment(
                    array(
                        'post_mime_type' => 'application/zip',
                        'post_title'     => basename( $file_name ),
                        'post_status'    => 'inherit',
                    ),
                    $file_name,
                    $new_release_id
                );

                if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
                    /** @noinspection PhpIncludeInspection */
                    require_once( get_home_path() . '/wp-admin/includes/image.php' );
                }

                wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $file_name ) );
            }
        }

        ius_refresh_latest_release( $project->ID );

        error_log( 'handle_tag_create() finished' );

        return $new_release_id;
    }

    /**
     * @return false|null|WP_Error|WP_Post
     */
    private function handle_tag_delete() {
        error_log( 'handle_tag_delete() invoked' );

        $repo_full_name = $this->get_repo_full_name( $this->payload );
        $tag            = ius_from_assoc( $this->payload, 'ref' );
        $ref_type       = ius_from_assoc( $this->payload, 'ref_type' );

        if ( $ref_type != 'tag' ) {
            return NULL;
        }

        if ( ! $repo_full_name || ! $tag ) {
            return new WP_Error(
                'handle_event',
                "Handle event 'delete' could not proceed because it is not a tag deletion event. Repository: '{$repo_full_name}', Tag: '{$tag}'"
            );
        }

        $project = ius_get_project_by_repo_name( $repo_full_name );

        if ( ! $project ) {
            return new WP_Error(
                'handle_event',
                "Repository '{$repo_full_name}' is not a proper project. Did you forget to create a post of it?"
            );
        }

        $release = get_posts(
            array(
                'post_type'   => 'ius_release',
                'post_parent' => $project->ID,
                'meta_key'    => 'ius_release_version',
                'meta_value'  => $tag,
                'fields'      => 'ids',
            )
        );

        $d = NULL;

        // NOTE: Do we have to care about any 'trash' status posts?
        foreach ( $release as $r ) {

            error_log( "Release post {$r} is being deleted." );

            // also delete attachments
            $attachments = get_attached_media( 'application/zip', $r );
            foreach ( $attachments as $attachment ) {
                error_log( "Attachment '{$attachment->ID}' of release '{$r}' is also deleted." );
                wp_delete_attachment( $attachment->ID, TRUE );
            }

            $d = wp_delete_post( $r, TRUE );
        }

        ius_refresh_latest_release( $project->ID );

        error_log( 'handle_tag_delete() finished' );

        return $d;
    }

    private function get_repo_full_name( &$payload ) {
        $repository = &ius_from_assoc_ref( $payload, 'repository', array() );

        return ius_from_assoc( $repository, 'full_name' );
    }

    private function get_tag_name( &$payload ) {
        $comp = explode( '/', ius_from_assoc( $payload, 'ref' ) );

        if ( count( $comp ) >= 3 && $comp[0] == 'refs' && $comp[1] == 'tags' ) {
            return $comp[2];
        }

        return $comp[0];
    }

    private function get_user( $project_id ) {

        $token_owner_id = get_post_meta( $project_id, 'ius_github_token_user_id', TRUE );

        if ( ! $token_owner_id ) {
            return NULL;
        }

        $user = get_user_meta( $token_owner_id, 'ius_github_user', TRUE );

        return $user ? $user : NULL;
    }

    private function get_private_token( $project_id ) {

        $token_owner_id = get_post_meta( $project_id, 'ius_github_token_user_id', TRUE );

        if ( ! $token_owner_id ) {
            return NULL;
        }

        $token = get_user_meta( $token_owner_id, 'ius_github_personal_access_token', TRUE );

        return $token ? $token : NULL;
    }
}
