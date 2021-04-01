#!/bin/sh -l

echo '------------------Releasing module-------------------'
mkdir -p EasySales/Integrari
#cp -R ./Integrari EasySales/
rsync -av --progress ./ EasySales/ --exclude .git --exclude .github
cd EasySales/Integrari
echo '------------------Setting URL------------------------'
sed -i "s,MICROSERVICE_URL = .*,MICROSERVICE_URL = \"$1\";," ./Core/EasySales.php
version=$(sed -nE 's/^\s*"version": "(.*?)",$/\1/p' ./composer.json)
cd ../../
echo '------------------Creating ZIP-----------------------'
zip -r magento2-microservice.zip ./EasySales
echo '------------------Sending ZIP------------------------'
curl -vvv -F "file=@./magento2-microservice.zip" -F "module=${WEBSITE_TYPE_ID}" -F "version=${version}" -F "secret=${MODULE_SECRET}" $2
