document.addEventListener('DOMContentLoaded', function() {
    // Função para analisar parâmetros da URL
    function getUrlParameters() {
        const params = {};
        const queryString = window.location.search.slice(1);
        const pairs = queryString.split('&');

        for (let i = 0; i < pairs.length; i++) {
            const pair = pairs[i].split('=');
            params[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || '');
        }

        return params;
    }

    // Função para exibir notificações
    function showNotification(type, message, formElement) {
        // Remover notificações anteriores
        const existingNotifications = document.querySelectorAll('.form-notification');
        existingNotifications.forEach(notification => notification.remove());

        // Criar elemento de notificação
        const notification = document.createElement('div');
        notification.className = `form-notification ${type}`;
        notification.innerHTML = `
            <div class="notification-icon">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            </div>
            <div class="notification-message">${message}</div>
            <button class="notification-close"><i class="fas fa-times"></i></button>
        `;

        // Adicionar depois do formulário
        if (formElement) {
            formElement.parentNode.insertBefore(notification, formElement.nextSibling);
        } else {
            document.body.appendChild(notification);
        }

        // Adicionar evento de fechar
        notification.querySelector('.notification-close').addEventListener('click', function() {
            notification.remove();
        });

        // Auto-fechar após 5 segundos
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 500);
        }, 5000);
    }

    // Verificar parâmetros da URL
    const params = getUrlParameters();

    if (params.status) {
        const formId = params.form || 'unknown';
        let formElement = null;
        let message = '';

        // Determinar qual formulário recebeu a resposta
        switch (formId) {
            case 'quote':
                formElement = document.getElementById('quote-form');
                message = params.status === 'success'
                    ? 'Sua solicitação de cotação foi enviada com sucesso! Entraremos em contato em breve.'
                    : 'Ocorreu um erro ao enviar sua solicitação. Por favor, tente novamente ou entre em contato por telefone.';
                break;

            case 'contact':
                formElement = document.getElementById('contact-form');
                message = params.status === 'success'
                    ? 'Sua mensagem foi enviada com sucesso! Responderemos em breve.'
                    : 'Ocorreu um erro ao enviar sua mensagem. Por favor, tente novamente ou entre em contato por telefone.';
                break;

            case 'newsletter':
                formElement = document.getElementById('newsletter-form');
                message = params.status === 'success'
                    ? 'Inscrição na newsletter realizada com sucesso!'
                    : 'Ocorreu um erro ao processar sua inscrição. Por favor, tente novamente.';
                break;

            default:
                message = params.status === 'success'
                    ? 'Operação realizada com sucesso!'
                    : 'Ocorreu um erro durante a operação. Por favor, tente novamente.';
        }

        // Mostrar notificação
        showNotification(
            params.status === 'success' ? 'success' : 'error',
            message,
            formElement
        );

        // Se for sucesso, também substituir o formulário por uma mensagem de sucesso
        if (params.status === 'success' && formElement) {
            formElement.innerHTML = `
                <div class="success-message animate__animated animate__fadeIn">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: var(--success); margin-bottom: 20px;"></i>
                    <h3>Mensagem Enviada!</h3>
                    <p>Agradecemos seu contato. Responderemos em breve.</p>
                </div>
            `;
        }

        // Limpar parâmetros da URL sem recarregar a página
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Validação no lado do cliente para todos os formulários
    const allForms = document.querySelectorAll('form');

    allForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                // Remover mensagens de erro anteriores
                const errorMessage = field.parentNode.querySelector('.error-message');
                if (errorMessage) errorMessage.remove();

                // Remover classe de erro
                field.classList.remove('error-field');

                // Validar campo
                if (!field.value.trim()) {
                    isValid = false;

                    // Adicionar classe de erro
                    field.classList.add('error-field');

                    // Criar mensagem de erro
                    const error = document.createElement('div');
                    error.className = 'error-message';
                    error.textContent = 'Este campo é obrigatório';

                    // Adicionar após o campo
                    field.parentNode.appendChild(error);

                    // Adicionar efeito de shake
                    field.classList.add('animate__animated', 'animate__shakeX');
                    setTimeout(() => {
                        field.classList.remove('animate__animated', 'animate__shakeX');
                    }, 1000);
                }

                // Validação específica para e-mail
                if (field.type === 'email' && field.value.trim() && !isValidEmail(field.value)) {
                    isValid = false;

                    // Adicionar classe de erro
                    field.classList.add('error-field');

                    // Criar mensagem de erro
                    const error = document.createElement('div');
                    error.className = 'error-message';
                    error.textContent = 'Por favor, informe um e-mail válido';

                    // Adicionar após o campo
                    field.parentNode.appendChild(error);
                }
            });

            // Impedir envio se não for válido
            if (!isValid) {
                e.preventDefault();

                // Rolar até o primeiro campo com erro
                const firstErrorField = form.querySelector('.error-field');
                if (firstErrorField) {
                    firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstErrorField.focus();
                }
            }
        });
    });

    // Função para validar e-mail
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
});