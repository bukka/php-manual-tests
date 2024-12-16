#!/bin/bash

cmd="sudo /usr/local/apache2/bin/httpd -DFOREGROUND -f `pwd`/apache2.conf"

echo $cmd
exec $cmd
