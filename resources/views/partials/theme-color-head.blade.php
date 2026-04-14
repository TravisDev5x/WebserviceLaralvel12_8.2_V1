{{-- Persistencia data-theme antes del CSS (evita flash). localStorage colorTheme; default = sin atributo. --}}
<script>
    (() => {
        try {
            const t = localStorage.getItem('colorTheme');
            if (t && t !== 'default') document.documentElement.setAttribute('data-theme', t);
        } catch (_) {}
    })();
</script>
