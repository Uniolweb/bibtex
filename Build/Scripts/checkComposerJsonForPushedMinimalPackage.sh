#!/usr/bin/env bash

TMP="$( cat composer.json | grep "typo3/minimal" )"
if [ "$?" -ne 1 ];then
  echo "[FAIL] 'typo3/minimal' package requirement was pushed in composer.json, remove this."
  exit 1
fi

echo "[OK] composer.json clean, no 'typo3/minimal' found in composer.json"
exit 0
