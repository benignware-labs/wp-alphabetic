<?php
require_once 'functions.php';

// create custom plugin settings menu
add_action('admin_menu', 'alphabetic_create_menu');

function alphabetic_create_menu() {

	//create new top-level menu
	add_menu_page('My Cool Plugin Settings', 'Alphabetic', 'administrator', __FILE__, 'alphabetic_settings_page' , 'dashicons-chart-pie' );

	//call register settings function
	add_action( 'admin_init', 'register_alphabetic_settings' );
}

function register_alphabetic_settings() {
	//register our settings
	register_setting( 'alphabetic-settings-group', 'post_types' );
	register_setting( 'alphabetic-settings-group', 'some_other_option' );
	register_setting( 'alphabetic-settings-group', 'option_etc' );
}

function alphabetic_settings_page() {
	$post_type_options = alphabetic_get_post_type_options();
?>
<div class="wrap">
<h1>Alphabetic</h1>

<form method="post" action="options.php">
    <?php settings_fields( 'alphabetic-settings-group' ); ?>
    <?php do_settings_sections( 'alphabetic-settings-group' ); ?>
    <div class="form-table">
			<?php foreach ($post_type_options as $key => $type_options): ?>
				<div>
					<?php $data = get_post_type_object($key); ?>
					<label>
						<input type="checkbox" name="post_types[<?= $key; ?>][enabled]" value="1" <?php checked( $type_options['enabled'], 1 ); ?> />
						<?= __($data->label); ?>
					</label>
				</div>
			<?php endforeach; ?>
    </div>

    <?php submit_button(); ?>

</form>
</div>
<?php } ?>
