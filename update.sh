#!/usr/bin/env bash

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

if [ "$1" == "all" ]; then
    echo "Updating all branches:";
    CURRENT_BRANCH=$(git rev-parse --symbolic-full-name --abbrev-ref HEAD)
    git checkout master
    git pull
    for branch in $(git for-each-ref --format='%(refname:short)' refs/heads/); do
        git checkout "$branch"
        if [ "$branch" != "master" ]; then
            git pull
            git rebase master
        fi
        ./update.sh
    done
    git checkout "$CURRENT_BRANCH"
    exit
fi

eval $(cat .env)

git push
git tag -af "$AKENEO_VERSION" -m "Tagging $AKENEO_VERSION"
git push --tags -f