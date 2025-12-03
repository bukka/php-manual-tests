#!/bin/bash

if [ -d acl-test ]; then
  rm -rf acl-test
fi

mkdir acl-test
setfacl -m d:g:www-data:rx acl-test
