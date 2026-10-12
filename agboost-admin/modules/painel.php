<?php
/**
 * Painel simples para o cliente:
 *  - tela inicial com atalhos grandes (Pedidos, Produtos, Adicionar produto, Ver o site, Suporte);
 *  - menu enxuto (lista do que fica, não do que sai: plugin novo não aparece sozinho);
 *  - sem banners e avisos de plugins;
 *  - barra do topo limpa, com o botão de suporte.
 * A equipe AgBoost vê o painel completo (e pode alternar).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function agboost_modo_simples( $usuario_id = 0 ) {
	$usuario = $usuario_id ? get_userdata( $usuario_id ) : wp_get_current_user();
	if ( ! $usuario || ! $usuario->exists() ) {
		return false;
	}
	$pref = get_user_meta( $usuario->ID, 'edu_admin_simples', true ); // mesma chave da 1.x: a escolha de cada um continua valendo
	if ( '' !== $pref ) {
		return '1' === $pref;
	}
	if ( agboost_e_equipe( $usuario ) ) {
		return false;
	}
	if ( in_array( 'administrator', (array) $usuario->roles, true ) ) {
		return '1' === agboost_cfg( 'simples_adm' );
	}
	return true;
}

function agboost_tem_woo() {
	return class_exists( 'WooCommerce' );
}

function agboost_url_pedidos() {
	if ( agboost_tem_woo() && class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
		return admin_url( 'admin.php?page=wc-orders' );
	}
	return admin_url( 'edit.php?post_type=shop_order' );
}

// ===== Alternar simples/completo (só quem pode administrar) =====
add_action(
	'admin_init',
	function () {
		if ( isset( $_GET['agboost_modo'], $_GET['_wpnonce'] ) && current_user_can( 'manage_options' ) && wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'agboost_modo' ) ) {
			update_user_meta( get_current_user_id(), 'edu_admin_simples', agboost_modo_simples() ? '0' : '1' );
			wp_safe_redirect( remove_query_arg( array( 'agboost_modo', '_wpnonce' ) ) );
			exit;
		}
	}
);

// ===== Barra do topo =====
add_action(
	'admin_bar_menu',
	function ( $barra ) {
		if ( ! is_user_logged_in() ) {
			return;
		}
		$barra->add_node(
			array(
				'id'     => 'agboost-suporte',
				'parent' => 'top-secondary',
				'title'  => '💬 Suporte AgBoost',
				'href'   => agboost_link_suporte(),
				'meta'   => array( 'target' => '_blank', 'rel' => 'noopener' ),
			)
		);
		if ( current_user_can( 'manage_options' ) ) {
			$barra->add_node(
				array(
					'id'     => 'agboost-modo',
					'parent' => 'top-secondary',
					'title'  => agboost_modo_simples() ? 'Ver painel completo' : 'Ver painel simples',
					'href'   => wp_nonce_url( add_query_arg( 'agboost_modo', '1', admin_url() ), 'agboost_modo' ),
				)
			);
		}
	},
	5
);

add_action(
	'admin_bar_menu',
	function ( $barra ) {
		if ( ! agboost_modo_simples() ) {
			return;
		}
		$fica = array( 'top-secondary', 'my-account', 'user-actions', 'user-info', 'edit-profile', 'logout', 'site-name', 'view-site', 'dashboard', 'menu-toggle', 'edit', 'view', 'agboost-suporte', 'agboost-modo' );
		foreach ( $barra->get_nodes() as $no ) {
			if ( ! in_array( $no->id, $fica, true ) ) {
				$barra->remove_node( $no->id );
			}
		}
	},
	9999
);

// ===== Menu enxuto =====
add_action(
	'admin_menu',
	function () {
		if ( ! agboost_modo_simples() ) {
			return;
		}
		global $menu;
		if ( agboost_tem_woo() && current_user_can( 'edit_shop_orders' ) ) {
			add_menu_page( 'Pedidos', 'Pedidos', 'edit_shop_orders', agboost_url_pedidos(), '', 'dashicons-cart', 3 );
		}
		$fica = array( 'index.php', 'edit.php?post_type=product', 'upload.php', 'edit.php?post_type=page', 'edit.php', 'profile.php', 'users.php', agboost_url_pedidos() );
		foreach ( (array) $menu as $pos => $item ) {
			$slug = $item[2] ?? '';
			if ( 0 === strpos( $slug, 'separator' ) ) {
				continue;
			}
			if ( ! in_array( $slug, $fica, true ) ) {
				unset( $menu[ $pos ] );
			}
		}
	},
	99999
);

// ===== Sem banners e avisos de plugins =====
add_action(
	'in_admin_header',
	function () {
		if ( ! agboost_modo_simples() ) {
			return;
		}
		foreach ( array( 'admin_notices', 'all_admin_notices', 'user_admin_notices', 'network_admin_notices' ) as $gancho ) {
			remove_all_actions( $gancho );
		}
	},
	PHP_INT_MAX
);

add_action(
	'admin_head',
	function () {
		if ( ! agboost_modo_simples() ) {
			return;
		}
		// #message e .woocommerce-message ficam: são as confirmações de «pedido atualizado», «produto salvo».
		echo '<style id="agboost-limpo">.notice:not(#message):not(.woocommerce-message):not(.agboost-ok),.update-nag,.updated:not(#message),.error:not(#message),#wpfooter #footer-upgrade,.woocommerce-layout__header,.woocommerce-store-alerts,#screen-meta-links{display:none!important}.index-php #dashboard-widgets .postbox-container{width:100%!important}.index-php #dashboard-widgets #postbox-container-2,.index-php #dashboard-widgets #postbox-container-3,.index-php #dashboard-widgets #postbox-container-4{display:none!important}.index-php .empty-container{display:none!important}</style>';
	}
);

// ===== Tela inicial =====
add_action(
	'wp_dashboard_setup',
	function () {
		if ( ! agboost_modo_simples() ) {
			return;
		}
		global $wp_meta_boxes;
		$wp_meta_boxes['dashboard'] = array();
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
		wp_add_dashboard_widget( 'agboost_inicio', 'Início', 'agboost_tela_inicio' );
	},
	99999
);

add_filter( 'get_user_option_screen_layout_dashboard', fn( $v ) => agboost_modo_simples() ? 1 : $v );

function agboost_tela_inicio() {
	$cartoes = array();
	if ( agboost_tem_woo() && current_user_can( 'edit_shop_orders' ) ) {
		$aguardando = 0;
		if ( function_exists( 'wc_orders_count' ) ) {
			$aguardando = (int) wc_orders_count( 'processing' );
		}
		$cartoes[] = array( 'Pedidos', $aguardando ? $aguardando . ' para separar' : 'Ver todos', agboost_url_pedidos(), 'dashicons-cart' );
	}
	if ( post_type_exists( 'product' ) && current_user_can( 'edit_products' ) ) {
		$cartoes[] = array( 'Produtos', 'Editar preço, estoque e fotos', admin_url( 'edit.php?post_type=product' ), 'dashicons-products' );
		$cartoes[] = array( 'Adicionar produto', 'Cadastrar uma peça nova', admin_url( 'post-new.php?post_type=product' ), 'dashicons-plus-alt' );
	} elseif ( current_user_can( 'edit_pages' ) ) {
		$cartoes[] = array( 'Páginas', 'Editar o conteúdo do site', admin_url( 'edit.php?post_type=page' ), 'dashicons-admin-page' );
	}
	$cartoes[] = array( 'Ver o site', wp_parse_url( home_url(), PHP_URL_HOST ), home_url( '/' ), 'dashicons-external' );
	$cartoes[] = array( 'Falar com a AgBoost', 'Suporte pelo WhatsApp', agboost_link_suporte(), 'dashicons-format-chat' );
	$cor = agboost_cfg( 'cor' );
	?>
	<style>
		#agboost_inicio .postbox-header{display:none}
		.agb-grade{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:14px;padding:6px 0}
		.agb-cartao{display:flex;gap:12px;align-items:center;padding:18px;border:1px solid #e3e3e3;border-radius:12px;text-decoration:none;color:#1d2327;background:#fff;transition:border-color .15s,box-shadow .15s}
		.agb-cartao:hover{border-color:<?php echo esc_html( $cor ); ?>;box-shadow:0 4px 14px rgba(0,0,0,.06);color:#1d2327}
		.agb-cartao .dashicons{font-size:28px;width:28px;height:28px;color:<?php echo esc_html( $cor ); ?>}
		.agb-cartao strong{display:block;font-size:15px}
		.agb-cartao span.agb-sub{color:#666;font-size:13px}
		.agb-ola{font-size:18px;margin:4px 0 14px}
	</style>
	<p class="agb-ola">Olá, <?php echo esc_html( wp_get_current_user()->display_name ); ?>! O que vamos fazer hoje?</p>
	<div class="agb-grade">
		<?php foreach ( $cartoes as $c ) : $externo = 0 !== strpos( $c[2], admin_url() ); ?>
			<a class="agb-cartao" href="<?php echo esc_url( $c[2] ); ?>" <?php echo $externo ? 'target="_blank" rel="noopener"' : ''; ?>>
				<span class="dashicons <?php echo esc_attr( $c[3] ); ?>"></span>
				<span><strong><?php echo esc_html( $c[0] ); ?></strong><span class="agb-sub"><?php echo esc_html( $c[1] ); ?></span></span>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
}

// ===== Rodapé do painel =====
add_filter( 'admin_footer_text', fn() => 'Site gerenciado pela <a href="https://agboost.com.br" target="_blank" rel="noopener">AgBoost</a> · <a href="' . esc_url( agboost_link_suporte() ) . '" target="_blank" rel="noopener">suporte pelo WhatsApp</a>' );
add_filter( 'update_footer', fn( $v ) => agboost_modo_simples() ? '' : $v, 11 );
