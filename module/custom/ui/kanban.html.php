<?php
declare(strict_types=1);
/**
 * The execution view file of custom module of ZenTaoPMS.
 * @copyright   Copyright 2009-2023 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.zentao.net)
 * @license     ZPL(https://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Shujie Tian<tianshujie@easycorp.ltd>
 * @package     custom
 * @link        https://www.zentao.net
 */
namespace zin;

$reminder   = isset($config->kanban->reminder) ? $config->kanban->reminder : null;
$expireDays = isset($reminder->expireDays) ? $reminder->expireDays : 3;
$frequency  = isset($reminder->frequency) ? $reminder->frequency : 'daily';

div
(
    setClass('flex'),
    formPanel
    (
        setID('closedExecutionForm'),
        set::actions(array('submit')),
        set::labelWidth('120px'),
        setClass('flex-auto'),
        formGroup
        (
            set::width('2/3'),
            set::label($lang->custom->closedKanban),
            radioList
            (
                set::name('kanban'),
                set::items($lang->custom->CRKanban),
                set::value(isset($config->CRKanban) ? $config->CRKanban : 0),
                set::inline(true)
            )
        ),
        formGroup
        (
            set::label(''),
            span
            (
                icon('info text-warning mr-2'),
                $lang->custom->notice->readOnlyOfKanban
            )
        ),
        formGroup
        (
            set::width('1/3'),
            set::label($lang->custom->kanbanExpireDays),
            input
            (
                set::name('expireDays'),
                set::type('number'),
                set::min(0),
                set::value($expireDays)
            )
        ),
        formGroup
        (
            set::width('2/3'),
            set::label($lang->custom->kanbanReminderFrequency),
            radioList
            (
                set::name('frequency'),
                set::items($lang->custom->kanbanReminderFrequencyList),
                set::value($frequency),
                set::inline(true)
            )
        ),
        formGroup
        (
            set::label(''),
            span
            (
                icon('info text-warning mr-2'),
                $lang->custom->notice->kanbanReminder
            )
        )
    )
);
