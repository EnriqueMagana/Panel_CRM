@props(['status'])

@php
    $classes = match ($status) {
        'in_progress' => 'border-blue-500/25 bg-blue-500/10 text-blue-700 dark:text-blue-300',
        'waiting' => 'border-amber-500/25 bg-amber-500/10 text-amber-700 dark:text-amber-300',
        'resolved' => 'border-emerald-500/25 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
        'cancelled' => 'border-slate-500/25 bg-slate-500/10 text-slate-700 dark:text-slate-300',
        default => 'border-rose-500/25 bg-rose-500/10 text-rose-700 dark:text-rose-300',
    };
@endphp

<span {{ $attributes->class("inline-flex min-h-7 items-center rounded-full border px-2.5 text-xs font-medium {$classes}") }}>
    {{ \App\Models\TicketEscalation::STATUSES[$status] ?? $status }}
</span>
