#!/bin/bash

# Bump version script
# Usage: ./scripts/bump-version.sh [major|minor|patch]

TYPE=${1:-patch}

if [ ! -f VERSION ]; then
    echo "1.0.0" > VERSION
fi

CURRENT=$(cat VERSION)

case $TYPE in
    major)
        NEW=$(echo $CURRENT | awk -F. '{print $1+1".0.0"}')
        ;;
    minor)
        NEW=$(echo $CURRENT | awk -F. '{print $1"."$2+1".0"}')
        ;;
    patch)
        NEW=$(echo $CURRENT | awk -F. '{print $1"."$2"."$3+1}')
        ;;
    *)
        echo "Usage: $0 [major|minor|patch]"
        exit 1
        ;;
esac

echo "Bumping version: $CURRENT → $NEW"
echo "$NEW" > VERSION

echo "Version bumped to $NEW"
echo "Commit and tag:"
echo "  git add VERSION"
echo "  git commit -m \"chore: bump version to v$NEW\""
echo "  git tag v$NEW"
echo "  git push origin v$NEW"
