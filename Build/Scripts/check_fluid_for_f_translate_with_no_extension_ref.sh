#!/bin/bash
# prüfen, ob es Fluid Dateien gibt, die f:translate enthalten, aber keinen extensionName Attribut oder vollen Pfad mit LLL
#
# s. https://gitlab.uni-oldenburg.de/it-dienste/typo3/unioltemplate/-/issues/265
# https://docs.typo3.org/m/typo3/reference-typoscript/12.4/en-us/ContentObjects/Fluidtemplate/Index.html#confval-fluidtemplate-extbase-controllerextensionname
# https://forge.typo3.org/issues/102315

# dies ist eine sehr grobe Abfrage, es könnten auch mehrere f:translate in einer Zeile sein, dies würde hiermit nicht gefunden
# werden, ist aber besser als nix

if [ ! -d Resources/Private ];then
    echo "[ok] No Resources/Private exists"
    exit 0
fi

count=$(grep -r f:translate Resources/Private | grep -v LLL | grep -v extensionName | wc -l)
if [ $count -ne 0 ];then
  echo "[FAIL] f:uri.resource found without EXT-path or extensionName"
  grep -r f:translate Resources/Private | grep -v LLL | grep -v extensionName
  exit 1
fi

echo "[ok] no  f:uri.resource found without EXT-path or extensionName"
exit 0
