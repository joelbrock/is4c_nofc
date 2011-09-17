#!/bin/bash
PATH='/pos/is4c/ini/ini.php'
ACTIVE=0
/bin/grep 'tenPercentDay\"] = 1' $PATH -q && ACTIVE=1

if [ $ACTIVE = 1 ]; then
	/usr/bin/perl -p -i -e 's/tenPercentDay\"] = 1/tenPercentDay\"] = 0/' $PATH
else
	/usr/bin/perl -p -i -e 's/tenPercentDay\"] = 0/tenPercentDay\"] = 1/' $PATH
fi
