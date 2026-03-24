# README bibtex

TYPO3 extension for displaying bibtex entries. The bibtex entries are loaded from a bibtex file which can be either
in the filesystem of the TYPO3 site or loaded via HTTPS.

Currently a no longer maintained bibtex library (osbib) is used. This will be replaced in the future!

## Build - create release for non Composer

* see Stack Overflow ["TYPO3: How to publish an extension to TER with Github actions and tailor and add third party library on the fly"](TYPO3: How to publish an extension to TER with Github actions and tailor and add third party library on the fly)

This should be modified slightly because we don't use TER.

Replace the version number with appropriate version number.

Run:

    composer build:non-composer-local
    git checkout -b release-non-composer-1.0.0
    git add .;git commit -m "Non-Composer release 1.0.0"
    git tag non-composer-release-1.0.0
    git push origin non-composer-release-1.0.0
    git archive --format=tar.gz -o /tmp/bibtex-1.0.0.tar.gz --prefix=bibtex/ non-composer-release-1.0.0
    git checkout main
    echo "Release is in /tmp/"
