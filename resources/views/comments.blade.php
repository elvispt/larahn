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
    @php $index = 0; @endphp
    @component('components.comment', ['item' => $item, 'index' => $index])@endcomponent
  @endforeach
@endsection
