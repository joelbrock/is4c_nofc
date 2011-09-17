#!/bin/bash

BACKUPDIR="
/pos/
"
REMUSER="root"
REMPASS=`cat /home/pos-admin/pass/nas_root.pwd`
REMHOST="192.168.123.19"
REMPATH="/mnt/soho_storage/samba/shares/Backups/POSBAK"

rsync -avz -e ssh $BACKUPDIR $REMUSER@$REMHOST:$REMPATH
sleep 4s
echo $REMPASS\r
