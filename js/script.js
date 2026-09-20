document.addEventListener('DOMContentLoaded', () => {
    const palikaSelect = document.getElementById('palikaSelect');
    const wardSelect = document.getElementById('wardSelect');

    if (palikaSelect && wardSelect) {
        palikaSelect.addEventListener('change', function() {
            const palikaId = this.value;
            wardSelect.innerHTML = '<option value="">Loading Wards...</option>';

            if (!palikaId) {
                wardSelect.innerHTML = '<option value="">-- Choose Palika First --</option>';
                return;
            }

            fetch(`api/get_wards.php?palika_id=${palikaId}`)
                .then(response => response.json())
                .then(data => {
                    wardSelect.innerHTML = '<option value="">-- Choose Ward --</option>';
                    if (data.total_wards) {
                        for (let i = 1; i <= data.total_wards; i++) {
                            const opt = document.createElement('option');
                            opt.value = i;
                            opt.textContent = `Ward ${i}`;
                            wardSelect.appendChild(opt);
                        }
                    }
                })
                .catch(() => {
                    wardSelect.innerHTML = '<option value="">Failed to load wards</option>';
                });
        });
    }
});