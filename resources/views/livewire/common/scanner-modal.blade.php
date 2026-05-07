<!-- Scanner Modal Shared Component -->
<div class="modal modal-blur fade" id="scannerModal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg overflow-hidden">
            <div class="modal-status-top bg-primary"></div>
            <div class="modal-header">
                <h5 class="modal-title">
                    <span class="material-symbols-outlined me-2 text-primary">qr_code_scanner</span>
                    Scan Barcode / QR
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="if(typeof closeScanner === 'function') closeScanner()"></button>
            </div>
            <div class="modal-body p-0 position-relative border-top border-bottom" style="overflow: hidden; background: #1d273b;">
                <!-- Scanner Viewport -->
                <div id="reader" style="width: 100%; min-height: 350px;"></div>
                
                <!-- Custom Overlay -->
                <div class="scanner-overlay">
                    <div class="scanner-laser"></div>
                    <div class="scanner-frame"></div>
                </div>

                <!-- Last Scanned Info Overlay (Optional, controlled by module JS) -->
                <div id="scannerInfoOverlay" class="position-absolute bottom-0 start-0 end-0 p-3 text-center d-none"
                     style="background: linear-gradient(to top, rgba(0,0,0,0.8), transparent); z-index: 10; color: white;">
                    <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                        <span class="material-symbols-outlined text-success">check_circle</span>
                        <span class="fw-bold">Produk Ditemukan!</span>
                    </div>
                    <div id="lastScannedNameDisplay" class="small opacity-75"></div>
                </div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-between py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="if(typeof toggleCamera === 'function') toggleCamera()">
                    <span class="material-symbols-outlined me-2">cached</span> Ganti Kamera
                </button>
                <button type="button" class="btn btn-primary px-4 shadow-sm" data-bs-dismiss="modal" @click="if(typeof closeScanner === 'function') closeScanner()">
                    <span class="material-symbols-outlined me-2">done_all</span> Selesai
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Shared Scanner Styles */
    .scanner-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        pointer-events: none;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 5;
        overflow: hidden;
    }
    .scanner-frame {
        width: 260px;
        height: 260px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 24px;
        box-shadow: 0 0 0 2000px rgba(0, 0, 0, 0.5);
        position: relative;
    }
    .scanner-laser {
        position: absolute;
        width: 240px;
        height: 2px;
        background: #2fb344;
        box-shadow: 0 0 15px #2fb344;
        animation: scanner-scan 2s linear infinite;
        z-index: 6;
    }
    @keyframes scanner-scan {
        0% { top: 20%; }
        50% { top: 80%; }
        100% { top: 20%; }
    }
    .scanner-success-flash {
        animation: scanner-flash 0.5s ease-out;
    }
    @keyframes scanner-flash {
        0% { background: rgba(47, 179, 68, 0); }
        50% { background: rgba(47, 179, 68, 0.3); }
        100% { background: rgba(47, 179, 68, 0); }
    }
</style>
