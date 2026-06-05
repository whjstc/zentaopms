#!/usr/bin/env php
<?php

/**

title=测试 formdom 解析 UTF-8 表单片段;
timeout=0
cid=1

- 解析包含中文和 HTML 的表单 >> 中文标题
- 解析包含中文和 HTML 的表单 >> 第一行第二行
- 解析 zui picker 布尔默认值时不触发未定义数组下标 >> flag

*/

include dirname(__FILE__, 5) . '/lib/formdom/formdom.class.php';

function testFormdomParse(): array
{
    $parser = new formdom();
    $html   = <<<HTML
<form id="caseForm">
  <input type="text" name="title" value="中文标题" />
  <textarea name="desc">第一行<p>第二行</p></textarea>
  <div zui-create="picker" zui-create-picker='{"name":"flag","defaultValue":false}'></div>
</form>
HTML;

    set_error_handler(function($severity, $message, $file, $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    try
    {
        return $parser->parse($html, 'caseForm');
    }
    finally
    {
        restore_error_handler();
    }
}

$result = testFormdomParse();

if(!isset($result['title']) || $result['title'] !== '中文标题') exit("FAIL title\n");
if(!isset($result['desc']) || $result['desc'] !== '第一行第二行') exit("FAIL desc\n");
if(!array_key_exists('flag', $result)) exit("FAIL flag\n");

echo "PASS\n";
