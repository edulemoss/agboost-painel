<?php
/**
 * Configuração por site: logo, cores e número de suporte.
 * Tela em Configurações › AgBoost Painel, visível só para a equipe AgBoost.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const AGBOOST_SUPORTE_PADRAO = '5549999092270';

function agboost_cfg( $chave ) {
	$padrao = array(
		'logo'         => '',          // vazio = usa o logo do tema (custom_logo) ou o ícone do site
		'cor'          => '#2D5147',   // botão e destaques do login
		'fundo'        => '#F4F2EF',   // fundo da tela de login
		'suporte'      => AGBOOST_SUPORTE_PADRAO,
		'simples_adm'  => '1',         // administradores do cliente também começam no modo simples
	);
	$opcoes = (array) get_option( 'agboost_painel', array() );
	$valor  = isset( $opcoes[ $chave ] ) && '' !== $opcoes[ $chave ] ? $opcoes[ $chave ] : ( $padrao[ $chave ] ?? '' );
	return apply_filters( 'agboost_painel_cfg_' . $chave, $valor );
}

/** É alguém da equipe AgBoost? (e-mail @agboost.com.br, ou usuário marcado como AgBoost) */
function agboost_e_equipe( $usuario = null ) {
	$usuario = $usuario ?: wp_get_current_user();
	if ( ! $usuario || ! $usuario->exists() ) {
		return false;
	}
	if ( '1' === get_user_meta( $usuario->ID, 'agboost_equipe', true ) ) {
		return true;
	}
	return (bool) preg_match( '/@agboost\.com\.br$/i', (string) $usuario->user_email );
}

/** URL do logo do cliente para o login. */
function agboost_logo_cliente() {
	$logo = agboost_cfg( 'logo' );
	if ( $logo ) {
		return $logo;
	}
	$id = get_theme_mod( 'custom_logo' );
	if ( $id ) {
		$img = wp_get_attachment_image_src( $id, 'medium' );
		if ( $img ) {
			return $img[0];
		}
	}
	$icone = get_site_icon_url( 192 );
	return $icone ?: '';
}

function agboost_link_suporte( $texto = '' ) {
	$numero = preg_replace( '/\D/', '', agboost_cfg( 'suporte' ) );
	$msg    = $texto ?: sprintf( 'Olá! Preciso de ajuda com o site %s.', wp_parse_url( home_url(), PHP_URL_HOST ) );
	return 'https://wa.me/' . $numero . '?text=' . rawurlencode( $msg );
}

// ===== Tela de configuração (só equipe AgBoost) =====
add_action(
	'admin_menu',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! agboost_e_equipe() ) {
			return;
		}
		add_options_page( 'AgBoost Painel', 'AgBoost Painel', 'manage_options', 'agboost-painel', 'agboost_painel_tela' );
	}
);

add_action(
	'admin_init',
	function () {
		register_setting(
			'agboost_painel',
			'agboost_painel',
			array(
				'type'              => 'array',
				'sanitize_callback' => function ( $v ) {
					$v = (array) $v;
					return array(
						'logo'        => esc_url_raw( $v['logo'] ?? '' ),
						'cor'         => sanitize_hex_color( $v['cor'] ?? '' ) ?: '',
						'fundo'       => sanitize_hex_color( $v['fundo'] ?? '' ) ?: '',
						'suporte'     => preg_replace( '/\D/', '', $v['suporte'] ?? '' ),
						'simples_adm' => empty( $v['simples_adm'] ) ? '0' : '1',
					);
				},
			)
		);
	}
);

function agboost_painel_tela() {
	$o = (array) get_option( 'agboost_painel', array() );
	?>
	<div class="wrap">
		<h1>AgBoost Painel <small style="font-weight:400;color:#888">v<?php echo esc_html( AGBOOST_PAINEL_VERSAO ); ?></small></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'agboost_painel' ); ?>
			<table class="form-table" role="presentation">
				<tr><th>Logo do login</th><td><input type="url" class="regular-text" name="agboost_painel[logo]" value="<?php echo esc_attr( $o['logo'] ?? '' ); ?>" placeholder="vazio = logo do tema"><p class="description">Agora: <?php echo esc_html( agboost_logo_cliente() ?: 'nenhum (mostra o nome do site)' ); ?></p></td></tr>
				<tr><th>Cor principal</th><td><input type="text" name="agboost_painel[cor]" value="<?php echo esc_attr( $o['cor'] ?? '' ); ?>" placeholder="<?php echo esc_attr( agboost_cfg( 'cor' ) ); ?>"></td></tr>
				<tr><th>Cor do fundo do login</th><td><input type="text" name="agboost_painel[fundo]" value="<?php echo esc_attr( $o['fundo'] ?? '' ); ?>" placeholder="<?php echo esc_attr( agboost_cfg( 'fundo' ) ); ?>"></td></tr>
				<tr><th>WhatsApp de suporte</th><td><input type="text" name="agboost_painel[suporte]" value="<?php echo esc_attr( $o['suporte'] ?? '' ); ?>" placeholder="<?php echo esc_attr( AGBOOST_SUPORTE_PADRAO ); ?>"></td></tr>
				<tr><th>Painel simples</th><td><label><input type="checkbox" name="agboost_painel[simples_adm]" value="1" <?php checked( ( $o['simples_adm'] ?? '1' ) !== '0' ); ?>> Administradores do cliente também começam no modo simples</label></td></tr>
				<tr><th>Chave do vigia</th><td><code><?php echo esc_html( get_option( 'agboost_painel_chave' ) ); ?></code><p class="description">Usada pelo monitoramento da AgBoost em <code>/wp-json/agboost/v1/saude</code>.</p></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
