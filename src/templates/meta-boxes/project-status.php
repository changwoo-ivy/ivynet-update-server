<?php
/**
 * Contexts:
 *
 * @var array  $options
 * @var string $selected
 */

?>
    <label for="project-status">
        <?php esc_html_e( 'Project Status', 'ius' ); ?>
    </label>:
    <select id="project-status" name="project-status">
        <?php
        /** @var WP_Term $option */
        foreach ( $options as $option ) : ?>
            <option value="<?php echo esc_attr( $option->slug ); ?>" <?php selected( $selected, $option->slug ) ?> >
                <?php echo esc_html( $option->name ); ?>
            </option>
        <?php endforeach; ?>
    </select>
<?php wp_nonce_field( 'nonce-project-status-1876', 'project-status-nonce', FALSE );
