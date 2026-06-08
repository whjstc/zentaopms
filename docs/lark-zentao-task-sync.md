# 飞书多维表格同步禅道任务配置

## 禅道接口

- URL: `https://mp.hexincorp.com:42443/index.php?m=task&f=syncFromLark`
- Method: `POST`
- Header:
  - `Content-Type: application/json`
  - `X-Lark-Zentao-Token: <同步密钥>`

同步密钥不提交到仓库。生产环境建议在容器环境变量或 `config/my.php` 中配置：

```php
$config->task->larkSync->token = '替换为一段长随机密钥';
$config->task->larkSync->account = 'admin';
```

也可以使用环境变量：

```bash
LARK_ZENTAO_SYNC_TOKEN=替换为一段长随机密钥
LARK_ZENTAO_SYNC_ACCOUNT=admin
```

## 固定配置

- 目标执行 ID: `37`
- 默认任务类型: `devel`
- 默认复杂度: `L1`
- 允许同步负责人: `吴汉剑`、`李思凡`、`郭正国`
- 负责人映射规则: 使用禅道用户 `realname` 精确匹配飞书负责人姓名。
- 优先级映射: 飞书 `急` -> 禅道 `A1`，飞书 `中` -> 禅道 `A2`，飞书 `低` -> 禅道 `A3`。

## 飞书表格字段建议

在「任务清单」表中增加以下字段：

- `禅道任务 ID`: 文本。为空时创建任务，非空时更新任务。字段 ID: `fldnPzSwxi`
- `禅道任务链接`: URL 文本。字段 ID: `fldMQpdjrH`
- `同步状态`: 单选，选项 `待同步`、`同步成功`、`同步失败`。字段 ID: `fldqyJ3mYZ`
- `同步时间`: 日期时间。字段 ID: `fldb8a78pg`
- `同步错误`: 文本。字段 ID: `fldIRBbTVY`

## 飞书自动化请求体

在「发送 HTTP 请求」动作中选择 `Raw 格式（JSON）`，请求体参考：

```json
{
  "record_id": "{{记录ID}}",
  "zentaoTaskID": "{{禅道任务 ID}}",
  "任务标题": "{{任务标题}}",
  "任务描述": "{{任务描述}}",
  "负责人": "{{负责人}}",
  "开始时间": "{{开始时间}}",
  "截止时间": "{{截止时间}}",
  "完成时间": "{{完成时间}}",
  "工时 (/人时)": "{{工时 (/人时)}}",
  "优先级": "{{优先级}}"
}
```

接口返回：

```json
{
  "success": true,
  "message": "保存成功",
  "action": "created",
  "taskID": 123,
  "taskLink": "/index.php?m=task&f=view&taskID=123"
}
```

飞书自动化后续步骤应将返回的 `taskID` 回写到 `禅道任务 ID`，将 `taskLink` 回写到 `禅道任务链接`，并更新同步状态。
