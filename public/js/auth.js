document.addEventListener('DOMContentLoaded', () => {
    const signUpButton = document.getElementById('signUp');
    const signInButton = document.getElementById('signIn');
    const container = document.getElementById('container');

    if (signUpButton && signInButton && container) {
        signUpButton.addEventListener('click', () => container.classList.add("right-panel-active"));
        signInButton.addEventListener('click', () => container.classList.remove("right-panel-active"));
    }

    // Máscara dinâmica para CNPJ
    const cnpjInput = document.getElementById('campo_cnpj');
    const razaoInput = document.getElementById('campo_razao_social');

    if (cnpjInput) {
        cnpjInput.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 14) value = value.slice(0, 14);

            value = value.replace(/^(\d{2})(\d)/, '$1.$2')
                .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/\.(\d{3})(\d)/, '.$1/$2')
                .replace(/(\d{4})(\d)/, '$1-$2');
            e.target.value = value;
        });

        // Integração automatizada com a BrasilAPI para validação cadastral da Receita Federal
        cnpjInput.addEventListener('blur', () => {
            const cnpjLimpo = cnpjInput.value.replace(/\D/g, '');
            if (cnpjLimpo.length === 14 && razaoInput) {
                razaoInput.value = "Buscando Razão Social...";
                razaoInput.disabled = true;

                fetch(`https://brasilapi.com.br/api/cnpj/v1/${cnpjLimpo}`)
                    .then(res => {
                        if (!res.ok) throw new Error("CNPJ não localizado ou falha na API.");
                        return res.json();
                    })
                    .then(data => {
                        razaoInput.value = data.razao_social || "";
                    })
                    .catch(() => {
                        razaoInput.value = "";
                    })
                    .finally(() => {
                        razaoInput.disabled = false;
                    });
            }
        });
    }
});