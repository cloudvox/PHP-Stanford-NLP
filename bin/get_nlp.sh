#!/bin/sh
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
JAR="$DIR/../vendor/bin/eNLP.jar";

mkdir -p $DIR/../vendor/bin

function checkCurrent() {
    echo 'Checking current NLP engine version...'
	REMOTE_VERSION=$(php -r "readfile('https://s3.amazonaws.com/enhanced-nlp-dist/current_md5');")
	echo "remote version: $REMOTE_VERSION"
	LOCAL_VERSION=$(md5 $JAR)
	echo "local version: $LOCAL_VERSION"
	if [[ $LOCAL_VERSION == *"$REMOTE_VERSION"* ]]; then
		echo "You have the latest NLP engine"
	else
		getJar
	fi
}

function getJar() {
	echo "Downloading new NLP engine..."
  	php -r "readfile('https://s3.amazonaws.com/enhanced-nlp-dist/enhanced-nlp-0.2.zip');" > $JAR
}

echo "Checking for existing NLP engine..."
if [ ! -f $JAR ]; then
	echo "No NLP engine found..."
	getJar
else
	echo "Found NLP engine"
	checkCurrent
fi
