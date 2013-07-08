git svn clone \
  https://magmi.svn.sourceforge.net/svnroot/magmi \
  --no-metadata \
  -A ~/magmi-conversion/authors-transform.txt \
  --stdlayout \
  magmi-git-tmp

mkdir ~/magmi-conversion/magmi.git
cd ~/magmi-conversion/magmi.git/
git init --bare .
git symbolic-ref HEAD refs/heads/trunk

cd ~/magmi-conversion/magmi-git-tmp
git remote add bare ~/magmi-conversion/magmi.git
git config remote.bare.push 'refs/remotes/*:refs/heads/*'
git push bare

cd ~/magmi-conversion/magmi.git/
git branch -m trunk master
git for-each-ref --format='%(refname)' refs/heads/tags | cut -d / -f 4 | while read ref; \
  do 
     git tag "$ref" "refs/heads/tags/$ref"
     git branch -D "tags/$ref"
  done
git remote add origin ssh://dweeves@magmi.git.sourceforge.net/gitroot/magmi/magmi
git config branch.master.remote origin
git config branch.master.merge refs/heads/master
git push --tags origin master
