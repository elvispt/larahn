@extends('layouts.master')

@section('content')
  <ul>
    @foreach ($items as $item)
      <li>
        <p>
          <a href="{{ route('item', ['id' => $item->id]) }}">{{ $item->title }}</a>
          |
          @isset($item->url)
            <a href="{{ $item->url }}" rel="nofollow" target="__{{ $item->id }}">[>]</a></p>
          @endisset
        <p><small>S: {{ $item->score }} | C: {{ $item->descendants }} | {{ \Carbon\Carbon::createFromTimestamp($item->time)->format('Y-m-d H:i:s') }}</small></p>
      </li>
    @endforeach
  </ul>
@endsection
