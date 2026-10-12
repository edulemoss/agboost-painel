<?php
/**
 * Quem é da equipe AgBoost e o usuário de automação.
 *
 * Equipe = tem a permissão própria «agboost_gerenciar». Ela só é concedida pela AgBoost (migração da 2.1 ou
 * endpoint de gestão), nunca pelo e-mail: um administrador do cliente poderia criar um usuário @agboost.com.br.
 *
 * @package AgBoost_Painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * É alguém da equipe AgBoost?
 *
 * @param WP_User|null $usuario Usuário (padrão: o atual).
 * @return bool
 */
function agboost_e_equipe( $usuario = null ) {
	$usuario = $usuario ? $usuario : wp_get_current_user();
	return $usuario instanceof WP_User && $usuario->exists() && $usuario->has_cap( 'agboost_gerenciar' );
}

/**
 * Papel mínimo para o n8n: só lê o painel e usa a API de gestão (com Application Password).
 * Não edita conteúdo, não instala plugin, não cria usuário.
 */
function agboost_criar_papel_bot() {
	$caps  = array(
		'read'                    => true,
		'agboost_gerenciar'       => true,
		'view_site_health_checks' => true,
	);
	$papel = get_role( 'agboost_bot' );
	if ( ! $papel ) {
		add_role( 'agboost_bot', 'AgBoost · automação', $caps );
		return;
	}
	foreach ( $caps as $cap => $sim ) {
		$papel->add_cap( $cap, $sim );
	}
}
