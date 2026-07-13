@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')<h1>{{ $pageTitle }}</h1>@stop

@section('content')
    @include('admin.partials.alerts')
    <div class="card card-warning">
        <form action="{{ route('admin.sms-providers.update', $provider) }}" method="POST">
            @csrf @method('PUT')
            <div class="card-body">@include('admin.sms-providers.partials.form', ['provider' => $provider])</div>
            <div class="card-footer">
                <button type="submit" class="btn btn-warning">Güncelle</button>
                <a href="{{ route('admin.sms-providers.index') }}" class="btn btn-secondary">İptal</a>
            </div>
        </form>
    </div>
@stop

@section('js')
@include('admin.sms-providers.partials.driver-script')
@stop
