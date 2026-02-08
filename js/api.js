/**
 * API Service - Comunicação com o Backend
 * Ondeline Tech - App do Técnico
 */

const API = {
    // URL base da API - ALTERE PARA O SEU DOMÍNIO
    baseUrl: '/api',  // Em produção: 'https://seudominio.com/api'

    /**
     * Obtém o token armazenado
     */
    getToken() {
        return localStorage.getItem('auth_token');
    },

    /**
     * Define o token
     */
    setToken(token) {
        localStorage.setItem('auth_token', token);
    },

    /**
     * Remove o token (logout)
     */
    removeToken() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
    },

    /**
     * Verifica se está autenticado
     */
    isAuthenticated() {
        const token = this.getToken();
        if (!token) return false;

        // Verifica se o token expirou
        try {
            const payload = JSON.parse(atob(token.split('.')[1]));
            return payload.exp > Date.now() / 1000;
        } catch {
            return false;
        }
    },

    /**
     * Obtém dados do usuário logado
     */
    getUser() {
        const userData = localStorage.getItem('user_data');
        return userData ? JSON.parse(userData) : null;
    },

    /**
     * Salva dados do usuário
     */
    setUser(user) {
        localStorage.setItem('user_data', JSON.stringify(user));
    },

    /**
     * Headers padrão para requisições
     */
    getHeaders() {
        const headers = {
            'Content-Type': 'application/json'
        };
        const token = this.getToken();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        return headers;
    },

    /**
     * Requisição genérica
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}/${endpoint}`;
        
        const config = {
            ...options,
            headers: {
                ...this.getHeaders(),
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                // Se token expirou, redireciona para login
                if (response.status === 401) {
                    this.removeToken();
                    window.location.href = 'login.html';
                    return;
                }
                throw new Error(data.message || 'Erro na requisição');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    /**
     * GET request
     */
    async get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request(url, { method: 'GET' });
    },

    /**
     * POST request
     */
    async post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    /**
     * PUT request
     */
    async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    /**
     * DELETE request
     */
    async delete(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request(url, { method: 'DELETE' });
    },

    // ==========================================
    // MÉTODOS ESPECÍFICOS
    // ==========================================

    /**
     * Login
     */
    async login(username, password) {
        const response = await this.post('login.php', { username, password });
        if (response.success) {
            this.setToken(response.token);
            this.setUser(response.user);
        }
        return response;
    },

    /**
     * Logout
     */
    logout() {
        this.removeToken();
        window.location.href = 'login.html';
    },

    /**
     * Dados do Dashboard
     */
    async getDashboard() {
        return this.get('dashboard.php');
    },

    /**
     * Listar clientes
     */
    async getClients(params = {}) {
        return this.get('clients.php', params);
    },

    /**
     * Buscar cliente por CPF
     */
    async getClient(cpf) {
        return this.get('clients.php', { cpf });
    },

    /**
     * Criar cliente
     */
    async createClient(data) {
        return this.post('clients.php', data);
    },

    /**
     * Atualizar cliente
     */
    async updateClient(data) {
        return this.put('clients.php', data);
    },

    /**
     * Excluir cliente
     */
    async deleteClient(cpf) {
        return this.delete('clients.php', { cpf });
    },

    /**
     * Listar planos
     */
    async getPlans() {
        return this.get('plans.php');
    },

    /**
     * Listar instaladores
     */
    async getInstallers() {
        return this.get('installers.php');
    },

    // ==========================================
    // UPLOAD DE FOTOS
    // ==========================================

    /**
     * Upload de foto via Base64
     */
    async uploadPhoto(cpf, base64Image, type = 'other') {
        return this.post('upload.php', {
            cpf: cpf,
            photo: base64Image,
            type: type
        });
    },

    /**
     * Upload de foto via FormData (arquivo)
     */
    async uploadPhotoFile(cpf, file, type = 'other') {
        const formData = new FormData();
        formData.append('photo', file);
        formData.append('cpf', cpf);
        formData.append('type', type);

        const url = `${this.baseUrl}/upload.php`;
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.getToken()}`
            },
            body: formData
        });

        return response.json();
    },

    /**
     * Buscar fotos de um cliente
     */
    async getPhotos(cpf) {
        return this.get('upload.php', { cpf });
    },

    /**
     * Excluir foto
     */
    async deletePhoto(id) {
        return this.delete('upload.php', { id });
    },

    // ==========================================
    // PERFIL DO USUÁRIO
    // ==========================================

    /**
     * Buscar perfil do usuário
     */
    async getProfile() {
        return this.get('user.php');
    },

    /**
     * Atualizar perfil do usuário
     */
    async updateProfile(data) {
        return this.put('user.php', data);
    },

    /**
     * Upload de foto de perfil (FormData - arquivo direto)
     */
    async uploadProfilePhoto(file) {
        const formData = new FormData();
        formData.append('photo', file);

        const url = `${this.baseUrl}/user.php`;
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.getToken()}`
            },
            body: formData
        });

        return response.json();
    }
};

// Exporta para uso global
window.API = API;
