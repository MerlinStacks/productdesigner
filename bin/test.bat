@echo off
set WP_TESTS_DIR=C:\path\to\wordpress-tests-lib
vendor\bin\phpunit -c phpunit.xml %*
