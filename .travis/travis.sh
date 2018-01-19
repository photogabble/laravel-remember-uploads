#!/usr/bin/env bash

# The problem is that we do not want to remove the configuration file, just disable it for a few tasks, then enable it
#
# For reference, see
#
# - https://docs.travis-ci.com/user/languages/php#Disabling-preinstalled-PHP-extensions
# - https://docs.travis-ci.com/user/languages/php#Custom-PHP-configuration
#
# Original Source (this was copied from):
# - https://github.com/codeclimate/php-test-reporter/blob/master/.travis/travis.sh

config="/home/travis/.phpenv/versions/$(phpenv version-name)/etc/conf.d/xdebug.ini"

function xdebug-disable() {
    if [[ -f $config ]]; then
        mv $config "$config.bak"
    fi
}

function xdebug-enable() {
    if [[ -f "$config.bak" ]]; then
        mv "$config.bak" $config
    fi
}

function run-tests() {
    if [[ "$WITH_COVERAGE" == "true" ]]; then
        xdebug-enable
        vendor/bin/phpunit --coverage-clover=$TRAVIS_BUILD_DIR/build/logs/clover.xml
        xdebug-disable
    else
       vendor/bin/phpunit
    fi
}