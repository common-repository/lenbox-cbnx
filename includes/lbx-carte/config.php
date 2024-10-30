<?php


function lenbox_carte_get_form_fields()
{
	return array(
		'live_client_id'      => array(
			'title' => __('Client ID / VD', 'lenbox-cbnx'),
			'type'  => 'text',
		),
		'live_client_authkey' => array(
			'title' => __('Authkey', 'lenbox-cbnx'),
			'type'  => 'text',
		),
		'use_test'            => array(
			'title'       => __('Use test environment', 'lenbox-cbnx'),
			'label'       => '',
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no',
		),
		'title'               => array(
			'title'       => __('Text displayed for Lenbox when choosing payment method', 'lenbox-cbnx'),
			'type'        => 'text',
			'description' => '',
			'default'     => __('Paiement en plusieurs fois', 'lenbox-cbnx'),
			'desc_tip'    => true,
		),
		'description'         => array(
			'title'       => __('Description', 'lenbox-cbnx'),
			'type'        => 'textarea',
			'description' => __('Description when user chooses lenbox as payment option', 'lenbox-cbnx'),
			'default'     => __('Request an EMI with Lenbox.', 'lenbox-cbnx'),
		),
	);
}
