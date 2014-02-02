#!/usr/bin/env bash
UUID=$(uuidgen)
openssl genrsa -out keys/$UUID.pem 1024
openssl rsa -in keys/$UUID.pem -pubout > keys/$UUID.pub