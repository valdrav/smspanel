@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $pageTitle }}</h1>
        <a href="{{ route('admin.support-tickets.index') }}" class="btn btn-outline-secondary btn-sm">Listeye Dön</a>
    </div>
@stop

@section('content')
    @include('admin.partials.alerts')
    <div class="row">
        <div class="col-lg-8">
            @include('admin.support-tickets.partials.status-tracker')
            <div class="card">
                <div class="card-header">
                    <strong>{{ $ticket->subject }}</strong>
                    <span class="badge badge-{{ $ticket->status->badgeClass() }} ml-2">{{ $ticket->status->label() }}</span>
                    <span class="badge badge-{{ $ticket->priority->badgeClass() }} ml-1">{{ $ticket->priority->label() }}</span>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    @foreach ($messages as $message)
                        <div class="mb-3 p-3 rounded {{ $message->user_id === auth()->id() ? 'bg-light' : 'border' }} {{ $message->is_internal ? 'border-warning' : '' }}">
                            <div class="d-flex justify-content-between mb-1">
                                <strong>{{ $message->user->name ?? 'Sistem' }}</strong>
                                <small class="text-muted">{{ $message->created_at?->format('d.m.Y H:i') }}</small>
                            </div>
                            @if($message->is_internal)<span class="badge badge-warning mb-2">Dahili Not</span>@endif
                            <div>{!! nl2br(e($message->body)) !!}</div>
                            @if($message->attachments->isNotEmpty())
                                @include('admin.support-tickets.partials.attachments', ['attachments' => $message->attachments])
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="card-footer">
                    <form action="{{ route('admin.support-tickets.reply', $ticket) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <textarea name="body" rows="4" class="form-control @error('body') is-invalid @enderror" placeholder="Yanıtınız..." required>{{ old('body') }}</textarea>
                            @error('body')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label class="mb-1">Görsel / Dosya Ekle</label>
                            <input type="file" name="attachments[]" class="form-control-file @error('attachments.*') is-invalid @enderror" multiple accept="image/*,.pdf">
                            <small class="form-text text-muted">En fazla 5 dosya, max 5 MB</small>
                            @error('attachments.*')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        </div>
                        @if($canManage)
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input" id="is_internal" name="is_internal" value="1">
                                <label class="custom-control-label" for="is_internal">Dahili not (kullanıcı görmez)</label>
                            </div>
                        @endif
                        <button type="submit" class="btn btn-primary">Yanıt Gönder</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Talep Bilgileri</div>
                <div class="card-body">
                    <p><strong>No:</strong> {{ $ticket->ticket_number }}</p>
                    <p><strong>Kategori:</strong> {{ $ticket->category->label() }}</p>
                    <p><strong>Oluşturan:</strong> {{ $ticket->user->name ?? '—' }}</p>
                    <p><strong>Oluşturulma:</strong> {{ $ticket->created_at?->format('d.m.Y H:i') }}</p>
                    @if($ticket->assignee)<p><strong>Atanan:</strong> {{ $ticket->assignee->name }}</p>@endif
                    @if($ticket->closed_at)<p><strong>Kapanış:</strong> {{ $ticket->closed_at->format('d.m.Y H:i') }}</p>@endif
                </div>
            </div>
            @if($canManage)
                <div class="card card-warning">
                    <div class="card-header">Yönetim</div>
                    <form action="{{ route('admin.support-tickets.update', $ticket) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="card-body">
                            <div class="form-group">
                                <label>Durum</label>
                                <select name="status" class="form-control form-control-sm">
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->value }}" @selected($ticket->status === $status)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Öncelik</label>
                                <select name="priority" class="form-control form-control-sm">
                                    @foreach ($priorities as $priority)
                                        <option value="{{ $priority->value }}" @selected($ticket->priority === $priority)>{{ $priority->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label>Atanan Personel</label>
                                <select name="assigned_to" class="form-control form-control-sm">
                                    <option value="">— Seçilmedi —</option>
                                    @foreach ($staffUsers as $staff)
                                        <option value="{{ $staff->id }}" @selected($ticket->assigned_to == $staff->id)>{{ $staff->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-warning btn-sm btn-block">Güncelle</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
@stop
