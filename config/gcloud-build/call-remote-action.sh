#!/bin/bash

ABNORMAL_EXIT=0

usage() {
  echo "Usage: $0 [ -a ACTION ] " 1>&2
}
prepare_abnormal_exit() {
  ABNORMAL_EXIT=1
}
abnormal_exit() {
  usage
  exit 1
}

success_exit() {
  echo "Remote action successful."
  exit 0
}


while getopts :a: option
do
  case "${option}" in
    a) ACTION=${OPTARG};;
    :)
      echo "Error: -${OPTARG} requires an argument."
      exit_abnormal
      ;;
    *) exit_abnormal;;
  esac
done

if [ "xyz" == "xyz$ACTION" ]; then
  echo "Error: -a option is required."
  prepare_abnormal_exit
fi

if [ "xyz" == "xyz$DOMAIN_NAME" ]; then
  echo "Error: DOMAIN_NAME env var is not defined"
  prepare_abnormal_exit
fi

if [ "xyz" == "xyz$APP_SECRET" ]; then
  echo "Error: APP_SECRET env var is not defined"
  prepare_abnormal_exit
fi

if [ $ABNORMAL_EXIT -eq 1 ]; then
  abnormal_exit
fi

echo "Calling $ACTION remote action..."

echo "" > remote-action-response-${ACTION}.txt

TIMESTAMP=`date +%s`
HASH=`echo  -n "$ACTION:$TIMESTAMP" | openssl sha256 -hmac "$APP_SECRET"`
HASH=${HASH#*= }
URL=https://${DOMAIN_NAME}/_util/${ACTION}
echo $URL

# If testing from dev environment with self-signed certs, add the "-k" option to curl
HTTP_STATUS=$(curl ${URL} -w "%{http_code}" -G -d timestamp=${TIMESTAMP} -d hmac=${HASH} -s -o remote-action-response-${ACTION}.txt)

# Host not resolved? Assume fresh install
if [ $? == 6 ]; then
  echo "Host not resolved. Stopping gracefully."
  success_exit
fi

# Connection refused? Assume fresh install
if [ $? == 7 ]; then
  echo "Host not resolved. Stopping gracefully."
  success_exit
fi

# 404? Assume fresh install or first deployment after adding script
if [ $HTTP_STATUS == "404" ]; then
  echo "Remote script not found. Stopping gracefully."
  success_exit
fi

cat remote-action-response-${ACTION}.txt
echo

if [ $HTTP_STATUS == "200" ]; then
  success_exit
fi


echo "Invalid HTTP response code: $HTTP_STATUS"
exit 1
