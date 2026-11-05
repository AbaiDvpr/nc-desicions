@if (auth()->check() && request()->cookie('cabinetHack'))
    <div class="admin-bar">
        <div class="admin-bar__text">admin mode: cabinetHack</div>
    </div>
@endif
