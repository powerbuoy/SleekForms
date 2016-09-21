<?php
/*
	Plugin Name: Sleek Forms
	Plugin URI: http://andreaslagerkvist.com/projects/sleek-forms/
	Description: A lightweight, modern and accessible WordPress form plug-in.
	Version: 0.0.0
	Author: Andreas Lagerkvist
	Author URI: http://andreaslagerkvist.com
	License: GPLv2 or later
	Text Domain: sleekforms
*/
# I stole this setup from Akismet :D
define('SLEEK_FORMS_URL', plugin_dir_url( __FILE__ ));
define('SLEEK_FORMS_DIR', plugin_dir_path( __FILE__ ));

register_activation_hook(__FILE__, ['SleekForms', 'activate']);
register_deactivation_hook(__FILE__, ['SleekForms', 'deactivate']);

require_once(SLEEK_FORMS_DIR . 'class.sleek-forms.php');
require_once(SLEEK_FORMS_DIR . 'class.sleek-form.php');

add_action('init', ['SleekForms', 'init']);

if (is_admin()) {
	require_once(SLEEK_FORMS_DIR . 'class.sleek-forms-admin.php' );

	add_action('admin_init', ['SleekFormsAdmin', 'init']);
}
