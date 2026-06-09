#!/bin/bash
# Run on Plesk server after Git pull (Git → Repository → Actions → Deploy script)
set -e
cd "$(dirname "$0")/.."

echo "==> Bisani Brothers deploy"

if [ ! -f db.local.php ]; then
  echo "ERROR: db.local.php missing in httpdocs."
  echo "Create it from db.local.example.php with Plesk DB credentials:"
  echo "  Database: bisanibrothers_2026"
  echo "  User:     BisaniBrothers_2026"
  exit 1
fi

if [ -d storage/mail-outbox ] && [ ! -f storage/mail-outbox/.gitkeep ]; then
  touch storage/mail-outbox/.gitkeep
fi

chmod 755 storage storage/mail-outbox uploads uploads/resumes uploads/marketing 2>/dev/null || true

if command -v php >/dev/null 2>&1; then
  echo "==> Ensuring uploads folders..."
  php scripts/ensure-uploads-dir.php || echo "WARN: ensure-uploads-dir failed — set IIS_IUSRS Modify on uploads in Plesk"
  echo "==> Warming locale strings..."
  php scripts/warm-locale-cache.php || echo "WARN: warm-locale-cache failed"
  echo "==> Warming blog translations (all locales)..."
  php scripts/warm-blog-translations.php || echo "WARN: warm-blog-translations failed — run manually on server"
fi

echo "==> Deploy complete. Run migrations manually if needed:"
echo "    php scripts/migrate-site-features.php"
echo "    php scripts/migrate-phase2-features.php"
