{{--
    Shared footer for all generated PDF reports. Placed in a
    "position: fixed; bottom: 0" block — dompdf's supported way of doing a
    running footer on every page — with CSS counter(page)/counter(pages) for
    page numbers, so every generated report shows the same footer.
--}}
<div class="rg-pdf-footer" style="position: fixed; bottom: -20px; left: 0; right: 0; text-align:center; font-size:8pt; color:#777; border-top:1px solid #ddd; padding-top:6px;">
    {{ config('raniag.name', 'RANIAG - MDRRMO Pamplona') }} &middot;
    Generated {{ now()->format('M d, Y h:i A') }} &middot;
    Official Document &mdash; Unauthorized reproduction or alteration is prohibited
    &middot; Page <span class="rg-page-number"></span> of <span class="rg-page-count"></span>
</div>
<style>
    .rg-page-number:after { content: counter(page); }
    .rg-page-count:after { content: counter(pages); }
</style>
