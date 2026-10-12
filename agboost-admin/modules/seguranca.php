<?php
/**
 * Segurança básica, igual em todos os sites.
 * (O bloqueio de edição de arquivos fica no arquivo principal, porque precisa ser definido cedo.)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// XML-RPC é porta de ataque de força bruta e quase nenhum site de cliente usa.
add_filter( 'xmlrpc_enabled', '__return_false' );

// Não anuncia a versão do WordPress no código da página.
add_filter( 'the_generator', '__return_empty_string' );
remove_action( 'wp_head', 'wp_generator' );

// Lista de usuários pela API só para quem está logado (impede descobrir logins).
add_filter(
	'rest_endpoints',
	function ( $rotas ) {
		if ( is_user_logged_in() ) {
			return $rotas;
		}
		unset( $rotas['/wp/v2/users'], $rotas['/wp/v2/users/(?P<id>[\d]+)'] );
		return $rotas;
	}
);

// ?author=1 não revela o login.
add_action(
	'template_redirect',
	function () {
		if ( ! is_user_logged_in() && isset( $_GET['author'] ) ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	}
);
