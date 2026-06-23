# ZemTab Stress Test & Deployment Guide

## What was implemented

### Performance optimizations (reduce DB load ~95%)
1. **File cache store** (`config/cache.php`) — already file-based, cached polls don't touch DB
2. **Persistent DB connections** (`config/database.php`) — `DB_PERSISTENT=true` reuses connections
3. **15-second poll interval** (`orders/index.blade.php`) — 33% fewer polls than 10s
4. **Poll endpoint caching 5s** (`DashboardController::poll`) — identical polls return cached JSON
5. **Access middleware cache 5min** (`EnsureRestaurantDashboardAccess`) — subscription check cached

### Stress test infrastructure
6. **StressTestSeeder** — creates 300 test restaurants with accounts, menu items, tables
7. **Setup page buttons** — seed/cleanup stress data via `/setup` (no SSH needed)
8. **Upgraded stress test** (`tools/stress-test.js`) — staged 100/200/300, live progress, clear verdict

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

### 3. Seed stress test data
- On the `/setup` page, click **"Seed stress test data (300 restaurants)"**
- Wait for it to complete (may take 1-2 minutes)
- You'll see "Stress test data seeded successfully"

### 4. Run the stress test
From your local machine:
```bash
ZEMTAB_BASE_URL=https://zemtab.com node tools/stress-test.js
```

The test runs in stages:
- **Stage 1**: 100 restaurants, 5 minutes
- **Stage 2**: 200 restaurants, 5 minutes  
- **Stage 3**: 300 restaurants, 5 minutes

Each stage shows live progress and a clear pass/fail verdict.

### 5. Read the results
At the end, the test prints:
```
═══════════════════════════════════════
  GUARANTEED SAFE: 200 restaurants
  RECOMMENDED LAUNCH: 150 restaurants (25% safety margin)
═══════════════════════════════════════
```

### 6. Clean up
- On the `/setup` page, click **"Clean up stress test data"**
- All test restaurants, users, orders, and related data are removed
- Production is clean for real launch

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