-- DROP TABLE IF EXISTS `zt_ai_teammate`;
CREATE TABLE IF NOT EXISTS `zt_ai_teammate` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(10) NOT NULL DEFAULT 'rnd' COMMENT '员工名称',
  `desc` varchar(1000) NOT NULL DEFAULT '' COMMENT '员工描述',
  `llm` varchar(255) NOT NULL DEFAULT '' COMMENT '执行 AI 任务时默认使用的模型 ID, 对应 ZAI 中的模型 ID',
  `role` varchar(10) NOT NULL DEFAULT '' COMMENT '员工职位, 与 zt_user.role 来源相同',
  `avatar` text DEFAULT NULL COMMENT '员工头像, 对应 ZAI 中的头像文件路径',
  `agents` varchar(255) NOT NULL DEFAULT '' COMMENT '挂载的智能体ID, 多个用逗号分隔',
  `klibs` varchar(255) NOT NULL DEFAULT '' COMMENT '挂载的知识库ID, 多个用逗号分隔',
  `disabled` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '是否禁用',
  `createdBy` varchar(30) NOT NULL DEFAULT '' COMMENT '创建者',
  `createdDate` datetime DEFAULT NULL COMMENT '创建时间',
  `editedBy` varchar(30) NOT NULL DEFAULT '' COMMENT '编辑者',
  `editedDate` datetime DEFAULT NULL COMMENT '编辑时间',
  `deleted` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '是否已删除',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `zt_ai_teammate` (`id`, `name`, `desc`, `llm`, `role`, `avatar`, `agents`, `klibs`, `disabled`, `createdBy`, `createdDate`, `editedBy`, `editedDate`, `deleted`) VALUES
(1, '向前', '专注于项目全周期管理与资源协调，擅长将复杂业务目标高效转化为可执行的项目计划与交付成果。', '', 'pm', '/static/images/ai/ai-teammate-pm.jpg', '', '', 0, 'system', '2025-12-29 08:42:37', '', NULL, 0),
(2, '李娟', '专注于测试用例设计与自动化脚本开发，擅长将业务场景高效转化为可执行的测试方案与质量保障体系。', '', 'qa', '/static/images/ai/ai-teammate-qa.jpg', 'zt_story_to_testcase,zt_bug_polishing', '', 0, 'system', '2025-12-29 08:42:37', '', NULL, 0),
(3, '阿道', '专注于后端架构设计与系统性能优化，擅长将业务需求高效转化为稳定可靠的代码实现。', '', 'td', '/static/images/ai/ai-teammate-td.jpg', 'zt_task_polishing,zt_story_to_task,zt_create_doc', '', 0, 'system', '2025-12-29 08:42:37', '', NULL, 0),
(4, '阿禅', '专注于产品全流程体验设计与用户需求挖掘，擅长将抽象业务逻辑高效转化为可落地的产品方案。', '', 'po', '/static/images/ai/ai-teammate-po.jpg', 'zt_story_polishing,zt_story_prototype,zt_release_newsletter,zt_bug_to_story,zt_split_productplan', '', 0, 'system', '2025-12-29 08:42:37', '', NULL, 0);

