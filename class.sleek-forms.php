<?php
class SleekForms {
	public static function activate () {
		# TODO: Remove?
	}

	public static function deactivate () {
		# TODO: Remove?
	}

	public static function init () {
		if (function_exists('acf_add_local_field_group')) {
			self::registerPostType();
			self::registerACF();
			self::registerShortcode();
		}
	}

	private static function registerShortcode () {
		add_shortcode('sleekform', function ($args) {
			if (function_exists('acf_add_local_field_group')) {
				if (!isset($args['id'])) {
					return wpautop(__('SleekForms Error: You have to specify a form ID', 'sleekforms'));
				}

				return self::renderForm($args['id']);
			}

			return wpautop(__('SleekForms requires the Advanced Custom Fields function acf_add_local_field_group()', 'sleekforms'));
		});
	}

	private static function registerPostType () {
		register_post_type('sleek_forms', [
			'labels' => [
				'name' => __('Sleek Forms', 'sleekforms'),
				'singular_label' => __('Sleek Form', 'sleekforms')
			],
			'rewrite' => [
				'with_front' => false,
				'slug' => __('sleek-forms', 'sleekforms')
			],
			'public' => true,
			'publicly_queryable' => false,
			'supports' => ['title', 'editor']
		]);

		# Remove Yoast SEO (http://wordpress.stackexchange.com/questions/108707/can-yoast-seo-fields-be-removed-from-custom-post-type)
		add_action('add_meta_boxes', function () {
			remove_meta_box('wpseo_meta', 'sleek_forms', 'normal');
		});
	}

	private static function registerACF () {
		acf_add_local_field_group([
			'key' => 'sleek_form_group',
			'title' => __('Sleek Form Settings', 'sleekforms'),
			'location' => [
				[
					[
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'sleek_forms'
					]
				]
			],
			'fields' => [
				[
					'key' => 'sleek_form_fields',
					'name' => 'sleek_form_fields',
					'label' => __('Form Fields', 'sleekforms'),
					'type' => 'repeater',
					'required' => true,
					'sub_fields' => [
						[
							'key' => 'sleek_form_field_label',
							'name' => 'sleek_form_field_label',
							'type' => 'text',
							'label' => __('Label', 'sleekforms'),
							'required' => true
						],
						[
							'key' => 'sleek_form_field_placeholder',
							'name' => 'sleek_form_field_placeholder',
							'type' => 'text',
							'label' => __('Placeholder', 'sleekforms')
						],
						[
							'key' => 'sleek_form_field_type',
							'name' => 'sleek_form_field_type',
							'type' => 'select',
							'label' => __('Type', 'sleekforms'),
							'required' => true,
							'choices' => [
								'text' => 'text',
								'textarea' => 'textarea',
								'password' => 'password',
								'email' => 'email',
								'date' => 'date',
								'color' => 'color',
								'url' => 'url',
								'tel' => 'tel',
								'number' => 'number',
								'hidden' => 'hidden'
								# checkbox, radio, select, range, captcha, more?
							]
						],
						[
							'key' => 'sleek_form_field_required',
							'name' => 'sleek_form_field_required',
							'type' => 'true_false',
							'label' => __('Required Field', 'sleekforms'),
							'message' => __('Required', 'sleekforms')
						]
					]
				],
				[
					'key' => 'sleek_form_submit_text',
					'name' => 'sleek_form_submit_text',
					'type' => 'text',
					'label' => __('Submit Button Text', 'sleekforms'),
					'default_value' => __('Submit', 'sleekforms'),
					'required' => true
				],
				[
					'key' => 'sleek_form_recipients',
					'name' => 'sleek_form_recipients',
					'type' => 'text',
					'label' => __('Recipients', 'sleekforms'),
					'instructions' => __('Separate multiple e-mail addresses with a comma', 'sleekforms'),
					'default_value' => get_option('admin_email'),
					'required' => true
				],
				[
					'key' => 'sleek_form_success_text',
					'name' => 'sleek_form_success_text',
					'type' => 'text',
					'label' => __('Thank you message', 'sleekforms'),
					'default_value' => __('Thank you.', 'sleekforms'),
					'required' => true
				],
				[
					'key' => 'sleek_form_error_text',
					'name' => 'sleek_form_error_text',
					'type' => 'text',
					'label' => __('Error message', 'sleekforms'),
					'default_value' => __('Something went wrong, please try again.', 'sleekforms'),
					'required' => true
				],
				[
					'key' => 'sleek_form_email_subject',
					'name' => 'sleek_form_email_subject',
					'type' => 'text',
					'label' => __('E-mail subject', 'sleekforms'),
					'default_value' => __('From your website', 'sleekforms'),
					'required' => true
				]
			]
		]);
	}

