@extends('layouts.master')

@section('content')
  <div class="mb-5 ax">
    <div class="font-weight-bold">{{ $story->title }}</div>
    <div class="comments">
      <small>S: {{ $story->score }}</small>
      |
      <small>{{ \Carbon\Carbon::createFromTimestamp($story->time)->diffForHumans() }}</small>
      | &nbsp;
      @isset($story->url)
        <a href="{{ $story->url }}" rel="nofollow" target="__{{ $story->id }}" class="ax--icon-small">&nbsp;</a>
      @endisset
    </div>
  </div>

  @foreach ($items as $item)
    <div class="row mb-2">
      <div class="col">
        <div>
          <small>by: {{ $item->by }} | {{ \Carbon\Carbon::createFromTimestamp($item->time)->diffForHumans() }}</small>
        </div>
        <p>
          {!! $item->text !!}
        </p>

      </div>
    </div>
    <hr>
  @endforeach
@endsection
