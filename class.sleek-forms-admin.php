<?php
class SleekFormsAdmin {
	public static function init () {
		if (function_exists('acf_add_local_field_group')) {
			self::addMetaBox();
		}
		else {
			add_action('admin_notices', function () {
				echo '<div class="notice notice-fail is-dismissable"><p>';
				echo __('SleekForms requires the Advanced Custom Fields function acf_add_local_field_group()', 'sleekforms');
				echo '</p></div>';
			});
		}
	}

	private static function addMetaBox () {
		add_meta_box('sleek-forms-info', __('Sleek Form Help', 'sleekforms'), function () {
			global $post;

			echo wpautop(__('You can use this shortcode to display the form anywhere:', 'sleekforms'));

			echo wpautop('<code>[sleekform id=' . $post->ID . ']</code>');

			echo wpautop(__("For nerds there's also:", 'sleekforms'));

			echo wpautop('<code>&lt;?php echo SleekForms::renderForm(' . $post->ID . ') ?&gt;</code>');
		}, 'sleek_forms', 'side');
	}
}
