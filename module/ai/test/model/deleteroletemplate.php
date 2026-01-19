#!/usr/bin/env php
<?php

/**

title=测试 aiModel::deleteRoleTemplate();
timeout=0
cid=15020

- 步骤1：删除存在的角色模板ID=1 @1
- 步骤2：删除存在的角色模板ID=2 @1
- 步骤3：删除不存在的角色模板ID=999 @1
- 步骤4：删除无效的角色模板ID=0 @1
- 步骤5：删除字符串类型的ID='abc' @1

*/

// 1. 导入依赖（路径固定，不可修改）
include dirname(__FILE__, 5) . '/test/lib/init.php';
include dirname(__FILE__, 2) . '/lib/ai.unittest.class.php';

// 2. zendata数据准备（根据需要配置）
$table = zenData('ai_agentrole');
$table->id->range('1-10');
$table->name->range('产品经理,开发工程师,测试工程师,QA工程师,文案编辑{2},项目经理{2},AI助手{2},自定义角色');
$table->role->range('请你扮演一名资深的产品经理,你是一名经验丰富的开发工程师,作为一名资深的测试工程师,假如你是一名资深的QA工程师,你是一名文章写得很好的文案编辑{2},请你扮演一名经验丰富的项目经理{2},你是一个自回归的语言模型{2},你是一名自定义角色');
$table->characterization->range('负责产品战略设计开发,精通多种编程语言和框架,测试工程师应该是专业且严谨的,熟悉质量管理体系和流程,文笔流畅条理清晰{2},具备项目计划制定进度管理{2},你仔细地提供准确事实{2},具有自定义特征');
$table->deleted->range('0{8},1{2}');
$table->gen(10);

// 3. 用户登录（选择合适角色）
su('admin');

// 4. 创建测试实例（变量名与模块名一致）
$aiTest = new aiTest();

// 5. 🔴 强制要求：必须包含至少5个测试步骤
r($aiTest->deleteRoleTemplateTest(1)) && p() && e('1'); // 步骤1：删除存在的角色模板ID=1
r($aiTest->deleteRoleTemplateTest(2)) && p() && e('1'); // 步骤2：删除存在的角色模板ID=2  
r($aiTest->deleteRoleTemplateTest(999)) && p() && e('1'); // 步骤3：删除不存在的角色模板ID=999
r($aiTest->deleteRoleTemplateTest(0)) && p() && e('1'); // 步骤4：删除无效的角色模板ID=0
r($aiTest->deleteRoleTemplateTest('abc')) && p() && e('1'); // 步骤5：删除字符串类型的ID='abc'