# Landing Page CMS Documentation

## 🎯 Overview

A complete dynamic Landing Page system with full CMS capabilities built with Laravel + Vue 3 + Inertia.js.

## 📦 Installation Steps

### 1. Run Migrations

```bash
php artisan migrate
```

This will create three new tables:
- `landing_pages`
- `landing_sections`
- `landing_section_items`

### 2. Seed Sample Data

```bash
php artisan db:seed --class=LandingPageSeeder
```

This creates a complete landing page with:
- Hero Section
- Features Section (6 features)
- Steps Section (3 steps)
- Services Section (6 services)
- FAQ Section (5 FAQs)
- Mobile App Section
- CTA Section

### 3. Clear Cache

```bash
php artisan optimize:clear
php artisan ziggy:generate
```

### 4. Build Frontend Assets

```bash
npm run dev
# or for production
npm run build
```

## 🌐 Routes

### Public Routes
- `GET /` - Home landing page
- `GET /landing/{slug}` - View any landing page by slug

### Admin Routes (requires authentication)
- `GET /admin/landing` - List all landing pages
- `GET /admin/landing/create` - Create new landing page
- `GET /admin/landing/{id}/edit` - Edit landing page
- `DELETE /admin/landing/{id}` - Delete landing page

#### Section Management
- `GET /admin/landing/{page}/sections` - List sections
- `GET /admin/landing/{page}/sections/create` - Create section
- `GET /admin/landing/{page}/sections/{id}/edit` - Edit section
- `POST /admin/landing/{page}/sections/reorder` - Reorder sections (drag & drop)
- `PATCH /admin/landing/{page}/sections/{id}/toggle` - Toggle visibility

#### Items Management
- `GET /admin/landing/{page}/sections/{section}/items` - List items
- `GET /admin/landing/{page}/sections/{section}/items/create` - Create item
- `GET /admin/landing/{page}/sections/{section}/items/{id}/edit` - Edit item
- `POST /admin/landing/{page}/sections/{section}/items/reorder` - Reorder items
- `PATCH /admin/landing/{page}/sections/{section}/items/{id}/toggle` - Toggle visibility

## 🧱 Architecture

### Backend Structure

```
app/
├── Models/
│   ├── LandingPage.php
│   ├── LandingSection.php
│   └── LandingSectionItem.php
│
├── Services/
│   ├── LandingPageService.php
│   ├── LandingSectionService.php
│   └── LandingSectionItemService.php
│
└── Http/Controllers/
    ├── LandingPageController.php (Public)
    └── Admin/
        ├── LandingPageController.php
        ├── LandingSectionController.php
        └── LandingSectionItemController.php
```

### Frontend Structure

```
resources/js/
├── pages/
│   ├── LandingPage.vue (Main public page)
│   └── Admin/Landing/
│       ├── Index.vue
│       ├── Create.vue
│       ├── Edit.vue
│       └── Sections/
│           ├── Index.vue
│           ├── Create.vue
│           ├── Edit.vue
│           └── Items/
│               ├── Index.vue
│               ├── Create.vue
│               └── Edit.vue
│
└── components/landing/
    ├── HeroSection.vue
    ├── FeaturesSection.vue
    ├── ServicesSection.vue
    ├── StepsSection.vue
    ├── TestimonialsSection.vue
    ├── FAQSection.vue
    ├── CTASection.vue
    ├── StatsSection.vue
    └── MobileAppSection.vue
```

## 📊 Database Schema

### landing_pages
- id
- title
- slug (unique)
- is_active
- meta_title
- meta_description
- timestamps

### landing_sections
- id
- landing_page_id (FK)
- type (enum: hero, features, services, steps, testimonials, faq, cta, stats, mobile_app)
- title (JSON: {ar, en})
- subtitle (JSON: {ar, en})
- order (integer)
- is_active (boolean)
- settings (JSON)
- timestamps

### landing_section_items
- id
- landing_section_id (FK)
- title (JSON: {ar, en})
- description (JSON: {ar, en})
- image_path
- icon
- link
- link_text
- order (integer)
- data (JSON - flexible storage)
- is_active (boolean)
- timestamps

## 🎨 Section Types

