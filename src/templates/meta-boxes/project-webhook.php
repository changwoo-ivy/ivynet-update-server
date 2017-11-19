<table class="form-table">
  <tr>
    <th>
      <label for="ius_plugin_main">
          <?php esc_html_e( 'Github Repository', 'ius' ); ?>
      </label>
    </th>
    <td>
      <input type="text"
             class="text"
             id="ius_github_repository"
             name="ius_github_repository"
             value="<?php echo esc_attr( get_post_meta( get_the_ID(), 'ius_github_repository', TRUE ) ); ?>"/>
      <br/>
      <span class="description">
        <?php esc_html_e( 'Your github repository. Input like <your-name>/<repository>', 'ius' ); ?>
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
</table>
<?php wp_nonce_field( 'project-webhook-nonce-8723', 'project-webhook-nonce' ); ?>
