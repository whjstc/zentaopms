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
$config->task->larkSync->defaultEstimate = 1;
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
- 默认工时: `1` 人时。飞书 `工时 (/人时)` 为空或 `0` 时使用该值，避免禅道生产环境要求“最初预计”必填时同步失败。
- 默认截止日期: 飞书 `截止时间` 为空时，使用 `完成时间` 的日期部分；如果完成时间也为空，使用接口处理时间。
- 负责人映射规则: 取飞书人员字段的第一个人，依次使用 `account`、`realname`、`email` 精确匹配禅道用户；多人任务仍只同步第一个负责人。
- 优先级映射: 飞书 `急` -> 禅道 `A1`，飞书 `中` -> 禅道 `A2`，飞书 `低` -> 禅道 `A3`。

## 飞书表格字段建议

在「任务清单」表中增加以下字段：

- `禅道任务 ID`: 文本。为空时创建任务，非空时更新任务。字段 ID: `fldnPzSwxi`
- `禅道任务链接`: URL 文本。字段 ID: `fldMQpdjrH`
- `同步状态`: 单选，选项 `待同步`、`同步成功`、`同步失败`。字段 ID: `fldqyJ3mYZ`
- `同步时间`: 日期时间。字段 ID: `fldb8a78pg`
- `同步错误`: 文本。字段 ID: `fldIRBbTVY`

## 飞书自动化请求体

在「发送 HTTP 请求」动作中优先选择表单或 URL-encoded 参数，逐字段传值；不要把飞书字段直接拼到 Raw JSON 字符串里。任务描述、标题等字段可能包含换行、双引号、代码片段，直接拼 Raw JSON 会生成非法 JSON，接口会返回 `Invalid JSON request body.`。

如果必须使用 `Raw 格式（JSON）`，请求体参考：

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
