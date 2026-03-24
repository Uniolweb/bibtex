#!/bin/bash
# check if Classes exist
# - used for packages where autoloading etc. is not set up

if [ -d Classes ];then
    echo "[FAIL] composer.lock exists, run cleanup script!"
    exit 1
fi

echo "[ok] Classes do not exist"
exit 0
