@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')<h1>{{ $pageTitle }}</h1>@stop

@section('content')
    @include('admin.partials.alerts')
    <div class="row">
        @foreach($roles as $role)
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ \App\Enums\RoleName::labelFor($role->name) }}</h5>
                        <p><span class="badge badge-primary">{{ $role->permissions_count }} yetki</span></p>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-shield-alt"></i> Yetkileri Düzenle
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@stop
