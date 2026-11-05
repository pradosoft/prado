# Composer Lock Update
composer update --lock

# When asking for github Password
ssh -T git@github.com
git remote -v
git remote set-url origin git@github.com:belisoful/prado.git

# Diff Branches
git log branchA..branchB --oneline
git log --oneline --left-right branchA...branchB
git diff branchA..branchB --stat

git status


#  Move HEAD back one, and revert the changes with a forced push
# does restore files
git reset --soft HEAD~1
git push --force


# remove deleted remote branches.
git fetch --prune

# replay unique current branch commits on top of -base- (eg. master)
git rebase master
git push --force-with-lease



git branch		# list local branches
git branch -r 	# remote hhhh
git branch -a	# list all branches 



git checkout main
git pull
git checkout -b my-new-branch		# new branch
git add file1 file2
git commit -m "Add two new files"
git push -u origin my-new-branch
git checkout main

# branch links
git branch -vv


# push local branch to its remote origin
git push -u origin my-branch

# Stashing
(https://git-scm.com/docs/git-stash)[https://git-scm.com/docs/git-stash]


# Stash
including untracked files in a stash
```sh
git stash push --include-untracked -m "message regarding stash"
git stash push -u 		# is the same as `--include-untracked`

git stash apply		# the top stash
git stash pop		# the top stash

git stash list                    # see what's there, e.g. stash@{0}, stash@{1}
git stash apply stash@{2}         # apply a specific one

git stash show stash@{0}          # summary of what's in it
git stash show -p stash@{0}       # full diff of what's in it
git stash drop stash@{0}          # delete a specific stash without applying
git stash clear                   # nuke all stashes
```



# remove push on upstream - standard setup
git remote set-url --push upstream no_push


# After a PR-merge - to bring local and fork up to date
#     Reset because no longer want your old local commits replayed
git fetch upstream
git checkout csp
git reset --hard upstream/csp
git push --force-with-lease origin csp


# lock problem:
rm -f .git/index.lock
find .git -name "*.lock"
rm -f .git/*.lock

# force move branch to the HEAD of another
#   force-moved the local `csp-update` pointer to current HEAD of `csp`
git branch -f csp-update csp
git push origin csp-update --force-with-lease



# fork a parent branch
git remote add upstream https://github.com/pradosoft/prado.git
git fetch upstream
git checkout -b newbranch upstream/newbranch
git push -u origin newbranch
