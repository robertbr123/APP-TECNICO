/**
 * Login Page Module
 * Ondeline Tech - App do Técnico
 */

App.initLoginPage = function() {
    const usernameInput = document.querySelector('input[type="text"]');
    const passwordInput = document.querySelector('input[type="password"]');
    const submitBtn = document.querySelector('button');
    const togglePassword = document.querySelector('.material-symbols-outlined[class*="visibility"]')?.closest('div');

    if (togglePassword) {
        const icon = togglePassword.querySelector('.material-symbols-outlined');
        togglePassword.addEventListener('click', () => {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                passwordInput.type = 'password';
                icon.textContent = 'visibility';
            }
        });
    }

    if (submitBtn) {
        submitBtn.addEventListener('click', async (e) => {
            e.preventDefault();

            const username = usernameInput?.value?.trim();
            const password = passwordInput?.value;

            if (!username || !password) {
                App.showToast('Preencha todos os campos', 'warning');
                return;
            }

            App.showLoading(true);

            try {
                const response = await API.login(username, password);

                if (response.success) {
                    App.showToast('Login realizado com sucesso!', 'success');
                    setTimeout(() => {
                        window.location.href = 'dashboard.html';
                    }, 500);
                } else {
                    App.showToast(response.message || 'Usuário ou senha inválidos', 'error');
                }
            } catch (error) {
                App.showToast('Erro ao fazer login. Verifique sua conexão.', 'error');
            } finally {
                App.showLoading(false);
            }
        });
    }

    if (usernameInput && passwordInput) {
        [usernameInput, passwordInput].forEach(input => {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    submitBtn?.click();
                }
            });
        });
    }
};
