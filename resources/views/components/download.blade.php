@if (session()->has('download') && session()->has('path') && session()->has('nombreArchivo'))
    <script>
        var link = document.createElement('a')
        link.href = "{{session('path')}}"
        link.download = "{{session('nombreArchivo')}}"
        link.click()
    </script>
@endif


{{-- 
Uso;    
session()->flash('download','1');
session()->flash('path', '/hola.txt');
session()->flash('nombreArchivo', 'CuentasFaltantes.txt'); --}}
