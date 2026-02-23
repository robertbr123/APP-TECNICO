/**
 * Scanner QR/Barcode via Câmera
 * Ondeline Tech - App do Técnico
 *
 * Usa a biblioteca html5-qrcode para leitura de QR codes e códigos de barras
 */

var BarcodeScanner = {
    _scanner: null,
    _modal: null,
    _onResult: null,
    _libraryLoaded: false,

    /**
     * Carrega a biblioteca html5-qrcode dinamicamente
     */
    _loadLibrary: function() {
        return new Promise(function(resolve, reject) {
            if (BarcodeScanner._libraryLoaded || typeof Html5Qrcode !== 'undefined') {
                BarcodeScanner._libraryLoaded = true;
                resolve();
                return;
            }

            var script = document.createElement('script');
            script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
            script.onload = function() {
                BarcodeScanner._libraryLoaded = true;
                resolve();
            };
            script.onerror = function() {
                reject(new Error('Falha ao carregar biblioteca do scanner'));
            };
            document.head.appendChild(script);
        });
    },

    /**
     * Abre o scanner
     * @param {function} onResult - Callback com o texto lido (string)
     * @param {object} options - { title: string }
     */
    open: async function(onResult, options) {
        options = options || {};
        this._onResult = onResult;

        try {
            await this._loadLibrary();
        } catch (e) {
            if (typeof showError === 'function') showError(e.message);
            return;
        }

        // Remove existing modal
        if (this._modal) this._modal.remove();

        // Create modal
        var modal = document.createElement('div');
        modal.id = 'scanner-modal';
        modal.className = 'fixed inset-0 z-[9999] bg-black flex flex-col';
        modal.innerHTML =
            '<div class="flex items-center justify-between px-4 py-3 bg-black/80">' +
                '<h3 class="text-white font-bold text-sm">' + (options.title || 'Escanear Código') + '</h3>' +
                '<button id="scanner-close" class="size-10 flex items-center justify-center text-white rounded-full bg-white/10">' +
                    '<span class="material-symbols-outlined">close</span>' +
                '</button>' +
            '</div>' +
            '<div class="flex-1 relative">' +
                '<div id="scanner-reader" class="w-full h-full"></div>' +
            '</div>' +
            '<div class="px-4 py-4 bg-black/80 safe-bottom">' +
                '<p class="text-white/60 text-xs text-center">Aponte a câmera para o código QR ou código de barras</p>' +
                '<div class="flex gap-3 mt-3">' +
                    '<button id="scanner-switch-camera" class="flex-1 py-2.5 bg-white/10 text-white rounded-xl text-sm font-medium flex items-center justify-center gap-2">' +
                        '<span class="material-symbols-outlined text-lg">flip_camera_ios</span>Trocar Câmera' +
                    '</button>' +
                    '<button id="scanner-manual" class="flex-1 py-2.5 bg-white/10 text-white rounded-xl text-sm font-medium flex items-center justify-center gap-2">' +
                        '<span class="material-symbols-outlined text-lg">keyboard</span>Digitar' +
                    '</button>' +
                '</div>' +
            '</div>';

        document.body.appendChild(modal);
        this._modal = modal;

        // Close button
        document.getElementById('scanner-close').addEventListener('click', function() {
            BarcodeScanner.close();
        });

        // Manual input
        document.getElementById('scanner-manual').addEventListener('click', function() {
            BarcodeScanner.close();
            var value = prompt('Digite o código manualmente:');
            if (value && BarcodeScanner._onResult) {
                BarcodeScanner._onResult(value.trim());
            }
        });

        // Start scanner
        this._startScanning();

        // Camera switch
        var usingBack = true;
        document.getElementById('scanner-switch-camera').addEventListener('click', function() {
            usingBack = !usingBack;
            BarcodeScanner._stopScanning();
            BarcodeScanner._startScanning(usingBack ? 'environment' : 'user');
        });
    },

    _startScanning: function(facingMode) {
        facingMode = facingMode || 'environment';

        try {
            this._scanner = new Html5Qrcode('scanner-reader');

            this._scanner.start(
                { facingMode: facingMode },
                {
                    fps: 10,
                    qrbox: function(viewfinderWidth, viewfinderHeight) {
                        var size = Math.min(viewfinderWidth, viewfinderHeight) * 0.7;
                        return { width: size, height: size * 0.6 };
                    },
                    aspectRatio: 1.0,
                    formatsToSupport: [
                        0,  // QR_CODE
                        4,  // CODE_128
                        2,  // CODE_39
                        11, // EAN_13
                        12, // EAN_8
                        14  // UPC_A
                    ]
                },
                function(decodedText) {
                    // Success
                    if (typeof UIEnhancements !== 'undefined') {
                        UIEnhancements.hapticMedium();
                    }
                    BarcodeScanner.close();
                    if (BarcodeScanner._onResult) {
                        BarcodeScanner._onResult(decodedText);
                    }
                },
                function() {
                    // Scanning... (no match yet)
                }
            ).catch(function(err) {
                console.error('Scanner error:', err);
                if (typeof showError === 'function') {
                    showError('Erro ao acessar câmera. Verifique as permissões.');
                }
            });
        } catch (e) {
            console.error('Scanner init error:', e);
        }
    },

    _stopScanning: function() {
        if (this._scanner) {
            try {
                this._scanner.stop();
            } catch (e) { /* already stopped */ }
        }
    },

    close: function() {
        this._stopScanning();
        if (this._modal) {
            this._modal.remove();
            this._modal = null;
        }
        this._scanner = null;
    }
};

window.BarcodeScanner = BarcodeScanner;
