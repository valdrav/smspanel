@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')<h1>{{ $pageTitle }}</h1>@stop

@section('content')
    @include('admin.partials.alerts')
    <div class="card card-primary">
        <form action="{{ route('admin.sms-providers.store') }}" method="POST">
            @csrf
            <div class="card-body">@include('admin.sms-providers.partials.form')</div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Kaydet</button>
                <a href="{{ route('admin.sms-providers.index') }}" class="btn btn-secondary">İptal</a>
            </div>
        </form>
    </div>
@stop

@section('js')
@include('admin.sms-providers.partials.driver-script')
@stop
