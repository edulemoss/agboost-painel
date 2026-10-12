<?php
/**
 * Segurança básica, igual em todos os sites.
 * (O bloqueio de edição de arquivos fica no arquivo principal, porque precisa ser definido cedo.)
 *
 * @package AgBoost_Painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * O XML-RPC fica liberado só se o Jetpack estiver ativo (ele precisa). Checado na hora, porque este
 * plugin carrega antes do Jetpack.
 *
 * @return bool
 */
function agboost_xmlrpc_liberado() {
	return defined( 'JETPACK__VERSION' );
}

add_filter( 'xmlrpc_enabled', 'agboost_xmlrpc_ligado' );
/**
 * Desliga o XML-RPC autenticado.
 *
 * @param bool $ligado Valor atual.
 * @return bool
 */
function agboost_xmlrpc_ligado( $ligado ) {
	return agboost_xmlrpc_liberado() ? $ligado : false;
}

add_filter( 'xmlrpc_methods', 'agboost_xmlrpc_metodos' );
/**
 * Lista de métodos vazia: fecha também o pingback e o system.multicall (força bruta).
 *
 * @param array $metodos Métodos.
 * @return array
 */
function agboost_xmlrpc_metodos( $metodos ) {
	return agboost_xmlrpc_liberado() ? $metodos : array();
}

add_filter( 'wp_headers', 'agboost_sem_pingback' );
/**
 * Tira o cabeçalho X-Pingback.
 *
 * @param array $cabecalhos Cabeçalhos.
 * @return array
 */
function agboost_sem_pingback( $cabecalhos ) {
	if ( ! agboost_xmlrpc_liberado() ) {
		unset( $cabecalhos['X-Pingback'] );
	}
	return $cabecalhos;
}

// Não anuncia a versão do WordPress no código da página.
add_filter( 'the_generator', '__return_empty_string' );
remove_action( 'wp_head', 'wp_generator' );

add_filter( 'rest_endpoints', 'agboost_esconder_usuarios' );
/**
 * Lista de usuários pela API só para quem está logado (impede descobrir logins).
 *
 * @param array $rotas Rotas.
 * @return array
 */
function agboost_esconder_usuarios( $rotas ) {
	if ( ! is_user_logged_in() ) {
		unset( $rotas['/wp/v2/users'], $rotas['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $rotas;
}

add_action( 'template_redirect', 'agboost_bloquear_author' );
/** ?author=1 não revela o login (só leitura: redireciona para a home). */
function agboost_bloquear_author() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- apenas lê a URL para redirecionar; nada é gravado.
	if ( ! is_user_logged_in() && isset( $_GET['author'] ) ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
