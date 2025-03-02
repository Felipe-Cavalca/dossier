# Pergunta a tag ao usuário
$tag = Read-Host "Digite a tag da imagem (ex: v1.0.0)";

# Lista das imagens a serem construídas
$images = @("api", "app", "database", "webdav")

# Nome base do usuário/repositório
$repository = "scriptplayer"

# Loop para construir e enviar todas as imagens
foreach ($image in $images) {
    $imageName = "$repository/dossier-$image"

    Write-Host "Construindo imagem: ${imageName}:${tag} e ${imageName}:latest"

    # Constrói a imagem com a tag fornecida e latest
    docker build -t "${imageName}:${tag}" -t "${imageName}:latest" "../${image}"
    docker push "${imageName}:${tag}"
    docker push "${imageName}:latest"

    Write-Host "Imagem enviada: ${imageName}:${tag} e ${imageName}:latest`n"
}

Write-Host "Todas as imagens foram criadas e enviadas com sucesso!";
