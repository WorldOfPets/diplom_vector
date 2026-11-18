@extends('layouts.admin')

@section('title', 'Аудит')

@section('content')
    <div>
        <p class="section-eyebrow">Безопасность</p>
        <h1 class="mt-1 text-3xl font-extrabold tracking-tight">Журнал действий</h1>
        <p class="mt-1 text-sm text-muted">Кто менял контент и сбрасывал попытки.</p>
    </div>

    <div class="card mt-5 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] text-left text-xs">
                <thead class="border-b border-line/70 bg-paper/60 text-[10px] font-bold uppercase tracking-[0.12em] text-muted">
                    <tr>
                        <th class="px-4 py-3">Когда</th>
                        <th class="px-4 py-3">Кто</th>
                        <th class="px-4 py-3">Действие</th>
                        <th class="px-4 py-3">Объект</th>
                        <th class="px-4 py-3">Детали</th>
                        <th class="px-4 py-3">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">
                    @forelse($logs as $log)
                        <tr class="align-top hover:bg-paper/40">
                            <td class="px-4 py-3 text-muted">{{ $log->created_at?->format('d.m.Y H:i') }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $log->user?->name ?? 'система' }}</td>
                            <td class="px-4 py-3"><span class="chip">{{ $log->action }}</span></td>
                            <td class="px-4 py-3 text-muted">
                                {{ class_basename($log->subject_type) }}{{ $log->subject_id ? ' #'.$log->subject_id : '' }}
                            </td>
                            <td class="max-w-xs px-4 py-3 text-[10px] text-muted">{{ $log->meta ? json_encode($log->meta, JSON_UNESCAPED_UNICODE) : '—' }}</td>
                            <td class="px-4 py-3 text-[10px] text-muted">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-muted">Записей пока нет.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $logs->links() }}</div>
@endsection
