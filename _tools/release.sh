#!/bin/bash

# This is a release helper script aimed at walking maintainers through the
# process of issuing new releases of the Plugin, both to reduce the risk of
# procedural errors and to provide a framework for future incremental
# automation.
#
# Step 1: Ensure the local copy has checked out the `main` branch
# Step 2: Ensure local copy of `main` is up-to-date with remote
# Step 3: Check versioning
# Step 3(a): Get all version declarations
# Step 3(b): Check that all version declarations exists
# Step 3(c)(1): Check that all development versions match
# Step 3(c)(2): Check that all stable versions match
# Step 3(d): Check that the development version is correctly flagged as such
# Step 3(e): Check that the 'Stable Tag' field is set to a stable version
# Step 3(f): Check that the 'Stable Tag' field points to a real tag on the SVN repo.
# Step 3(g): Check that the changelog is up-to-date
# Step 4: Build the Plugin
# Step 5: Update the version to pre-release
# Step 6: Tag the release

echo "Welcome to the footnotes release helper!"
echo "========================================"

if [[ $1 == "-c" ]]; then
	read -p "You have passed the \`commit\` flag (\`-c\`). Did you mean to do this? (Y/N): " CONFIRM && [[ $CONFIRM == [yY] || $CONFIRM == [yY][eE][sS] ]] || exit 1
fi

# Unless forced to, the script will only run on the `main` branch.
if [[ $1 != "-f" ]]; then

	# Step 1: Ensure the local copy has checked out the `main` branch

	if [[ "$(git rev-parse --abbrev-ref HEAD)" != "main" ]]; then
		echo "ERR: You are not on the \`main\` branch, please check it out and re-run this command."
		exit 1
	else
		echo "- \`main\` branch is checked out."
	fi

	# Step 2: Ensure local copy of `main` is up-to-date with remote

	if [[ "$(git status | grep -c 'Your branch is up to date')" != 1 ]]; then
		echo "ERR: Your local copy is not up-to-date with the remote, please update it and re-run this command."
		exit 1
	else
		echo "- Local copy of \`main\` is up-to-date with remote."
	fi

fi

rm -rf ./{dist,tmp,svn-tmp}

# Step 3: Check versioning

# Step 3(a): Get all version declarations

# NOTE: I'm not sure why, but if you try to get the root header
# version without piping it through the second `grep` command,
# you'll end up with a list of files before the version 
# declaration.

echo "- Checking versions..."

STABLE_TAG="$(grep "Stable Tag:" src/readme.txt)"
ROOT_HEADER_VERSION="$(grep " Version:" src/footnotes.php | grep -Po " Version: \d+\.\d+(\.\d+)?[a-z]?$")"
JS_VERSION="$(grep "version:" src/js/wysiwyg-editor.js)"

# Step 3(b): Check that all version declarations exists

if [[ -z $STABLE_TAG ]]; then
	echo "ERR: No 'Stable Tag' field found in \`src/readme.txt\`!"
	exit 1
else echo "- 'Stable Tag' field set in \`src/readme.txt\`."
fi
if [[ -z $ROOT_HEADER_VERSION ]]; then
	echo "ERR: No 'Version' field found in \`src/footnotes.php\` file header!"
	exit 1
else echo "- 'Version' field set in \`src/footnotes.php\` file header."
fi
if [[ -z $JS_VERSION ]]; then
	echo "ERR: No \`version\` variable found in \`src/js/wysiwyg-editor.js\`!"
	exit 1
else echo "- \`version\` variable set in \`src/js/wysiwyg-editor.js\`."
fi

# Step 3(c)(1): Check that all development versions match
# NB: This doesn't currently do anything as there is only one place where the
# development version is listed.

if [[ "$(echo $JS_VERSION | grep -Poc '\d+\.\d+(\.\d+)?')" != 1 ]]; then
	echo "ERR: Development version mismatch!"
	echo -e "The following versions were found:\n"
	echo -e '\t' $JS_VERSION '\n'
	echo "Please ensure that all development versions match and re-run this command."
	exit 1
else echo "- Development versions match."
fi

# Step 3(c)(2): Check that all stable versions match

if [[ "$(echo $ROOT_HEADER_VERSION $STABLE_TAG | grep -Poc '\d+\.\d+(\.\d+)?')" != 1 ]]; then
	echo "ERR: Stable version mismatch!"
	echo -e "The following versions were found:\n"
	echo -e '\t' $ROOT_HEADER_VERSION
	echo -e '\t' $STABLE_TAG '\n'
	echo "Please ensure that all stable versions match and re-run this command."
	exit 1
else echo "- Stable versions match."
fi

# Step 3(d): Check that the development version is correctly flagged as such

if [[ "$(echo $JS_VERSION | grep -Poc '\d+d')" != 1 ]]; then
	echo "ERR: Development version flag not set!"
	echo -e "The following version was found:\n"
	echo -e '\t' $JS_VERSION '\n'
	echo "Please ensure that the development flag ('d') is set and re-run this command."
	exit 1
else echo "- Development version flag is set."
fi

DEVELOPMENT_VERSION="$(echo $JS_VERSION | grep -Po '\d+\.\d+(\.\d+)?')"

echo -e "- Development version:" $DEVELOPMENT_VERSION

# Step 3(e): Check that the 'Stable Tag' field is set to a stable version

if [[ "$(echo $STABLE_TAG | grep -Poc '\d+\.\d+(\.\d+)?$')" != 1 ]]; then
	echo "ERR: 'Stable Tag' not set to a stable version!"
	echo -e "The 'Stable Tag' field is set to the following:\n"
	echo -e '\t' $STABLE_TAG '\n'
	echo "Please ensure that the 'Stable Tag' field is set to a stable version and re-run this command."
	exit 1
