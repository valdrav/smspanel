@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')<h1>{{ $pageTitle }}</h1>@stop

@section('content')
    @include('admin.partials.alerts')
    <div class="card card-primary">
        <form action="{{ route('admin.contacts.update', $contact) }}" method="POST">
            @csrf @method('PUT')
            <div class="card-body">@include('admin.contacts.partials.form', ['contact' => $contact])</div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Güncelle</button>
                <a href="{{ route('admin.contacts.index') }}" class="btn btn-secondary">İptal</a>
            </div>
        </form>
    </div>
@stop
