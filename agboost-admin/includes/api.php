<?php
/**
 * API de gestão da AgBoost: /wp-json/agboost/v1/…
 *
 * Autenticação: Application Password (Basic Auth no cabeçalho, só HTTPS) de um usuário com a permissão
 * «agboost_gerenciar» — de preferência o papel mínimo «AgBoost · automação». Nada de chave na URL.
 * Só leitura e ações seguras: nada de executar código, gravar arquivo, instalar plugin ou criar administrador.
 * Cada chamada fica registrada (últimas 50) e pode ser lida em GET /log.
 *
 * @package AgBoost_Painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', 'agboost_api_rotas' );
/** Registra as rotas. */
function agboost_api_rotas() {
	$ns = 'agboost/v1';

	register_rest_route(
		$ns,
		'/saude',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => '__return_true', // Público de propósito: só diz que está vivo.
			'callback'            => 'agboost_api_saude',
		)
	);
	register_rest_route(
		$ns,
		'/status',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => 'agboost_api_pode',
			'callback'            => 'agboost_api_status',
		)
	);
	register_rest_route(
		$ns,
		'/saude-site',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => 'agboost_api_pode',
			'callback'            => 'agboost_api_saude_site',
		)
	);
	register_rest_route(
		$ns,
		'/checar-atualizacoes',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'permission_callback' => 'agboost_api_pode',
			'callback'            => 'agboost_api_checar_atualizacoes',
		)
	);
	register_rest_route(
		$ns,
		'/limpar-cache',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'permission_callback' => 'agboost_api_pode',
			'callback'            => 'agboost_api_limpar_cache',
			'args'                => array(
				'qual' => array(
					'type'    => 'string',
					'enum'    => array( 'tudo', 'pagina', 'elementor', 'objeto' ),
					'default' => 'tudo',
				),
			),
		)
	);
	register_rest_route(
		$ns,
		'/usuario/reset',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'permission_callback' => 'agboost_api_pode',
			'callback'            => 'agboost_api_reset_senha',
			'args'                => array(
				'id' => array(
					'type'     => 'integer',
					'required' => true,
					'minimum'  => 1,
				),
			),
		)
	);
	register_rest_route(
		$ns,
		'/equipe',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'permission_callback' => 'agboost_api_pode',
			'callback'            => 'agboost_api_equipe',
			'args'                => array(
				'id'       => array(
					'type'     => 'integer',
					'required' => true,
					'minimum'  => 1,
				),
				'conceder' => array(
					'type'    => 'boolean',
					'default' => true,
				),
			),
		)
	);
	register_rest_route(
		$ns,
		'/log',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => 'agboost_api_pode',
			'callback'            => 'agboost_api_log',
		)
	);
}

/**
 * Só quem tem a permissão da AgBoost. Também registra a chamada.
 *
 * @param WP_REST_Request $req Pedido.
 * @return bool|WP_Error
 */
function agboost_api_pode( WP_REST_Request $req ) {
	if ( ! current_user_can( 'agboost_gerenciar' ) ) {
		return new WP_Error( 'agboost_sem_permissao', 'Sem permissão.', array( 'status' => rest_authorization_required_code() ) );
	}
	$log   = (array) get_option( 'agboost_api_log', array() );
	$log[] = array(
		'quando'  => gmdate( 'c' ),
		'rota'    => $req->get_method() . ' ' . $req->get_route(),
		'usuario' => wp_get_current_user()->user_login,
		'ip'      => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
	);
	update_option( 'agboost_api_log', array_slice( $log, -50 ), false );
	return true;
}

/** Público: está vivo e em que versão do plugin. */
function agboost_api_saude() {
	return array(
		'ok'     => true,
		'painel' => AGBOOST_PAINEL_VERSAO,
	);
}

/**
 * Plugins com atualização pendente (do cache do WordPress).
 *
 * @return array
 */
function agboost_api_pendentes() {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$todos  = get_plugins();
	$cache  = get_site_transient( 'update_plugins' );
	$saida  = array();
	$listas = isset( $cache->response ) ? (array) $cache->response : array();
	foreach ( $listas as $arquivo => $info ) {
		$saida[] = array(
			'plugin' => isset( $todos[ $arquivo ]['Name'] ) ? $todos[ $arquivo ]['Name'] : $arquivo,
			'atual'  => isset( $todos[ $arquivo ]['Version'] ) ? $todos[ $arquivo ]['Version'] : '?',
			'nova'   => isset( $info->new_version ) ? $info->new_version : '?',
			'ativo'  => is_plugin_active( $arquivo ),
		);
	}
	return $saida;
}

