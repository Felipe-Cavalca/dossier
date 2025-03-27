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
            let data = await response.json();

            let button = document.querySelector("button[type=submit]");
            button.disabled = false;
            button.innerHTML = originalTextButton;

            if (data.isSuccess) {
                alert().success("Login realizado com sucesso, você será redirecionado para a página inicial");
                localStorage.setItem("user.id", data.id);
                localStorage.setItem("user.role", data.role);
                setInterval(() => {
                    window.location.href = "home.html";
                }, 2000);
            } else {
                alert().error(data.message);
            }
        });
    }
);
