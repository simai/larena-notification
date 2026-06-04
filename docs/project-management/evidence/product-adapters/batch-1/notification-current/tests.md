# Tests

Commands executed:

```bash
PATH=/opt/homebrew/opt/php@8.3/bin:$PATH /Applications/ServBay/package/bin/composer validate --strict
PATH=/opt/homebrew/opt/php@8.3/bin:$PATH /Applications/ServBay/package/bin/composer dump-autoload
PATH=/opt/homebrew/opt/php@8.3/bin:$PATH /Applications/ServBay/package/bin/composer run quality:gate
git diff --check
/bin/zsh .githooks/pre-commit
/bin/zsh .githooks/pre-push
```

Expected result: all checks pass before merge.

