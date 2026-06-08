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

chmod 755 storage storage/mail-outbox uploads uploads/resumes 2>/dev/null || true

echo "==> Deploy complete. Run migrations manually if needed:"
echo "    php scripts/migrate-site-features.php"
echo "    php scripts/migrate-phase2-features.php"
