<?php
/**
 * Segurança básica, igual em todos os sites.
 * (O bloqueio de edição de arquivos fica no arquivo principal, porque precisa ser definido cedo.)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// XML-RPC é porta de ataque de força bruta e quase nenhum site de cliente usa.
// «xmlrpc_enabled» sozinho só bloqueia o que exige login; a lista de métodos e o pingback seguem abertos.
// Por isso a lista de métodos vai vazia. Exceção: se o Jetpack estiver ativo, ele precisa do XML-RPC.
// A checagem do Jetpack é feita na hora (dentro das funções), porque este plugin carrega antes dele.
function agboost_xmlrpc_liberado() {
	return defined( 'JETPACK__VERSION' );
}
add_filter( 'xmlrpc_enabled', fn( $v ) => agboost_xmlrpc_liberado() ? $v : false );
add_filter( 'xmlrpc_methods', fn( $m ) => agboost_xmlrpc_liberado() ? $m : array() );
add_filter(
	'wp_headers',
	function ( $cabecalhos ) {
		if ( ! agboost_xmlrpc_liberado() ) {
			unset( $cabecalhos['X-Pingback'] );
		}
		return $cabecalhos;
	}
);

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
