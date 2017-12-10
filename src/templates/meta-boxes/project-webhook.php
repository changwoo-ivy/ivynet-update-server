<table class="form-table">
  <tr>
    <th>
      <label for="ius_github_repository">
          <?php _e( 'Github Repository', 'ius' ); ?>
      </label>
    </th>
    <td>
        <?php $repository = get_post_meta( get_the_ID(), 'ius_github_repository', TRUE ); ?>
      <input type="text"
             class="text"
             id="ius_github_repository"
             name="ius_github_repository"
             value="<?php echo esc_attr( $repository ); ?>"/>
        <?php if ( $repository ) : ?>
          <br/>
          <a href="https://github.com/<?php echo esc_attr( $repository ); ?>/" target="_blank"><?php esc_html_e( 'Visit Repository',
                  'ius' ); ?></a>
            <?php esc_html_e( '(You may need authorization if the repository is private.)', 'ius' ); ?>
        <?php endif; ?>
      <br/>
      <span class="description">
        <?php esc_html_e( 'Your github repository. Input like &lt;your-name&gt;/&lt;repository&gt;', 'ius' ); ?>
      </span>
    </td>
  </tr>
  <tr>
    <th>
      <label for="ius_github_webhook_secret">
          <?php esc_html_e( 'Webhook Secret', 'ius' ); ?>
      </label>
    </th>
    <td>
      <input type="text"
             class="text"
             id="ius_github_webhook_secret"
             name="ius_github_webhook_secret"
             value="<?php echo esc_attr( get_post_meta( get_the_ID(), 'ius_github_webhook_secret', TRUE ) ); ?>"/>
      <br/>
      <span class="description">
        <?php esc_html_e( 'Secret for the webhook. Blank for no password.', 'ius' ); ?>
      </span>
    </td>
  </tr>
  <tr>
    <th>
      <label for="ius_token_user_id">
          <?php _e( 'Github Personal Access Token', 'ius' ); ?>
      </label>
    </th>
    <td>
        <?php
        wp_dropdown_users(
            array(
                'id'               => 'ius_github_token_user_id',
                'name'             => 'ius_github_token_user_id',
                'selected'         => get_post_meta( get_the_ID(), 'ius_github_token_user_id', TRUE ),
                'show_option_none' => 'Choose a user',
                'role'             => 'administrator',
                'meta_key'         => 'ius_github_personal_access_token',
                'meta_compare'     => 'EXISTS',
            )
        );
        ?>
      <br/>
      <span class="description">
        <?php _e( 'Settings &gt; Developer settings &gt; <a href="https://github.com/settings/tokens" target="_blank">Personal access tokens</a>',
            'ius' ); ?>
      </span>
    </td>
  </tr>
</table>
<?php wp_nonce_field( 'project-webhook-nonce-8723', 'project-webhook-nonce' ); ?>