else echo "- 'Stable Tag' field set to stable version."
fi

STABLE_VERSION="$(echo $STABLE_TAG | grep -Po '\d+\.\d+(\.\d+)?$')"

echo "- Stable version:" $STABLE_VERSION

# Step 3(f): Check that the 'Stable Tag' field points to a real tag on the SVN repo.

echo "- Checking stable tag exists..."
git svn tag --dry-run $STABLE_VERSION &>/dev/null

if [ $? -ne 1 ]; then
	echo "ERR: 'Stable Tag' does not point to an existing tag!"
	echo "Please ensure that the 'Stable Tag' field points to an existing stable version and re-run this command."
	exit 1
else echo "- 'Stable Tag' field is set to existing tag."
fi

# Step 3(g): Check that the changelog is up-to-date

CHANGELOG_LATEST="$(awk -e '/== Changelog ==/,/= [0-9]+\.[0-9]+(\.[0-9]+)? =/' src/readme.txt | grep -Po '\d+\.\d+(\.\d+)?')"
if [[ $CHANGELOG_LATEST != $DEVELOPMENT_VERSION ]]; then
	echo "ERR: Changelog is not up-to-date!"
	echo "Current version is $DEVELOPMENT_VERSION"
	echo "Latest version in changelog is $CHANGELOG_LATEST"
	echo "Please ensure that the changelog is up-to-date and re-run this command."
	exit 1
else echo "- Changelog is up-to-date."
fi

echo -e "- Version check complete.\n"

# Step 4: Build the Plugin

echo "- Building Plugin..."
composer run build
if [ $? != 0 ]; then echo "Build failed!"; exit 1; fi
echo -e "- Build complete.\n"

# Step 5: Update the version to pre-release

echo "- Setting pre-release version flags..."
PRERELEASE_VERSION=$DEVELOPMENT_VERSION'p'
sed -i "s/$JS_VERSION/$PRERELEASE_VERSION/g" dist/js/wysiwyg-editor.min.js
echo "- Pre-release flags set." 

# Step 6: Tag the release

echo "- Tagging release..."
git tag -a $DEVELOPMENT_VERSION -m "Pre-release of version $DEVELOPMENT_VERSION"
if [ $? != 0 ]; then echo "Tag already exists!"; exit 1; fi
git push --tags --no-verify
if [ $? != 0 ]; then echo "Push failed (tag probably exists on remote)!"; exit 1; fi
echo "- Release tagged."

# Step 7: Push release to SVN repo.

# Step 7(a): Create a new local copy of the SVN repo.

echo -e "\nThe helper will now guide you through the steps to push the new release to the WordPress Plugin Directory SVN repository."
echo "For the time being, this part of the process shall remain (mostly) manual."
read -p "Are you ready to continue? (Y/N): " CONFIRM && [[ $CONFIRM == [yY] || $CONFIRM == [yY][eE][sS] ]] || exit 1

echo "Creating local copy of SVN repo..."
svn checkout https://plugins.svn.wordpress.org/footnotes svn-tmp --depth immediates
svn update --quiet svn-tmp/trunk --set-depth infinity
svn update --quiet svn-tmp/assets --set-depth infinity
svn update --quiet svn-tmp/tags/$PRERELEASE_VERSION --set-depth infinity
echo -e "Local copy created.\n"

# Step 7(b): Update `trunk/`
echo -e "Copying files from \`dist/\` to SVN \`trunk/\`...\n"
rsync -avhic dist/ svn-tmp/trunk/ --delete | grep -v "^\."
rsync -avhic assets/ svn-tmp/assets/ --delete | grep -v "^\."
read -p "Does the above list of changes (additions and deletions ONLY) look correct? (Y/N): " CONFIRM && [[ $CONFIRM == [yY] || $CONFIRM == [yY][eE][sS] ]] || exit 1
echo -e "Copying complete.\n"

# Step 7(c): Set a release message

echo "Getting commit message from changelog..."
CHANGELOG_MESSAGE="$(awk -e "/= $DEVELOPMENT_VERSION =/,/= $STABLE_VERSION =/" dist/readme.txt | grep '^-')"
echo -e "The changelog message for this version is:\n"
echo -e "$CHANGELOG_MESSAGE\n"
read -p "Is this correct? (Y/N): " CONFIRM && [[ $CONFIRM == [yY] || $CONFIRM == [yY][eE][sS] ]] || exit 1
echo -e "Commit message set.\n"

# Step 7(d): Review

clear
echo "Let's review before pushing to the SVN..."
echo -e "Here is my current state:\n"
echo "Current version:"
echo -e '\t' $PRERELEASE_VERSION
echo "Stable version:"
echo -e '\t' $STABLE_VERSION '\n'
echo -e "Commit message:\n"
echo -e "$CHANGELOG_MESSAGE" '\n'
svn stat svn-tmp/trunk/ | grep '^\!' | sed 's/! *//' | xargs -I% svn rm % >/dev/null
echo -e "Changes made to local \`trunk/\` (should only be 'M', 'D' and '?'):\n"
svn stat svn-tmp/trunk/
echo ""
echo -e "\`readme.txt\` header:\n"
head svn-tmp/trunk/readme.txt
echo ""
read -p "Is this all correct? (Y/N): " CONFIRM && [[ $CONFIRM == [yY] || $CONFIRM == [yY][eE][sS] ]] || exit 1

# Step 7(d): Push to remote `trunk/` (provided the flag is set)

if [[ $1 == "-c" ]]; then
	cd svn-tmp && svn ci -m "$CHANGELOG_MESSAGE" && cd ..
else echo "- Commit flag not set, skipping commit step."
fi

# Step 8: Cleanup

#rm -rf {dist/,svn-tmp/}
