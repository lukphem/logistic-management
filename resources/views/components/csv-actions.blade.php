@props(['exportRoute', 'importRoute', 'label' => null])

<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <form method="POST" action="{{ $importRoute }}" enctype="multipart/form-data" class="flex items-center gap-2">
        @csrf
        <input type="file" name="file" accept=".csv,text/csv" required
               class="text-sm text-ink-500 file:mr-3 file:rounded-md file:border-0 file:bg-surface-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-ink-900 hover:file:bg-line">
        <button type="submit" class="rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink-900 transition-colors hover:bg-surface-50">
            Import{{ $label ? ' ' . $label : '' }} CSV
        </button>
    </form>
    <a href="{{ $exportRoute }}" class="rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink-900 transition-colors hover:bg-surface-50">
        Export{{ $label ? ' ' . $label : '' }} CSV
    </a>
</div>
