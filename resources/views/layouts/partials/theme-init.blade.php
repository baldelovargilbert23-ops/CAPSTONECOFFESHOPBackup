<script>
    (function () {
        try {
            var s = localStorage.getItem('theme');
            var dark = s ? s === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        } catch (e) {}
    })();
</script>