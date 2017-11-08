#!/bin/bash
# Backup the database before starting.
cd /var/www
dir=bartonphillipsnet/other
bkupdate=`date +%B-%d-%y`
filename="BARTONLP_NET_BACKUP.$bkupdate.sql"

mysqldump --user=root --no-data --password=bartonl411 barton 2>/dev/null > $dir/bartonphillipsnet.schema
mysqldump --user=root --add-drop-table --password=bartonl411 barton 2>/dev/null >$dir/$filename
gzip $dir/$filename

echo "bkupdb.sh for bartonphillipsnet Done"

