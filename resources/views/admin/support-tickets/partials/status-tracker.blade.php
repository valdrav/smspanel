@php
    $steps = \App\Enums\TicketStatus::cases();
    $currentIndex = collect($steps)->search(fn ($s) => $s === $ticket->status);
@endphp
<div class="card mb-3">
    <div class="card-header py-2"><strong>Durum Takibi</strong></div>
    <div class="card-body py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            @foreach($steps as $index => $step)
                @php
                    $isDone = $currentIndex !== false && $index <= $currentIndex;
                    $isCurrent = $step === $ticket->status;
                @endphp
                <div class="text-center flex-fill px-1 mb-2" style="min-width: 90px;">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1
                        {{ $isCurrent ? 'bg-primary text-white' : ($isDone ? 'bg-success text-white' : 'bg-light border') }}"
                        style="width: 32px; height: 32px;">
                        @if($isDone && ! $isCurrent)<i class="fas fa-check fa-xs"></i>@else<span>{{ $index + 1 }}</span>@endif
                    </div>
                    <small class="d-block {{ $isCurrent ? 'font-weight-bold text-primary' : 'text-muted' }}">{{ $step->label() }}</small>
                </div>
                @if(! $loop->last)
                    <div class="flex-grow-0 d-none d-md-block" style="height: 2px; width: 24px; background: {{ $isDone ? '#28a745' : '#dee2e6' }}; margin-bottom: 28px;"></div>
                @endif
            @endforeach
        </div>
        <div class="timeline timeline-inverse mt-2">
            @forelse($statusLogs as $log)
                <div class="time-label"><span class="bg-secondary">{{ $log->created_at?->format('d.m.Y') }}</span></div>
                <div>
                    <i class="fas fa-exchange-alt bg-info"></i>
                    <div class="timeline-item">
                        <span class="time"><i class="fas fa-clock"></i> {{ $log->created_at?->format('H:i') }}</span>
                        <h3 class="timeline-header">
                            @if($log->from_status)
                                <span class="badge badge-secondary">{{ $log->from_status->label() }}</span>
                                <i class="fas fa-arrow-right mx-1"></i>
                            @endif
                            <span class="badge badge-{{ $log->to_status->badgeClass() }}">{{ $log->to_status->label() }}</span>
                        </h3>
                        <div class="timeline-body">
                            @if($log->note)<p class="mb-1">{{ $log->note }}</p>@endif
                            <small class="text-muted">{{ $log->user?->name ?? 'Sistem' }}</small>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">Henüz durum kaydı yok.</p>
            @endforelse
            <div><i class="fas fa-clock bg-gray"></i></div>
        </div>
    </div>
</div>
