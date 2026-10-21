@props(['register', 'label' => 'Register'])

<div class="btn-group btn-group-sm rg-view-mode" role="group" aria-label="{{ $label }} display" data-view-mode-register="{{ $register }}">
    <button type="button" class="btn btn-outline-secondary" data-view-mode-choice="table" aria-pressed="true" title="Table view">
        <i class="bi bi-list-ul me-1" aria-hidden="true"></i>Table
    </button>
    <button type="button" class="btn btn-outline-secondary" data-view-mode-choice="cards" aria-pressed="false" title="Card view">
        <i class="bi bi-grid-3x2-gap me-1" aria-hidden="true"></i>Cards
    </button>
</div>
