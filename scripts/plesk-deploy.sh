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
  echo "==> Seeding SEO blog posts (if missing)..."
  php scripts/seed-seo-blog-posts.php || echo "WARN: seed-seo-blog-posts failed"
  echo "==> Seeding case studies (if missing)..."
  php scripts/seed-case-studies.php || echo "WARN: seed-case-studies failed"
  echo "==> Blog schema migrations (locale, orphan, tags, faq)..."
  php scripts/migrate-locale.php || echo "WARN: migrate-locale failed"
  php scripts/migrate-orphan-blogs.php || echo "WARN: migrate-orphan-blogs failed"
  echo "==> Job schema migrations..."
  php scripts/migrate-job-fields.php || echo "WARN: migrate-job-fields failed"
  echo "==> SEO text column migrations (keywords, tags, meta)..."
  php scripts/migrate-seo-text-columns.php || echo "WARN: migrate-seo-text-columns failed"
fi

echo "==> Deploy complete. Run migrations manually if needed:"
echo "    php scripts/migrate-site-features.php"
echo "    php scripts/migrate-phase2-features.php"
