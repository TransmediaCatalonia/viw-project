#!/usr/bin/env bash

FTPURL="ftp://${VIW_FTP_USER}:${VIW_FTP_PASS}@${VIW_FTP_HOST}"

WEB_URL="http://${VIW_FTP_HOST}/easit-data/"

RCD="/httpdocs"
DELETE="--delete"

RETURN_CODE=$(curl -s -o /dev/null -w "%{http_code}" ${WEB_URL})

if [ "${RETURN_CODE}" != "200" ]
then
    echo "Return code: ${RETURN_CODE}"
fi

STARTTIME=$(date +%s)

lftp -c "set ftp:list-options -a 
set ftp:ssl-allow true
set ftp:ssl-force true
set ftp:ssl-protect-data true
set ftp:ssl-protect-list true
set mirror:parallel-directories true;
open '$FTPURL';
lcd $VIW_LCD;
cd $RCD;

mirror -c -vvv -RL \
       --parallel=16 \
       --only-newer \
       $DELETE \
       --verbose \
       --exclude-glob .idea/ \
       --exclude-glob .git/
"

ENDTIME=$(date +%s)

echo "It takes $((($ENDTIME - $STARTTIME)/60)) minutes to complete this task..."
