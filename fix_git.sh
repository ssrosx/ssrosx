#!/usr/bin/env bash
# Clear local git cache
# Author:Patatas

git rm -r --cached .
git add .
git commit -m 'update .gitignore'