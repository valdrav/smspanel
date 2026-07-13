@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')<h1>{{ $pageTitle }}</h1>@stop

@section('content')
    @include('admin.partials.alerts')
    <div class="card card-warning">
        <form action="{{ route('admin.user-sender-numbers.update', $senderNumber) }}" method="POST">
            @csrf @method('PUT')
            <div class="card-body">@include('admin.user-sender-numbers.partials.form', ['senderNumber' => $senderNumber])</div>
            <div class="card-footer">
                <button type="submit" class="btn btn-warning">Güncelle</button>
                <a href="{{ route('admin.user-sender-numbers.index') }}" class="btn btn-secondary">İptal</a>
            </div>
        </form>
    </div>
@stop
