# HX ZenTao PMS Maintenance

This repository tracks the `zt155-upgrade-web:/var/www/html` codebase against upstream ZenTao PMS.

## Baseline

- Upstream remote: `https://github.com/easysoft/zentaopms.git`
- Current baseline tag: `zentaopms_22.1_20260413`
- Local customization branch: `hx/zt155-upgrade-web-current`
- Runtime files are intentionally not versioned:
  - `config/db.php`
  - `my.php`
  - `tmp/`
  - `log/`
  - `www/data/`
  - online repair backup files such as `*.bak-*`

## Refresh From 66.42 Container

Run from this repository root:

```bash
ssh ops@192.168.66.42 "sudo docker exec zt155-upgrade-web sh -lc 'cd /var/www/html && tar --exclude=./tmp --exclude=./log --exclude=./www/data --exclude=./config/db.php --exclude=./my.php --exclude=./.git -cf - .'" | tar -xf - -C .
git status --short
```

Review the diff, then commit the intended changes.

## Merge A New Upstream Version

Use a new integration branch for each official upgrade:

```bash
git fetch upstream --tags
git switch -c hx/merge-upstream-22.x hx/zt155-upgrade-web-current
git merge <upstream-tag-or-branch>
```

Resolve conflicts by keeping local fixes where they still apply, especially permission/session/doc compatibility changes. Then run syntax checks for changed PHP files and test the upgraded container before deploying.

If this repository was cloned shallowly and Git refuses an operation that needs more history:

```bash
git fetch --unshallow upstream
```

## Deploy Back To Container

Prefer building a proper image later. For the current rehearsal container, deploy reviewed file changes explicitly, or rsync the working tree while still excluding runtime paths.