	public static function renderForm ($id) {
		$formPost = get_post($id);

		# Make sure a sleek form with this ID exists
		if (!$formPost or $formPost->post_type !== 'sleek_forms') {
			return sprintf(__('No form with ID %s', 'sleekforms'), $id);
		}

		# Store all the variables we need
		$title = $formPost->post_title;
		$slug = str_replace('-', '_', sanitize_title($title));
		$content = apply_filters('the_content', $formPost->post_content);

		$recipients = get_field('sleek_form_recipients', $id);
		$recipients = $recipients ? $recipients : get_option('admin_email');

		$submitText = get_field('sleek_form_submit_text', $id);
		$submitText = $submitText ? $submitText : __('Submit', 'sleekforms');

		$successText = get_field('sleek_form_success_text', $id);
		$successText = $successText ? $successText : __('Thank you.', 'sleekforms');

		$emailSubject = get_field('sleek_form_email_subject', $id);
		$emailSubject = $emailSubject ? $emailSubject : __('From your website', 'sleekforms');

		$errorText = get_field('sleek_form_error_text', $id);
		$errorText = $errorText ? $errorText : __('Something went wrong, please try again.', 'sleekforms');

		$formFields = get_field('sleek_form_fields', $id);
		$fields = [];

		# Make sure some fields are defined
		if (!$formFields) {
			return sprintf(__('No form fields defined for form %s', 'sleekforms'), $id);
		}

		# return "Creating form with title: $title, slug: $slug, content: $content, recipients: $recipients, submitText: $submitText and fields " . count($fields);

		# Store all the fields in a way the form class expects
		foreach ($formFields as $field) {
			$fieldSlug = $slug . '_' . str_replace('-', '_', sanitize_title($field['sleek_form_field_label']));

			$fields[] = [
				'name' => $fieldSlug,
				'type' => $field['sleek_form_field_type'],
				'label' => $field['sleek_form_field_label'],
				'required' => $field['sleek_form_field_required'] ? true : false,
				'placeholder' => $field['sleek_form_field_placeholder']
			];
		}

		# Create the form
		$form = new SleekForm($slug);

		$form
			->method('post')
			->action("#$slug-form")
			->submitTxt($submitText)
			->addFields($fields);

		# Handle form submission
		$done = $errors = false;

		# Form is being submitted
		if ($form->submit()) {
			# Validate it
			if ($form->validate()) {
				# Data to be emailed
				$formData = $form->data();

				# Remove some internal vars
				unset($formData[$slug . '_submit']);
				unset($formData['g-recaptcha-response']);

				# Fetch email template
				$mailTemplate = self::getTemplate('template', ['data' => $formData]);

				# Try to send an email
				if (!wp_mail($recipients, $emailSubject, $mailTemplate, "Content-type: text/html\r\n")) {
					$errors = true;

					if (defined('DOING_AJAX') and DOING_AJAX) {
						return json_encode(array('success' => false, 'errors' => $form->errors(), 'msg' => 'WP_Mail() failed.'));
					}
				}
				# Email wasnt sent :/
				else {
					$done = true;

					if (defined('DOING_AJAX') and DOING_AJAX) {
						return json_encode(array('success' => $form->data(), 'msg' => $successText));
					}
				}
			}
			# Form didn't validate
			else {
				$errors = true;

				if (defined('DOING_AJAX') and DOING_AJAX) {
					return json_encode(array('success' => false, 'errors' => $form->errors(), 'msg' => $errorText));
				}
			}
		}

		# Render the form/error message
		$return = "<h2>$title</h2>";
		$return .= $content;

		# Form has been successfully submitted
		if ($done) {
			$return = wpautop($successText);
		}
		# Form hasn't been submitted, or there's an error
		else {
			if ($errors) {
				$return .= wpautop("<strong>$errorText</strong>");
			}

			$return .= $form->render();
		}

		return $return;
	}

	private static function getTemplate ($templateName, $args) {
		if ($args) {
			extract($args);
		}

		ob_start();

		include SLEEK_FORMS_DIR . basename($templateName) . '.php';

		$contents = ob_get_contents();

		ob_end_clean();

		return $contents;
	}
}
