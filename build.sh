#!/bin/bash

PLUGIN_SLUG="film-equipment-rental"
BUILD_DIR="build"
VERSION=$(grep -oP 'Version:\s*\K[^\s]+' gear.php)

# Clean up previous builds
rm -rf $BUILD_DIR
mkdir $BUILD_DIR

# Copy plugin files to build directory
rsync -av --exclude='build' --exclude='node_modules' --exclude='.git' --exclude='.gitignore' --exclude='composer.lock' --exclude='composer.json' . $BUILD_DIR/$PLUGIN_SLUG

# Install production dependencies
cd $BUILD_DIR/$PLUGIN_SLUG
composer install --no-dev

# Create the .zip file
cd ..
zip -r ${PLUGIN_SLUG}-${VERSION}.zip $PLUGIN_SLUG

# Clean up
rm -rf $PLUGIN_SLUG

echo "Build complete: ${PLUGIN_SLUG}-${VERSION}.zip"