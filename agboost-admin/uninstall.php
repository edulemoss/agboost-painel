<?php
/**
 * Desinstalação: apaga só o que o plugin criou. Não mexe em usuários nem em conteúdo.
 *
 * @package AgBoost_Painel
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

foreach ( array( 'agboost_painel', 'agboost_painel_db', 'agboost_painel_chave', 'agboost_avisos_escondidos' ) as $agboost_opcao ) {
	delete_option( $agboost_opcao );
}
remove_role( 'agboost_bot' );
foreach ( get_users( array( 'capability' => 'agboost_gerenciar' ) ) as $agboost_usuario ) {
	$agboost_usuario->remove_cap( 'agboost_gerenciar' );
}
delete_metadata( 'user', 0, 'agboost_equipe', '', true );
