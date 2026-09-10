document.addEventListener("DOMContentLoaded", function () {
    
    // 1. Otomatis Menghilangkan Alert Notifikasi setelah 3 Detik
    const alerts = document.querySelectorAll(".alert");
    alerts.forEach(function (alert) {
        setTimeout(function () {
            // Cek apakah instance bootstrap tersedia agar tidak error
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                let bsAlert = bootstrap.Alert.getInstance(alert) || new bootstrap.Alert(alert);
                bsAlert.close();
            } else {
                alert.style.display = 'none'; // Fallback jika Bootstrap JS tidak terload
            }
        }, 3000);
    });

    // 2. Konfirmasi Hapus Data Interaktif untuk Semua Tombol 'Hapus'
    const deleteButtons = document.querySelectorAll(".btn-delete, a[href*='hapus.php']");
    deleteButtons.forEach(function (button) {
        button.addEventListener("click", function (e) {
            if (!confirm("Apakah Anda yakin ingin menghapus data ini? Data yang dihapus tidak dapat dikembalikan!")) {
                e.preventDefault();
            }
        });
    });

    // 3. Highlight Otomatis Baris Tabel Saat Di-hover
    const tableRows = document.querySelectorAll(".table-hover tbody tr");
    tableRows.forEach(function (row) {
        row.addEventListener("mouseenter", function () {
            this.style.cursor = "pointer";
        });
    });

    // 4. Format Input Angka / Biaya Secara Real-time
    const rupiahInputs = document.querySelectorAll(".input-rupiah");
    rupiahInputs.forEach(function (input) {
        input.addEventListener("input", function (e) {
            let value = this.value.replace(/[^,\d]/g, "").toString();
            let split = value.split(",");
            let sisa = split[0].length % 3;
            let rupiah = split[0].substr(0, sisa);
            let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                let separator = sisa ? "." : "";
                rupiah += separator + ribuan.join(".");
            }

            rupiah = split[1] != undefined ? rupiah + "," + split[1] : rupiah;
            this.value = rupiah;
        });
    });

});