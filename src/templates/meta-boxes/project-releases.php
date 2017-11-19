<?php

global $post;

$releases = ius_get_project_releases( $post->ID );

?>

<?php if ( ! $releases ) : ?>
    <?php esc_html_e( 'No releases yet.', 'ius' ); ?>
<?php else : ?>
  <ul>
      <?php foreach ( $releases as $release ) : ?>
        <li>
          <a href="<?php echo esc_url( get_edit_post_link( $release['post_id'] ) ); ?>" target="_blank"><?php echo esc_html( $release['version'] ); ?></a>
          <span class="version-description">
            <?php echo esc_html( get_post_field( 'post_excerpt', $release['post_id'] ) ); ?>
          </span>
        </li>
      <?php endforeach; ?>
  </ul>
<?php endif; ?>

<?php wp_nonce_field( 'project-releases-nonce-8892', 'project-releases-nonce' ); ?>
