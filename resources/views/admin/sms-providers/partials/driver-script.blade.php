<script>
    function toggleDriverConfig() {
        var driver = document.getElementById('driver-select').value;
        document.querySelectorAll('.driver-config').forEach(function (el) {
            el.style.display = 'none';
            el.querySelectorAll('input, select, textarea').forEach(function (input) {
                input.disabled = true;
            });
        });
        var target = document.getElementById('config-' + driver);
        if (target) {
            target.style.display = 'block';
            target.querySelectorAll('input, select, textarea').forEach(function (input) {
                input.disabled = false;
            });
        }
    }
    document.getElementById('driver-select').addEventListener('change', toggleDriverConfig);
    toggleDriverConfig();
</script>
