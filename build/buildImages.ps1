# buildImages.ps1

# Construir e enviar a imagem da API
docker build -t scriptplayer/dossier_api:latest ./api
docker image push scriptplayer/dossier_api:latest

# Construir e enviar a imagem do APP
docker build -t scriptplayer/dossier_app:latest ./app
docker image push scriptplayer/dossier_app:latest

# Construir e enviar a imagem do database
docker build -t scriptplayer/dossier_db:latest ./database
docker image push scriptplayer/dossier_db:latest

# Construir e enviar a imagem do webdav
docker build -t scriptplayer/dossier_webdav:latest ./webdav
docker image push scriptplayer/dossier_webdav:latest
