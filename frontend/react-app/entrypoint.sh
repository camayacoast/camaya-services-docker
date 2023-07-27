#!/bin/sh


npm rebuild esbuild
npm install

exec "$@"