#!/bin/bash
# Run on Plesk server after Git pull (Git → Repository → Actions → Deploy script)
set -e
cd "$(dirname "$0")/.."

echo "==> Bisani Brothers deploy"

if [ ! -f db.local.php ]; then
  echo "WARNING: db.local.php missing — copy from db.local.example.php and set production DB credentials."
fi

if [ -d storage/mail-outbox ] && [ ! -f storage/mail-outbox/.gitkeep ]; then
  touch storage/mail-outbox/.gitkeep
fi

chmod 755 storage storage/mail-outbox uploads uploads/resumes 2>/dev/null || true

echo "==> Deploy complete. Run migrations manually if needed:"
echo "    php scripts/migrate-site-features.php"
echo "    php scripts/migrate-phase2-features.php"
