
# LyLink

LyLink is a multiplatform lyrics tracker that allows you to sync your lyrics live with the musis you're listening to.

## Installation

### Docker compose

Instalation with docker compose is currently the only tested way to reliably run LyLink.

You can either clone the repository and use the inbuilt compose:

```bash
git clone https://github.com/wlczak/lylink.git
cd lylink
docker compose up -d
```

Or you can create your own docker compose file:

```yaml
services:
  lylink:
    image: wlczak/lylink:latest
    volumes:
      - ./lyrics.db:/var/www/html/lyrics.db
      - ./.env:/var/www/html/.env
    ports:
      - "1592:80"
    init: true
    command: ["php", "-S", "0.0.0.0:80", "-t", "/var/www/html/public_html"]
```

In both cases you will have to privide a config of the .env file. You can copy the skeleton from the .env.example file or this example:

```bash
CLIENT_ID=
CLIENT_SECRET=
BASE_DOMAIN=
```