-- DROP TABLE IF EXISTS `zt_ai_task`;
CREATE TABLE IF NOT EXISTS `zt_ai_task` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `teammate` int unsigned NOT NULL DEFAULT 0 COMMENT '执行此任务的数字员工 ID',
  `type` varchar(50)  NOT NULL DEFAULT '' COMMENT '任务类型',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '任务名称',
  `desc` varchar(1000) NOT NULL DEFAULT '' COMMENT '任务描述',
  `status` varchar(20)  NOT NULL DEFAULT '' COMMENT '任务状态',
  `objectType` varchar(30) NOT NULL DEFAULT '' COMMENT '任务相关对象类型',
  `objectID` int unsigned NOT NULL DEFAULT 0 COMMENT '任务相关对象 ID',
  `agents` varchar(255) NOT NULL DEFAULT '' COMMENT '实际使用的智能体 ID, 多个用逗号分隔',
  `agentsData` text DEFAULT NULL COMMENT '禅道智能体填写的表单数据',
  `klibs` varchar(255) NOT NULL DEFAULT '' COMMENT '实际挂载的知识库ID, 多个用逗号分隔',
  `chatID` int unsigned NOT NULL DEFAULT 0 COMMENT '对话 ID。任务未被队列处理时为 0, 重新处理时会创建新对话并更新此 ID',
  `result` text DEFAULT NULL COMMENT '任务处理结果，包括要操作的禅道对象、要创建的文档等数据，对此次任务的 AI 总结, 使用 JSON 存储',
  `userPrompt` text DEFAULT NULL COMMENT '验收不通过时，用户补充的提示词',
  `consumed` decimal(10,2) unsigned NOT NULL DEFAULT 0.00 COMMENT '总计耗时',
  `token` int unsigned NOT NULL DEFAULT 0 COMMENT '总计消耗 token',
  `createdBy` varchar(30) NOT NULL DEFAULT '' COMMENT '创建人',
  `createdDate` datetime DEFAULT NULL COMMENT '创建时间',
  `assignedTo` varchar(30) NOT NULL DEFAULT '' COMMENT '负责人',
  `assignedDate` datetime DEFAULT NULL COMMENT '指派时间',
  `verifiedBy` varchar(30) NOT NULL DEFAULT '' COMMENT '验收者',
  `verifiedDate` datetime DEFAULT NULL COMMENT '验收时间',
  `activatedBy` varchar(30) NOT NULL DEFAULT '' COMMENT '激活者',
  `activatedDate` datetime DEFAULT NULL COMMENT '激活时间',
  `cancelledBy` varchar(30) NOT NULL DEFAULT '' COMMENT '取消者',
  `cancelledDate` datetime DEFAULT NULL COMMENT '取消时间',
  `closedBy` varchar(30) NOT NULL DEFAULT '' COMMENT '关闭者',
  `closedDate` datetime DEFAULT NULL COMMENT '关闭时间',
  `executedBy` varchar(30) NOT NULL DEFAULT '' COMMENT '执行者，通过定时任务执行的任务为 system，手动执行为当前用户',
  `executedDate` datetime DEFAULT NULL COMMENT '任务开始执行时间',
  `finishedDate` datetime DEFAULT NULL COMMENT '完成时间',
  `deleted` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '是否已删除',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- DROP TABLE IF EXISTS `zt_ai_chat`;
CREATE TABLE IF NOT EXISTS `zt_ai_chat` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `type` varchar(50)  NOT NULL DEFAULT '' COMMENT '对话类型 chat(用户普通聊天)、task(任务处理)、agent(禅道智能体)、miniprogram(通用智能体)',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '对话标题',
  `task` int unsigned NOT NULL DEFAULT 0 COMMENT '当类型为 task 时，对应的 AI 任务 ID',
  `llm` varchar(255) NOT NULL DEFAULT '' COMMENT '实际使用的大模型 ID, 对应 ZAI 中的模型 ID',
  `agents` text DEFAULT NULL COMMENT '实际使用的智能体列表及相关数据(JSON 数组)',
  `klibs` text DEFAULT NULL COMMENT '实际挂载的知识库列表及过滤配置(JSON 数组)',
  `objects` text DEFAULT NULL COMMENT '对话中涉及的禅道对象数据列表(JSON 数组)',
  `prompt` text DEFAULT NULL COMMENT '初始系统提示词',
  `tools` text DEFAULT NULL COMMENT '该对话中提供的工具名称和参数(JSON 数组)',
  `externalID` varchar(255) NOT NULL DEFAULT '' COMMENT '外部对话 ID, 目前为 ZAI 中的对话 ID(GUID 格式)',
  `token` int unsigned NOT NULL DEFAULT 0 COMMENT '对话消耗的 token 数量',
  `createdBy` varchar(30) NOT NULL DEFAULT '' COMMENT '创建人',
  `createdDate` datetime DEFAULT NULL COMMENT '创建时间',
  `updateDate` datetime DEFAULT NULL COMMENT '更新时间',
  `deleted` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '是否已删除',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- DROP TABLE IF EXISTS `zt_ai_chat_message`;
