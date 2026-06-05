<?php
/**
 * PMO builtin charts for product, project, execution, quality and people screens.
 */

$pmoField = function(string $field, string $name, string $type = 'number', string $object = ''): array
{
    return array('field' => $field, 'name' => $name, 'type' => $type, 'object' => $object, 'show' => true);
};

$pmoLang = function(string $name): array
{
    return array('zh-cn' => $name, 'zh-tw' => $name, 'en' => $name);
};

$config->bi->builtin->charts[] = array
(
    'id'        => 41000,
    'name'      => 'PMO-产品需求总数',
    'code'      => 'pmo_product_story_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '38',
    'sql'       => <<<'EOT'
SELECT id AS product, totalStories AS storyCount FROM zt_product WHERE deleted = '0' AND shadow = 0
EOT
,
    'settings' => array('value' => array('field' => 'storyCount', 'name' => 'storyCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('storyCount' => $pmoField('storyCount', '需求总数', 'number')),
    'langs'    => array('storyCount' => $pmoLang('需求总数')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41001,
    'name'      => 'PMO-产品进行中需求数',
    'code'      => 'pmo_product_active_story_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '38',
    'sql'       => <<<'EOT'
SELECT id AS product, activeStories AS activeStoryCount FROM zt_product WHERE deleted = '0' AND shadow = 0
EOT
,
    'settings' => array('value' => array('field' => 'activeStoryCount', 'name' => 'activeStoryCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('activeStoryCount' => $pmoField('activeStoryCount', '进行中需求', 'number')),
    'langs'    => array('activeStoryCount' => $pmoLang('进行中需求')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41002,
    'name'      => 'PMO-产品已关闭需求数',
    'code'      => 'pmo_product_closed_story_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '38',
    'sql'       => <<<'EOT'
SELECT id AS product, closedStories AS closedStoryCount FROM zt_product WHERE deleted = '0' AND shadow = 0
EOT
,
    'settings' => array('value' => array('field' => 'closedStoryCount', 'name' => 'closedStoryCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('closedStoryCount' => $pmoField('closedStoryCount', '已关闭需求', 'number')),
    'langs'    => array('closedStoryCount' => $pmoLang('已关闭需求')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41003,
    'name'      => 'PMO-产品未关闭Bug数',
    'code'      => 'pmo_product_unclosed_bug_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '44',
    'sql'       => <<<'EOT'
SELECT product, COUNT(1) AS unclosedBugCount FROM zt_bug WHERE deleted = '0' AND status != 'closed' GROUP BY product
EOT
,
    'settings' => array('value' => array('field' => 'unclosedBugCount', 'name' => 'unclosedBugCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('unclosedBugCount' => $pmoField('unclosedBugCount', '未关闭Bug', 'number')),
    'langs'    => array('unclosedBugCount' => $pmoLang('未关闭Bug')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41004,
    'name'      => 'PMO-产品需求Bug概览',
    'code'      => 'pmo_product_story_bug_overview',
    'dimension' => '1',
    'type'      => 'table',
    'group'     => '38',
    'sql'       => <<<'EOT'
SELECT p.id AS product, p.name AS productName, p.totalStories AS storyCount, p.activeStories AS activeStoryCount, p.closedStories AS closedStoryCount, IFNULL(b.unclosedBugCount, 0) AS unclosedBugCount FROM zt_product p LEFT JOIN (SELECT product, COUNT(1) AS unclosedBugCount FROM zt_bug WHERE deleted = '0' AND status != 'closed' GROUP BY product) b ON b.product = p.id WHERE p.deleted = '0' AND p.shadow = 0 ORDER BY storyCount DESC LIMIT 12
EOT
,
    'settings' => array('column' => array(array('field' => 'productName', 'name' => '产品'), array('field' => 'storyCount', 'name' => '需求总数'), array('field' => 'activeStoryCount', 'name' => '进行中需求'), array('field' => 'unclosedBugCount', 'name' => '未关闭Bug'))),
    'filters'  => array(),
    'fields'   => array('productName' => $pmoField('productName', '产品', 'string'), 'storyCount' => $pmoField('storyCount', '需求总数', 'number'), 'activeStoryCount' => $pmoField('activeStoryCount', '进行中需求', 'number'), 'unclosedBugCount' => $pmoField('unclosedBugCount', '未关闭Bug', 'number')),
    'langs'    => array('productName' => $pmoLang('产品'), 'storyCount' => $pmoLang('需求总数'), 'activeStoryCount' => $pmoLang('进行中需求'), 'unclosedBugCount' => $pmoLang('未关闭Bug')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41005,
    'name'      => 'PMO-产品当前执行',
    'code'      => 'pmo_product_current_execution',
    'dimension' => '1',
    'type'      => 'table',
    'group'     => '39',
    'sql'       => <<<'EOT'
SELECT pp.product, e.project, e.id AS execution, p.name AS productName, e.name AS executionName, e.status, e.begin, e.end FROM zt_projectproduct pp LEFT JOIN zt_project e ON e.id = pp.project LEFT JOIN zt_product p ON p.id = pp.product WHERE e.deleted = '0' AND e.type != 'project' AND e.status != 'closed' AND p.deleted = '0' ORDER BY e.id DESC LIMIT 20
EOT
,
    'settings' => array('column' => array(array('field' => 'productName', 'name' => '产品'), array('field' => 'executionName', 'name' => '当前执行'), array('field' => 'status', 'name' => '状态'), array('field' => 'begin', 'name' => '开始'), array('field' => 'end', 'name' => '结束'))),
    'filters'  => array(),
    'fields'   => array('productName' => $pmoField('productName', '产品', 'string'), 'executionName' => $pmoField('executionName', '当前执行', 'string'), 'status' => $pmoField('status', '状态', 'string'), 'begin' => $pmoField('begin', '开始', 'string'), 'end' => $pmoField('end', '结束', 'string')),
    'langs'    => array('productName' => $pmoLang('产品'), 'executionName' => $pmoLang('当前执行'), 'status' => $pmoLang('状态'), 'begin' => $pmoLang('开始'), 'end' => $pmoLang('结束')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41006,
    'name'      => 'PMO-产品当前版本',
    'code'      => 'pmo_product_current_build_release',
    'dimension' => '1',
    'type'      => 'table',
    'group'     => '39',
    'sql'       => <<<'EOT'
SELECT t.product, p.name AS productName, t.buildName, t.buildDate, t.releaseName, t.releaseDate FROM (SELECT product, name AS buildName, date AS buildDate, '' AS releaseName, '' AS releaseDate FROM zt_build WHERE deleted = '0' UNION ALL SELECT product, '' AS buildName, '' AS buildDate, name AS releaseName, date AS releaseDate FROM zt_release WHERE deleted = '0') t LEFT JOIN zt_product p ON p.id = t.product WHERE p.deleted = '0' ORDER BY IF(t.releaseDate = '', t.buildDate, t.releaseDate) DESC LIMIT 20
EOT
,
    'settings' => array('column' => array(array('field' => 'productName', 'name' => '产品'), array('field' => 'buildName', 'name' => '最新构建'), array('field' => 'buildDate', 'name' => '构建日期'), array('field' => 'releaseName', 'name' => '最新发布'), array('field' => 'releaseDate', 'name' => '发布日期'))),
    'filters'  => array(),
    'fields'   => array('productName' => $pmoField('productName', '产品', 'string'), 'buildName' => $pmoField('buildName', '最新构建', 'string'), 'buildDate' => $pmoField('buildDate', '构建日期', 'string'), 'releaseName' => $pmoField('releaseName', '最新发布', 'string'), 'releaseDate' => $pmoField('releaseDate', '发布日期', 'string')),
    'langs'    => array('productName' => $pmoLang('产品'), 'buildName' => $pmoLang('最新构建'), 'buildDate' => $pmoLang('构建日期'), 'releaseName' => $pmoLang('最新发布'), 'releaseDate' => $pmoLang('发布日期')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41100,
    'name'      => 'PMO-延期项目数',
    'code'      => 'pmo_project_delay_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '38',
    'sql'       => <<<'EOT'
SELECT id AS project, 1 AS delayCount FROM zt_project WHERE deleted = '0' AND type = 'project' AND status != 'closed' AND end IS NOT NULL AND end != '0000-00-00' AND end < CURDATE()
EOT
,
    'settings' => array('value' => array('field' => 'delayCount', 'name' => 'delayCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('delayCount' => $pmoField('delayCount', '延期项目', 'number')),
    'langs'    => array('delayCount' => $pmoLang('延期项目')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41101,
    'name'      => 'PMO-延期任务数',
    'code'      => 'pmo_project_overdue_task_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '39',
    'sql'       => <<<'EOT'
SELECT project, execution, COUNT(1) AS overdueTaskCount FROM zt_task WHERE deleted = '0' AND status NOT IN ('done','closed','cancel') AND deadline IS NOT NULL AND deadline != '0000-00-00' AND deadline < CURDATE() GROUP BY project, execution
EOT
,
    'settings' => array('value' => array('field' => 'overdueTaskCount', 'name' => 'overdueTaskCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('overdueTaskCount' => $pmoField('overdueTaskCount', '延期任务', 'number')),
    'langs'    => array('overdueTaskCount' => $pmoLang('延期任务')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41102,
    'name'      => 'PMO-阻塞任务数',
    'code'      => 'pmo_project_blocked_task_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '39',
    'sql'       => <<<'EOT'
SELECT project, execution, COUNT(1) AS blockedTaskCount FROM zt_task WHERE deleted = '0' AND status NOT IN ('done','closed','cancel') AND (status IN ('pause') OR subStatus IN ('blocked','pause','suspend')) GROUP BY project, execution
EOT
,
    'settings' => array('value' => array('field' => 'blockedTaskCount', 'name' => 'blockedTaskCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('blockedTaskCount' => $pmoField('blockedTaskCount', '阻塞任务', 'number')),
    'langs'    => array('blockedTaskCount' => $pmoLang('阻塞任务')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41103,
    'name'      => 'PMO-项目需求完成率',
    'code'      => 'pmo_project_story_finish_rate',
    'dimension' => '1',
    'type'      => 'table',
    'group'     => '38',
    'sql'       => <<<'EOT'
SELECT p.id AS project, p.name AS projectName, ROUND(IF(COUNT(DISTINCT s.id)=0,0,SUM(IF(s.status='closed' OR s.stage IN ('released','closed'),1,0))/COUNT(DISTINCT s.id)*100),1) AS storyFinishRate FROM zt_project p LEFT JOIN zt_projectstory ps ON ps.project = p.id LEFT JOIN zt_story s ON s.id = ps.story AND s.deleted = '0' WHERE p.deleted = '0' AND p.type = 'project' GROUP BY p.id,p.name ORDER BY storyFinishRate DESC LIMIT 15
EOT
,
    'settings' => array('column' => array(array('field' => 'projectName', 'name' => '项目'), array('field' => 'storyFinishRate', 'name' => '需求完成率'))),
    'filters'  => array(),
    'fields'   => array('projectName' => $pmoField('projectName', '项目', 'string'), 'storyFinishRate' => $pmoField('storyFinishRate', '需求完成率', 'number')),
    'langs'    => array('projectName' => $pmoLang('项目'), 'storyFinishRate' => $pmoLang('需求完成率')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41104,
    'name'      => 'PMO-项目执行完成率',
    'code'      => 'pmo_project_execution_finish_rate',
    'dimension' => '1',
    'type'      => 'table',
    'group'     => '38',
    'sql'       => <<<'EOT'
SELECT p.id AS project, p.name AS projectName, ROUND(IF(COUNT(e.id)=0,0,SUM(IF(e.status='closed',1,0))/COUNT(e.id)*100),1) AS executionFinishRate FROM zt_project p LEFT JOIN zt_project e ON e.project = p.id AND e.deleted = '0' AND e.type != 'project' WHERE p.deleted = '0' AND p.type = 'project' GROUP BY p.id,p.name ORDER BY executionFinishRate DESC LIMIT 15
EOT
,
    'settings' => array('column' => array(array('field' => 'projectName', 'name' => '项目'), array('field' => 'executionFinishRate', 'name' => '执行完成率'))),
    'filters'  => array(),
    'fields'   => array('projectName' => $pmoField('projectName', '项目', 'string'), 'executionFinishRate' => $pmoField('executionFinishRate', '执行完成率', 'number')),
    'langs'    => array('projectName' => $pmoLang('项目'), 'executionFinishRate' => $pmoLang('执行完成率')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41105,
    'name'      => 'PMO-项目任务完成率',
    'code'      => 'pmo_project_task_finish_rate',
    'dimension' => '1',
    'type'      => 'table',
    'group'     => '39',
    'sql'       => <<<'EOT'
SELECT p.id AS project, p.name AS projectName, ROUND(IF(COUNT(t.id)=0,0,SUM(IF(t.status IN ('done','closed'),1,0))/COUNT(t.id)*100),1) AS taskFinishRate FROM zt_project p LEFT JOIN zt_task t ON t.project = p.id AND t.deleted = '0' WHERE p.deleted = '0' AND p.type = 'project' GROUP BY p.id,p.name ORDER BY taskFinishRate DESC LIMIT 15
EOT
,
    'settings' => array('column' => array(array('field' => 'projectName', 'name' => '项目'), array('field' => 'taskFinishRate', 'name' => '任务完成率'))),
    'filters'  => array(),
    'fields'   => array('projectName' => $pmoField('projectName', '项目', 'string'), 'taskFinishRate' => $pmoField('taskFinishRate', '任务完成率', 'number')),
    'langs'    => array('projectName' => $pmoLang('项目'), 'taskFinishRate' => $pmoLang('任务完成率')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41106,
    'name'      => 'PMO-项目里程碑达成',
    'code'      => 'pmo_project_milestone_status',
    'dimension' => '1',
    'type'      => 'table',
    'group'     => '38',
    'sql'       => <<<'EOT'
SELECT project, projectName, version, milestoneName, begin, `end`, achieveStatus
FROM (
    SELECT p.id AS project, p.name AS projectName, ps.version, ps.name AS milestoneName, ps.begin, ps.end, IF(p.status='closed' OR (ps.end IS NOT NULL AND ps.end != '0000-00-00' AND ps.end >= CURDATE()), '正常', '逾期') AS achieveStatus
    FROM zt_projectspec ps
    LEFT JOIN zt_project p ON p.id = ps.project
    WHERE p.deleted = '0' AND p.type = 'project' AND ps.milestone = '1'
    UNION ALL
    SELECT p.id AS project, p.name AS projectName, '' AS version, '项目计划' AS milestoneName, p.begin, p.end, IF(p.status='closed' OR (p.end IS NOT NULL AND p.end != '0000-00-00' AND p.end >= CURDATE()), '正常', '逾期') AS achieveStatus
    FROM zt_project p
    WHERE p.deleted = '0' AND p.type = 'project'
    AND NOT EXISTS(SELECT 1 FROM zt_projectspec ps2 LEFT JOIN zt_project p2 ON p2.id = ps2.project WHERE p2.deleted = '0' AND p2.type = 'project' AND ps2.milestone = '1')
) AS t
ORDER BY `end` DESC
LIMIT 20
EOT
,
    'settings' => array('column' => array(array('field' => 'projectName', 'name' => '项目'), array('field' => 'milestoneName', 'name' => '里程碑'), array('field' => 'begin', 'name' => '计划开始'), array('field' => 'end', 'name' => '计划结束'), array('field' => 'achieveStatus', 'name' => '达成状态'))),
    'filters'  => array(),
    'fields'   => array('projectName' => $pmoField('projectName', '项目', 'string'), 'milestoneName' => $pmoField('milestoneName', '里程碑', 'string'), 'begin' => $pmoField('begin', '计划开始', 'string'), 'end' => $pmoField('end', '计划结束', 'string'), 'achieveStatus' => $pmoField('achieveStatus', '达成状态', 'string')),
    'langs'    => array('projectName' => $pmoLang('项目'), 'milestoneName' => $pmoLang('里程碑'), 'begin' => $pmoLang('计划开始'), 'end' => $pmoLang('计划结束'), 'achieveStatus' => $pmoLang('达成状态')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41200,
    'name'      => 'PMO-执行计划需求数',
    'code'      => 'pmo_execution_story_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '38',
    'sql'       => <<<'EOT'
SELECT t.execution, t.project, COUNT(DISTINCT t.story) AS storyCount FROM zt_task t WHERE t.deleted = '0' AND t.story > 0 GROUP BY t.execution, t.project
EOT
,
    'settings' => array('value' => array('field' => 'storyCount', 'name' => 'storyCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('storyCount' => $pmoField('storyCount', '计划需求', 'number')),
    'langs'    => array('storyCount' => $pmoLang('计划需求')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41201,
    'name'      => 'PMO-执行已完成需求数',
    'code'      => 'pmo_execution_finished_story_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '38',
    'sql'       => <<<'EOT'
SELECT t.execution, t.project, COUNT(DISTINCT t.story) AS finishedStoryCount FROM zt_task t LEFT JOIN zt_story s ON s.id = t.story WHERE t.deleted = '0' AND t.story > 0 AND (s.status = 'closed' OR s.stage IN ('released','closed')) GROUP BY t.execution, t.project
EOT
,
    'settings' => array('value' => array('field' => 'finishedStoryCount', 'name' => 'finishedStoryCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('finishedStoryCount' => $pmoField('finishedStoryCount', '已完成需求', 'number')),
    'langs'    => array('finishedStoryCount' => $pmoLang('已完成需求')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41202,
    'name'      => 'PMO-执行进行中任务数',
    'code'      => 'pmo_execution_doing_task_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '39',
    'sql'       => <<<'EOT'
SELECT execution, project, COUNT(1) AS doingTaskCount FROM zt_task WHERE deleted = '0' AND status IN ('doing','wait') GROUP BY execution, project
EOT
,
    'settings' => array('value' => array('field' => 'doingTaskCount', 'name' => 'doingTaskCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('doingTaskCount' => $pmoField('doingTaskCount', '进行中任务', 'number')),
    'langs'    => array('doingTaskCount' => $pmoLang('进行中任务')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41203,
    'name'      => 'PMO-执行已完成任务数',
    'code'      => 'pmo_execution_done_task_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '39',
    'sql'       => <<<'EOT'
SELECT execution, project, COUNT(1) AS doneTaskCount FROM zt_task WHERE deleted = '0' AND status IN ('done','closed') GROUP BY execution, project
EOT
,
    'settings' => array('value' => array('field' => 'doneTaskCount', 'name' => 'doneTaskCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('doneTaskCount' => $pmoField('doneTaskCount', '已完成任务', 'number')),
    'langs'    => array('doneTaskCount' => $pmoLang('已完成任务')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41204,
    'name'      => 'PMO-执行延期任务数',
    'code'      => 'pmo_execution_overdue_task_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '39',
    'sql'       => <<<'EOT'
SELECT execution, project, COUNT(1) AS overdueTaskCount FROM zt_task WHERE deleted = '0' AND status NOT IN ('done','closed','cancel') AND deadline IS NOT NULL AND deadline != '0000-00-00' AND deadline < CURDATE() GROUP BY execution, project
EOT
,
    'settings' => array('value' => array('field' => 'overdueTaskCount', 'name' => 'overdueTaskCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('overdueTaskCount' => $pmoField('overdueTaskCount', '延期任务', 'number')),
    'langs'    => array('overdueTaskCount' => $pmoLang('延期任务')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41205,
    'name'      => 'PMO-执行未关闭Bug数',
    'code'      => 'pmo_execution_unclosed_bug_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '44',
    'sql'       => <<<'EOT'
SELECT execution, project, product, COUNT(1) AS unclosedBugCount FROM zt_bug WHERE deleted = '0' AND status != 'closed' GROUP BY execution, project, product
EOT
,
    'settings' => array('value' => array('field' => 'unclosedBugCount', 'name' => 'unclosedBugCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('unclosedBugCount' => $pmoField('unclosedBugCount', '未关闭Bug', 'number')),
    'langs'    => array('unclosedBugCount' => $pmoLang('未关闭Bug')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41206,
    'name'      => 'PMO-执行任务完成排行',
    'code'      => 'pmo_execution_task_finish_rank',
    'dimension' => '1',
    'type'      => 'table',
    'group'     => '39',
    'sql'       => <<<'EOT'
SELECT e.project, e.id AS execution, e.name AS executionName, SUM(IF(t.status IN ('done','closed'),1,0)) AS doneTaskCount, SUM(IF(t.status NOT IN ('done','closed','cancel'),1,0)) AS pendingTaskCount FROM zt_project e LEFT JOIN zt_task t ON t.execution = e.id AND t.deleted = '0' WHERE e.deleted = '0' AND e.type != 'project' GROUP BY e.id,e.name,e.project ORDER BY doneTaskCount DESC LIMIT 15
EOT
,
    'settings' => array('column' => array(array('field' => 'executionName', 'name' => '执行'), array('field' => 'doneTaskCount', 'name' => '已完成任务'), array('field' => 'pendingTaskCount', 'name' => '未完成任务'))),
    'filters'  => array(),
    'fields'   => array('executionName' => $pmoField('executionName', '执行', 'string'), 'doneTaskCount' => $pmoField('doneTaskCount', '已完成任务', 'number'), 'pendingTaskCount' => $pmoField('pendingTaskCount', '未完成任务', 'number')),
    'langs'    => array('executionName' => $pmoLang('执行'), 'doneTaskCount' => $pmoLang('已完成任务'), 'pendingTaskCount' => $pmoLang('未完成任务')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41300,
    'name'      => 'PMO-Bug总数',
    'code'      => 'pmo_quality_bug_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '44',
    'sql'       => <<<'EOT'
SELECT product, project, execution, COUNT(1) AS bugCount FROM zt_bug WHERE deleted = '0' GROUP BY product, project, execution
EOT
,
    'settings' => array('value' => array('field' => 'bugCount', 'name' => 'bugCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('bugCount' => $pmoField('bugCount', 'Bug总数', 'number')),
    'langs'    => array('bugCount' => $pmoLang('Bug总数')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41301,
    'name'      => 'PMO-严重Bug数',
    'code'      => 'pmo_quality_severe_bug_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '44',
    'sql'       => <<<'EOT'
SELECT product, project, execution, COUNT(1) AS severeBugCount FROM zt_bug WHERE deleted = '0' AND severity IN (1,2) GROUP BY product, project, execution
EOT
,
    'settings' => array('value' => array('field' => 'severeBugCount', 'name' => 'severeBugCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('severeBugCount' => $pmoField('severeBugCount', '严重Bug', 'number')),
    'langs'    => array('severeBugCount' => $pmoLang('严重Bug')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41302,
    'name'      => 'PMO-线上遗留Bug数',
    'code'      => 'pmo_quality_left_bug_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '44',
    'sql'       => <<<'EOT'
SELECT product, project, SUM(IF(leftBugs IS NULL OR leftBugs = '', 0, LENGTH(leftBugs) - LENGTH(REPLACE(leftBugs, ',', '')) + 1)) AS leftBugCount FROM zt_release WHERE deleted = '0' GROUP BY product, project
UNION ALL
SELECT 0 AS product, 0 AS project, 0 AS leftBugCount
WHERE NOT EXISTS(SELECT 1 FROM zt_release WHERE deleted = '0')
EOT
,
    'settings' => array('value' => array('field' => 'leftBugCount', 'name' => 'leftBugCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('leftBugCount' => $pmoField('leftBugCount', '线上遗留Bug', 'number')),
    'langs'    => array('leftBugCount' => $pmoLang('线上遗留Bug')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41303,
    'name'      => 'PMO-重复Bug数',
    'code'      => 'pmo_quality_duplicate_bug_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '44',
    'sql'       => <<<'EOT'
SELECT product, project, execution, COUNT(1) AS duplicateBugCount FROM zt_bug WHERE deleted = '0' AND (resolution = 'duplicate' OR duplicateBug > 0) GROUP BY product, project, execution
EOT
,
    'settings' => array('value' => array('field' => 'duplicateBugCount', 'name' => 'duplicateBugCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('duplicateBugCount' => $pmoField('duplicateBugCount', '重复Bug', 'number')),
    'langs'    => array('duplicateBugCount' => $pmoLang('重复Bug')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41304,
    'name'      => 'PMO-重新激活Bug数',
    'code'      => 'pmo_quality_reactivated_bug_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '44',
    'sql'       => <<<'EOT'
SELECT product, project, execution, COUNT(1) AS reactivatedBugCount FROM zt_bug WHERE deleted = '0' AND activatedCount > 0 GROUP BY product, project, execution
EOT
,
    'settings' => array('value' => array('field' => 'reactivatedBugCount', 'name' => 'reactivatedBugCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('reactivatedBugCount' => $pmoField('reactivatedBugCount', '重新激活Bug', 'number')),
    'langs'    => array('reactivatedBugCount' => $pmoLang('重新激活Bug')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41305,
    'name'      => 'PMO-Bug关闭率',
    'code'      => 'pmo_quality_bug_close_rate',
    'dimension' => '1',
    'type'      => 'table',
    'group'     => '44',
    'sql'       => <<<'EOT'
SELECT p.id AS product, p.name AS productName, ROUND(IF(COUNT(b.id)=0,0,SUM(IF(b.status='closed',1,0))/COUNT(b.id)*100),1) AS closeRate FROM zt_product p LEFT JOIN zt_bug b ON b.product = p.id AND b.deleted = '0' WHERE p.deleted = '0' AND p.shadow = 0 GROUP BY p.id,p.name ORDER BY closeRate DESC LIMIT 15
EOT
,
    'settings' => array('column' => array(array('field' => 'productName', 'name' => '产品'), array('field' => 'closeRate', 'name' => '关闭率'))),
    'filters'  => array(),
    'fields'   => array('productName' => $pmoField('productName', '产品', 'string'), 'closeRate' => $pmoField('closeRate', '关闭率', 'number')),
    'langs'    => array('productName' => $pmoLang('产品'), 'closeRate' => $pmoLang('关闭率')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41306,
    'name'      => 'PMO-Bug新增关闭趋势',
    'code'      => 'pmo_quality_bug_trend',
    'dimension' => '1',
    'type'      => 'table',
    'group'     => '44',
    'sql'       => <<<'EOT'
SELECT monthLabel, SUM(openedCount) AS openedCount, SUM(closedCount) AS closedCount FROM (SELECT DATE_FORMAT(openedDate, '%Y-%m') AS monthLabel, COUNT(1) AS openedCount, 0 AS closedCount FROM zt_bug WHERE deleted = '0' AND openedDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(openedDate, '%Y-%m') UNION ALL SELECT DATE_FORMAT(closedDate, '%Y-%m') AS monthLabel, 0 AS openedCount, COUNT(1) AS closedCount FROM zt_bug WHERE deleted = '0' AND closedDate IS NOT NULL AND closedDate != '0000-00-00 00:00:00' AND closedDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(closedDate, '%Y-%m')) t WHERE monthLabel IS NOT NULL GROUP BY monthLabel ORDER BY monthLabel
EOT
,
    'settings' => array('column' => array(array('field' => 'monthLabel', 'name' => '月份'), array('field' => 'openedCount', 'name' => '新增Bug'), array('field' => 'closedCount', 'name' => '关闭Bug'))),
    'filters'  => array(),
    'fields'   => array('monthLabel' => $pmoField('monthLabel', '月份', 'string'), 'openedCount' => $pmoField('openedCount', '新增Bug', 'number'), 'closedCount' => $pmoField('closedCount', '关闭Bug', 'number')),
    'langs'    => array('monthLabel' => $pmoLang('月份'), 'openedCount' => $pmoLang('新增Bug'), 'closedCount' => $pmoLang('关闭Bug')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41307,
    'name'      => 'PMO-Bug严重级别分布',
    'code'      => 'pmo_quality_bug_severity',
    'dimension' => '1',
    'type'      => 'table',
    'group'     => '44',
    'sql'       => <<<'EOT'
SELECT b.product, b.project, b.execution, IFNULL(p.name, '未关联产品') AS productName, CONCAT('S', b.severity) AS severityName, COUNT(1) AS bugCount FROM zt_bug b LEFT JOIN zt_product p ON p.id = b.product WHERE b.deleted = '0' GROUP BY b.product,b.project,b.execution,p.name,b.severity
EOT
,
    'settings' => array('column' => array(array('field' => 'productName', 'name' => '产品'), array('field' => 'severityName', 'name' => '严重级别'), array('field' => 'bugCount', 'name' => 'Bug数'))),
    'filters'  => array(),
    'fields'   => array('productName' => $pmoField('productName', '产品', 'string'), 'severityName' => $pmoField('severityName', '严重级别', 'string'), 'bugCount' => $pmoField('bugCount', 'Bug数', 'number')),
    'langs'    => array('productName' => $pmoLang('产品'), 'severityName' => $pmoLang('严重级别'), 'bugCount' => $pmoLang('Bug数')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41400,
    'name'      => 'PMO-个人任务数',
    'code'      => 'pmo_user_task_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '56',
    'sql'       => <<<'EOT'
SELECT assignedTo, project, execution, COUNT(1) AS taskCount FROM zt_task WHERE deleted = '0' AND assignedTo NOT IN ('', 'closed') GROUP BY assignedTo, project, execution
EOT
,
    'settings' => array('value' => array('field' => 'taskCount', 'name' => 'taskCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('taskCount' => $pmoField('taskCount', '个人任务', 'number')),
    'langs'    => array('taskCount' => $pmoLang('个人任务')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41401,
    'name'      => 'PMO-个人进行中任务数',
    'code'      => 'pmo_user_doing_task_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '56',
    'sql'       => <<<'EOT'
SELECT assignedTo, project, execution, COUNT(1) AS doingTaskCount FROM zt_task WHERE deleted = '0' AND assignedTo NOT IN ('', 'closed') AND status IN ('doing','wait') GROUP BY assignedTo, project, execution
EOT
,
    'settings' => array('value' => array('field' => 'doingTaskCount', 'name' => 'doingTaskCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('doingTaskCount' => $pmoField('doingTaskCount', '进行中任务', 'number')),
    'langs'    => array('doingTaskCount' => $pmoLang('进行中任务')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41402,
    'name'      => 'PMO-个人延期任务数',
    'code'      => 'pmo_user_overdue_task_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '56',
    'sql'       => <<<'EOT'
SELECT assignedTo, project, execution, COUNT(1) AS overdueTaskCount FROM zt_task WHERE deleted = '0' AND assignedTo NOT IN ('', 'closed') AND status NOT IN ('done','closed','cancel') AND deadline IS NOT NULL AND deadline != '0000-00-00' AND deadline < CURDATE() GROUP BY assignedTo, project, execution
EOT
,
    'settings' => array('value' => array('field' => 'overdueTaskCount', 'name' => 'overdueTaskCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('overdueTaskCount' => $pmoField('overdueTaskCount', '延期任务', 'number')),
    'langs'    => array('overdueTaskCount' => $pmoLang('延期任务')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41403,
    'name'      => 'PMO-个人阻塞任务数',
    'code'      => 'pmo_user_blocked_task_total',
    'dimension' => '1',
    'type'      => 'card',
    'group'     => '56',
    'sql'       => <<<'EOT'
SELECT assignedTo, project, execution, COUNT(1) AS blockedTaskCount FROM zt_task WHERE deleted = '0' AND assignedTo NOT IN ('', 'closed') AND status NOT IN ('done','closed','cancel') AND (status IN ('pause') OR subStatus IN ('blocked','pause','suspend')) GROUP BY assignedTo, project, execution
EOT
,
    'settings' => array('value' => array('field' => 'blockedTaskCount', 'name' => 'blockedTaskCount', 'type' => 'value', 'agg' => 'sum')),
    'filters'  => array(),
    'fields'   => array('blockedTaskCount' => $pmoField('blockedTaskCount', '阻塞任务', 'number')),
    'langs'    => array('blockedTaskCount' => $pmoLang('阻塞任务')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41404,
    'name'      => 'PMO-人员任务工时Top',
    'code'      => 'pmo_user_task_effort_top',
    'dimension' => '1',
    'type'      => 'table',
    'group'     => '56',
    'sql'       => <<<'EOT'
SELECT t.project, t.execution, t.assignedTo, IFNULL(u.realname, t.assignedTo) AS realname, COUNT(1) AS taskCount, SUM(IF(t.status IN ('doing','wait'),1,0)) AS doingTaskCount, SUM(IF(t.deadline IS NOT NULL AND t.deadline != '0000-00-00' AND t.deadline < CURDATE() AND t.status NOT IN ('done','closed','cancel'),1,0)) AS overdueTaskCount, ROUND(SUM(t.estimate),1) AS estimateHours, ROUND(SUM(t.consumed),1) AS consumedHours, ROUND(SUM(t.consumed - t.estimate),1) AS diffHours FROM zt_task t LEFT JOIN zt_user u ON u.account = t.assignedTo WHERE t.deleted = '0' AND t.assignedTo NOT IN ('', 'closed') GROUP BY t.assignedTo,u.realname,t.project,t.execution ORDER BY taskCount DESC LIMIT 20
EOT
,
    'settings' => array('column' => array(array('field' => 'realname', 'name' => '人员'), array('field' => 'taskCount', 'name' => '任务'), array('field' => 'doingTaskCount', 'name' => '进行中'), array('field' => 'overdueTaskCount', 'name' => '延期'), array('field' => 'estimateHours', 'name' => '预计'), array('field' => 'consumedHours', 'name' => '实际'), array('field' => 'diffHours', 'name' => '偏差'))),
    'filters'  => array(),
    'fields'   => array('realname' => $pmoField('realname', '人员', 'string'), 'taskCount' => $pmoField('taskCount', '任务', 'string'), 'doingTaskCount' => $pmoField('doingTaskCount', '进行中', 'string'), 'overdueTaskCount' => $pmoField('overdueTaskCount', '延期', 'string'), 'estimateHours' => $pmoField('estimateHours', '预计', 'string'), 'consumedHours' => $pmoField('consumedHours', '实际', 'string'), 'diffHours' => $pmoField('diffHours', '偏差', 'string')),
    'langs'    => array('realname' => $pmoLang('人员'), 'taskCount' => $pmoLang('任务'), 'doingTaskCount' => $pmoLang('进行中'), 'overdueTaskCount' => $pmoLang('延期'), 'estimateHours' => $pmoLang('预计'), 'consumedHours' => $pmoLang('实际'), 'diffHours' => $pmoLang('偏差')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41405,
    'name'      => 'PMO-任务复杂度分布',
    'code'      => 'pmo_user_task_complexity',
    'dimension' => '1',
    'type'      => 'table',
    'group'     => '56',
    'sql'       => <<<'EOT'
SELECT /*keepTaskTable*/ t.project, t.execution, IFNULL(p.name, '未关联项目') AS projectName, IFNULL(e.name, '未关联执行') AS executionName, IF(t.complexity IS NULL OR t.complexity = '' OR t.complexity = '0', '未设置', t.complexity) AS complexityName, COUNT(1) AS taskCount FROM zt_task t LEFT JOIN zt_project p ON p.id = t.project LEFT JOIN zt_project e ON e.id = t.execution WHERE t.deleted = '0' GROUP BY t.project,t.execution,p.name,e.name,complexityName
EOT
,
    'settings' => array('column' => array(array('field' => 'projectName', 'name' => '项目'), array('field' => 'executionName', 'name' => '执行'), array('field' => 'complexityName', 'name' => '复杂度'), array('field' => 'taskCount', 'name' => '任务数'))),
    'filters'  => array(),
    'fields'   => array('projectName' => $pmoField('projectName', '项目', 'string'), 'executionName' => $pmoField('executionName', '执行', 'string'), 'complexityName' => $pmoField('complexityName', '复杂度', 'string'), 'taskCount' => $pmoField('taskCount', '任务数', 'number')),
    'langs'    => array('projectName' => $pmoLang('项目'), 'executionName' => $pmoLang('执行'), 'complexityName' => $pmoLang('复杂度'), 'taskCount' => $pmoLang('任务数')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41406,
    'name'      => 'PMO-人员任务进度',
    'code'      => 'pmo_user_task_progress',
    'dimension' => '1',
    'type'      => 'table',
    'group'     => '56',
    'sql'       => <<<'EOT'
SELECT t.project, t.execution, IFNULL(u.realname, t.assignedTo) AS realname, ROUND(IF(SUM(t.consumed + t.`left`)=0,0,SUM(t.consumed)/SUM(t.consumed + t.`left`)*100),1) AS progressRate FROM zt_task t LEFT JOIN zt_user u ON u.account = t.assignedTo WHERE t.deleted = '0' AND t.assignedTo NOT IN ('', 'closed') GROUP BY t.assignedTo,u.realname,t.project,t.execution ORDER BY progressRate DESC LIMIT 15
EOT
,
    'settings' => array('column' => array(array('field' => 'realname', 'name' => '人员'), array('field' => 'progressRate', 'name' => '任务进度'))),
    'filters'  => array(),
    'fields'   => array('realname' => $pmoField('realname', '人员', 'string'), 'progressRate' => $pmoField('progressRate', '任务进度', 'number')),
    'langs'    => array('realname' => $pmoLang('人员'), 'progressRate' => $pmoLang('任务进度')),
    'stage'    => 'published',
    'builtin'  => '1'
);

$config->bi->builtin->charts[] = array
(
    'id'        => 41407,
    'name'      => 'PMO-人员实际工时日志',
    'code'      => 'pmo_user_effort_log_top',
    'dimension' => '1',
    'type'      => 'table',
    'group'     => '57',
    'sql'       => <<<'EOT'
SELECT e.project, e.execution, e.account AS assignedTo, IFNULL(u.realname, e.account) AS realname, ROUND(SUM(e.consumed),1) AS consumedHours, COUNT(1) AS effortCount, MAX(e.date) AS lastDate FROM zt_effort e LEFT JOIN zt_user u ON u.account = e.account WHERE e.deleted = '0' GROUP BY e.account,u.realname,e.project,e.execution ORDER BY consumedHours DESC LIMIT 20
EOT
,
    'settings' => array('column' => array(array('field' => 'realname', 'name' => '人员'), array('field' => 'consumedHours', 'name' => '实际工时'), array('field' => 'effortCount', 'name' => '日志数'), array('field' => 'lastDate', 'name' => '最近日志'))),
    'filters'  => array(),
    'fields'   => array('realname' => $pmoField('realname', '人员', 'string'), 'consumedHours' => $pmoField('consumedHours', '实际工时', 'string'), 'effortCount' => $pmoField('effortCount', '日志数', 'string'), 'lastDate' => $pmoField('lastDate', '最近日志', 'string')),
    'langs'    => array('realname' => $pmoLang('人员'), 'consumedHours' => $pmoLang('实际工时'), 'effortCount' => $pmoLang('日志数'), 'lastDate' => $pmoLang('最近日志')),
    'stage'    => 'published',
    'builtin'  => '1'
);

unset($pmoField, $pmoLang);
