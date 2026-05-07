<?php
declare(strict_types=1);
/**
* The UI file of story module of ZenTaoPMS.
*
* @copyright   Copyright 2009-2023 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.zentao.net)
* @license     ZPL(https://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
* @author      Wang Yidong <yidong@easycorp.ltd>
* @package     story
* @link        https://www.zentao.net
*/

namespace zin;

$isMarkdownContent = static function(string $content): bool
{
    $content = trim(htmlspecialchars_decode($content, ENT_QUOTES));
    if($content === '' || isHTML($content)) return false;

    return preg_match('/(^|\n)(#{1,6}\s+\S+|[-*+]\s+\S+|\d+\.\s+\S+|>\s+\S+|```|~~~|\|.+\||\[[^\]]+\]\([^)]+\)|\*\*[^*]+\*\*)/m', $content) === 1;
};

$renderStoryContent = static function(string $content) use($isMarkdownContent): string
{
    return $isMarkdownContent($content) ? \commonModel::processMarkdown($content) : $content;
};

modalHeader(set::title($lang->story->URChanged));
div(setClass('text-gray mb-2'), icon(setClass('text-warning pr-2'), 'exclamation'), $lang->story->changeTips);
foreach($changedStories as $story)
{
    div
    (
        setClass('changedStoryBox'),
        entityLabel(set(array('entityID' => $story->id, 'level' => 3, 'text' => $story->title))),
        div
        (
            setClass('p-3'),
            p(setClass('text-gray pb-3'), "[{$lang->story->legendSpec}]"),
            html($renderStoryContent($story->spec)),
            p(setClass('text-gray pb-3 pt-3'), "[{$lang->story->legendVerify}]"),
            html($renderStoryContent($story->verify))
        )
    );
}

div
(
    on::click('.changeBtn', 'closeModal'),
    setClass('actions text-center mt-3'),
    btn(set::url(inlink('processstorychange', "id=$storyID&result=no")), setClass('secondary changeBtn mr-5 btn-wide ajax-submit'), $lang->story->changeList['no']),
    btn(set::url(inlink('change', "id=$storyID")), setClass('primary changeBtn btn-wide'), $lang->story->changeList['yes'])
);

render();
