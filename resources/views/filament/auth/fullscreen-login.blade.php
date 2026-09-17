<div class="bp-fs">
    <div class="bp-fs-form-col">
        <div class="bp-fs-form-inner">
            <img src="{{ asset('images/logo-bp.svg') }}" alt="PT Borneo Prima" class="bp-fs-logo" width="120" height="80" fetchpriority="high" decoding="async" />

            <span class="bp-auth-badge">
                <span class="bp-auth-badge-dot"></span>
                PT Borneo Prima &bull; Item Creation
            </span>

            <h1 class="bp-fs-heading">{{ $this->getHeading() }}</h1>
            <p class="bp-fs-subheading">{{ $this->getSubheading() }}</p>

            {{ $this->content }}

            <p class="bp-internal-note">
                <x-heroicon-m-lock-closed class="bp-internal-note-icon" />
                Portal internal — khusus karyawan PT Borneo Prima.
            </p>
        </div>
    </div>

    @include('filament.auth.fs-side')

    <x-filament-actions::modals />
</div>

{{-- Polish login tanpa mengubah layout: hint Caps Lock khusus kolom password. --}}
<script>
    (() => {
        const root = document.querySelector('.bp-fs');
        if (!root) return;
        const password = root.querySelector('input[type="password"]');
        if (!password) return;

        const hint = document.createElement('p');
        hint.className = 'bp-caps-hint';
        hint.setAttribute('role', 'status');
        hint.hidden = true;
        hint.textContent = 'Caps Lock aktif — periksa huruf besar/kecil.';
        password.closest('.fi-fo-field-wrp, .fi-field, div')?.appendChild(hint);

        const update = (event) => {
            const on = event?.getModifierState ? event.getModifierState('CapsLock') : false;
            hint.hidden = !on;
        };
        password.addEventListener('keyup', update);
        password.addEventListener('keydown', update);
        password.addEventListener('blur', () => { hint.hidden = true; });
    })();
</script>
