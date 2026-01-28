<?php
declare(strict_types = 1);
require_once dirname(__FILE__, 5) . '/test/lib/test.class.php';

class testtaskModelTest extends baseTest
{
    protected $moduleName = 'testtask';
    protected $className  = 'model';

    /**
     * 获取测试单的所属分支信息。
     * Get the branch information of the test task.
     *
     * @param  object $task
     * @param  int    $productID
     * @access public
     * @return array
     */
    public function getBranchesByTaskTest(object $task, int $productID = 0): array
    {
        $result = $this->invokeArgs('getBranchesByTask', [$task, $productID]);
        if(dao::isError()) return dao::getError();
        return $result;
    }
}
