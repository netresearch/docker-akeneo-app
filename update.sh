#!/usr/bin/env bash

set -e

git update-index -q --ignore-submodules --refresh

# Disallow unstaged changes in the working tree
if ! git diff-files --quiet --
then
    echo >&2 "You have unstaged changes:"
    git diff-files --name-status -r -- >&2
    err=1
fi

# Disallow uncommitted changes in the index
if ! git diff-index --cached --quiet HEAD --ignore-submodules --
then
    echo >&2 "Your index contains uncommitted changes:"
    git diff-index --cached --name-status -r --ignore-submodules HEAD -- >&2
    err=1
fi

if [ $err ]
then
    echo >&2 "Please commit or stash them."
    exit 1
fi

eval $(cat .env)

git push
git tag -af "$AKENEO_VERSION" -m "Tagging $AKENEO_VERSION"
git push --tags -f