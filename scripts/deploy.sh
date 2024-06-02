#!/usr/bin/env bash

set -e

FTPURL="ftp://${VIW_FTP_USER}:${VIW_FTP_PASS}@${VIW_FTP_HOST}"

WEB_URL="https://${VIW_FTP_HOST}/viw-data/public/"

DELETE="--delete"


STARTTIME=$(date +%s)

APP_SECRET=$(date | md5sum | head -c32)

cat <<EOF > .env
APP_SECRET=${APP_SECRET}
APP_ENV=prod
APP_DEBUG=0
EOF

docker compose -f .docker/docker-compose.yml exec -e APP_ENV=prod -e APP_DEBUG=0 php composer install --no-dev --optimize-autoloader
docker compose -f .docker/docker-compose.yml exec -e APP_ENV=prod -e APP_DEBUG=0 php bin/console cache:clear

lftp -c "set ftp:list-options -a 
set ftp:ssl-allow true
set ftp:ssl-force true
set ftp:ssl-protect-data true
set ftp:ssl-protect-list true
set mirror:parallel-directories true;
open '$FTPURL';

mirror -c -vvv -RL \
       --parallel=16 \
       $DELETE \
       --verbose \
       --exclude-glob .idea/ \
       --exclude-glob .docker/ \
       --exclude-glob var/cache/ \
       --exclude-glob var/log/ \
       --exclude-glob app/bootstrap.php.cache/ \
       --exclude-glob .git/
"

ENDTIME=$(date +%s)

RETURN_CODE=$(curl -s -o /dev/null -w "%{http_code}" ${WEB_URL})

if [ "${RETURN_CODE}" != "200" ]
then
    echo "Return code: ${RETURN_CODE}"
fi

echo "It takes $((($ENDTIME - $STARTTIME)/60)) minutes to complete this task..."
