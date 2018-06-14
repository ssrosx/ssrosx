#!/usr/bin/env bash
# Clear local git cache
# Author:SSROSX

git rm -r --cached .
git add .
git commit -m 'update .gitignore'