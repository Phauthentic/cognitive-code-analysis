# Get the latest tag reachable from the previous commit
TAG=$(git describe --tags --abbrev=0 HEAD^ 2>/dev/null)

if [ -n "$TAG" ]; then
  sed -i "s/public const VERSION = .*/public const VERSION = '$TAG';/" src/Application.php
  git add src/Application.php
  echo "Version set to $TAG"
else
  echo "No previous tag found."
fi
