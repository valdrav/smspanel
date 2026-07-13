@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $pageTitle }}</h1>
        @can('create', App\Models\SmsTemplate::class)
            <a href="{{ route('admin.sms-templates.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Yeni Şablon</a>
        @endcan
    </div>
@stop

@section('content')
    @include('admin.partials.alerts')
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Ad</th>
                        <th>Mesaj</th>
                        <th>Durum</th>
                        <th>Güncelleme</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td>{{ $template->id }}</td>
                            <td>{{ $template->name }}</td>
                            <td>{{ Str::limit($template->body, 60) }}</td>
                            <td>
                                <span class="badge badge-{{ $template->is_active ? 'success' : 'secondary' }}">
                                    {{ $template->is_active ? 'Aktif' : 'Pasif' }}
                                </span>
                            </td>
                            <td>{{ $template->updated_at?->format('d.m.Y H:i') }}</td>
                            <td>
                                @can('update', $template)
                                    <a href="{{ route('admin.sms-templates.edit', $template) }}" class="btn btn-xs btn-warning"><i class="fas fa-edit"></i></a>
                                @endcan
                                @can('delete', $template)
                                    <form action="{{ route('admin.sms-templates.destroy', $template) }}" method="POST" class="d-inline" onsubmit="return confirm('Silinsin mi?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Şablon bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($templates->hasPages())
            <div class="card-footer">{{ $templates->links('pagination::bootstrap-4') }}</div>
        @endif
    </div>
@stop
