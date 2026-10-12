#!/bin/sh
# Gera agboost-admin.zip com a pasta agboost-admin/ na raiz (formato que o WordPress e o atualizador esperam).
set -e
cd "$(dirname "$0")"
rm -f agboost-admin.zip
zip -rq agboost-admin.zip agboost-admin -x '*.DS_Store' 'agboost-admin/vendor/plugin-update-checker/.git*'
unzip -l agboost-admin.zip | tail -1
