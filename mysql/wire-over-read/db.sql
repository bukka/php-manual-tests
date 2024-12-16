CREATE DATABASE php_test;

CREATE USER 'php_test'@'localhost' IDENTIFIED BY 'PHPdev0*';
GRANT ALL PRIVILEGES ON php_test.* TO 'php_test'@'localhost';