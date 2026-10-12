<?php
/**
 * Configuração por site: logo, cores e número de suporte.
 * Tela em Configurações › AgBoost Painel, visível só para a equipe AgBoost.
 *
 * @package AgBoost_Painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const AGBOOST_SUPORTE_PADRAO = '5549999092270';

/**
 * Lê uma configuração do site, com valor padrão.
 *
 * @param string $chave logo|cor|fundo|suporte|simples_adm.
 * @return string
 */
function agboost_cfg( $chave ) {
	$padrao = array(
		'logo'        => '',        // Vazio = usa o logo do tema (custom_logo) ou o ícone do site.
		'cor'         => '#2D5147', // Botão e destaques do login e da tela inicial.
		'fundo'       => '#F4F2EF', // Fundo da tela de login.
		'suporte'     => AGBOOST_SUPORTE_PADRAO,
		'simples_adm' => '1',       // Administradores do cliente também começam no painel simples.
	);
	$opcoes = (array) get_option( 'agboost_painel', array() );
	$valor  = isset( $opcoes[ $chave ] ) && '' !== $opcoes[ $chave ] ? $opcoes[ $chave ] : ( isset( $padrao[ $chave ] ) ? $padrao[ $chave ] : '' );
	return apply_filters( 'agboost_painel_cfg_' . $chave, $valor );
}

/**
 * URL do logo do cliente para o login.
 *
 * @return string
 */
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
	return $icone ? $icone : '';
}

/**
 * Link do WhatsApp de suporte, já com a mensagem.
 *
 * @param string $texto Mensagem (padrão: pedido de ajuda com o domínio).
 * @return string
 */
function agboost_link_suporte( $texto = '' ) {
	$numero = preg_replace( '/\D/', '', agboost_cfg( 'suporte' ) );
	$msg    = $texto ? $texto : sprintf( 'Olá! Preciso de ajuda com o site %s.', wp_parse_url( home_url(), PHP_URL_HOST ) );
	return 'https://wa.me/' . $numero . '?text=' . rawurlencode( $msg );
}

add_action( 'admin_menu', 'agboost_menu_config' );
/** Tela de configuração, só para a equipe. */
function agboost_menu_config() {
	if ( current_user_can( 'manage_options' ) && agboost_e_equipe() ) {
		add_options_page( 'AgBoost Painel', 'AgBoost Painel', 'manage_options', 'agboost-painel', 'agboost_tela_config' );
	}
}

add_action( 'admin_init', 'agboost_registrar_config' );
/** Registra a opção com tipo e limpeza dos dados. */
function agboost_registrar_config() {
	register_setting(
		'agboost_painel',
		'agboost_painel',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'agboost_limpar_config',
			'default'           => array(),
		)
	);
}

/**
 * Limpa o que vem do formulário.
 *
 * @param mixed $v Valores enviados.
 * @return array
 */
function agboost_limpar_config( $v ) {
	$v = (array) $v;
	return array(
		'logo'        => esc_url_raw( isset( $v['logo'] ) ? $v['logo'] : '' ),
		'cor'         => (string) sanitize_hex_color( isset( $v['cor'] ) ? $v['cor'] : '' ),
		'fundo'       => (string) sanitize_hex_color( isset( $v['fundo'] ) ? $v['fundo'] : '' ),
		'suporte'     => preg_replace( '/\D/', '', isset( $v['suporte'] ) ? $v['suporte'] : '' ),
		'simples_adm' => empty( $v['simples_adm'] ) ? '0' : '1',
	);
}

/** Desenha a tela de configuração. */
function agboost_tela_config() {
	if ( ! current_user_can( 'manage_options' ) || ! agboost_e_equipe() ) {
		return;
	}
	$o = (array) get_option( 'agboost_painel', array() );
	$g = function ( $k ) use ( $o ) {
		return isset( $o[ $k ] ) ? $o[ $k ] : '';
	};
	?>
	<div class="wrap">
		<h1>AgBoost Painel <small style="font-weight:400;color:#888">v<?php echo esc_html( AGBOOST_PAINEL_VERSAO ); ?></small></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'agboost_painel' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Logo do login</th><td><input type="url" class="regular-text" name="agboost_painel[logo]" value="<?php echo esc_attr( $g( 'logo' ) ); ?>" placeholder="vazio = logo do tema"><p class="description">Agora: <?php echo esc_html( agboost_logo_cliente() ? agboost_logo_cliente() : 'nenhum (mostra o nome do site)' ); ?></p></td></tr>
				<tr><th scope="row">Cor principal</th><td><input type="text" name="agboost_painel[cor]" value="<?php echo esc_attr( $g( 'cor' ) ); ?>" placeholder="<?php echo esc_attr( agboost_cfg( 'cor' ) ); ?>"></td></tr>
				<tr><th scope="row">Cor do fundo do login</th><td><input type="text" name="agboost_painel[fundo]" value="<?php echo esc_attr( $g( 'fundo' ) ); ?>" placeholder="<?php echo esc_attr( agboost_cfg( 'fundo' ) ); ?>"></td></tr>
				<tr><th scope="row">WhatsApp de suporte</th><td><input type="text" name="agboost_painel[suporte]" value="<?php echo esc_attr( $g( 'suporte' ) ); ?>" placeholder="<?php echo esc_attr( AGBOOST_SUPORTE_PADRAO ); ?>"></td></tr>
				<tr><th scope="row">Painel simples</th><td><label><input type="checkbox" name="agboost_painel[simples_adm]" value="1" <?php checked( '0' !== $g( 'simples_adm' ) ); ?>> Administradores do cliente também começam no painel simples</label></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<h2>API de gestão</h2>
		<p>Endereço: <code><?php echo esc_html( rest_url( 'agboost/v1/' ) ); ?></code> · autenticação por Application Password de um usuário com o papel <strong>AgBoost · automação</strong>.</p>
	</div>
	<?php
}
