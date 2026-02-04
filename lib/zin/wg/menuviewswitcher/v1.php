<?php
declare(strict_types=1);
/**
 * The menuViewSwitcher widget class file of zin module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2023 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.zentao.net)
 * @license     ZPL(https://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      sunhao<sunhao@easycorp.ltd>
 * @package     zin
 * @link        http://www.zentao.net
 */
namespace zin;

require_once dirname(__DIR__) . DS . 'mainnavbar' . DS . 'v1.php';

/**
 * 导航视图切换组件（暂适用于国际版执行模块）。
 * The menuViewSwitcher widget class.
 *
 * @author Hao Sun
 */
class menuViewSwitcher extends wg
{
    /**
     * Override the build method.
     *
     * @access protected
     * @return mixed
     */
    protected function build()
    {
        $workspace = commonModel::getWorkspaceInfo();
        if($workspace['opened']) return null;

        list($items, $selectedItem) = static::getItems();
        if(empty($items)) return null;

        return dropdown
        (
            btn
            (
                setID('menuViewSwitcher'),
                setClass('rounded-full'),
                set::type('surface'),
                set::text($selectedItem['text']),
                css('#actionBar>a[href^="' . str_replace('.html', '', createLink('task', 'report')) . '"]{display: none;}')
            ),
            set::items($items)
        );
    }

    /**
     * Get the items for the menuViewSwitcher.
     *
     * @access public
     * @return array
     */
    public static function getItems()
    {
        global $lang, $app;

        $navbarInMainMenu = data('navbarInMainMenu');
        if(empty($navbarInMainMenu)) return null;

        $selectedItem = null;
        $items        = [];
        $lastItem     = null;
        if($navbarInMainMenu)
        {
            $addItem = function($item) use (&$items, &$lastItem, &$selectedItem, $lang)
            {
                if(is_array($lastItem) && ($lastItem['data-id'] === 'kanban' || $lastItem['data-id'] === 'burn')) $items[] = ['type' => 'divider'];
                if($item['data-id'] === 'task') $item['text'] = $lang->execution->list;
                if($item['active'])
                {
                    $item['active']   = false;
                    $item['selected'] = true;
                    $selectedItem = $item;
                }
                $item['icon'] = null;
                $items[] = $item;
                $lastItem = $item;
            };
            foreach($navbarInMainMenu as $item)
            {
                if($item['data-id'] === 'view')
                {
                    $viewItems = mainNavbar::getItems(['activeMenu' => 'view']);
                    foreach($viewItems as $viewItem) $addItem($viewItem);
                    continue;
                }
                $addItem($item);
            }

            $execution = data('execution');
            if(hasPriv('task', 'report') && empty($execution->isTpl))
            {
                if(!empty($items)) $items[] = ['type' => 'divider'];
                $browseType = data('browseType');
                $active     = $app->moduleName === 'task' && $app->methodName === 'report';
                $items[] = [
                    'active'   => $active,
                    'text'     => $lang->task->report->common,
                    'data-app' => $app->tab,
                    'url'      => createLink('task', 'report', "execution={$execution->id}&browseType={$browseType}")
                ];
            }
        }

        return [$items, $selectedItem];
    }
}
