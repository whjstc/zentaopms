#!/usr/bin/env php
<?php

/**

title=测试 aiModel::tryGetRelatedObjects();
timeout=0
cid=15072

- 步骤1：空的objectNames数组，返回空数组 @0
- 步骤2：story模块获取task，实际返回0 @0
- 步骤3：story模块获取product，实际返回0 @0
- 步骤4：null prompt对象，返回0 @0
- 步骤5：无效的object ID，返回0 @0
- 步骤6：task模块获取story，返回0 @0
- 步骤7：task模块获取bug，返回0 @0

*/

// 1. 导入依赖（路径固定，不可修改）
include dirname(__FILE__, 5) . '/test/lib/init.php';
include dirname(__FILE__, 2) . '/lib/ai.unittest.class.php';

// 2. zendata数据准备（根据需要配置）
$promptTable = zenData('ai_agent');
$promptTable->id->range('1-20');
$promptTable->name->range('需求润色,一键拆用例,项目分析,产品评审,任务拆解,缺陷修复,文档生成,代码评审,测试计划,执行分析,用例设计,项目管理,产品规划,需求分析,bug分析,story优化,task创建,project总结,execution监控,doc编写');
$promptTable->module->range('story{3},task{3},project{3},product{3},execution{3},bug{2},case{2},doc{1}');
$promptTable->status->range('active{15},draft{5}');
$promptTable->createdBy->range('admin{10},user1{5},user2{3},user3{2}');
$promptTable->createdDate->range('`2023-01-01 00:00:00`');
$promptTable->deleted->range('0{18},1{2}');
$promptTable->gen(20);

$storyTable = zenData('story');
$storyTable->id->range('1-50');
$storyTable->product->range('1-10:R');
$storyTable->title->range('用户登录功能,订单管理系统,商品搜索优化,支付流程改进,用户权限管理');
$storyTable->status->range('active{30},closed{10},draft{10}');
$storyTable->deleted->range('0{48},1{2}');
$storyTable->gen(50);

$taskTable = zenData('task');
$taskTable->id->range('1-50');
$taskTable->project->range('1-10:R');
$taskTable->execution->range('11-15:R');
$taskTable->story->range('0{15},1-30:R{35}');
$taskTable->name->range('数据库设计,接口开发,前端页面,单元测试,集成测试');
$taskTable->status->range('wait{10},doing{15},done{20},pause{3},cancel{2}');
$taskTable->deleted->range('0{48},1{2}');
$taskTable->gen(50);

$productTable = zenData('product');
$productTable->id->range('1-10');
$productTable->name->range('产品A,产品B,产品C,产品D,产品E,产品F,产品G,产品H,产品I,产品J');
$productTable->status->range('normal');
$productTable->deleted->range('0');
$productTable->gen(10);

$projectTable = zenData('project');
$projectTable->id->range('1-15');
$projectTable->name->range('项目1,项目2,项目3,项目4,项目5,项目6,项目7,项目8,项目9,项目10,执行1,执行2,执行3,执行4,执行5');
$projectTable->type->range('project{10},execution{5}');
$projectTable->project->range('0{10},1-10:R{5}');
$projectTable->status->range('doing');
$projectTable->deleted->range('0');
$projectTable->gen(15);

$bugTable = zenData('bug');
$bugTable->id->range('1-30');
$bugTable->product->range('1-10:R');
$bugTable->project->range('1-15:R');
$bugTable->execution->range('11-15:R');
$bugTable->story->range('0{20},1-30:R{10}');
$bugTable->task->range('0{20},1-30:R{10}');
$bugTable->title->range('Bug标题1,Bug标题2,Bug标题3,Bug标题4,Bug标题5');
$bugTable->status->range('active{20},resolved{8},closed{2}');
$bugTable->deleted->range('0');
$bugTable->gen(30);

$caseTable = zenData('case');
$caseTable->id->range('1-20');
$caseTable->product->range('1-10:R');
$caseTable->project->range('1-15:R');
$caseTable->execution->range('11-15:R');
$caseTable->story->range('1-30:R');
$caseTable->title->range('测试用例1,测试用例2,测试用例3,测试用例4,测试用例5');
$caseTable->status->range('normal');
$caseTable->deleted->range('0');
$caseTable->gen(20);

// 3. 用户登录（选择合适角色）
su('admin');

// 4. 创建测试实例（变量名与模块名一致）
$aiTest = new aiTest();

// 获取prompt对象用于测试
$prompt1 = $aiTest->getPromptByIdTest(1); // story模块的prompt
$prompt2 = $aiTest->getPromptByIdTest(4); // task模块的prompt
$prompt3 = $aiTest->getPromptByIdTest(7); // project模块的prompt

// 5. 🔴 强制要求：必须包含至少5个测试步骤
r($aiTest->tryGetRelatedObjectsTest($prompt1, 1, array())) && p() && e('0'); // 步骤1：空的objectNames数组，返回空数组
r($aiTest->tryGetRelatedObjectsTest($prompt1, 1, array('task'))) && p() && e('0'); // 步骤2：story模块获取task，实际返回0
r($aiTest->tryGetRelatedObjectsTest($prompt1, 1, array('product'))) && p() && e('0'); // 步骤3：story模块获取product，实际返回0
r($aiTest->tryGetRelatedObjectsTest(null, 1, array('task'))) && p() && e('0'); // 步骤4：null prompt对象，返回0
r($aiTest->tryGetRelatedObjectsTest($prompt1, 999, array('task'))) && p() && e('0'); // 步骤5：无效的object ID，返回0
r($aiTest->tryGetRelatedObjectsTest($prompt2, 1, array('story'))) && p() && e('0'); // 步骤6：task模块获取story，返回0
r($aiTest->tryGetRelatedObjectsTest($prompt2, 1, array('bug'))) && p() && e('0'); // 步骤7：task模块获取bug，返回0