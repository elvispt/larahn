@extends('layouts.master')

@section('content')
  @foreach ($items as $item)
    <div class="row mb-2 ax">
      <div class="col-10">
        <div class="ax--left">
          <a href="{{ route('item', ['id' => $item->id]) }}">{{ $item->title }}</a>
          <div>
            <small>S: {{ $item->score }} | C: {{ $item->descendants }} | {{ \Carbon\Carbon::createFromTimestamp($item->time)->format('Y-m-d H:i:s') }}</small>
          </div>
        </div>
      </div>
      <div class="col-2">
        @isset($item->url)
          <a href="{{ $item->url }}" rel="nofollow" target="__{{ $item->id }}" class="ax--icon"></a>
        @endisset
      </div>
    </div>
    <hr>
  @endforeach
@endsection
