@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')<h1>{{ $pageTitle }}</h1>@stop

@section('content')
    @include('admin.partials.alerts')

    @if($role->name === \App\Enums\RoleName::SuperAdmin->value)
        <div class="alert alert-info">Süper yönetici rolü tüm yetkilere sahiptir ve değiştirilemez.</div>
    @endif

    <form action="{{ route('admin.roles.update', $role) }}" method="POST">
        @csrf @method('PUT')
        <div class="card">
            <div class="card-body">
                @foreach($permissions as $group => $items)
                    <h5 class="text-muted mt-3 mb-2">{{ \App\Support\PermissionLabel::group($group) }}</h5>
                    <div class="row">
                        @foreach($items as $permission)
                            <div class="col-md-4 mb-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input"
                                        id="perm_{{ $permission->id }}"
                                        name="permissions[]"
                                        value="{{ $permission->name }}"
                                        @checked($role->hasPermissionTo($permission->name))
                                        @disabled($role->name === \App\Enums\RoleName::SuperAdmin->value)>
                                    <label class="custom-control-label" for="perm_{{ $permission->id }}">
                                        {{ \App\Support\PermissionLabel::permission($permission->name) }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <hr>
                @endforeach
            </div>
            <div class="card-footer">
                @if($role->name !== \App\Enums\RoleName::SuperAdmin->value)
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                @endif
                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">Geri</a>
            </div>
        </div>
    </form>
@stop
