=== AgBoost Painel ===
Contributors: agboost
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later

Login com a marca do cliente, painel simples, suporte AgBoost, segurança básica e atualização automática.

== Changelog ==
= 2.1.0 =
* Equipe AgBoost reconhecida pela permissão própria «agboost_gerenciar» (o e-mail @agboost.com.br dava para burlar). Migração automática concede a permissão a quem já era equipe.
* Avisos e banners: o cliente não vê; a equipe vê recolhidos numa caixa «Avisos dos plugins (N)». Tudo o que foi escondido fica registrado e sai no /status.
* API de gestão /wp-json/agboost/v1 com Application Password e papel mínimo «AgBoost · automação»: status, saude-site, checar-atualizacoes, limpar-cache, usuario/reset, equipe, log. /saude público só diz que está vivo. A chave na URL foi removida.
* Migração por versão (a ativação não roda quando o plugin é atualizado), uninstall.php, traduções no init, funções com nome em vez de closures, padrão WordPress Coding Standards.
= 2.0.2 =
* A exceção do Jetpack para o XML-RPC passa a ser checada na hora (o plugin carrega antes do Jetpack).
= 2.0.1 =
* XML-RPC fechado de verdade (lista de métodos vazia e sem cabeçalho de pingback), exceto quando o Jetpack está ativo.
= 2.0.0 =
* Login com o logo e as cores do cliente (Configurações › AgBoost Painel), AgBoost discreta no rodapé.
* Suporte pelo WhatsApp (49) 99909-2270 (o 4848 foi desativado).
* Painel simples: tela inicial com atalhos, menu enxuto, sem banners de plugins.
* Segurança: edição de arquivos bloqueada, XML-RPC desligado, versão escondida, usuários protegidos na API.
* Vigia /wp-json/agboost/v1/saude para o monitoramento da AgBoost.
* Atualização automática a partir do GitHub.
