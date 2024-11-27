## Construir imagem da API

```bash
docker build -t scriptplayer/dossier_api:latest .
docker image push scriptplayer/dossier_api:latest
```

## Construir imagem do APP

```bash
docker build -t scriptplayer/dossier_app:latest .
docker image push scriptplayer/dossier_app:latest
```

## Construir imagem database

```bash
docker build -t scriptplayer/dossier_db:latest .
docker image push scriptplayer/dossier_db:latest
```

## Construir imagem do webdav

```bash
docker build -t scriptplayer/dossier_webdav:latest .
docker image push scriptplayer/dossier_webdav:latest
```
