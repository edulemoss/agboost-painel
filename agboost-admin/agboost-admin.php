<?php
/**
 * Plugin Name: AgBoost Painel
 * Plugin URI:  https://agboost.com.br
 * Description: Login com a marca do cliente, painel simples para quem administra a loja, suporte AgBoost, segurança básica e atualização automática.
 * Version:     2.0.2
 * Author:      AgBoost
 * Author URI:  https://agboost.com.br
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: agboost-painel
 *
 * A pasta continua «agboost-admin» de propósito: assim a 2.0 entra por cima da 1.1 nos sites
 * que já têm o plugin, sem duplicar e sem precisar reativar.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AGBOOST_PAINEL_VERSAO', '2.0.2' );
define( 'AGBOOST_PAINEL_DIR', plugin_dir_path( __FILE__ ) );
define( 'AGBOOST_PAINEL_URL', plugin_dir_url( __FILE__ ) );

// De onde vêm as atualizações. Se o repositório mudar de lugar, basta uma versão nova apontando para o endereço novo.
if ( ! defined( 'AGBOOST_PAINEL_REPO' ) ) {
	define( 'AGBOOST_PAINEL_REPO', 'https://github.com/edulemoss/agboost-painel/' );
}

// Segurança: impede editar arquivos de tema e plugin pelo painel. Precisa ser definido aqui, cedo.
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

require_once AGBOOST_PAINEL_DIR . 'modules/config.php';
require_once AGBOOST_PAINEL_DIR . 'modules/login.php';
require_once AGBOOST_PAINEL_DIR . 'modules/painel.php';
require_once AGBOOST_PAINEL_DIR . 'modules/seguranca.php';
require_once AGBOOST_PAINEL_DIR . 'modules/saude.php';

// ===== Atualização automática a partir do GitHub (releases) =====
$agboost_puc = AGBOOST_PAINEL_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';
if ( file_exists( $agboost_puc ) ) {
	require_once $agboost_puc;
	$agboost_atualizador = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		AGBOOST_PAINEL_REPO,
		__FILE__,
		'agboost-admin'
	);
	$agboost_atualizador->setBranch( 'main' );
	$agboost_atualizador->getVcsApi()->enableReleaseAssets();
}

// Atualiza sozinho, sem esperar alguém clicar.
add_filter(
	'auto_update_plugin',
	function ( $atualizar, $item ) {
		if ( isset( $item->plugin ) && 'agboost-admin/agboost-admin.php' === $item->plugin ) {
			return true;
		}
		return $atualizar;
	},
	10,
	2
);

register_activation_hook( __FILE__, 'agboost_painel_ativar' );
function agboost_painel_ativar() {
	if ( ! get_option( 'agboost_painel_chave' ) ) {
		update_option( 'agboost_painel_chave', wp_generate_password( 32, false, false ), false );
	}
}
