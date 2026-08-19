@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')
<div class="dash-app">
    <div class="section-heading">{{ __('Dashboard') }}</div>

    <div class="dash-toolbar">
        <button type="button" class="btn btn-primary" id="dash-add-widget-btn">
            <i class="glyphicon glyphicon-plus"></i> {{ __('Add widget') }}
        </button>
    </div>

    @if (empty($boardWidgets))
        <div class="dash-empty">{{ __('No widgets yet — click "Add widget" to get started.') }}</div>
    @else
        <div class="dash-grid" id="dash-grid">
            @foreach ($boardWidgets as $widget)
                <div class="dash-widget dash-widget-{{ $widget['size'] }}" data-widget-id="{{ $widget['id'] }}" data-widget-key="{{ $widget['key'] }}">
                    <div class="dash-widget-header">
                        <span class="dash-widget-handle glyphicon glyphicon-move" title="{{ __('Drag to reorder') }}"></span>
                        <span class="dash-widget-title"><i class="glyphicon {{ $widget['icon'] }}"></i> {{ $widget['label'] }}</span>
                        @if ($widget['cyclable'])
                            <button type="button" class="btn-icon dash-widget-resize" title="{{ __('Change size') }}"><i class="glyphicon glyphicon-resize-full"></i></button>
                        @endif
                        <button type="button" class="btn-icon dash-widget-remove" title="{{ __('Remove widget') }}"><i class="glyphicon glyphicon-remove"></i></button>
                    </div>
                    <div class="dash-widget-body">
                        {!! $widget['html'] !!}
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="dash-picker-backdrop" id="dash-picker-backdrop" style="display:none;">
        <div class="dash-picker dash-app">
            <h4>{{ __('Add widget') }}</h4>
            <div id="dash-picker-list">
                @forelse ($availableToAdd as $item)
                    <div class="dash-picker-item" data-widget-key="{{ $item['key'] }}">
                        <i class="glyphicon {{ $item['icon'] }}"></i> {{ $item['label'] }}
                    </div>
                @empty
                    <div class="dash-picker-empty">{{ __('All available widgets are already on your board.') }}</div>
                @endforelse
            </div>
            <div class="margin-top" style="text-align:right;">
                <button type="button" class="btn btn-default" id="dash-picker-close">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

@php
    // Cache-bust on each file's own mtime — same pattern as Calendar's own
    // index.blade.php (nginx serves module assets with no query param by
    // default, so a stale copy survives until the URL itself changes).
    $dashAssetVersion = function ($relativePath) {
        $path = public_path('modules/dashboard/'.$relativePath);
        return file_exists($path) ? filemtime($path) : time();
    };
@endphp
<link rel="stylesheet" href="{{ Module::getPublicPath('dashboard') }}/css/dashboard.css?v={{ $dashAssetVersion('css/dashboard.css') }}">
<script {!! \Helper::cspNonceAttr() !!} src="{{ asset('js/html5sortable.js') }}"></script>
<script {!! \Helper::cspNonceAttr() !!}>
(function () {
    function csrfHeaders() {
        return {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
    }

    var grid = document.getElementById('dash-grid');
    if (grid) {
        sortable(grid, {
            handle: '.dash-widget-handle',
            items: '.dash-widget',
            placeholderClass: 'dash-widget-placeholder',
            draggingClass: 'dash-widget-dragging'
        });

        grid.addEventListener('sortupdate', function () {
            var ids = Array.from(grid.querySelectorAll('.dash-widget')).map(function (el) {
                return parseInt(el.getAttribute('data-widget-id'), 10);
            });
            fetch('{{ route('dashboard.reorder') }}', {
                method: 'POST',
                headers: csrfHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify({ ids: ids })
            });
        });

        grid.addEventListener('click', function (e) {
            var resizeBtn = e.target.closest('.dash-widget-resize');
            var removeBtn = e.target.closest('.dash-widget-remove');

            if (resizeBtn) {
                // A full reload, not just swapping the size class — a
                // widget's rendered content (e.g. how many list rows it
                // shows) scales with its size, and that HTML was only
                // ever rendered server-side at the size it had on page
                // load. Swapping just the class would leave a widget that
                // grew to "large" still showing its stale "medium" content.
                var card = resizeBtn.closest('.dash-widget');
                var id = card.getAttribute('data-widget-id');
                fetch('/dashboard/' + id + '/size', {
                    method: 'PUT',
                    headers: csrfHeaders(),
                    credentials: 'same-origin'
                }).then(function () {
                    location.reload();
                }).catch(function () { /* best-effort */ });
            }

            if (removeBtn) {
                var removeCard = removeBtn.closest('.dash-widget');
                var removeId = removeCard.getAttribute('data-widget-id');
                fetch('/dashboard/' + removeId, {
                    method: 'DELETE',
                    headers: csrfHeaders(),
                    credentials: 'same-origin'
                }).then(function () {
                    location.reload();
                }).catch(function () { /* best-effort */ });
            }
        });
    }

    var addBtn = document.getElementById('dash-add-widget-btn');
    var backdrop = document.getElementById('dash-picker-backdrop');
    var closeBtn = document.getElementById('dash-picker-close');

    if (addBtn && backdrop) {
        addBtn.addEventListener('click', function () { backdrop.style.display = 'flex'; });
        closeBtn.addEventListener('click', function () { backdrop.style.display = 'none'; });
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) { backdrop.style.display = 'none'; }
        });

        backdrop.querySelectorAll('.dash-picker-item').forEach(function (item) {
            item.addEventListener('click', function () {
                var key = item.getAttribute('data-widget-key');
                fetch('{{ route('dashboard.store') }}', {
                    method: 'POST',
                    headers: csrfHeaders(),
                    credentials: 'same-origin',
                    body: JSON.stringify({ widget_key: key })
                }).then(function () {
                    location.reload();
                }).catch(function () { /* best-effort */ });
            });
        });
    }
})();
</script>
@endsection
