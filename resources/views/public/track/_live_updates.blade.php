@include('public.track._status', ['incident' => $incident])
@include('public.track._assignments', ['incident' => $incident])
<div id="case-history-content">
    @include('public.track._history', ['incident' => $incident])
</div>
