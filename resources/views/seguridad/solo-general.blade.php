{{-- Aviso en los formularios que comparten todas las empresas, para el administrador de una sola empresa. --}}
@unless ($esGeneral)
    <div class="alert alert-soft-warning fs--1 py-2 mb-3">Lo comparten todas las empresas: solo el administrador general puede cambiarlo.</div>
@endunless