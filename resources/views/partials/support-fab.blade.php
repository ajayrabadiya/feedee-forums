@if ($support['enabled'])
    <div class="support-fab">
        <div class="support-dial" id="supportDial" role="group" aria-label="Support options">
            @foreach (array_reverse($support['links']) as $link)
                <a class="support-dial-item dial-{{ $link['key'] }}" style="--d: {{ count($support['links']) - 1 - $loop->index }}" href="{{ $link['url'] }}" target="_blank" rel="noopener nofollow">
                    <span class="support-dial-label"><strong>{{ $link['label'] }}</strong>@if (filled($link['note']))<small>{{ $link['note'] }}</small>@endif</span>
                    <span class="support-dial-icon"><i class="bi {{ $link['icon'] }}"></i></span>
                </a>
            @endforeach
        </div>
        <button type="button" class="support-fab-btn" aria-expanded="false" aria-controls="supportDial">
            <span class="support-fab-icon"><i class="bi bi-heart-fill"></i></span><span class="support-fab-text">Support</span>
        </button>
    </div>
@endif
