@extends('adminlte::page')

@section('title', $pageTitle)
@section('content_header')<h1>{{ $pageTitle }}</h1>@stop

@section('content')
    @include('admin.partials.alerts')
    <div class="card card-primary">
        <form action="{{ route('admin.support-tickets.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label for="subject">Konu *</label>
                    <input type="text" name="subject" id="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}" required>
                    @error('subject')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="category">Kategori *</label>
                        <select name="category" id="category" class="form-control @error('category') is-invalid @enderror" required>
                            @foreach ($categories as $category)
                                <option value="{{ $category->value }}" @selected(old('category') === $category->value)>{{ $category->label() }}</option>
                            @endforeach
                        </select>
                        @error('category')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group col-md-6">
                        <label for="priority">Öncelik *</label>
                        <select name="priority" id="priority" class="form-control @error('priority') is-invalid @enderror" required>
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}" @selected(old('priority', 'normal') === $priority->value)>{{ $priority->label() }}</option>
                            @endforeach
                        </select>
                        @error('priority')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="form-group">
                    <label for="body">Mesajınız *</label>
                    <textarea name="body" id="body" rows="6" class="form-control @error('body') is-invalid @enderror" required>{{ old('body') }}</textarea>
                    @error('body')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group mb-0">
                    <label for="attachments">Görseller / Dosyalar</label>
                    <input type="file" name="attachments[]" id="attachments" class="form-control-file @error('attachments.*') is-invalid @enderror" multiple accept="image/*,.pdf">
                    <small class="form-text text-muted">En fazla 5 dosya, her biri max 5 MB (JPG, PNG, GIF, WEBP, PDF)</small>
                    @error('attachments.*')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Talep Oluştur</button>
                <a href="{{ route('admin.support-tickets.index') }}" class="btn btn-secondary">İptal</a>
            </div>
        </form>
    </div>
@stop
