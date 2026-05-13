<?php
namespace zin;
global $lang, $config;

$fields = defineFieldList('task');

$fields->field('execution')
    ->hidden((data('execution.type') == 'kanban' && $config->vision != 'lite') || empty(data('execution.multiple')))
    ->control('picker')
    ->items(data('executions'))
    ->value(data('execution.id'));

$fields->field('type')
    ->control('picker')
    ->items($lang->task->typeList)
    ->value(data('task.type'));

$fields->field('name')
    ->control('colorInput', array('colorValue' => data('task.color')))
    ->value(data('task.name'));

$fields->field('complexity')
    ->label($lang->task->complexity)
    ->labelClass('required')
    ->width('1/4')
    ->control('picker')
    ->items($lang->task->complexityList)
    ->value(data('task.complexity') ? data('task.complexity') : 'L1')
    ->required(strpos($config->task->create->requiredFields, 'complexity') !== false);

$fields->field('datePlan')
    ->lable($lang->task->datePlan)
    ->required(strpos($config->task->create->requiredFields, 'estStarted') !== false || strpos($config->task->create->requiredFields, 'deadline') !== false)
    ->control('inputGroup')
    ->itemBegin('estStarted')->control('datePicker')->placeholder($lang->task->estStarted)->value(data('task.estStarted'))->itemEnd()
    ->item(array('control' => 'span', 'text' => '-'))
    ->itemBegin('deadline')->control('datePicker')->placeholder($lang->task->deadline)->value(data('task.deadline'))->itemEnd();

$fields->field('pri')
    ->label($lang->task->pri)
    ->width('1/4')
    ->control('priPicker')
    ->items($lang->task->priList)
    ->value(data('task.pri'));

$fields->field('estimate')
    ->control('input')
    ->label($lang->task->estimateLabel)
    ->width('1/4');

$fields->field('desc')
    ->width('full')
    ->control('editor');

$fields->field('files')
    ->width('full')
    ->value('')
    ->control('fileSelector');

$fields->field('mailto')
    ->control('mailto')
    ->multiple(true)
    ->items(data('users'));

$fields->field('keywords')
    ->control('input');
