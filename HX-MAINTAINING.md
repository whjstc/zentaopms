# HX 禅道代码维护说明

这个仓库用于管理 `zt155-upgrade-web:/var/www/html` 的代码版本，并保留它和禅道官方上游代码之间的差异。

## 当前基线

- 官方上游：`https://github.com/easysoft/zentaopms.git`
- 当前官方基线标签：`zentaopms_22.1_20260413`
- 本地定制分支：`hx/zt155-upgrade-web-current`
- 当前容器快照提交：
  - `d9712d1a Snapshot zt155 upgrade web customizations`
  - `67f856ba Document HX maintenance workflow`

下面这些运行时文件不纳入版本管理：

- `config/db.php`
- `my.php`
- `tmp/`
- `log/`
- `www/data/`
- 在线修复时留下的备份文件，例如 `*.bak-*`

## 查看我们改了什么

查看当前分支相对官方 22.1 基线的提交：

```bash
git log --oneline zentaopms_22.1_20260413..HEAD
```

查看当前分支相对官方 22.1 基线的文件清单：

```bash
git diff --name-status zentaopms_22.1_20260413..HEAD
```

查看当前分支相对官方 22.1 基线的改动统计：

```bash
git diff --stat zentaopms_22.1_20260413..HEAD
```

查看某个文件的具体改动：

```bash
git diff zentaopms_22.1_20260413..HEAD -- module/doc/model.php
```

如果只想看最近一次容器快照提交改了什么：

```bash
git show --stat d9712d1a
git show d9712d1a -- module/doc/model.php
```

## 从 66.42 容器刷新代码

在本仓库根目录执行：

```bash
ssh ops@192.168.66.42 "sudo docker exec zt155-upgrade-web sh -lc 'cd /var/www/html && tar --exclude=./tmp --exclude=./log --exclude=./www/data --exclude=./config/db.php --exclude=./my.php --exclude=./.git -cf - .'" | tar -xf - -C .
git status --short
```

然后先 review diff，只提交确认要纳入版本管理的正式代码，不要提交运行时文件、备份文件和临时文件。

## 合并官方新版本

每次合并官方版本都新建一个集成分支：

```bash
git fetch upstream --tags
git switch -c hx/merge-upstream-22.x hx/zt155-upgrade-web-current
git merge <官方新版本标签或分支>
```

处理冲突时，优先确认我们自己的补丁是否还需要保留，尤其是这些类型的改动：

- 权限视野和 `userview` 刷新
- API session 兼容
- 文档库权限兼容
- Markdown 上传
- 富文本表格展示

合并完成后，对改过的 PHP 文件跑语法检查，再放到测试容器验证，确认没问题后再部署。

如果因为浅克隆导致 Git 提示历史不足，先补齐历史：

```bash
git fetch --unshallow upstream
```

## 部署回容器

长期建议做成正式镜像。当前演练环境可以先把 review 过的文件显式同步回容器，或者用 rsync 同步工作树，但仍要排除运行时目录：

- `config/db.php`
- `my.php`
- `tmp/`
- `log/`
- `www/data/`
