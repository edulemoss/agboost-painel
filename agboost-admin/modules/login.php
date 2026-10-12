<?php
/**
 * Tela de login com a marca do CLIENTE (logo e cores) e a AgBoost discreta no rodapé.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'login_enqueue_scripts',
	function () {
		$logo  = agboost_logo_cliente();
		$cor   = agboost_cfg( 'cor' );
		$fundo = agboost_cfg( 'fundo' );
		?>
		<style id="agboost-login">
			body.login{background:<?php echo esc_html( $fundo ); ?>;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
			#login{width:360px;padding-top:6vh}
			.login h1 a{<?php if ( $logo ) : ?>background-image:url('<?php echo esc_url( $logo ); ?>')!important;background-size:contain!important;background-position:center!important;background-repeat:no-repeat!important;width:260px!important;height:90px!important;<?php else : ?>background:none!important;text-indent:0!important;overflow:visible!important;width:auto!important;height:auto!important;font-size:26px!important;font-weight:700;line-height:1.2;color:#222!important;<?php endif; ?>margin-bottom:20px}
			.login h1 a:focus{box-shadow:none}
			.login form{border:0;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,.08);padding:28px 26px}
			.login form .input,.login input[type=text],.login input[type=password]{border-radius:10px;border-color:#d8d8d8;box-shadow:none;font-size:18px}
			.login form .input:focus{border-color:<?php echo esc_html( $cor ); ?>;box-shadow:0 0 0 2px <?php echo esc_html( $cor ); ?>33}
			body.login .button-primary,body.login #wp-submit{background:<?php echo esc_html( $cor ); ?>!important;border-color:<?php echo esc_html( $cor ); ?>!important;color:#fff!important;border-radius:10px!important;padding:2px 22px!important;font-weight:600!important;box-shadow:none!important;text-shadow:none!important}
			body.login .button-primary:hover,body.login .button-primary:focus,body.login #wp-submit:hover{filter:brightness(1.12)}
			body.login .button.wp-hide-pw .dashicons,body.login a{color:<?php echo esc_html( $cor ); ?>}
			body.login input[type=checkbox]:checked::before{filter:hue-rotate(0)}
			.login #nav a,.login #backtoblog a{color:#555}
			.login .language-switcher{display:none}
			.agboost-login-rodape{text-align:center;margin:26px auto 0;font-size:12px;color:#777;line-height:1.7}
			.agboost-login-rodape a{color:#555;text-decoration:none}
			.agboost-login-rodape a:hover{color:<?php echo esc_html( $cor ); ?>}
			.agboost-login-rodape .ag-wa{display:inline-block;margin-top:6px;padding:6px 14px;border-radius:999px;border:1px solid #d6d6d6;color:#333}
		</style>
		<?php
	}
);

add_filter( 'login_headerurl', fn() => home_url( '/' ) );
add_filter( 'login_headertext', fn() => get_bloginfo( 'name' ) );
add_filter( 'login_display_language_dropdown', '__return_false' );

add_action(
	'login_footer',
	function () {
		?>
		<div class="agboost-login-rodape">
			Gerenciado por <a href="https://agboost.com.br/?utm_source=<?php echo esc_attr( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?>&amp;utm_medium=referral&amp;utm_campaign=credito_site&amp;utm_content=login" target="_blank" rel="nofollow noopener">AgBoost</a><br>
			<a class="ag-wa" href="<?php echo esc_url( agboost_link_suporte() ); ?>" target="_blank" rel="noopener">Precisa de ajuda? Fale com o suporte</a>
		</div>
		<?php
	}
);

// Mensagem de erro genérica: não revela se o usuário existe.
add_filter( 'login_errors', fn() => 'Usuário ou senha incorretos.' );
