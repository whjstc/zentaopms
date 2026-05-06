
/* Keep pasted rich-text tables from overflowing into the side panel. */
$(function()
{
    $(".detail-section table, .detail-content.article-content table").each(function()
    {
        const $table = $(this);
        if($table.parent().hasClass("zentao-rich-table-scroll")) return;
        if($table.closest(".datatable, .dtable, .table-data, #stepsTable").length) return;

        $table.wrap("<div class=\"zentao-rich-table-scroll\"></div>");
    });
});
