/**
 * Utils - Funções Auxiliares do App
 * Ondeline Tech - App do Técnico
 */

// Cache de CEP em memória e localStorage
const CEP_CACHE_KEY = 'cepCache';
const CEP_CACHE_EXPIRY = 7 * 24 * 60 * 60 * 1000; // 7 dias

const Utils = {
    // ==========================================
    // CACHE DE CEP
    // ==========================================
    
    /**
     * Obtém cache de CEP do localStorage
     */
    getCepCache() {
        try {
            const cache = JSON.parse(localStorage.getItem(CEP_CACHE_KEY) || '{}');
            // Limpa entradas expiradas
            const now = Date.now();
            Object.keys(cache).forEach(key => {
                if (cache[key].expiry < now) {
                    delete cache[key];
                }
            });
            return cache;
        } catch (e) {
            return {};
        }
    },
    
    /**
     * Salva CEP no cache
     */
    setCepCache(cep, data) {
        try {
            const cache = this.getCepCache();
            cache[cep] = {
                data: data,
                expiry: Date.now() + CEP_CACHE_EXPIRY
            };
            localStorage.setItem(CEP_CACHE_KEY, JSON.stringify(cache));
        } catch (e) {
            // Cache full, limpa tudo
            localStorage.removeItem(CEP_CACHE_KEY);
        }
    },
    
    /**
     * Busca CEP com cache (evita requests duplicados)
     */
    async fetchCEP(cep) {
        // Remove formatação
        cep = cep.replace(/\D/g, '');
        
        if (cep.length !== 8) {
            return { error: true, message: 'CEP inválido' };
        }
        
        // Verifica cache primeiro
        const cache = this.getCepCache();
        if (cache[cep]) {
            return cache[cep].data;
        }
        
        // Busca na API
        try {
            const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
            const data = await response.json();
            
            if (data.erro) {
                return { error: true, message: 'CEP não encontrado' };
            }
            
            // Normaliza resposta
            const result = {
                cep: data.cep,
                logradouro: data.logradouro || '',
                complemento: data.complemento || '',
                bairro: data.bairro || '',
                localidade: data.localidade || '',
                uf: data.uf || ''
            };
            
            // Salva no cache
            this.setCepCache(cep, result);
            
            return result;
        } catch (error) {
            return { error: true, message: 'Erro ao buscar CEP', offline: !navigator.onLine };
        }
    },

    // ==========================================
    // RASCUNHO DE FORMULÁRIOS
    // ==========================================
    
    /**
     * Salva rascunho do formulário automaticamente
     * @param {string} formId - Identificador único do formulário
     * @param {object} data - Dados do formulário
     */
    saveDraft(formId, data) {
        try {
            const drafts = JSON.parse(localStorage.getItem('formDrafts') || '{}');
            drafts[formId] = {
                data: data,
                savedAt: Date.now()
            };
            localStorage.setItem('formDrafts', JSON.stringify(drafts));
            return true;
        } catch (e) {
            console.error('Erro ao salvar rascunho:', e);
            return false;
        }
    },
    
    /**
     * Recupera rascunho do formulário
     * @param {string} formId - Identificador único do formulário
     * @param {number} maxAge - Idade máxima do rascunho em ms (default: 24h)
     */
    getDraft(formId, maxAge = 24 * 60 * 60 * 1000) {
        try {
            const drafts = JSON.parse(localStorage.getItem('formDrafts') || '{}');
            const draft = drafts[formId];
            
            if (!draft) return null;
            
            // Verifica se expirou
            if (Date.now() - draft.savedAt > maxAge) {
                this.clearDraft(formId);
                return null;
            }
            
            return draft.data;
        } catch (e) {
            return null;
        }
    },
    
    /**
     * Limpa rascunho do formulário
     * @param {string} formId - Identificador único do formulário
     */
    clearDraft(formId) {
        try {
            const drafts = JSON.parse(localStorage.getItem('formDrafts') || '{}');
            delete drafts[formId];
            localStorage.setItem('formDrafts', JSON.stringify(drafts));
        } catch (e) {
            // Ignore
        }
    },
    
    /**
     * Configura auto-save para um formulário
     * @param {string} formId - Identificador único do formulário
     * @param {function} getDataFn - Função que retorna os dados atuais do formulário
     * @param {number} interval - Intervalo de salvamento em ms (default: 5s)
     */
    setupFormAutoSave(formId, getDataFn, interval = 5000) {
        // Salva a cada X segundos
        const autoSaveInterval = setInterval(() => {
            const data = getDataFn();
            if (data && Object.keys(data).some(k => data[k])) {
                this.saveDraft(formId, data);
            }
        }, interval);
        
        // Salva ao sair da página
        window.addEventListener('beforeunload', () => {
            const data = getDataFn();
            if (data && Object.keys(data).some(k => data[k])) {
                this.saveDraft(formId, data);
            }
        });
        
        // Retorna função para parar auto-save
        return () => clearInterval(autoSaveInterval);
    },

    // ==========================================
    // VALIDAÇÕES
    // ==========================================

    /**
     * Valida CPF usando algoritmo matemático
     */
    validateCPF(cpf) {
        // Remove caracteres não numéricos
        cpf = cpf.replace(/\D/g, '');
        
        // Verifica tamanho
        if (cpf.length !== 11) {
            return { valid: false, message: 'CPF deve ter 11 dígitos' };
        }
        
        // Verifica se todos os dígitos são iguais
        if (/^(\d)\1+$/.test(cpf)) {
            return { valid: false, message: 'CPF inválido' };
        }
        
        // Calcula primeiro dígito verificador
        let sum = 0;
        for (let i = 0; i < 9; i++) {
            sum += parseInt(cpf.charAt(i)) * (10 - i);
        }
        let remainder = (sum * 10) % 11;
        let digit1 = remainder === 10 ? 0 : remainder;
        
        if (digit1 !== parseInt(cpf.charAt(9))) {
            return { valid: false, message: 'CPF inválido' };
        }
        
        // Calcula segundo dígito verificador
        sum = 0;
        for (let i = 0; i < 10; i++) {
            sum += parseInt(cpf.charAt(i)) * (11 - i);
        }
        remainder = (sum * 10) % 11;
        let digit2 = remainder === 10 ? 0 : remainder;
        
        if (digit2 !== parseInt(cpf.charAt(10))) {
            return { valid: false, message: 'CPF inválido' };
        }
        
        return { valid: true, message: '' };
    },

    /**
     * Valida telefone
     */
    validatePhone(phone) {
        phone = phone.replace(/\D/g, '');
        
        if (phone.length < 10 || phone.length > 11) {
            return { valid: false, message: 'Telefone deve ter 10 ou 11 dígitos' };
        }
        
        // Verifica DDD válido (não é 00 e começa com 2-9)
        const ddd = parseInt(phone.substring(0, 2));
        if (ddd < 11 || ddd > 99) {
            return { valid: false, message: 'DDD inválido' };
        }
        
        return { valid: true, message: '' };
    },

    /**
     * Valida CEP
     */
    validateCEP(cep) {
        cep = cep.replace(/\D/g, '');
        
        if (cep.length !== 8) {
            return { valid: false, message: 'CEP deve ter 8 dígitos' };
        }
        
        return { valid: true, message: '' };
    },

    /**
     * Sanitiza input para prevenir XSS
     */
    sanitizeInput(input) {
        if (typeof input !== 'string') return input;
        
        // Remove tags HTML e caracteres perigosos
        return input
            .replace(/</g, '<')
            .replace(/>/g, '>')
            .replace(/"/g, '"')
            .replace(/'/g, '&#x27;')
            .replace(/\//g, '&#x2F;');
    },

    /**
     * Valida email
     */
    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            return { valid: false, message: 'Email inválido' };
        }
        return { valid: true, message: '' };
    },

    // ==========================================
    // FORMATAÇÃO
    // ==========================================

    /**
     * Formata CPF
     */
    formatCPF(cpf) {
        if (!cpf) return '';
        cpf = cpf.replace(/\D/g, '');
        if (cpf.length !== 11) return cpf;
        return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    },

    /**
     * Formata telefone
     */
    formatPhone(phone) {
        if (!phone) return '';
        phone = phone.replace(/\D/g, '');
        
        if (phone.length <= 10) {
            return phone.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
        } else {
            return phone.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        }
    },

    /**
     * Formata CEP
     */
    formatCEP(cep) {
        if (!cep) return '';
        cep = cep.replace(/\D/g, '');
        if (cep.length !== 8) return cep;
        return cep.replace(/(\d{5})(\d{3})/, '$1-$2');
    },

    /**
     * Formata data
     */
    formatDate(date, format = 'pt-BR') {
        if (!date) return '';
        
        const d = new Date(date);
        if (isNaN(d.getTime())) return '';
        
        if (format === 'pt-BR') {
            return d.toLocaleDateString('pt-BR');
        } else if (format === 'ISO') {
            return d.toISOString().split('T')[0];
        } else if (format === 'datetime') {
            return d.toLocaleString('pt-BR');
        }
        
        return d.toLocaleDateString('pt-BR');
    },

    /**
     * Formata moeda
     */
    formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    },

    // ==========================================
    // IMAGENS
    // ==========================================

    /**
     * Comprime imagem usando Canvas
     * @param {File} file - Arquivo de imagem
     * @param {Object} options - Opções de compressão
     * @returns {Promise<string>} - Base64 da imagem comprimida
     */
    async compressImage(file, options = {}) {
        const {
            maxWidth = 1200,
            maxHeight = 1200,
            quality = 0.7,
            outputFormat = 'image/jpeg'
        } = options;

        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            
            reader.onload = (e) => {
                const img = new Image();
                
                img.onload = () => {
                    // Calcula dimensões mantendo proporção
                    let width = img.width;
                    let height = img.height;
                    
                    if (width > maxWidth) {
                        height = (height * maxWidth) / width;
                        width = maxWidth;
                    }
                    
                    if (height > maxHeight) {
                        width = (width * maxHeight) / height;
                        height = maxHeight;
                    }
                    
                    // Cria canvas e desenha imagem
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);
                    
                    // Converte para base64
                    const compressedDataUrl = canvas.toDataURL(outputFormat, quality);
                    resolve(compressedDataUrl);
                };
                
                img.onerror = reject;
                img.src = e.target.result;
            };
            
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    },

    /**
     * Comprime imagem com marca d'água (timestamp + GPS)
     * @param {File} file - Arquivo de imagem
     * @param {Object} options - Opções de compressão e marca d'água
     * @returns {Promise<string>} - Base64 da imagem com marca d'água
     */
    async compressImageWithWatermark(file, options = {}) {
        const {
            maxWidth = 1200,
            maxHeight = 1200,
            quality = 0.7,
            outputFormat = 'image/jpeg',
            showTimestamp = true,
            showLocation = true,
            showTechnician = true,
            technicianName = null,
            latitude = null,
            longitude = null,
            accuracy = null
        } = options;

        // Tenta obter localização se não fornecida
        let coords = { latitude, longitude, accuracy };
        if (showLocation && !latitude && navigator.geolocation) {
            try {
                const position = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 60000
                    });
                });
                coords = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy
                };
            } catch (e) {
                console.warn('Não foi possível obter localização:', e.message);
            }
        }

        // Obtém nome do técnico se não fornecido
        let tech = technicianName;
        if (showTechnician && !tech) {
            try {
                const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
                tech = userData.full_name || userData.name || userData.username || 'Técnico';
            } catch (e) {
                tech = 'Técnico';
            }
        }

        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            
            reader.onload = (e) => {
                const img = new Image();
                
                img.onload = () => {
                    // Calcula dimensões mantendo proporção
                    let width = img.width;
                    let height = img.height;
                    
                    if (width > maxWidth) {
                        height = (height * maxWidth) / width;
                        width = maxWidth;
                    }
                    
                    if (height > maxHeight) {
                        width = (width * maxHeight) / height;
                        height = maxHeight;
                    }
                    
                    // Cria canvas e desenha imagem
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);
                    
                    // === MARCA D'ÁGUA ===
                    const padding = 12;
                    const lineHeight = 18;
                    const lines = [];
                    
                    // Data e hora
                    if (showTimestamp) {
                        const now = new Date();
                        const dateStr = now.toLocaleDateString('pt-BR');
                        const timeStr = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                        lines.push('📅 ' + dateStr + ' ' + timeStr);
                    }
                    
                    // Localização GPS
                    if (showLocation && coords.latitude && coords.longitude) {
                        const lat = coords.latitude.toFixed(6);
                        const lon = coords.longitude.toFixed(6);
                        const acc = coords.accuracy ? ' (±' + Math.round(coords.accuracy) + 'm)' : '';
                        lines.push('📍 ' + lat + ', ' + lon + acc);
                    }
                    
                    // Nome do técnico
                    if (showTechnician && tech) {
                        lines.push('👤 ' + tech);
                    }
                    
                    if (lines.length > 0) {
                        // Calcula altura do bloco de texto
                        const blockHeight = (lines.length * lineHeight) + (padding * 2);
                        const blockWidth = width;
                        
                        // Fundo semi-transparente no rodapé
                        ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';
                        ctx.fillRect(0, height - blockHeight, blockWidth, blockHeight);
                        
                        // Texto branco
                        ctx.fillStyle = '#FFFFFF';
                        ctx.font = '500 14px Inter, -apple-system, BlinkMacSystemFont, sans-serif';
                        ctx.textAlign = 'left';
                        ctx.textBaseline = 'top';
                        
                        // Desenha cada linha
                        lines.forEach((line, index) => {
                            const y = height - blockHeight + padding + (index * lineHeight);
                            ctx.fillText(line, padding, y);
                        });
                        
                        // Adiciona marca "Ondeline Tech" no canto direito
                        ctx.font = '600 11px Inter, -apple-system, BlinkMacSystemFont, sans-serif';
                        ctx.textAlign = 'right';
                        ctx.fillStyle = 'rgba(255, 255, 255, 0.7)';
                        ctx.fillText('Ondeline Tech', width - padding, height - padding - 4);
                    }
                    
                    // Converte para base64
                    const compressedDataUrl = canvas.toDataURL(outputFormat, quality);
                    resolve(compressedDataUrl);
                };
                
                img.onerror = reject;
                img.src = e.target.result;
            };
            
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    },

    /**
     * Obtém dimensões da imagem
     */
    getImageDimensions(file) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => {
                resolve({ width: img.width, height: img.height });
            };
            img.onerror = reject;
            img.src = URL.createObjectURL(file);
        });
    },

    /**
     * Converte File para Base64
     */
    fileToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    },

    // ==========================================
    // STORAGE
    // ==========================================

    /**
     * Salva dados offline (delega para sistema unificado em feedback.js)
     */
    saveOffline(actionType, data) {
        if (typeof saveOffline === 'function') {
            return saveOffline(actionType, data);
        }
        return false;
    },

    /**
     * Obtém fila de sincronização offline (delega para sistema unificado em feedback.js)
     */
    getOfflineQueue() {
        if (typeof getOfflineQueue === 'function') {
            return getOfflineQueue();
        }
        return [];
    },

    /**
     * Limpa fila de sincronização offline
     */
    clearOfflineQueue() {
        localStorage.removeItem('offlineQueue');
    },

    // ==========================================
    // LAZY LOADING
    // ==========================================

    /**
     * Implementa lazy loading de imagens
     */
    initLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        const src = img.dataset.src;
                        
                        if (src) {
                            img.src = src;
                            img.classList.add('loaded');
                            img.removeAttribute('data-src');
                            observer.unobserve(img);
                        }
                    }
                });
            }, {
                rootMargin: '50px',
                threshold: 0.01
            });

            // Observa todas as imagens com data-src
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        } else {
            // Fallback para navegadores antigos
            document.querySelectorAll('img[data-src]').forEach(img => {
                img.src = img.dataset.src;
                img.classList.add('loaded');
            });
        }
    },

    // ==========================================
    // GEOFUNÇÕES
    // ==========================================

    /**
     * Calcula distância entre dois pontos (em km)
     * Fórmula de Haversine
     */
    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Raio da Terra em km
        const dLat = this.toRad(lat2 - lat1);
        const dLon = this.toRad(lon2 - lon1);
        
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(this.toRad(lat1)) * Math.cos(this.toRad(lat2)) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    },

    /**
     * Converte graus para radianos
     */
    toRad(degrees) {
        return degrees * (Math.PI / 180);
    },

    /**
     * Abre URL do mapa (Google Maps)
     */
    openMapUrl(lat, lon, label = '') {
        const url = `https://www.google.com/maps/search/?api=1&query=${lat},${lon}`;
        window.open(url, '_blank');
    },

    /**
     * Abre URL de rota (Google Maps)
     */
    openRouteUrl(fromLat, fromLon, toLat, toLon) {
        const url = `https://www.google.com/maps/dir/${fromLat},${fromLon}/${toLat},${toLon}`;
        window.open(url, '_blank');
    },

    // ==========================================
    // UI/UX
    // ==========================================

    /**
     * Adiciona classe de animação
     */
    animateElement(element, animationClass, duration = 300) {
        element.classList.add(animationClass);
        
        return new Promise(resolve => {
            setTimeout(() => {
                element.classList.remove(animationClass);
                resolve();
            }, duration);
        });
    },

    /**
     * Copia texto para clipboard
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (error) {
            // Fallback para navegadores antigos
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            
            try {
                document.execCommand('copy');
                document.body.removeChild(textarea);
                return true;
            } catch (error) {
                document.body.removeChild(textarea);
                return false;
            }
        }
    },

    /**
     * Gera cor baseada em string
     */
    stringToColor(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash);
        }
        
        const hue = Math.abs(hash % 360);
        return `hsl(${hue}, 70%, 50%)`;
    },

    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Throttle function
     */
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    // ==========================================
    // PULL TO REFRESH
    // ==========================================

    /**
     * Implementa pull-to-refresh
     */
    initPullToRefresh(element, callback) {
        let startY = 0;
        let currentY = 0;
        let isDragging = false;
        let isPulling = false;
        
        const threshold = 80; // Distância mínima para acionar
        const maxPull = 150; // Distância máxima
        
        const handleTouchStart = (e) => {
            if (element.scrollTop === 0) {
                startY = e.touches[0].clientY;
                isDragging = true;
            }
        };
        
        const handleTouchMove = (e) => {
            if (!isDragging) return;
            
            currentY = e.touches[0].clientY;
            const diff = currentY - startY;
            
            if (diff > 0 && diff < maxPull) {
                isPulling = true;
                element.style.transform = `translateY(${diff * 0.5}px)`;
                
                // Mostra indicador
                const indicator = document.getElementById('pull-indicator') || createPullIndicator();
                indicator.style.opacity = Math.min(diff / threshold, 1);
                indicator.querySelector('.arrow').style.transform = diff > threshold ? 'rotate(180deg)' : 'rotate(0deg)';
            }
        };
        
        const handleTouchEnd = () => {
            if (isPulling && currentY - startY > threshold) {
                // Aciona refresh
                element.style.transition = 'transform 0.3s';
                element.style.transform = 'translateY(50px)';
                
                callback().then(() => {
                    element.style.transform = 'translateY(0)';
                    setTimeout(() => {
                        element.style.transition = '';
                    }, 300);
                });
            } else {
                // Cancela
                element.style.transform = 'translateY(0)';
            }
            
            isDragging = false;
            isPulling = false;
            
            // Esconde indicador
            const indicator = document.getElementById('pull-indicator');
            if (indicator) {
                indicator.style.opacity = '0';
            }
        };
        
        // Cria indicador de pull
        const createPullIndicator = () => {
            const indicator = document.createElement('div');
            indicator.id = 'pull-indicator';
            indicator.style.cssText = `
                position: fixed;
                top: -60px;
                left: 50%;
                transform: translateX(-50%);
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 12px 24px;
                background: #135bec;
                color: white;
                border-radius: 0 0 16px 16px;
                font-weight: 600;
                opacity: 0;
                transition: opacity 0.2s;
                z-index: 1000;
            `;
            indicator.innerHTML = `
                <span class="arrow" style="transition: transform 0.3s;">↓</span>
                <span>Puxe para atualizar</span>
            `;
            document.body.appendChild(indicator);
            return indicator;
        };
        
        element.addEventListener('touchstart', handleTouchStart);
        element.addEventListener('touchmove', handleTouchMove);
        element.addEventListener('touchend', handleTouchEnd);
        
        // Cleanup
        return () => {
            element.removeEventListener('touchstart', handleTouchStart);
            element.removeEventListener('touchmove', handleTouchMove);
            element.removeEventListener('touchend', handleTouchEnd);
        };
    },

    // ==========================================
    // SKELETON LOADING
    // ==========================================

    /**
     * Cria skeleton loading
     */
    createSkeleton(lines = 3) {
        const skeleton = document.createElement('div');
        skeleton.className = 'skeleton-container';
        
        for (let i = 0; i < lines; i++) {
            const line = document.createElement('div');
            line.className = 'skeleton-line';
            line.style.cssText = `
                height: 16px;
                background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                background-size: 200% 100%;
                animation: skeleton-loading 1.5s infinite;
                border-radius: 4px;
                margin-bottom: 12px;
            `;
            
            // Varia a largura
            const widths = ['100%', '80%', '90%', '70%', '85%'];
            line.style.width = widths[i % widths.length];
            
            skeleton.appendChild(line);
        }
        
        // Adiciona keyframes se não existirem
        if (!document.getElementById('skeleton-keyframes')) {
            const style = document.createElement('style');
            style.id = 'skeleton-keyframes';
            style.textContent = `
                @keyframes skeleton-loading {
                    0% { background-position: 200% 0; }
                    100% { background-position: -200% 0; }
                }
            `;
            document.head.appendChild(style);
        }
        
        return skeleton;
    },

    /**
     * Mostra skeleton em um container
     */
    showSkeleton(container, lines = 3) {
        // Salva conteúdo atual
        const originalContent = container.innerHTML;
        container.dataset.originalContent = originalContent;
        
        // Limpa e mostra skeleton
        container.innerHTML = '';
        container.appendChild(this.createSkeleton(lines));
    },

    /**
     * Esconde skeleton e restaura conteúdo
     */
    hideSkeleton(container, newContent) {
        const originalContent = container.dataset.originalContent;
        
        if (newContent) {
            container.innerHTML = newContent;
        } else if (originalContent) {
            container.innerHTML = originalContent;
        } else {
            container.innerHTML = '';
        }
        
        delete container.dataset.originalContent;
    },

    // ==========================================
    // UTILIDADES DIVERSAS
    // ==========================================

    /**
     * Gera ID único
     */
    generateId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    },

    /**
     * Dorme por X ms
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    },

    /**
     * Obtém parâmetro da URL
     */
    getUrlParam(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    },

    /**
     * Define parâmetro na URL
     */
    setUrlParam(name, value) {
        const url = new URL(window.location);
        url.searchParams.set(name, value);
        window.history.replaceState({}, '', url);
    },

    /**
     * Remove parâmetro da URL
     */
    removeUrlParam(name) {
        const url = new URL(window.location);
        url.searchParams.delete(name);
        window.history.replaceState({}, '', url);
    },

    /**
     * Verifica se é dispositivo móvel
     */
    isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    },

    /**
     * Verifica se é iOS
     */
    isIOS() {
        return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    },

    /**
     * Verifica se é Android
     */
    isAndroid() {
        return /Android/.test(navigator.userAgent);
    },

    /**
     * Obtém info do dispositivo
     */
    getDeviceInfo() {
        return {
            isMobile: this.isMobile(),
            isIOS: this.isIOS(),
            isAndroid: this.isAndroid(),
            userAgent: navigator.userAgent,
            language: navigator.language,
            platform: navigator.platform,
            screenWidth: window.screen.width,
            screenHeight: window.screen.height,
            viewportWidth: window.innerWidth,
            viewportHeight: window.innerHeight
        };
    }
};

// Exporta para uso global
window.Utils = Utils;