### 1. Hero Section
- Main title & subtitle
- Hero image/video
- CTA buttons
- Rating badges
- Consultant count

### 2. Features Section
- Grid of feature cards
- Icons or images
- Title & description
- Optional links

### 3. Services Section
- Service cards with colors
- Background gradients
- Images/illustrations
- CTA links

### 4. Steps Section
- Timeline-style steps
- Numbered circles
- Step descriptions

### 5. Testimonials Section
- Carousel/slider
- Star ratings
- Customer photos
- Quotes

### 6. FAQ Section
- Accordion-style
- Expandable questions
- Searchable (future)

### 7. Mobile App Section
- App screenshots
- App store badges
- Feature highlights

### 8. CTA Section
- Call to action
- Multiple buttons
- Gradient background

### 9. Stats Section
- Number counters
- Achievement metrics
- Grid layout

## 🔧 Usage Examples

### Creating a New Section Programmatically

```php
use App\Models\LandingPage;
use App\Services\LandingSectionService;

$landingPage = LandingPage::where('slug', 'home')->first();
$sectionService = app(LandingSectionService::class);

$section = $sectionService->create($landingPage, [
    'type' => 'features',
    'title' => [
        'ar' => 'لماذا نحن',
        'en' => 'Why Us',
    ],
    'subtitle' => [
        'ar' => 'مميزات منصتنا',
        'en' => 'Our Platform Features',
    ],
    'order' => 2,
    'is_active' => true,
    'settings' => [
        'badge' => 'المميزات',
    ],
]);
```

### Adding Items to a Section

```php
use App\Models\LandingSection;
use App\Services\LandingSectionItemService;

$section = LandingSection::find(1);
$itemService = app(LandingSectionItemService::class);

$item = $itemService->create($section, [
    'title' => [
        'ar' => 'خدمة متميزة',
        'en' => 'Excellent Service',
    ],
    'description' => [
        'ar' => 'نقدم أفضل خدمة',
        'en' => 'We provide the best service',
    ],
    'icon' => 'star',
    'link' => '/services',
    'order' => 1,
    'is_active' => true,
]);
```

## 🎯 Customization

### Adding New Section Type

1. Add to migration enum:
```php
->enum('type', [..., 'new_type'])
```

2. Create Vue component:
```vue
// resources/js/components/landing/NewTypeSection.vue
<template>
  <section>
    <!-- Your custom layout -->
  </section>
</template>
```

3. Register in LandingPage.vue:
```javascript
import NewTypeSection from './landing/NewTypeSection.vue'

const getSectionComponent = (type) => {
  const components = {
    // ...
    new_type: NewTypeSection,
  }
  return components[type]
}
```

4. Update controller:
```php
private function getSectionTypes(): array
{
    return [
        // ...
        ['value' => 'new_type', 'label' => 'New Type Section'],
    ];
}
```

## 🚀 Next Steps

### To Complete the System:

1. **Create Remaining Admin Pages:**
   - Create.vue
   - Edit.vue
   - Sections/Create.vue
   - Sections/Edit.vue
   - Sections/Items pages

2. **Add Image Upload:**
   - File upload UI
   - Image preview
   - Image optimization

3. **Add Drag & Drop:**
   - Sortable.js integration
   - Visual reordering
   - Save order via AJAX

4. **Add More Features:**
   - Duplicate section/item
   - Export/import landing pages
   - Version history
   - Preview mode
   - A/B testing

## 📝 Notes

- All sections are reusable and customizable
- Multi-language support (AR/EN) built-in
- Responsive design across all components
- Image storage uses Laravel's public disk
- JSON fields for flexible data storage
- Service layer pattern for clean code
- Proper authorization with middleware

## 🐛 Troubleshooting

### Landing page not showing:
```bash
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

### Images not displaying:
```bash
php artisan storage:link
```

### Frontend not updating:
```bash
npm run dev
# or force rebuild
rm -rf node_modules/.vite
npm run dev
```

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Vue 3 Documentation](https://vuejs.org/)
- [Inertia.js Documentation](https://inertiajs.com/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)

---

**Ready to use!** Visit `/admin/landing` to start creating your landing pages.
