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

    public function handle_webhook() {

        $this->initialize();
        $this->payload = $this->get_payload();

        error_log( print_r( $_SERVER, TRUE ) );
        error_log( print_r( $this->payload, TRUE ) );

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

            case 'release':
                return $this->handle_release();
                break;

            default:
                return new WP_Error( 'handle_webhook', 'Unknown event: ' . $event );
        }
    }

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
            error_log( "Tag '{$tag}' of repository '{$repo_full_name}' is already created. Just return its ID." );

            return $release[0]->ID;
        }

        $new_release_id = wp_insert_post(
            array(
                'post_title'  => sprintf( __( '%s v%s', 'ius' ), $project->post_title, $tag ),
                'post_type'   => 'ius_release',
                'post_status' => 'draft',
                'post_parent' => $project->ID,
                'meta_input'  => array( 'ius_release_version' => $tag ),
            )
        );

        if ( is_wp_error( $new_release_id ) ) {
            return $new_release_id;
        }

        error_log( 'handle_tag_create() finished' );

        return $new_release_id;
    }

    private function handle_tag_delete() {
        error_log( 'handle_tag_delete() invoked' );

        $repo_full_name = $this->get_repo_full_name( $this->payload );
        $tag            = $this->get_tag_name( $this->payload );
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

        error_log( 'handle_tag_delete() finished' );

        return $d;
    }

    private function handle_release() {

        error_log( 'handle_release() invoked' );

        $release        = &ius_from_assoc_ref( $this->payload, 'release', array() );
        $repository     = &ius_from_assoc_ref( $this->payload, 'repository', array() );
        $tag            = ius_from_assoc( $release, 'tag_name', '' );
        $zipall_url     = ius_from_assoc( $release, 'zipball_url' );
        $action         = ius_from_assoc( $this->payload, 'action' );
        $repo_full_name = $this->get_repo_full_name( $this->payload );

        if ( ! $repo_full_name || $action != 'published' || ! $tag || ! $zipall_url ) {
            return new WP_Error(
                'handle_event',
                "Handle event \'release\' could not proceed because it is not a valid request. Repository: \'{$repo_full_name}\', Tag: \'{$tag}\', Action: \'{$action}\', Download URL: {$zipall_url}"
            );
        }

        $project = ius_get_project_by_repo_name( $repo_full_name );

        if ( ! $project ) {
            return new WP_Error(
                'handle_event',
                "Repository '{$repo_full_name}' is not a proper project. Did you forget to create a post of it?"
            );
        }

        $private = intval( ius_from_assoc( $repository, 'private' ) );

        if ( ! $private ) {
            $release_id = ius_save_released( $project->ID, $tag, $zipall_url );
        } else {
            error_log( 'Downloading is skipped because the repository is private.' );
            $release_id = NULL;
        }

        error_log( 'handle_release() finished' );

        return $release_id;
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
}
