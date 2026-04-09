#!/usr/bin/env php
<?php

/**

title=测试 convertTao::createSystem();
timeout=0
cid=15840

*/

include dirname(__FILE__, 5) . '/test/lib/init.php';
include dirname(__FILE__, 2) . '/lib/tao.class.php';

zenData('product')->gen(10);

su('admin');

$convertTest = new convertTaoTest();

r($convertTest->createSystemTest(1)) && p() && e('true');
