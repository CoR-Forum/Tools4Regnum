# Tools4Regnum

A comprehensive wiki and resource browser for **Champions of Regnum** (formerly Regnum Online). Built with PHP 8.4, SQLite, and Bootstrap 5 in a medieval dark theme.

**Live:** [tools4regnum.cor-forum.de](https://tools4regnum.cor-forum.de)

## Features

- **Wiki** — Browse and manage entries for NPCs, Quests, Items, Monsters, and Events
- **Game Assets** — Browse 11,000+ textures, 670+ sounds, and 40+ music files from the game client with preview and download
- **Live Search** — Instant as-you-type search across all wiki entries and game assets
- **Multi-language** — Full support for German, English, and Spanish (DE/EN/ES)
- **Forum Login** — Authenticate via the [CoR-Forum](https://cor-forum.de) API
- **Favorites** — Save entries to a personal favorites list
- **Admin Panel** — Create, edit, and delete wiki entries with multi-image upload (restricted to specific forum user IDs)
- **Full-text Search** — SQLite FTS5 for fast wiki search, substring matching for game assets
- **Medieval Theme** — Custom dark theme with Cinzel & Lora fonts, gold accents

## Tech Stack

- **PHP 8.4** on Apache
- **SQLite** with WAL mode and FTS5
- **Bootstrap 5.3** (dark mode)
- **Docker** with bind mounts for development

## Quick Start

1. Clone the repository:
   ```bash
   git clone https://github.com/CoR-Forum/Tools4Regnum.git
   cd Tools4Regnum
   ```

2. Copy and configure the environment file:
   ```bash
   cp .env.example .env
   ```

   Required variables:
   | Variable | Description |
   |---|---|
   | `FORUM_API_URL` | Forum API endpoint |
   | `FORUM_API_KEY` | API key for authentication |
   | `ADMIN_USER_IDS` | Comma-separated forum user IDs with admin access |
   | `DEFAULT_LANG` | Default language (`de`, `en`, or `es`) |

3. Start with Docker Compose:
   ```bash
   docker compose up --build -d
   ```

4. Open [http://localhost:2526](http://localhost:2526)

## Project Structure

```
├── .github/workflows/   # CI/CD (deploy via rsync)
├── data/                 # SQLite database + files.json (game assets index)
├── lang/                 # Translation files (de/en/es)
├── public/               # Web root (front controller, assets)
│   ├── assets/           # CSS, JS
│   └── index.php         # Front controller
├── src/                  # Application code
│   ├── controllers/      # Route handlers
│   ├── helpers/           # Functions & i18n
│   └── models/           # Data models
├── templates/            # PHP templates
├── docker-compose.yml
├── Dockerfile
└── docker-entrypoint.sh
```

## Game Assets

Game textures, sounds, and music are indexed in `data/files.json` and served from `cor-forum.de/regnum/datengrab/res/`. These are read-only and not stored in the database.

## Deployment

Pushes to `main` trigger automatic deployment via GitHub Actions:
- Rsync to the production server
- Rebuild and restart the Docker container

## License

This project is not affiliated with or endorsed by NGD Studios.
