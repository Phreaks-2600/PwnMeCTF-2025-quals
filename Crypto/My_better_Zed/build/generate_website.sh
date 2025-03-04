#!/bin/bash

TEMPLATE_DIR=$PWD/templates_generator
WEB_DIR=$PWD/web

cd $TEMPLATE_DIR
# suppression des fichiers css
rm public/css/*.css
rm $WEB_DIR/static/css/*.css

# déplacement du .html

hugo
cp public/index.html $WEB_DIR/templates/index.html

# déplacement du css
cp public/css/*.css $WEB_DIR/static/css/main.css   

# renommage du main.bundle....css en main.css et correction du lien vers le CSS

cd $WEB_DIR/templates
perl -i -p -e 's/\/css\/main.bundle.min.*/static\/css\/main.css"/' index.html

