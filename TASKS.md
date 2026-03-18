# TASKS: Podcast Analyser — Laravel 13 AI SDK Showcase

Reference: PLAN_podcast_analyser.md

---

## Default Workflow (Applies to Every Phase)

- [ ] Write/update tests for the changes in the phase
- [ ] Run test suite and confirm all tests pass
- [ ] Commit with a clear message
- [ ] Push to remote before starting the next phase

---

## Phase 1 — Project Setup

- [x] Run `laravel new podcast-analyser` and `cd podcast-analyser`
- [x] Run `composer require laravel/ai`
- [x] Add to `.env`: `AI_DEFAULT_PROVIDER=anthropic`
- [x] Add to `.env`: `ANTHROPIC_API_KEY=your_key`
- [x] Confirm `php artisan` runs without errors

---

## Phase 2 — FeedFetcher Service

- [x] Create `app/Services/FeedFetcher.php`
- [x] Add `fetch(string $url): \SimpleXMLElement` method
- [x] Use `Http::timeout(10)->get($url)` to fetch the feed
- [x] Throw `\RuntimeException` if response is not successful
- [x] Parse body with `simplexml_load_string()`
- [x] Throw `\RuntimeException` if parsing fails
- [x] Register itunes namespace on the XML object
- [x] Return the `SimpleXMLElement`

---

## Phase 3 — FeedChecker Service

- [x] Create `app/Services/FeedChecker.php`
- [x] Add `check(\SimpleXMLElement $xml): array` method
- [x] Implement `checkTitleLength()` — pass 30–60 chars, warn otherwise, fail if empty
- [x] Implement `checkDescription()` — fail if empty, warn if < 50 chars, pass otherwise
- [x] Implement `checkArtworkPresent()` — use XPath to find `itunes:image`, fail if missing
- [x] Implement `checkArtworkFormat()` — warn if URL doesn't end in `.jpg` or `.png`
- [x] Implement `checkEpisodeCount()` — fail if 0 episodes, pass with count otherwise
- [x] Each method returns `['name' => '...', 'status' => 'pass|warn|fail', 'message' => '...']`
- [x] `check()` returns array of all 5 results

---

## Phase 4 — AiCoach Service

- [x] Create `app/Services/AiCoach.php`
- [x] Add `analyse(string $title, string $description, array $checks): string` method
- [x] Filter checks to only non-passing ones
- [x] Build prompt string — include podcast title, description, and failed/warned checks
- [x] Call `Ai::text($prompt)` from `Illuminate\Support\Facades\Ai`
- [x] Return `$response->text()`
- [x] Wrap in try/catch and throw descriptive exception on failure

---

## Phase 5 — Artisan Command

- [ ] Run `php artisan make:command AnalysePodcast`
- [ ] Set signature: `podcast:analyse {feed_url : The RSS feed URL to analyse}`
- [ ] Set description: `Analyse a podcast RSS feed and get an AI coaching summary`
- [ ] Inject `FeedFetcher`, `FeedChecker`, `AiCoach` via `handle()` method parameters
- [ ] Call `$fetcher->fetch($url)` — catch exception and show error, return `FAILURE`
- [ ] Extract `$title` and `$description` from XML
- [ ] Call `$checker->check($xml)` to get results
- [ ] Calculate score: start at 100, subtract 15 per fail, 5 per warn, min 0
- [ ] Display score with colour: green ≥ 80, yellow ≥ 50, red < 50
- [ ] Display pass/warn/fail counts on one line
- [ ] Loop through checks and display each with ✓ / ⚠ / ✗ icon and colour
- [ ] Call `$coach->analyse()` and display the result
- [ ] Add footer line linking to podcheck.dev
- [ ] Return `self::SUCCESS`

---

## Phase 6 — Test

- [ ] Run: `php artisan podcast:analyse https://feeds.transistor.fm/build-your-saas`
- [ ] Confirm score displays with correct colour
- [ ] Confirm all 5 checks display correctly
- [ ] Confirm AI Coach generates a real personalised summary
- [ ] Run on a second feed: `php artisan podcast:analyse https://feeds.transistor.fm/laracasts-snippet`
- [ ] Fix any errors or edge cases

---

## Phase 7 — GitHub

- [ ] `git init` (if not already)
- [ ] Create a clean `README.md` with:
  - One-liner description
  - Install steps
  - Example command and output
  - "Built with Laravel 13 + Laravel AI SDK" badge line
  - Link to podcheck.dev for full 68-check report
- [ ] `git add . && git commit -m "feat: initial podcast analyser command"`
- [ ] Push to new public GitHub repo: `podcast-analyser`

---

## Phase 8 — LinkedIn Post

- [ ] Take a screenshot of the terminal output showing score + checks + AI Coach summary
- [ ] Write post — no links in body, links in first comment
- [ ] Post body:

```
Laravel 13 dropped today. I built something with the new AI SDK in an afternoon.

php artisan podcast:analyse https://your-feed.com

A CLI tool that fetches your podcast RSS feed, runs health checks, then uses
the Laravel 13 AI SDK to generate a personalised coaching summary in your terminal.

What I liked about the Laravel AI SDK:
— Clean, expressive API — feels like the rest of Laravel
— Swappable providers (I used Anthropic/Claude)
— Zero boilerplate to get a response

The full version lives at podcheck.dev (68 checks + shareable reports).
This is the afternoon side project version.

Stack: Laravel 13 · Laravel AI SDK · Anthropic/Claude · PHP 8.3
```

- [ ] Post immediately after publishing — add first comment with links:
  - podcheck.dev
  - github.com/daniloradovic/podcast-analyser

---

## Done When

- [ ] Command runs cleanly on 2 real feed URLs
- [ ] Score colour is correct
- [ ] AI Coach output is personalised and specific to the show
- [ ] README is clean and clear
- [ ] Code is on GitHub (public)
- [ ] LinkedIn post is live with screenshot
