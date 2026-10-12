<?php
/**
 * Plugin Name: AgBoost Painel
 * Plugin URI:  https://github.com/edulemoss/agboost-painel
 * Description: Login com a marca do cliente, painel simples para quem administra a loja, suporte AgBoost, segurança básica, endpoints de gestão e atualização automática.
 * Version:     2.1.0
 * Author:      AgBoost
 * Author URI:  https://agboost.com.br
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: agboost-admin
 * License:     GPLv2 or later
 *
 * A pasta continua «agboost-admin» de propósito: assim a 2.x entra por cima da 1.x nos sites
 * que já têm o plugin, sem duplicar e sem precisar reativar.
 *
 * @package AgBoost_Painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AGBOOST_PAINEL_VERSAO', '2.1.0' );
define( 'AGBOOST_PAINEL_DIR', plugin_dir_path( __FILE__ ) );

// De onde vêm as atualizações. Se o repositório mudar de lugar, basta uma versão nova apontando para o endereço novo.
if ( ! defined( 'AGBOOST_PAINEL_REPO' ) ) {
	define( 'AGBOOST_PAINEL_REPO', 'https://github.com/edulemoss/agboost-painel/' );
}

// Segurança: impede editar arquivos de tema e plugin pelo painel. Precisa ser definido aqui, cedo.
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

require_once AGBOOST_PAINEL_DIR . 'includes/config.php';
require_once AGBOOST_PAINEL_DIR . 'includes/equipe.php';
require_once AGBOOST_PAINEL_DIR . 'includes/login.php';
require_once AGBOOST_PAINEL_DIR . 'includes/painel.php';
require_once AGBOOST_PAINEL_DIR . 'includes/avisos.php';
require_once AGBOOST_PAINEL_DIR . 'includes/seguranca.php';
require_once AGBOOST_PAINEL_DIR . 'includes/api.php';

// ===== Atualização automática a partir do GitHub (releases) =====
if ( file_exists( AGBOOST_PAINEL_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php' ) ) {
	require_once AGBOOST_PAINEL_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';
	$agboost_atualizador = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker( AGBOOST_PAINEL_REPO, __FILE__, 'agboost-admin' );
	$agboost_atualizador->setBranch( 'main' );
	$agboost_atualizador->getVcsApi()->enableReleaseAssets();
}

add_filter( 'auto_update_plugin', 'agboost_atualizar_sozinho', 10, 2 );
/**
 * Este plugin se atualiza sozinho; os outros seguem a escolha do site.
 *
 * @param bool|null $atualizar Decisão atual.
 * @param object    $item      Plugin sendo avaliado.
 * @return bool|null
 */
function agboost_atualizar_sozinho( $atualizar, $item ) {
	if ( isset( $item->plugin ) && 'agboost-admin/agboost-admin.php' === $item->plugin ) {
		return true;
	}
	return $atualizar;
}

add_action( 'init', 'agboost_carregar_traducoes' );
/** Carrega as traduções (no init, como pede o WordPress 6.7+). */
function agboost_carregar_traducoes() {
	load_plugin_textdomain( 'agboost-admin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

register_activation_hook( __FILE__, 'agboost_migrar' );
add_action( 'plugins_loaded', 'agboost_migrar_se_preciso' );

/** O gancho de ativação NÃO roda quando o plugin é atualizado. Por isso a migração é por versão gravada. */
function agboost_migrar_se_preciso() {
	if ( version_compare( (string) get_option( 'agboost_painel_db', '0' ), AGBOOST_PAINEL_VERSAO, '<' ) ) {
		agboost_migrar();
	}
}

/** Prepara o site para a versão atual. Seguro rodar mais de uma vez. */
function agboost_migrar() {
	agboost_criar_papel_bot();
	// 2.0 → 2.1: a equipe deixa de ser reconhecida pelo e-mail (dava para burlar) e passa a ter a permissão
	// própria agboost_gerenciar. Quem já era da equipe pelo e-mail @agboost.com.br recebe a permissão UMA vez, aqui.
	if ( version_compare( (string) get_option( 'agboost_painel_db', '0' ), '2.1.0', '<' ) ) {
		foreach ( get_users( array( 'role' => 'administrator' ) ) as $usuario ) {
			if ( preg_match( '/@agboost\.com\.br$/i', (string) $usuario->user_email ) || '1' === get_user_meta( $usuario->ID, 'agboost_equipe', true ) ) {
				$usuario->add_cap( 'agboost_gerenciar' );
			}
		}
	}
	delete_option( 'agboost_painel_chave' ); // A chave na URL saiu na 2.1 (vazava em log): a API usa Application Password.
	update_option( 'agboost_painel_db', AGBOOST_PAINEL_VERSAO, false );
}
