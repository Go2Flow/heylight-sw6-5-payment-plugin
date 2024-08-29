#!/bin/sh
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color
if [ -z "$1" ]
  then
    echo "${RED}No arguments supplied. Please name the Plugin Version${NC}"
    exit 1
fi
if [ -z "$2" ]
  then
    echo "${RED}No arguments supplied. Please name the Shopware Version${NC}"
    exit 1
fi
echo "${GREEN}-----start packing-----${NC}"
echo
mkdir -p Archive/tmp/Go2FlowHeyLightPayment
rm -rf Archive/tmp/Go2FlowHeyLightPayment/*
cp -rp src Archive/tmp/Go2FlowHeyLightPayment/
cp -rp composer.json Archive/tmp/Go2FlowHeyLightPayment/
cp -rp .gitignore Archive/tmp/Go2FlowHeyLightPayment/
cp -rp CHANGELOG.md Archive/tmp/Go2FlowHeyLightPayment/
cp -rp CHANGELOG_de-DE.md Archive/tmp/Go2FlowHeyLightPayment/
cp -rp DESCRIPTION.md Archive/tmp/Go2FlowHeyLightPayment/
cp -rp DESCRIPTION_de-DE.md Archive/tmp/Go2FlowHeyLightPayment/
cd Archive/tmp/
echo
zip -r Go2FlowHeyLightPayment-SW$2-P$1.zip . -x '**/.*' -x '**/__MACOSX'
mv Go2FlowHeyLightPayment-SW$2-P$1.zip ../
#rm -rf ./*
cd ../
cd ../
echo
echo "${GREEN}-----done!-----${NC}"
