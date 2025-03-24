@props(['value', 'mensaje'])

<button id='{{$value}}' class="bg-transparent border-0" data-bs-toggle="modal" data-bs-target="#confirmModal{{$value}}" data-toggle="tooltip" data-bs-placement="top" title='{{ $mensaje }}'>
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
        class="bi bi-toggle-on" viewBox="0 0 16 16">
        <path d="M5 3a5 5 0 0 0 0 10h6a5 5 0 0 0 0-10H5zm6 9a4 4 0 1 1 0-8 4 4 0 0 1 0 8z" />
    </svg>
</button>
