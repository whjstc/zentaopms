#!/usr/bin/env php
<?php
include dirname(__FILE__, 5) . '/test/lib/init.php';
include dirname(__FILE__, 2) . '/lib/ai.unittest.class.php';

/**

title=测试 aiModel::getPromptsByIdList();
timeout=0
cid=1

- 获取id为1的数据第1条的name属性 @需求润色
- 获取id为3的数据第3条的name属性 @任务润色
- 获取id数量，应返回两条数据 @2
- 获取id数量，应返回五条数据 @5
- 获取id数量，应返回六条数据 @6

*/

$table = zenData('ai_agent');
$table->id->range('1-10');
$table->name->range('需求润色,一键拆用例,任务润色,需求转任务,Bug润色,文档润色,Bug转需求,拆分一个子计划,测试提示词1,测试提示词2');
$table->desc->range('测试描述内容{10}');
$table->model->range('0,1,2,3');
$table->module->range('story{3},task{2},bug{2},doc{1},productplan{2}');
$table->source->range(',story.title,story.spec,,task.name,task.desc,,bug.title,bug.steps,');
$table->targetForm->range('story.change,task.edit,bug.edit,doc.edit,story.create');
$table->purpose->range('测试目的内容{10}');
$table->elaboration->range('测试详细说明内容{10}');
$table->role->range('测试角色描述{10}');
$table->characterization->range('测试角色特征{10}');
$table->createdBy->range('admin,system,user1,user2');
$table->createdDate->range('`2023-08-10 10:00:00`,`2023-08-11 11:00:00`,`2023-08-12 12:00:00`');
$table->status->range('active{6},draft{4}');
$table->gen(10);

su('admin');

$aiTest = new aiTest();
r($aiTest->getPromptsByIdListTest('1'))                  && p('1:name') && e('需求润色'); // 获取id为1的数据
r($aiTest->getPromptsByIdListTest('1,3'))                && p('3:name') && e('任务润色'); // 获取id为3的数据
r(count($aiTest->getPromptsByIdListTest('1,3')))         && p()         && e('2');       // 获取id数量，应返回两条数据
r(count($aiTest->getPromptsByIdListTest('1,3,4,5,6')))   && p()         && e('5');       // 获取id数量，应返回五条数据
r(count($aiTest->getPromptsByIdListTest('2,3,4,5,7,8'))) && p()         && e('6');       // 获取id数量，应返回六条数据
