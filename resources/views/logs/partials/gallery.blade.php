{{-- Props: $images (collection of WorkLogImage), $group ('before'|'after') --}}
@if($images->isNotEmpty())
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-3">
        @foreach($images as $image)
            <figure class="space-y-1">
                <button type="button" data-lightbox data-src="{{ $image->url }}" data-caption="{{ $image->caption }}" data-group="{{ $group }}"
                        class="block w-full aspect-video rounded-lg overflow-hidden border border-slate-700 bg-slate-900 hover:border-indigo-400 transition-all">
                    <img src="{{ $image->url }}" alt="{{ $image->caption ?: 'Screenshot '.$group }}" loading="lazy" class="w-full h-full object-cover">
                </button>
                @if($image->caption)
                    <figcaption class="text-[11px] text-slate-400 leading-snug">{{ $image->caption }}</figcaption>
                @endif
            </figure>
        @endforeach
    </div>
@endif
