@props(['items' => []])

@if (count($items))
    <div class="kpi-grid mb-4">
        @foreach ($items as $item)
            @php $tag = ! empty($item['href']) ? 'a' : 'div'; @endphp
            <{{ $tag }}
                @if (! empty($item['href'])) href="{{ $item['href'] }}" @endif
                class="kpi-card tone-{{ $item['tone'] ?? 'primary' }}"
            >
                <span class="kpi-icon tone-{{ $item['tone'] ?? 'primary' }}"><i class="bi {{ $item['icon'] ?? 'bi-bar-chart' }}"></i></span>
                <span class="kpi-body">
                    <span class="kpi-value">{{ $item['value'] }}</span>
                    <span class="kpi-label">{{ $item['label'] }}</span>
                    @if (! empty($item['sub']))
                        <span class="kpi-sub">{{ $item['sub'] }}</span>
                    @endif
                </span>
            </{{ $tag }}>
        @endforeach
    </div>
@endif