/** Fotografia do site para o monitoramento. */
function agboost_api_status() {
	global $wp_version;
	$home  = wp_remote_get(
		add_query_arg( 'agboost_vigia', time(), home_url( '/' ) ),
		array(
			'timeout'    => 20,
			'user-agent' => 'AgBoost-Vigia',
		)
	);
	$corpo = is_wp_error( $home ) ? '' : (string) wp_remote_retrieve_body( $home );
	$cron  = _get_cron_array();
	$atras = 0;
	foreach ( (array) $cron as $quando => $eventos ) {
		if ( $quando < time() - 900 ) {
			$atras += count( $eventos );
		}
	}
	$woo_banco = null;
	if ( class_exists( 'WC_Install' ) && method_exists( 'WC_Install', 'needs_db_update' ) ) {
		$woo_banco = WC_Install::needs_db_update();
	}
	return array(
		'site'              => home_url(),
		'painel'            => AGBOOST_PAINEL_VERSAO,
		'wordpress'         => $wp_version,
		'php'               => PHP_VERSION,
		'tema'              => wp_get_theme()->get( 'Name' ) . ' ' . wp_get_theme()->get( 'Version' ),
		'woocommerce'       => defined( 'WC_VERSION' ) ? WC_VERSION : null,
		'woo_banco_pede'    => $woo_banco,
		'https'             => is_ssl() || 0 === strpos( home_url(), 'https://' ),
		'plugins_ativos'    => count( (array) get_option( 'active_plugins' ) ),
		'atualizacoes'      => agboost_api_pendentes(),
		'cron_atrasado'     => $atras,
		'home_status'       => is_wp_error( $home ) ? $home->get_error_message() : wp_remote_retrieve_response_code( $home ),
		'home_bytes'        => strlen( $corpo ),
		'home_fecha'        => false !== stripos( $corpo, '</html>' ),
		'avisos_escondidos' => array_values( (array) get_option( 'agboost_avisos_escondidos', array() ) ),
	);
}

/** Resultado dos testes diretos do «Saúde do site» (os mesmos da tela Ferramentas › Saúde do site). */
function agboost_api_saude_site() {
	if ( ! class_exists( 'WP_Site_Health' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
	}
	require_once ABSPATH . 'wp-admin/includes/admin.php';
	$saude  = WP_Site_Health::get_instance();
	$testes = WP_Site_Health::get_tests();
	$saida  = array(
		'critico'     => array(),
		'recomendado' => array(),
		'ok'          => 0,
	);
	foreach ( $testes['direct'] as $teste ) {
		$resultado = null;
		if ( is_string( $teste['test'] ) && method_exists( $saude, 'get_test_' . $teste['test'] ) ) {
			$resultado = call_user_func( array( $saude, 'get_test_' . $teste['test'] ) );
		} elseif ( is_callable( $teste['test'] ) ) {
			$resultado = call_user_func( $teste['test'] );
		}
		if ( ! is_array( $resultado ) || empty( $resultado['status'] ) ) {
			continue;
		}
		if ( 'good' === $resultado['status'] ) {
			++$saida['ok'];
		} elseif ( 'critical' === $resultado['status'] ) {
			$saida['critico'][] = wp_strip_all_tags( $resultado['label'] );
		} else {
			$saida['recomendado'][] = wp_strip_all_tags( $resultado['label'] );
		}
	}
	return $saida;
}

/** Força a checagem de atualizações (não instala nada). */
function agboost_api_checar_atualizacoes() {
	require_once ABSPATH . 'wp-admin/includes/update.php';
	delete_site_transient( 'update_plugins' );
	wp_update_plugins();
	wp_version_check();
	return array(
		'ok'           => true,
		'atualizacoes' => agboost_api_pendentes(),
	);
}

/**
 * Limpa cache (lista fixa).
 *
 * @param WP_REST_Request $req Pedido.
 * @return array
 */
function agboost_api_limpar_cache( WP_REST_Request $req ) {
	$qual  = $req->get_param( 'qual' );
	$feito = array();
	if ( in_array( $qual, array( 'tudo', 'pagina' ), true ) && has_action( 'litespeed_purge_all' ) ) {
		do_action( 'litespeed_purge_all' );
		$feito[] = 'litespeed';
	}
	if ( in_array( $qual, array( 'tudo', 'elementor' ), true ) && class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
		$feito[] = 'elementor';
	}
	if ( in_array( $qual, array( 'tudo', 'objeto' ), true ) ) {
		wp_cache_flush();
		$feito[] = 'objeto';
	}
	return array(
		'ok'    => true,
		'feito' => $feito,
	);
}

/**
 * Manda o e-mail de redefinição de senha (nunca devolve senha).
 *
 * @param WP_REST_Request $req Pedido.
 * @return array|WP_Error
 */
function agboost_api_reset_senha( WP_REST_Request $req ) {
	$usuario = get_userdata( (int) $req->get_param( 'id' ) );
	if ( ! $usuario ) {
		return new WP_Error( 'agboost_usuario', 'Usuário não encontrado.', array( 'status' => 404 ) );
	}
	$r = retrieve_password( $usuario->user_login );
	return is_wp_error( $r ) ? $r : array(
		'ok'      => true,
		'enviado' => true,
	);
}

/**
 * Concede ou tira a permissão de equipe AgBoost de um usuário.
 *
 * @param WP_REST_Request $req Pedido.
 * @return array|WP_Error
 */
function agboost_api_equipe( WP_REST_Request $req ) {
	$usuario = get_userdata( (int) $req->get_param( 'id' ) );
	if ( ! $usuario ) {
		return new WP_Error( 'agboost_usuario', 'Usuário não encontrado.', array( 'status' => 404 ) );
	}
	if ( $req->get_param( 'conceder' ) ) {
		$usuario->add_cap( 'agboost_gerenciar' );
	} else {
		$usuario->remove_cap( 'agboost_gerenciar' );
	}
	return array(
		'ok'     => true,
		'equipe' => $usuario->has_cap( 'agboost_gerenciar' ),
	);
}

/** Últimas chamadas da API. */
function agboost_api_log() {
	return array_reverse( (array) get_option( 'agboost_api_log', array() ) );
}
