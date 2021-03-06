#!/bin/bash

echo "Building Plugin..."

# Moves everything including the style sheets over to `dist/`
echo "Copying directories..."
rm -rf dist/
mkdir dist
cp -r -t dist src/{class,languages,templates,img}/
echo "Copying files..."
cp -t dist ./{SECURITY.md,CHANGELOG.md,wpml-config.xml} src/{license.txt,readme.txt,includes.php}
echo "Setting production flag..."
sed "s/'PRODUCTION_ENV', false/'PRODUCTION_ENV', true/g" src/footnotes.php > dist/footnotes.php
echo "Production flag set." 

# TODO: once automatic minification is implemented, this should handle that.
# For now, we shall have to assume that this command is being run on a repo. with
# minimised stylesheet files already in `dist/css/`.
echo "Building stylesheets..."
./_tools/build-stylesheets.sh -c
if [ $? != 0 ]; then echo "Concatenation failed!"; exit 1; fi
echo "Stylesheet build complete."

echo "Minifying CSS and JS..."
mkdir -p dist/{css,js}
npm run minify
if [ $? != 0 ]; then echo "Minification failed!"; exit 1; fi
echo "Minification complete."

echo "Build complete."
exit 0
