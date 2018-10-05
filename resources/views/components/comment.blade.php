<div class="comment {{ $index > 0 ? 'ml-3' : '' }}">
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
  @isset($item->sub)
    @foreach ($item->sub as $item)
      @php $index += 1; @endphp
      @component('components.comment', ['item' => $item, 'index' => $index])@endcomponent
    @endforeach
  @endisset
</div>

