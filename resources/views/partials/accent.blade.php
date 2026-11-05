{{-- config('admin.accent_color') as the --la-accent CSS variable (laravel-admin.css falls back to the link color) --}}
@if($accent = config('admin.accent_color'))
<style>:root { --la-accent: {{ $accent }}; }</style>
@endif
