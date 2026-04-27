document.addEventListener('DOMContentLoaded', function() {
    // Bottom Sheet Logic
    const sheetOverlay = document.querySelector('.sheet-overlay');
    const bottomSheets = document.querySelectorAll('.bottom-sheet');
    
    window.openSheet = function(id) {
        const sheet = document.getElementById(id);
        if (sheet) {
            sheet.classList.add('open');
            if (sheetOverlay) sheetOverlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
    };
    
    window.closeSheet = function() {
        bottomSheets.forEach(sheet => sheet.classList.remove('open'));
        if (sheetOverlay) sheetOverlay.classList.remove('open');
        document.body.style.overflow = 'auto';
    };
    
    if (sheetOverlay) {
        sheetOverlay.addEventListener('click', closeSheet);
    }
    
    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert-auto-dismiss');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    });

    // Form validation and animations
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                btn.disabled = true;
            }
        });
    });

    // Geolocation Helper (for Presence)
    window.getLocation = function(callback) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    callback({
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        error: null
                    });
                },
                (error) => {
                    let msg = "Gagal mendapatkan lokasi.";
                    switch(error.code) {
                        case error.PERMISSION_DENIED: msg = "Izin lokasi ditolak."; break;
                        case error.POSITION_UNAVAILABLE: msg = "Lokasi tidak tersedia."; break;
                        case error.TIMEOUT: msg = "Waktu permintaan lokasi habis."; break;
                    }
                    callback({ lat: null, lng: null, error: msg });
                }
            );
        } else {
            callback({ lat: null, lng: null, error: "Geolocation tidak didukung browser." });
        }
    };
});
