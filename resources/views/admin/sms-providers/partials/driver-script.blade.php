<script>
    function toggleDriverConfig() {
        var driver = document.getElementById('driver-select').value;
        document.querySelectorAll('.driver-config').forEach(function (el) { el.style.display = 'none'; });
        var target = document.getElementById('config-' + driver);
        if (target) target.style.display = 'block';
    }
    document.getElementById('driver-select').addEventListener('change', toggleDriverConfig);
    toggleDriverConfig();
</script>
