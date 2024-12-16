export PHP_DL_TEST_MODULE_DEBUG=1
exec php-fpm -R -F -c root/etc/php.ini -y fpm.conf
