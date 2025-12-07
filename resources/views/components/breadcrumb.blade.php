@props(['items' => []])

@php
    $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
@endphp

<nav class="flex" aria-label="{{ __('Breadcrumb') }}" dir="{{ $dir }}">
    <ol role="list" class="flex items-center space-x-2 {{ $dir === 'rtl' ? 'space-x-reverse' : '' }}">
        {{-- Home --}}
        <li>
            <div>
                <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.293 2.293a1 1 0 011.414 0l7 7A1 1 0 0117 11h-1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1H9a1 1 0 00-1 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-6H3a1 1 0 01-.707-1.707l7-7z" clip-rule="evenodd" />
                    </svg>
                    <span class="sr-only">{{ __('Home') }}</span>
                </a>
            </div>
        </li>

        {{-- Breadcrumb Items --}}
        @foreach ($items as $item)
            <li>
                <div class="flex items-center">
                    <svg class="h-5 w-5 flex-shrink-0 text-slate-300 {{ $dir === 'rtl' ? 'rotate-180' : '' }}" 
                         xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                    </svg>
                    
                    @if (isset($item['url']) && !($item['active'] ?? false))
                        <a href="{{ $item['url'] }}" 
                           class="{{ $dir === 'rtl' ? 'mr-2' : 'ml-2' }} text-sm font-medium text-slate-500 hover:text-slate-700 transition">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="{{ $dir === 'rtl' ? 'mr-2' : 'ml-2' }} text-sm font-medium text-slate-700" aria-current="page">
                            {{ $item['label'] }}
                        </span>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</nav>
