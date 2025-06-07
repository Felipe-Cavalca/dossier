# 📁 Dossier

**Dossier** é um gestor de arquivos que pode ser instalado tanto em servidores locais quanto na nuvem.
A proposta é oferecer uma solução simples, eficiente e autônoma para gerenciamento de arquivos — como se fosse a sua própria nuvem.

This README is available in **English** and **Português**.

- [English](#english)
- [Português](#portugu%C3%AAs)

---

## English

This repository contains the back-end service of **BifrostPHP**, a minimal framework focused on small APIs.

More information about the project can be found in the main repository: [BifrostPHP](https://github.com/Felipe-Cavalca/BifrostPHP)

### Getting started

To run the project, you will need to have Docker and Docker Compose installed on your machine.

Follow the steps below to start the development containers:

1. Open a terminal and navigate to the project's `api` folder.
2. Run the following command to build and start the Docker containers:

    ```bash
    docker-compose up -d
    ```

    This will download the required images, build the containers and start the services.

3. Once complete, you can access the API in your browser using the following URL:

    ```http
    http://localhost:80
    ```

### Project structure

The most important directories inside `api/` are:

| Folder | Description |
|--------|-------------|
| `Controller` | Endpoint controllers |
| `Model` | Data models and business logic |
| `Core` | Request handling and utilities |
| `tests` | Example JSON requests |
| `docs` | Project documentation |

### Documentation

Additional information can be found in [api/docs](api/docs/README.md).

---

## Português

Este repositório contém o serviço de back-end do **BifrostPHP**, um framework minimalista focado em APIs pequenas.

Mais informações sobre o projeto podem ser encontradas no repositório principal: [BifrostPHP](https://github.com/Felipe-Cavalca/BifrostPHP)

### Primeiros passos

Para executar o projeto, você precisará ter o Docker e o Docker Compose instalados em sua máquina.

Siga as etapas abaixo para iniciar os contêineres de desenvolvimento:

1. Abra um terminal e navegue até a pasta `api` do projeto.
2. Execute o seguinte comando para construir e iniciar os contêineres do Docker:

    ```bash
    docker-compose up -d
    ```

    Isso irá baixar as imagens necessárias, construir os contêineres e iniciar os serviços.

3. Após a conclusão, você poderá acessar a API em seu navegador usando o seguinte URL:

    ```bash
    http://localhost:80
    ```

### Estrutura do projeto

Algumas das principais pastas dentro de `api/` são:

| Pasta | Descrição |
|-------|-----------|
| `Controller` | Controladores de endpoint |
| `Model` | Lógicas de negócio e modelos de dados |
| `Core` | Tratamento de requisições e utilidades |
| `tests` | Requisições exemplo para testes |
| `docs` | Documentação do projeto |

### Documentação

Informações adicionais podem ser encontradas em [api/docs](api/docs/README-PT.md).

---

### Sponsored by / Patrocinado por

As funcionalidades em desenvolvimento e futuras ideias estão organizadas nas [issues do repositório](https://github.com/Felipe-Cavalca/dossier/issues).
