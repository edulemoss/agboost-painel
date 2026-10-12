<?php
/**
 * Vigia: /wp-json/agboost/v1/saude?chave=…
 * Devolve o estado do site para o monitoramento da AgBoost (n8n).
 * Sem a chave certa, responde só {"ok": true} — não expõe nada.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'agboost/v1',
			'/saude',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => 'agboost_saude',
			)
		);
	}
);

function agboost_saude( WP_REST_Request $req ) {
	$chave = (string) get_option( 'agboost_painel_chave' );
	if ( ! $chave || ! hash_equals( $chave, (string) $req->get_param( 'chave' ) ) ) {
		return array( 'ok' => true );
	}
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/update.php';
	wp_update_plugins();
	$atualizacoes = get_site_transient( 'update_plugins' );
	$pendentes    = array();
	foreach ( (array) ( $atualizacoes->response ?? array() ) as $arquivo => $info ) {
		$pendentes[] = $arquivo . ' → ' . ( $info->new_version ?? '?' );
	}
	$home = wp_remote_get( home_url( '/?agboost_vigia=' . time() ), array( 'timeout' => 20, 'user-agent' => 'AgBoost-Vigia' ) );
	$corpo = is_wp_error( $home ) ? '' : wp_remote_retrieve_body( $home );
	return array(
		'ok'             => true,
		'site'           => home_url(),
		'painel'         => AGBOOST_PAINEL_VERSAO,
		'wordpress'      => get_bloginfo( 'version' ),
		'php'            => PHP_VERSION,
		'tema'           => wp_get_theme()->get( 'Name' ) . ' ' . wp_get_theme()->get( 'Version' ),
		'woocommerce'    => defined( 'WC_VERSION' ) ? WC_VERSION : null,
		'plugins_ativos' => count( (array) get_option( 'active_plugins' ) ),
		'atualizacoes'   => $pendentes,
		'home_status'    => is_wp_error( $home ) ? $home->get_error_message() : wp_remote_retrieve_response_code( $home ),
		'home_bytes'     => strlen( $corpo ),
		'home_fecha'     => false !== stripos( $corpo, '</html>' ),
	);
}
