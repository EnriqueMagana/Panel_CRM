@props([
    'media' => collect(),
    'audio' => collect(),
])

@php
    $media = collect($media);
    $audio = collect($audio);
    $total = $media->count() + $audio->count();
@endphp

<section class="chat-info-section chat-shared-media" aria-labelledby="chat-shared-media-title">
    <div class="chat-info-section-title">
        <h4 id="chat-shared-media-title">Archivos compartidos</h4>
        <span class="chat-media-count">{{ $total }}</span>
    </div>

    @if ($total === 0)
        <div class="chat-media-empty">
            <x-chat.icon name="image" class="size-5" />
            <p>Aún no hay imágenes, GIF ni audios.</p>
        </div>
    @else
        @if ($media->isNotEmpty())
            <div class="chat-media-grid" aria-label="Imágenes y GIF compartidos">
                @foreach ($media as $item)
                    <a href="{{ $item->attachmentUrlFor($item->type === 'gif' ? 'original' : 'large') }}"
                        target="_blank" rel="noreferrer" class="chat-media-tile"
                        aria-label="Abrir {{ $item->attachment_name ?: 'imagen compartida' }}">
                        <img src="{{ $item->attachmentUrlFor('small') }}"
                            alt="{{ $item->attachment_name ?: 'Imagen compartida' }}" width="112" height="112"
                            loading="lazy" decoding="async">
                        @if ($item->type === 'gif')
                            <span class="chat-media-badge">GIF</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif

        @if ($audio->isNotEmpty())
            <div class="chat-audio-list" aria-label="Audios compartidos">
                @foreach ($audio as $item)
                    <div class="chat-audio-item">
                        <span class="chat-audio-icon"><x-chat.icon name="mic" class="size-4" /></span>
                        <span class="chat-audio-copy">
                            <strong>{{ $item->attachment_name ?: 'Audio' }}</strong>
                            <small>{{ $item->sent_at?->translatedFormat('d M Y, H:i') }}</small>
                        </span>
                        <audio controls preload="none" aria-label="{{ $item->attachment_name ?: 'Audio compartido' }}">
                            <source src="{{ $item->attachment_url }}" type="{{ $item->attachment_mime }}">
                        </audio>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</section>
