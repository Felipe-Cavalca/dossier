const bifrost = new Bifrost(
    (bifrost) => {
        bifrost.replaceTextInElement("body", bifrost.config);
    },
    (bifrost) => {
        let before = () => {
            let invalido = document.querySelector("input:invalid");
            if (invalido) {
                invalido.focus();
                return false;
            }
            return true;
        };
        let after = async (response) => {
            let elementHeader = document.querySelector("div[slot='header']");
            let elementResponse = document.querySelector("pre[slot='body']");
            let elementAlert = document.querySelector("c-alert");
            if (response.ok) {
                elementResponse.innerHTML = "Redirecionando para a página de login...";
                elementHeader.innerHTML = "Usuário cadastrado com sucesso";
                elementAlert.style.display = "block";
                setTimeout(() => {
                    window.location.href = "/";
                }, 3000);
            } else {
                elementResponse.innerHTML = JSON.stringify(await response.json(), null, 2);
                elementHeader.innerHTML = "Houve um erro ao cadastrar o usuário";
                elementAlert.style.display = "block";
            }
        };

        bifrost.form("form", before, after);
    }
);

function toInput(element) {
    window.location.href = element;
    setTimeout(() => {
        let input = document.querySelector(`${element} input`);
        if(input) {
            input.focus();
        }
    }, 500);
}

function focusOnEnter(event, nextElement) {
    if (event.key === 'Enter') {
        event.preventDefault();
        toInput(nextElement);
    }
}

function focusOnButton(nextElement) {
    toInput(nextElement);
}
