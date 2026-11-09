@props(['kind' => 'chicken', 'artId' => 'dish'])
<svg viewBox="0 0 500 500" class="dish-art" role="img" aria-label="Illustration of a home-cooked meal with rice and fresh vegetables">
    <defs>
        <radialGradient id="plate-{{ $artId }}"><stop offset="70%" stop-color="#fffefa"/><stop offset="93%" stop-color="#e8e7dc"/><stop offset="100%" stop-color="#fffff8"/></radialGradient>
        <pattern id="rice-{{ $artId }}" width="22" height="20" patternUnits="userSpaceOnUse" patternTransform="rotate(25)"><rect width="22" height="20" fill="#f5edd7"/><ellipse cx="6" cy="5" rx="3" ry="7" fill="#fffdf1"/><ellipse cx="17" cy="16" rx="3" ry="6" fill="#e7dcc1"/></pattern>
        <linearGradient id="sauce-{{ $artId }}" x2="1" y2="1"><stop stop-color="#ba7437"/><stop offset="1" stop-color="#713e24"/></linearGradient>
    </defs>
    <ellipse cx="256" cy="272" rx="211" ry="209" fill="#163c25" opacity=".11"/>
    <circle cx="250" cy="250" r="213" fill="url(#plate-{{ $artId }})"/><circle cx="250" cy="250" r="188" fill="none" stroke="#d4d8c6" stroke-width="2"/><circle cx="250" cy="250" r="175" fill="#ede9d9"/>
    <path d="M255 80C354 78 424 161 423 257s-75 171-169 166c-35-36-50-96-42-149 7-69 50-115 43-194Z" fill="url(#rice-{{ $artId }})"/>
    @if($kind === 'egg')
        <path d="M91 180c15-44 53-72 97-62 39-29 72 4 73 36 59 29 48 82 10 95-4 54-50 68-85 47-40 24-70-13-62-43-40-7-57-47-33-73Z" fill="#fffaf0" stroke="#d8ba69" stroke-width="5"/><circle cx="191" cy="204" r="46" fill="#efa52b"/><circle cx="181" cy="194" r="30" fill="#ffc543"/>
    @elseif($kind === 'rice')
        <ellipse cx="180" cy="213" rx="96" ry="124" fill="#d8b66a"/>
        @foreach(range(0, 30) as $grain)
            <ellipse cx="{{ 110 + ($grain * 37) % 140 }}" cy="{{ 120 + ($grain * 53) % 188 }}" rx="5" ry="10" fill="{{ $grain % 3 === 0 ? '#a86037' : '#efd494' }}" transform="rotate({{ $grain * 17 }} {{ 110 + ($grain * 37) % 140 }} {{ 120 + ($grain * 53) % 188 }})"/>
        @endforeach
    @else
        <path d="M88 179c19-67 91-101 145-62 41 29 54 117 15 173-37 49-116 66-148 7-21-40-30-72-12-118Z" fill="#8c582f"/>
        @foreach([[135,157,-20],[204,157,25],[128,224,25],[207,233,-15],[160,288,15]] as $chunk)
            <g transform="translate({{ $chunk[0] }} {{ $chunk[1] }}) rotate({{ $chunk[2] }})"><rect x="-33" y="-30" width="66" height="60" rx="19" fill="url(#sauce-{{ $artId }})" stroke="#663a20" stroke-width="3"/><path d="M-21-16Q0-28 22-12M-19-6 10-15" fill="none" stroke="#d99b58" stroke-width="5" stroke-linecap="round"/><circle cx="18" cy="17" r="3" fill="#352b1d"/></g>
        @endforeach
    @endif
    <g fill="#467345" stroke="#315b37" stroke-width="2"><path d="M108 332q-33-52 7-84 45 24 15 78 12-59 53-44 17 44-44 63 68-5 61 34-45 26-75-23-8 54-42 32-19-33 25-56Z"/><path d="M318 112q-30-36-4-58 36 8 19 47 19-42 45-20 4 35-44 33 42 13 19 34-33 8-35-36Z"/></g>
    <g fill="#df6850" stroke="#bf4f38" stroke-width="3"><circle cx="253" cy="365" r="32"/><circle cx="310" cy="366" r="28"/></g>
    <g fill="none" stroke="#f6a780" stroke-width="3"><path d="m253 340 0 50m-22-36 43 24m-44-4 43-16M310 344v43m-19-31 37 23m-36-4 35-15"/></g>
    <circle cx="350" cy="311" r="26" fill="#7e994b" stroke="#d8dba0" stroke-width="5"/><path d="m350 288 0 46m-23-23h46m-39-16 32 32m-32 0 32-32" stroke="#e7e7b2" stroke-width="2"/>
    @foreach([[150,125],[183,250],[224,300],[287,156],[320,262],[258,213],[317,327],[177,343]] as $herb)
        <path d="M{{ $herb[0] }} {{ $herb[1] }}l9-5 4 8-9 5Z" fill="#507447"/>
    @endforeach
</svg>
