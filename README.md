# AgBoost Painel

Plugin WordPress instalado em todos os sites de clientes da AgBoost.

- **Login** com o logo e as cores do cliente; AgBoost discreta no rodapé; botão de suporte no WhatsApp.
- **Painel simples** para o cliente: tela inicial com atalhos (Pedidos, Produtos, Adicionar produto, Ver o site, Suporte), menu enxuto e sem banners de plugins. Quem tem e-mail @agboost.com.br vê o painel completo.
- **Segurança básica** igual em todos os sites.
- **Vigia**: `/wp-json/agboost/v1/saude?chave=…` (a chave fica em Configurações › AgBoost Painel).
- **Atualização automática**: cada release deste repositório vira atualização nos sites (Plugin Update Checker, MIT).

## Como publicar uma versão
1. Suba o número em `Version:` e `AGBOOST_PAINEL_VERSAO` (`agboost-admin/agboost-admin.php`) e em `Stable tag` (`readme.txt`).
2. `./empacotar.sh` → gera `agboost-admin.zip`.
3. `gh release create vX.Y.Z agboost-admin.zip --title "vX.Y.Z" --notes "…"`.
Os sites pegam a versão nova na próxima checagem (até 12 h) e se atualizam sozinhos.

A pasta do plugin é `agboost-admin` para entrar por cima da 1.x sem duplicar.
Nada de segredo neste repositório: configuração de cada cliente fica no próprio site.