CREATE TABLE IF NOT EXISTS `zt_ai_chat_message` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `chat` int unsigned NOT NULL DEFAULT 0 COMMENT '对话 ID',
  `externalID` varchar(255) NOT NULL DEFAULT '' COMMENT '外部对话 ID, 目前为 ZAI 中的对话 ID(GUID 格式)',
  `role` varchar(50)  NOT NULL DEFAULT '' COMMENT '消息角色: user(用户)、assistant(助手)、system(系统)',
  `content` text DEFAULT NULL COMMENT '消息内容',
  `reasonContent` text DEFAULT NULL COMMENT '思考内容，当角色为 assistant 时记录 AI 的思考过程',
  `tools` text DEFAULT NULL COMMENT '消息中使用的工具名称和定义(JSON 数组)',
  `refs` text DEFAULT NULL COMMENT '消息中引用的数据列表(JSON 数组)',
  `toolCalls` text DEFAULT NULL COMMENT '当角色为 assistant 时，工具调用列表(JSON 数组)',
  `toolCallID` varchar(255) NOT NULL DEFAULT '' COMMENT '当角色为 system 时，工具调用 ID（GUID 格式），用于向 AI 返回工具调用结果',
  `token` int unsigned NOT NULL DEFAULT 0 COMMENT '对话消耗的 token 数量',
  `gid` varchar(50) NOT NULL DEFAULT '' COMMENT '全局 ID',
  `bgid` varchar(50) NOT NULL DEFAULT '' COMMENT '批次 ID',
  `createdDate` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

RENAME TABLE `zt_ai_prompt` TO `zt_ai_agent`;
RENAME TABLE `zt_ai_promptrole` TO `zt_ai_agentrole`;
RENAME TABLE `zt_ai_promptfield` TO `zt_ai_agentfield`;

ALTER TABLE `zt_ai_agent`
ADD COLUMN `code` varchar(30) NOT NULL DEFAULT '' COMMENT '内部 code'
AFTER `id`;

UPDATE `zt_ai_agent`
SET `code` = 'zt_story_polishing'
WHERE `name` = '需求润色' AND `createdBy` = 'system';
UPDATE `zt_ai_agent`
SET `code` = 'zt_story_to_testcase'
WHERE `name` = '一键拆用例' AND `createdBy` = 'system';
UPDATE `zt_ai_agent`
SET `code` = 'zt_task_polishing'
WHERE `name` = '任务润色' AND `createdBy` = 'system';
UPDATE `zt_ai_agent`
SET `code` = 'zt_story_to_task'
WHERE `name` = '需求转任务' AND `createdBy` = 'system';
UPDATE `zt_ai_agent`
SET `code` = 'zt_bug_polishing'
WHERE `name` = 'Bug润色' AND `createdBy` = 'system';
UPDATE `zt_ai_agent`
SET `code` = 'zt_doc_polishing'
WHERE `name` = '文档润色' AND `createdBy` = 'system';
UPDATE `zt_ai_agent`
SET `code` = 'zt_bug_to_story'
WHERE `name` = 'Bug转需求' AND `createdBy` = 'system';
UPDATE `zt_ai_agent`
SET `code` = 'zt_split_productplan'
WHERE `name` = '拆分一个子计划' AND `createdBy` = 'system';
UPDATE `zt_ai_agent`
SET `code` = 'zt_story_review'
WHERE `name` = '需求评审' AND `createdBy` = 'system';
UPDATE `zt_ai_agent`
SET `code` = 'zt_create_doc'
WHERE `name` = '编写开发设计文档智能体' AND `createdBy` = 'system';
UPDATE `zt_ai_agent`
SET `code` = 'zt_story_prototype'
WHERE `name` = '绘制需求原型图智能体' AND `createdBy` = 'system';
UPDATE `zt_ai_agent`
SET `code` = 'zt_release_newsletter'
WHERE `name` = '编写发布新闻稿' AND `createdBy` = 'system';

INSERT INTO `zt_cron` (`m`, `h`, `dom`, `mon`, `dow`, `command`, `remark`, `type`, `buildin`, `status`) VALUES ('*/1','*','*','*','*','moduleName=aitask&methodName=exec', '执行数字人任务','zentao', 1, 'normal');
