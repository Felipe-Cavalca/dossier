var originalTextButton;

const bifrost = new Bifrost(
    (bifrost) => {
        bifrost.replaceTextInElement("body", bifrost.config);
    },
    (bifrost) => {
        bifrost.form("form", () => {
            let invalido = document.querySelector("input:invalid");

            if (invalido) {
                alert().error("Preencha todos os campos corretamente");
                return false;
            }

            let button = document.querySelector("button[type=submit]");
            button.disabled = true;
            originalTextButton = button.innerHTML;
            button.innerHTML = "Entrando...";
            return true;
        }, async (response) => {
            let bodyResponse = await response.json();

            let button = document.querySelector("button[type=submit]");
            button.disabled = false;
            button.innerHTML = originalTextButton;

            if (bodyResponse.status === 200) {
                alert().success("Login realizado com sucesso, você será redirecionado para a página inicial");
                localStorage.setItem("user.id", bodyResponse.data.id);
                localStorage.setItem("user.role.id", bodyResponse.data.role.code);
                setInterval(() => {
                    window.location.href = "home.html";
                }, 2000);
            } else if (bodyResponse.status === 401) {
                alert().error("Usuário ou senha inválidos");
            } else {
                alert().error("Erro ao realizar login, tente novamente mais tarde");
            }
        });
    }
);
