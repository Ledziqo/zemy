# ZemTab Stress Test & Deployment Guide

## What was implemented

### Performance optimizations (reduce DB load ~95%)
1. **File cache store** (`config/cache.php`) — already file-based, cached polls don't touch DB
2. **Persistent DB connections** (`config/database.php`) — `DB_PERSISTENT=true` reuses connections
3. **15-second poll interval** (`orders/index.blade.php`) — 33% fewer polls than 10s
4. **Poll endpoint caching 5s** (`DashboardController::poll`) — identical polls return cached JSON
5. **Access middleware cache 5min** (`EnsureRestaurantDashboardAccess`) — subscription check cached

### Stress test infrastructure
6. **StressTestSeeder** — creates disposable test restaurants with accounts, menu items, tables
7. **Setup page buttons** — seed/cleanup stress data via `/setup` (no SSH needed)
8. **Capacity runner** (`tools/capacity-10min.js`) — staged 10/20/40/60/80/100 venue testing against an isolated clone

The capacity runner must not target the live production URL. A temporary subdomain with its own database is required. The runner now refuses to start without an explicit `ZEMTAB_BASE_URL`.

### Post-launch monitoring
9. **SlowRequestMiddleware** — logs requests >500ms and queries >100ms to `storage/logs/slow.log`
10. **Slow log channel** (`config/logging.php`) — separate daily-rotated log file

## Deployment steps

### 1. Push to GitHub
```bash
git add -A
git commit -m "Performance optimizations + stress test infrastructure + monitoring"
git push origin main
```

### 2. Deploy on Hostinger
- Pull the code on Hostinger (git pull or upload)
- Run the setup page: `https://zemtab.com/setup`
- Click **"Run setup / updates now"** (applies migrations + clears cache)
- Confirm `CACHE_STORE=file` and `DB_PERSISTENT=true` in your `.env`

### 3. Create the temporary test site
- Create a temporary subdomain and separate database on the same hosting account/server.
- Deploy the same commit to that subdomain and configure its `.env` for the disposable database.
- Confirm `APP_ENV=staging` (or another non-production value), `DB_HOST=localhost`, and `STRESS_TEST_ALLOW_PRODUCTION=false`.
- Run the normal setup/migrations on the temporary site.

### 4. Seed stress test data
- On the temporary site’s `/setup` page, enable the stress controls if required.
- Seed batches until the required test venues exist (the default runner tests up to 100).
- Never seed stress data in the live production database.

### 5. Run the stress test
From your local machine:
```bash
$env:ZEMTAB_BASE_URL="https://staging.example.com"; node tools/capacity-10min.js
```

The test ramps through 10, 20, 40, 60, 80, and 100 active venues. Each stage runs for two minutes by default and stops on server errors, timeouts, failed recovery, or excessive poll latency. Set `ZEMTAB_STAGE_SECONDS` to change the stage duration.

The runner signs into each seeded staff account, obtains the current signed Work Board polling URL, exercises guest menu/order/service-request flows, and prints latency and error summaries per stage.

### 6. Read the results
At the end, the test prints:
```
Highest passed active venues: 80
Recommended launch estimate with 40% headroom: 48
```

### 7. Clean up
- On the temporary site’s `/setup` page, click **"Clean up stress test data"**.
- All test restaurants, users, orders, and related data are removed
- The production database is never touched by this test.

## Post-launch monitoring

### Check slow requests
- View `storage/logs/slow.log` on Hostinger (via File Manager or SSH)
- Any request taking >500ms is logged with URL, duration, query count, and slow queries
- Empty slow.log = healthy

### Set LOG_LEVEL=error in production
- In `.env`: `LOG_LEVEL=error`
- This ensures `laravel.log` only captures real errors, not debug noise

### UptimeRobot (free)
- Set up monitoring at https://uptimerobot.com
- Monitor `https://zemtab.com` and a menu URL like `https://zemtab.com/r/bole-bistro/table/1`
- Get email alerts if the site goes down

## Scaling path

| Restaurants | What's needed |
|---|---|
| 1-100 | Current setup (Hostinger Web Business + optimizations) |
| 100-200 | Monitor slow.log closely, should still work |
| 200-300 | Pushing the limit, watch for worker exhaustion |
| 300+ | Add Pusher (free tier) to eliminate polling, or move to VPS |

## Environment variables

| Variable | Default | Purpose |
|---|---|---|
| `DB_PERSISTENT` | `true` | Reuse DB connections across requests |
| `CACHE_STORE` | `file` | File-based cache (no DB for cache hits) |
| `SLOW_REQUEST_THRESHOLD_MS` | `500` | Log requests slower than this |
| `SLOW_QUERY_THRESHOLD_MS` | `100` | Log queries slower than this |
| `LOG_LEVEL` | `error` | Only log real errors in production |
| `STRESS_SEED` | (unset) | Set to `1` to run StressTestSeeder via CLI |
| `STRESS_SEED_COUNT` | `300` | Number of test restaurants to create |
