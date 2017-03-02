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
					return wpautop(__('SleekForm Error: You have to specify a form ID', 'sleekform'));
				}

				return self::renderForm($args['id']);
			}

			return wpautop(__('SleekForm requires the Advanced Custom Fields function acf_add_local_field_group()', 'sleekform'));
		});
	}

	private static function registerPostType () {
		register_post_type('sleek_form', [
			'labels' => [
				'name' => __('Sleek Form', 'sleekform'),
				'singular_label' => __('Sleek Form', 'sleekform')
			],
			'rewrite' => [
				'with_front' => false,
				'slug' => __('sleek-form', 'sleekform')
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
			'title' => __('Sleek Form Settings', 'sleekform'),
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
					'label' => __('Form Fields', 'sleekform'),
					'type' => 'repeater',
					'required' => true,
					'sub_fields' => [
						[
							'key' => 'sleek_form_field_label',
							'name' => 'sleek_form_field_label',
							'type' => 'text',
							'label' => __('Label', 'sleekform'),
							'required' => true
						],
						[
							'key' => 'sleek_form_field_placeholder',
							'name' => 'sleek_form_field_placeholder',
							'type' => 'text',
							'label' => __('Placeholder', 'sleekform')
						],
						[
							'key' => 'sleek_form_field_type',
							'name' => 'sleek_form_field_type',
							'type' => 'select',
							'label' => __('Type', 'sleekform'),
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
							'label' => __('Required Field', 'sleekform'),
							'message' => __('Required', 'sleekform')
						]
					]
				],
				[
					'key' => 'sleek_form_submit_text',
					'name' => 'sleek_form_submit_text',
					'type' => 'text',
					'label' => __('Submit Button Text', 'sleekform'),
					'default_value' => __('Submit', 'sleekform'),
					'required' => true
				],
				[
					'key' => 'sleek_form_recipients',
					'name' => 'sleek_form_recipients',
					'type' => 'text',
					'label' => __('Recipients', 'sleekform'),
					'instructions' => __('Separate multiple e-mail addresses with a comma', 'sleekform'),
					'default_value' => get_option('admin_email'),
					'required' => true
				],
				[
					'key' => 'sleek_form_success_text',
					'name' => 'sleek_form_success_text',
					'type' => 'text',
					'label' => __('Thank you message', 'sleekform'),
					'default_value' => __('Thank you.', 'sleekform'),
					'required' => true
				],
				[
					'key' => 'sleek_form_error_text',
					'name' => 'sleek_form_error_text',
					'type' => 'text',
					'label' => __('Error message', 'sleekform'),
					'default_value' => __('Something went wrong, please try again.', 'sleekform'),
					'required' => true
				],
				[
					'key' => 'sleek_form_email_subject',
					'name' => 'sleek_form_email_subject',
					'type' => 'text',
					'label' => __('E-mail subject', 'sleekform'),
					'default_value' => __('From your website', 'sleekform'),
					'required' => true
				]
			]
		]);
	}

	public static function renderForm ($id) {
		$formPost = get_post($id);

		# Make sure a sleek form with this ID exists
		if (!$formPost or $formPost->post_type !== 'sleek_forms') {
			return sprintf(__('No form with ID %s', 'sleekform'), $id);
		}

		# Store all the variables we need
		$title = $formPost->post_title;
		$slug = str_replace('-', '_', sanitize_title($title));
		$content = apply_filters('the_content', $formPost->post_content);

		$recipients = get_field('sleek_form_recipients', $id);
		$recipients = $recipients ? $recipients : get_option('admin_email');

		$submitText = get_field('sleek_form_submit_text', $id);
		$submitText = $submitText ? $submitText : __('Submit', 'sleekform');

		$successText = get_field('sleek_form_success_text', $id);
		$successText = $successText ? $successText : __('Thank you.', 'sleekform');

		$emailSubject = get_field('sleek_form_email_subject', $id);
		$emailSubject = $emailSubject ? $emailSubject : __('From your website', 'sleekform');

		$errorText = get_field('sleek_form_error_text', $id);
		$errorText = $errorText ? $errorText : __('Something went wrong, please try again.', 'sleekform');

		$formFields = get_field('sleek_form_fields', $id);
		$fields = [];

		# Make sure some fields are defined
		if (!$formFields) {
			return sprintf(__('No form fields defined for form %s', 'sleekform'), $id);
		}

		# return "Creating form with title: $title, slug: $slug, content: $content, recipients: $recipients, submitText: $submitText and fields " . count($fields);

		# Store all the fields in a way the form class expects
		foreach ($formFields as $field) {
			$fieldSlug = $slug . '_' . str_replace('-', '_', sanitize_title($field['sleek_form_field_label'])); # TODO: $field['sleek_form_field_name']

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
				if (wp_mail($recipients, $emailSubject, $mailTemplate, "Content-type: text/html\r\n")) {
					$done = true;

					if (defined('DOING_AJAX') and DOING_AJAX) {
						return json_encode(array('success' => $form->data(), 'message' => $successText));
					}
				}
				# Email wasnt sent :/
				else {
					$errors = true;

					if (defined('DOING_AJAX') and DOING_AJAX) {
						return json_encode(array('success' => false, 'errors' => $form->errors(), 'message' => 'wp_mail() failed.'));
					}
				}
			}
			# Form didn't validate
			else {
				$errors = true;

				if (defined('DOING_AJAX') and DOING_AJAX) {
					return json_encode(array('success' => false, 'errors' => $form->errors(), 'message' => $errorText));
				}
			}
		}

		# Render the form/error message
		$return = '<div class="sleek-form sleek-form--' . $slug . '">';
		$return .= "<h2>$title</h2>";
		$return .= $content;

		# Form has been successfully submitted
		if ($done) {
			$return .= wpautop($successText);
		}
		# Form hasn't been submitted, or there's an error
		else {
			if ($errors) {
				$return .= wpautop("<strong>$errorText</strong>");
			}

			$return .= $form->render();
		}

		$return .= '</div>';

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
