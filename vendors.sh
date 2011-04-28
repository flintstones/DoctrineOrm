#!/bin/sh
cd $(dirname $0)
git submodule update --init
cd vendor/silex && git submodule update --init && cd ../..
