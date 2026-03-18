# Podcast Analyser

A Laravel CLI tool that analyses podcast RSS feeds and generates an AI coaching summary.

![Built with Laravel 13 + Laravel AI SDK](https://img.shields.io/badge/Built%20with-Laravel%2013%20%2B%20Laravel%20AI%20SDK-red)

## Install

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set your AI provider and key in `.env`:

```env
AI_DEFAULT_PROVIDER=anthropic
ANTHROPIC_API_KEY=your_key
```

## Example Usage

```bash
php artisan podcast:analyse https://feeds.transistor.fm/build-your-saas
```

Example output:

```text
Podcast Health Score: 85/100
Pass: 4 | Warn: 1 | Fail: 0
✓ Title length is in recommended range.
✓ Description is present and sufficiently detailed.
✓ Podcast artwork is present.
⚠ Artwork URL should ideally end in .jpg or .png.
✓ Feed contains episodes.

AI Coach:
Your show has strong metadata fundamentals. Improve artwork format consistency and
refine positioning in the description to increase discoverability.
```

## Full 68-Check Report

For the complete analysis experience, visit [podcheck.dev](https://podcheck.dev).
