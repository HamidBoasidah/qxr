#!/bin/bash

# Landing Page CMS Quick Setup Script
# This script will set up everything you need to run the Landing Page CMS

echo "🚀 Starting Landing Page CMS Setup..."
echo ""

# Step 1: Run migrations
echo "📦 Running migrations..."
php artisan migrate --force
if [ $? -ne 0 ]; then
    echo "❌ Migration failed!"
    exit 1
fi
echo "✅ Migrations completed"
echo ""

# Step 2: Seed data
echo "🌱 Seeding sample landing page data..."
php artisan db:seed --class=LandingPageSeeder --force
if [ $? -ne 0 ]; then
    echo "❌ Seeding failed!"
    exit 1
fi
echo "✅ Seeding completed"
echo ""

# Step 3: Clear cache
echo "🧹 Clearing cache..."
php artisan optimize:clear
php artisan ziggy:generate
echo "✅ Cache cleared"
echo ""

# Step 4: Create storage link (if not exists)
if [ ! -L public/storage ]; then
    echo "🔗 Creating storage link..."
    php artisan storage:link
    echo "✅ Storage link created"
else
    echo "✅ Storage link already exists"
fi
echo ""

# Success message
echo "🎉 Setup completed successfully!"
echo ""
echo "📝 Next steps:"
echo "   1. Run: npm run dev (in another terminal)"
echo "   2. Visit: http://localhost:8000/"
echo "   3. Admin: http://localhost:8000/admin/landing"
echo ""
echo "📚 Read LANDING_PAGE_README.md for full documentation"
echo ""
