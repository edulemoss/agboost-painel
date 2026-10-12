<?php
/**
 * Avisos e banners dos plugins.
 *  - Cliente (painel simples): não vê nenhum. Mas nada some: o texto fica registrado e sai no GET /status,
 *    para o monitoramento da AgBoost alertar (ex.: «o WooCommerce precisa atualizar o banco»).
 *  - Equipe (painel completo): os avisos ficam recolhidos numa caixa só, «Avisos dos plugins (N) · mostrar».
 *
 * @package AgBoost_Painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// A captura começa no PRIMEIRO gancho de aviso e termina no último. Não pode começar antes (in_admin_header):
// entre eles o WordPress imprime a estrutura da página (#wpbody), que seria engolida.
foreach ( array( 'admin_notices', 'network_admin_notices', 'user_admin_notices' ) as $agboost_gancho ) {
	add_action( $agboost_gancho, 'agboost_avisos_comecar', PHP_INT_MIN );
}
add_action( 'all_admin_notices', 'agboost_avisos_terminar', PHP_INT_MAX );

/** Começa a capturar (uma vez por página). */
function agboost_avisos_comecar() {
	if ( ! empty( $GLOBALS['agboost_capturando'] ) ) {
		return;
	}
	$GLOBALS['agboost_capturando'] = true;
	ob_start();
}

/** Fecha a captura e decide o que mostrar. */
function agboost_avisos_terminar() {
	if ( empty( $GLOBALS['agboost_capturando'] ) ) {
		return;
	}
	$GLOBALS['agboost_capturando'] = false;
	$html                          = (string) ob_get_clean();
	if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
		return;
	}
	agboost_avisos_registrar( $html );
	if ( agboost_modo_simples() ) {
		return; // Cliente não vê; fica registrado para o monitoramento.
	}
	$total = max( 1, preg_match_all( '/class=["\'][^"\']*\b(notice|updated|error|update-nag)\b/i', $html ) );
	echo '<details class="agboost-avisos" style="margin:12px 20px 0 2px;background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:8px 12px">';
	echo '<summary style="cursor:pointer;font-weight:600">' . esc_html( sprintf( 'Avisos dos plugins (%d) · mostrar', $total ) ) . '</summary>';
	// «inline»: sem isso o common.js do WordPress move os avisos para baixo do título e eles saem da caixa.
	$html = preg_replace( '/(<div[^>]+class=["\'])((?:[^"\']*\s)?(?:notice|updated|error|update-nag)\b)/i', '$1inline $2', $html );
	echo '<div style="margin-top:8px">' . $html . '</div></details>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML gerado pelos próprios plugins nos ganchos de aviso do WordPress.
}

/**
 * Guarda o texto dos avisos (no máximo 30, só grava quando aparece um novo).
 *
 * @param string $html Avisos capturados.
 */
function agboost_avisos_registrar( $html ) {
	$partes = preg_split( '/(?=<div[^>]+class=["\'][^"\']*\b(?:notice|updated|error|update-nag)\b)/i', $html );
	$lista  = (array) get_option( 'agboost_avisos_escondidos', array() );
	$mudou  = false;
	$tela   = function_exists( 'get_current_screen' ) && get_current_screen() ? get_current_screen()->id : '';
	foreach ( $partes as $parte ) {
		$texto = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $parte ) ) );
		if ( strlen( $texto ) < 8 ) {
			continue;
		}
		$chave = md5( $texto );
		if ( ! isset( $lista[ $chave ] ) ) {
			$lista[ $chave ] = array(
				'texto'    => mb_substr( $texto, 0, 220 ),
				'erro'     => (bool) preg_match( '/notice-error|\berror\b/i', $parte ),
				'tela'     => $tela,
				'primeiro' => gmdate( 'c' ),
			);
			$mudou           = true;
		}
		$lista[ $chave ]['ultimo'] = gmdate( 'Y-m-d' );
	}
	if ( $mudou ) {
		update_option( 'agboost_avisos_escondidos', array_slice( $lista, -30, 30, true ), false );
	}
}

add_action( 'admin_head', 'agboost_avisos_estilo' );
/** Avisos que alguns plugins injetam por JavaScript, fora dos ganchos: some só no painel simples. */
function agboost_avisos_estilo() {
	if ( agboost_modo_simples() ) {
		echo '<style id="agboost-sem-avisos">.notice:not(#message):not(.woocommerce-message):not(.agboost-ok),.update-nag,.updated:not(#message),.error:not(#message){display:none!important}</style>';
	}
}
