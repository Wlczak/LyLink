
# LyLink

![status](https://shokuin.vlastas.cc/api/badge/32/status?style=for-the-badge) ![uptime](https://shokuin.vlastas.cc/api/badge/32/uptime?style=for-the-badge) ![phpstan](https://img.shields.io/github/actions/workflow/status/wlczak/lylink/.github%2Fworkflows%2Fgo-lint.yml?branch=main&style=for-the-badge&label=phpstan) ![eslint](https://img.shields.io/github/actions/workflow/status/wlczak/lylink/.github%2Fworkflows%2Fts-lint.yml?branch=main&style=for-the-badge&label=eslint) ![build](https://img.shields.io/github/actions/workflow/status/wlczak/lylink/.github%2Fworkflows%2Fbuild.yml?branch=main&style=for-the-badge&label=build)

LyLink is a multiplatform lyrics tracker that allows you to sync your lyrics live with the music you're listening to.

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
      - ./db:/var/www/html/db
      - ./config/:/var/www/html/config/
    ports:
      - "1592:80"
    init: true
    command: ["php", "-S", "0.0.0.0:80", "-t", "/var/www/html/public_html"]
```

In both cases you will have to set up environment variables in the .env file. You can copy the skeleton from the .env.example file or this example:

```bash
CLIENT_ID=
CLIENT_SECRET=
BASE_DOMAIN=
SMTP_USERNAME=
SMTP_PASSWORD=
```

Both files can be found in the config directory.
