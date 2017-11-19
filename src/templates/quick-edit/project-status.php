<div class="wp-clearfix"></div>
<fieldset class="inline-edit-col-left">
  <div class="inline-edit-col">
    <label>
      <span class="title"><?php esc_html_e( 'Proj. Stat.', 'ius' ); ?></span>
      <span class="input-text-wrap">
        <?php
        global $post;
        $terms = wp_get_post_terms( $post->ID, 'project-status' );
        wp_dropdown_categories(
            array(
                'taxonomy'    => 'project-status',
                'hide_empty'  => 0,
                'name'        => 'project-status',
                'value_field' => 'slug',
                '',
            )
        );
        wp_nonce_field( 'nonce-project-status-1876', 'project-status-nonce', FALSE, TRUE );
        ?>
      </span>
    </label>
  </div>
</fieldset>