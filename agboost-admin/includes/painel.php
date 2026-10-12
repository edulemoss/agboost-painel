<?php
/**
 * Painel simples para o cliente:
 *  - tela inicial com atalhos grandes (Pedidos, Produtos, Adicionar produto, Ver o site, Suporte);
 *  - menu enxuto (lista do que FICA: plugin novo não aparece sozinho);
 *  - barra do topo limpa, com o botão de suporte.
 * A equipe AgBoost vê o painel completo e pode alternar. Esconder menu é só visual: a permissão continua valendo.
 *
 * @package AgBoost_Painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * O usuário está no painel simples?
 *
 * @param int $usuario_id ID (padrão: atual).
 * @return bool
 */
function agboost_modo_simples( $usuario_id = 0 ) {
	$usuario = $usuario_id ? get_userdata( $usuario_id ) : wp_get_current_user();
	if ( ! $usuario || ! $usuario->exists() ) {
		return false;
	}
	$pref = get_user_meta( $usuario->ID, 'edu_admin_simples', true ); // Mesma chave da 1.x: a escolha continua valendo.
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

/**
 * A loja usa WooCommerce?
 *
 * @return bool
 */
function agboost_tem_woo() {
	return class_exists( 'WooCommerce' );
}

/**
 * Endereço da lista de pedidos (com ou sem a tabela nova do WooCommerce).
 *
 * @return string
 */
function agboost_url_pedidos() {
	if ( agboost_tem_woo() && class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
		return admin_url( 'admin.php?page=wc-orders' );
	}
	return admin_url( 'edit.php?post_type=shop_order' );
}

add_action( 'admin_init', 'agboost_alternar_modo' );
/** Alterna simples/completo (só quem administra, com nonce). */
function agboost_alternar_modo() {
	if ( ! isset( $_GET['agboost_modo'], $_GET['_wpnonce'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'agboost_modo' ) ) {
		return;
	}
	update_user_meta( get_current_user_id(), 'edu_admin_simples', agboost_modo_simples() ? '0' : '1' );
	wp_safe_redirect( remove_query_arg( array( 'agboost_modo', '_wpnonce' ) ) );
	exit;
}

add_action( 'admin_bar_menu', 'agboost_barra_botoes', 5 );
/**
 * Botões AgBoost na barra do topo.
 *
 * @param WP_Admin_Bar $barra Barra.
 */
function agboost_barra_botoes( $barra ) {
	if ( ! is_user_logged_in() ) {
		return;
	}
	$barra->add_node(
		array(
			'id'     => 'agboost-suporte',
			'parent' => 'top-secondary',
			'title'  => '💬 Suporte AgBoost',
			'href'   => agboost_link_suporte(),
			'meta'   => array(
				'target' => '_blank',
				'rel'    => 'noopener',
			),
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
}

add_action( 'admin_bar_menu', 'agboost_barra_limpa', 9999 );
/**
 * Barra do topo limpa no painel simples.
 *
 * @param WP_Admin_Bar $barra Barra.
 */
function agboost_barra_limpa( $barra ) {
	if ( ! agboost_modo_simples() ) {
		return;
	}
	$fica = array( 'top-secondary', 'my-account', 'user-actions', 'user-info', 'edit-profile', 'logout', 'site-name', 'view-site', 'dashboard', 'menu-toggle', 'edit', 'view', 'agboost-suporte', 'agboost-modo' );
	foreach ( $barra->get_nodes() as $no ) {
		if ( ! in_array( $no->id, $fica, true ) ) {
			$barra->remove_node( $no->id );
		}
	}
}

add_action( 'admin_menu', 'agboost_menu_enxuto', 99999 );
/** Menu enxuto: só o que o cliente usa no dia a dia. */
function agboost_menu_enxuto() {
	if ( ! agboost_modo_simples() ) {
		return;
	}
	global $menu;
	if ( agboost_tem_woo() && current_user_can( 'edit_shop_orders' ) ) {
		add_menu_page( 'Pedidos', 'Pedidos', 'edit_shop_orders', agboost_url_pedidos(), '', 'dashicons-cart', 3 );
	}
	$fica = array( 'index.php', 'edit.php?post_type=product', 'upload.php', 'edit.php?post_type=page', 'edit.php', 'profile.php', 'users.php', agboost_url_pedidos() );
	foreach ( (array) $menu as $pos => $item ) {
		$slug = isset( $item[2] ) ? $item[2] : '';
		if ( 0 !== strpos( $slug, 'separator' ) && ! in_array( $slug, $fica, true ) ) {
			remove_menu_page( $slug );
		}
	}
}

add_action( 'wp_dashboard_setup', 'agboost_tela_inicial', 99999 );
/** Tela inicial com atalhos no lugar dos widgets do painel. */
function agboost_tela_inicial() {
	if ( ! agboost_modo_simples() ) {
		return;
	}
	foreach ( array( 'normal', 'side', 'column3', 'column4', 'advanced' ) as $contexto ) {
		foreach ( array( 'high', 'core', 'default', 'low' ) as $prioridade ) {
			global $wp_meta_boxes;
			if ( empty( $wp_meta_boxes['dashboard'][ $contexto ][ $prioridade ] ) ) {
				continue;
			}
			foreach ( array_keys( $wp_meta_boxes['dashboard'][ $contexto ][ $prioridade ] ) as $id ) {
				remove_meta_box( $id, 'dashboard', $contexto );
			}
		}
	}
	remove_action( 'welcome_panel', 'wp_welcome_panel' );
	wp_add_dashboard_widget( 'agboost_inicio', 'Início', 'agboost_desenhar_inicio' );
}

add_filter( 'get_user_option_screen_layout_dashboard', 'agboost_uma_coluna' );
/**
 * Painel em uma coluna no modo simples.
 *
 * @param mixed $valor Layout atual.
 * @return mixed
 */
function agboost_uma_coluna( $valor ) {
	return agboost_modo_simples() ? 1 : $valor;
}

add_action( 'admin_head', 'agboost_estilo_simples' );
/** Ajustes visuais do painel simples (largura total, sem «Opções de tela»). */
function agboost_estilo_simples() {
	if ( ! agboost_modo_simples() ) {
		return;
	}
	echo '<style id="agboost-simples">#screen-meta-links{display:none!important}.index-php #dashboard-widgets .postbox-container{width:100%!important}.index-php #dashboard-widgets #postbox-container-2,.index-php #dashboard-widgets #postbox-container-3,.index-php #dashboard-widgets #postbox-container-4,.index-php .empty-container{display:none!important}.woocommerce-layout__header,.woocommerce-store-alerts{display:none!important}</style>';
}

/** Desenha os atalhos da tela inicial. */
function agboost_desenhar_inicio() {
	$cartoes = array();
	if ( agboost_tem_woo() && current_user_can( 'edit_shop_orders' ) ) {
		$aguardando = function_exists( 'wc_orders_count' ) ? (int) wc_orders_count( 'processing' ) : 0;
		$cartoes[]  = array( 'Pedidos', $aguardando ? $aguardando . ' para separar' : 'Ver todos', agboost_url_pedidos(), 'dashicons-cart' );
	}
	if ( post_type_exists( 'product' ) && current_user_can( 'edit_products' ) ) {
		$cartoes[] = array( 'Produtos', 'Editar preço, estoque e fotos', admin_url( 'edit.php?post_type=product' ), 'dashicons-products' );
		$cartoes[] = array( 'Adicionar produto', 'Cadastrar uma peça nova', admin_url( 'post-new.php?post_type=product' ), 'dashicons-plus-alt' );
	} elseif ( current_user_can( 'edit_pages' ) ) {
		$cartoes[] = array( 'Páginas', 'Editar o conteúdo do site', admin_url( 'edit.php?post_type=page' ), 'dashicons-admin-page' );
	}
	$cartoes[] = array( 'Ver o site', wp_parse_url( home_url(), PHP_URL_HOST ), home_url( '/' ), 'dashicons-external' );
	$cartoes[] = array( 'Falar com a AgBoost', 'Suporte pelo WhatsApp', agboost_link_suporte(), 'dashicons-format-chat' );
	$cor       = agboost_cfg( 'cor' );
	?>
	<style>
		#agboost_inicio .postbox-header{display:none}
		.agb-grade{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:14px;padding:6px 0}
		.agb-cartao{display:flex;gap:12px;align-items:center;padding:18px;border:1px solid #e3e3e3;border-radius:12px;text-decoration:none;color:#1d2327;background:#fff;transition:border-color .15s,box-shadow .15s}
		.agb-cartao:hover{border-color:<?php echo esc_html( $cor ); ?>;box-shadow:0 4px 14px rgba(0,0,0,.06);color:#1d2327}
		.agb-cartao .dashicons{font-size:28px;width:28px;height:28px;color:<?php echo esc_html( $cor ); ?>}
		.agb-cartao strong{display:block;font-size:15px}
		.agb-cartao .agb-sub{color:#666;font-size:13px}
		.agb-ola{font-size:18px;margin:4px 0 14px}
	</style>
	<p class="agb-ola">Olá, <?php echo esc_html( wp_get_current_user()->display_name ); ?>! O que vamos fazer hoje?</p>
	<div class="agb-grade">
		<?php
		foreach ( $cartoes as $c ) :
			$externo = 0 !== strpos( $c[2], admin_url() );
			?>
			<a class="agb-cartao" href="<?php echo esc_url( $c[2] ); ?>"<?php echo $externo ? ' target="_blank" rel="noopener"' : ''; ?>>
				<span class="dashicons <?php echo esc_attr( $c[3] ); ?>"></span>
				<span><strong><?php echo esc_html( $c[0] ); ?></strong><span class="agb-sub"><?php echo esc_html( $c[1] ); ?></span></span>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
}

add_filter( 'admin_footer_text', 'agboost_rodape_painel' );
/** Rodapé do painel. */
function agboost_rodape_painel() {
	return 'Site gerenciado pela <a href="https://agboost.com.br" target="_blank" rel="noopener">AgBoost</a> · <a href="' . esc_url( agboost_link_suporte() ) . '" target="_blank" rel="noopener">suporte pelo WhatsApp</a>';
}

add_filter( 'update_footer', 'agboost_rodape_versao', 11 );
/**
 * Esconde a versão do WordPress no rodapé do painel simples.
 *
 * @param string $texto Texto atual.
 * @return string
 */
function agboost_rodape_versao( $texto ) {
	return agboost_modo_simples() ? '' : $texto;
}
