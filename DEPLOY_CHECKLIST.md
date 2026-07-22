# Deploy Checklist (any host)

Before every deploy, confirm these Railway/host environment variables are set
(dashboard, NOT your local .env):

- APP_ENV=production
- APP_DEBUG=false
- APP_URL=https://your-real-domain
- SESSION_SECURE_COOKIE=true
- SESSION_DOMAIN=  (leave blank unless using a custom domain)

The nixpacks.toml in this repo now builds CSS/JS (npm run build) and caches
config/routes automatically on every deploy — you should never need to build
assets manually again.

If something looks unstyled or JS-broken after deploy, check the build logs
first: confirm "npm run build" ran and produced public/build/manifest.json.
