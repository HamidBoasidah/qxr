<?php

namespace Database\Seeders;

use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Models\LandingSectionItem;
use Illuminate\Database\Seeder;

class LandingPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create landing page
        $landingPage = LandingPage::create([
            'title' => 'نماء الأعمال - الصفحة الرئيسية',
            'slug' => 'home',
            'is_active' => true,
            'meta_title' => 'نماء الأعمال - منصة استشارات مالية وأعمال',
            'meta_description' => 'منصة تربط أصحاب المشاريع بمستشارين ماليين ومحاسبين معتمدين لتقديم استشارات احترافية وخدمات متخصصة',
        ]);

        // 1. Hero Section
        $heroSection = LandingSection::create([
            'landing_page_id' => $landingPage->id,
            'type' => 'hero',
            'title' => [
                'ar' => 'خُذ قراراتك بثقة مع خُبراء مال وأعمال بجانبك دائمًا',
                'en' => 'Make Confident Decisions with Financial and Business Experts Always by Your Side',
            ],
            'subtitle' => [
                'ar' => 'من تحليل القوائم المالية إلى دراسة الجدوى وخطط النمو، كل ما تحتاجه متاح في مكان واحد.',
                'en' => 'From financial statement analysis to feasibility studies and growth plans, everything you need is available in one place.',
            ],
            'order' => 1,
            'is_active' => true,
            'settings' => [
                'background' => 'linear-gradient(139deg, rgba(236, 253, 245, 0.6) 10.51%, rgba(209, 250, 229, 0.6) 87.75%)',
                'showOverlays' => true,
            ],
        ]);

        // Hero CTA Buttons
        LandingSectionItem::create([
            'landing_section_id' => $heroSection->id,
            'title' => ['ar' => 'صورة البطل', 'en' => 'Hero Image'],
            'image_path' => 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=800&h=600&fit=crop',
            'order' => 0,
            'is_active' => true,
            'data' => ['type' => 'hero_image'],
        ]);

        LandingSectionItem::create([
            'landing_section_id' => $heroSection->id,
            'title' => ['ar' => 'حمّل التطبيق الآن', 'en' => 'Download App Now'],
            'link' => '#',
            'order' => 1,
            'is_active' => true,
            'data' => ['type' => 'cta_button', 'variant' => 'primary'],
        ]);

        LandingSectionItem::create([
            'landing_section_id' => $heroSection->id,
            'title' => ['ar' => 'انضم كمستشار', 'en' => 'Join as Consultant'],
            'link' => '/register',
            'order' => 2,
            'is_active' => true,
            'data' => ['type' => 'cta_button', 'variant' => 'secondary'],
        ]);

        // 2. Features Section
        $featuresSection = LandingSection::create([
            'landing_page_id' => $landingPage->id,
            'type' => 'features',
            'title' => [
                'ar' => 'لماذا تختار منصتنا؟',
                'en' => 'Why Choose Our Platform?',
            ],
            'subtitle' => [
                'ar' => 'نوفر لك كل ما تحتاجه للحصول على استشارات مالية ومحاسبية احترافية بسهولة وأمان',
                'en' => 'We provide everything you need to get professional financial and accounting consultations easily and securely',
            ],
            'order' => 2,
            'is_active' => true,
            'settings' => ['badge' => 'المميزات'],
        ]);

        $features = [
            [
                'title' => ['ar' => 'ابحث عن المستشار المناسب لمشروعك', 'en' => 'Find the Right Consultant'],
                'description' => ['ar' => 'البحث عن المستشارين حسب التخصص والتقييمات واختر الخدمة المناسبة', 'en' => 'Search for consultants by specialty and ratings'],
                'icon' => 'search',
            ],
            [
                'title' => ['ar' => 'احجز جلسات استشارية عبر الإنترنت', 'en' => 'Book Online Consultation Sessions'],
                'description' => ['ar' => 'جلسات مباشرة عبر الفيديو أو الصوت أو المحادثة النصية حسب راحتك', 'en' => 'Direct sessions via video, audio, or text chat'],
                'icon' => 'video',
            ],
            [
                'title' => ['ar' => 'اطلب خدمات جاهزة ومتخصصة', 'en' => 'Request Ready and Specialized Services'],
                'description' => ['ar' => 'دراسات جدوى، تحليل مالي، خطط أعمال، إعادة هيكلة الديون، وتقييم الأداء', 'en' => 'Feasibility studies, financial analysis, business plans'],
                'icon' => 'document',
            ],
            [
                'title' => ['ar' => 'تابع استشاراتك من مكان واحد', 'en' => 'Track Your Consultations From One Place'],
                'description' => ['ar' => 'إدارة سهلة لكل مواعيدك، سجلات الجلسات، والتقارير المستلمة', 'en' => 'Easy management of all appointments and reports'],
                'icon' => 'location',
            ],
            [
                'title' => ['ar' => 'مدفوعات آمنة وسياسة استرداد واضحة', 'en' => 'Secure Payments and Clear Refund Policy'],
                'description' => ['ar' => 'تُصفح مئات المستشارين المعتمدين واختر الأنسب حسب تخصصك واحتياجات عملك', 'en' => 'Browse hundreds of verified consultants'],
                'icon' => 'shield',
            ],
            [
                'title' => ['ar' => 'قيّم المستشار وشارك تجربتك', 'en' => 'Rate Consultants and Share Your Experience'],
                'description' => ['ar' => 'امنح المستشار تقييمك، وساعد باقي رواد الأعمال بختارونا الأنسب لاحتياجاتهم', 'en' => 'Give your rating and help other entrepreneurs'],
                'icon' => 'star',
            ],
        ];

        foreach ($features as $index => $feature) {
            LandingSectionItem::create([
                'landing_section_id' => $featuresSection->id,
                'title' => $feature['title'],
                'description' => $feature['description'],
                'icon' => $feature['icon'],
                'order' => $index + 1,
                'is_active' => true,
            ]);
        }

        // 3. Steps Section
        $stepsSection = LandingSection::create([
            'landing_page_id' => $landingPage->id,
            'type' => 'steps',
            'title' => [
                'ar' => 'ثلاث خطوات بسيطة',
                'en' => 'Three Simple Steps',
            ],
            'subtitle' => [
                'ar' => 'للحصول على الاستشارة التي تحتاجها',
                'en' => 'To get the consultation you need',
            ],
            'order' => 3,
            'is_active' => true,
            'settings' => ['badge' => 'كيف يعمل'],
        ]);

        $steps = [
            [
                'title' => ['ar' => 'تصميم المستشارين والخدمات', 'en' => 'Design Consultants and Services'],
                'description' => ['ar' => 'البحث عن المستشارين حسب التخصص والتقييمات واختر الخدمة المناسبة', 'en' => 'Search for consultants and choose the appropriate service'],
            ],
            [
                'title' => ['ar' => 'اختر الوقت والطريقة وادفع بأمان', 'en' => 'Choose Time, Method, and Pay Securely'],
                'description' => ['ar' => 'حدد موعد الجلسة ونوع الاستشارة (فيديو/صوت/نص)، وأتمم الدفع الآمن', 'en' => 'Set session time and consultation type, complete secure payment'],
            ],
            [
                'title' => ['ar' => 'التق بالمستشار واستلم التقرير', 'en' => 'Meet Consultant and Receive Report'],
                'description' => ['ar' => 'احضر الجلسة الاستشارية، أستلم التقاريروالمخرجات، ثم قمّ الخدمة', 'en' => 'Attend consultation session, receive reports, then rate the service'],
            ],
        ];

        foreach ($steps as $index => $step) {
            LandingSectionItem::create([
                'landing_section_id' => $stepsSection->id,
                'title' => $step['title'],
                'description' => $step['description'],
                'order' => $index + 1,
                'is_active' => true,
            ]);
        }

        // 4. Services Section
        $servicesSection = LandingSection::create([
            'landing_page_id' => $landingPage->id,
            'type' => 'services',
            'title' => [
                'ar' => 'خدمات متكاملة تلبي مشروعك',
                'en' => 'Comprehensive Services for Your Project',
            ],
            'subtitle' => [
                'ar' => 'مجموعة متنوعة من الخدمات المالية والمحاسبية المصممة لدعم نمو أعمالك',
                'en' => 'A variety of financial and accounting services designed to support your business growth',
            ],
            'order' => 4,
            'is_active' => true,
            'settings' => ['badge' => 'الخدمات'],
        ]);

        $services = [
            [
                'title' => ['ar' => 'دراسة جدوى', 'en' => 'Feasibility Study'],
                'description' => ['ar' => 'دراسة حجم السوق، أنواع المصاد، سلوكهم، والمنافسين فاختناقهم وفرص النمو والتهديدات', 'en' => 'Study market size, competitors, and growth opportunities'],
                'color' => 'teal',
            ],
            [
                'title' => ['ar' => 'تحليل سوق', 'en' => 'Market Analysis'],
                'description' => ['ar' => 'دراسة حجم السوق. أنواع المصادر إ الطلب، والمنافسين فختاصهم وفرص النمو والتهديدات', 'en' => 'Study market size and demand analysis'],
                'color' => 'orange',
            ],
            [
                'title' => ['ar' => 'تحليل مالي', 'en' => 'Financial Analysis'],
                'description' => ['ar' => 'تقدير التكاليف والإيرادات والأرباح المتوقعة للمشروع، وحساب مؤشرات مثل نقطة التعادل والعائد على الاستثمار', 'en' => 'Cost and revenue estimation and profitability indicators'],
                'color' => 'pink',
            ],
            [
                'title' => ['ar' => 'إدارة استثمار', 'en' => 'Investment Management'],
                'description' => ['ar' => 'مراقبة وتنظيم قرارات الاستثمار من اختيار الفرص وتوزيع رأس المال حتى مراقبة الأداء وتقليل المخاطر', 'en' => 'Monitor and organize investment decisions'],
                'color' => 'blue',
            ],
            [
                'title' => ['ar' => 'تقييم أعمال', 'en' => 'Business Valuation'],
                'description' => ['ar' => 'فحص أداء ومدى وربحية الشركة لتحديد قيمتها السوقية أو جاهزيتها للاستثمار أو البيع', 'en' => 'Examine performance and profitability to determine market value'],
                'color' => 'green',
            ],
            [
                'title' => ['ar' => 'خطة عمل', 'en' => 'Business Plan'],
                'description' => ['ar' => 'وثيقة توضيح أهداف المشروع، وخطوات التشغيل، والموارد المطلوبة، والخطة المالية والتسويقية لفترة زمنية محددة', 'en' => 'Document outlining project goals and operational steps'],
                'color' => 'purple',
            ],
        ];

        foreach ($services as $index => $service) {
            LandingSectionItem::create([
                'landing_section_id' => $servicesSection->id,
                'title' => $service['title'],
                'description' => $service['description'],
                'link' => '#',
                'link_text' => 'مزيد من المعلومات',
                'order' => $index + 1,
                'is_active' => true,
                'data' => ['color' => $service['color']],
            ]);
        }

        // 5. FAQ Section
        $faqSection = LandingSection::create([
            'landing_page_id' => $landingPage->id,
            'type' => 'faq',
            'title' => [
                'ar' => 'استفسارات متكررة',
                'en' => 'Frequently Asked Questions',
            ],
            'subtitle' => [
                'ar' => 'إجابات على أكثر الأسئلة شيوعاً حول خدماتنا',
                'en' => 'Answers to the most common questions about our services',
            ],
            'order' => 5,
            'is_active' => true,
            'settings' => ['badge' => 'تساؤلات'],
        ]);

        $faqs = [
            [
                'title' => ['ar' => 'كيف يمكنني حجز استشارة؟', 'en' => 'How can I book a consultation?'],
                'description' => ['ar' => 'يمكنك تصميم المستشارين المتاحين، اختيار المستشار المناسب، ثم تحديد نوع الاستشارة (فيديو/صوت/نص) والوقت المناسب لك. بعد إتمام الدفع الآمن، ستتلقى تأكيداً بالحجز وتفاصيل الاتصال.', 'en' => 'You can browse available consultants, choose the suitable one, then select consultation type and time.'],
            ],
            [
                'title' => ['ar' => 'ما أنواع الخدمات المتاحة؟', 'en' => 'What types of services are available?'],
                'description' => ['ar' => 'نوفر خدمات متنوعة تشمل: تحليل مالي، دراسة جدوى، خطط أعمال، إدارة استثمار، تقييم أعمال وخدمات أخرى', 'en' => 'We provide various services including financial analysis, feasibility studies, business plans, and more.'],
            ],
            [
                'title' => ['ar' => 'كيف يتم فحص المستشارين؟', 'en' => 'How are consultants verified?'],
                'description' => ['ar' => 'جميع المستشارين يخضعون لعملية فحص دقيقة للتأكد من مؤهلاتهم وخبراتهم قبل الموافقة على انضمامهم للمنصة', 'en' => 'All consultants undergo a rigorous vetting process to ensure their qualifications and experience.'],
            ],
            [
                'title' => ['ar' => 'كيف يتلقى المستشارون أرباحهم؟', 'en' => 'How do consultants receive their earnings?'],
                'description' => ['ar' => 'يتلقى المستشارون أرباحهم بشكل آمن ومباشر بعد إتمام الخدمة وتقييمها من قبل العميل', 'en' => 'Consultants receive their earnings securely and directly after service completion and client evaluation.'],
            ],
            [
                'title' => ['ar' => 'هل الاستشارات سرية وآمنة؟', 'en' => 'Are consultations confidential and secure?'],
                'description' => ['ar' => 'نعم، جميع الاستشارات والبيانات محمية بأعلى معايير الأمان والخصوصية', 'en' => 'Yes, all consultations and data are protected with the highest security and privacy standards.'],
            ],
        ];

        foreach ($faqs as $index => $faq) {
            LandingSectionItem::create([
                'landing_section_id' => $faqSection->id,
                'title' => $faq['title'],
                'description' => $faq['description'],
                'order' => $index + 1,
                'is_active' => true,
            ]);
        }

        // 6. Mobile App Section
        $mobileAppSection = LandingSection::create([
            'landing_page_id' => $landingPage->id,
            'type' => 'mobile_app',
            'title' => [
                'ar' => 'انضم كمستشار واستفد من منصتنا',
                'en' => 'Join as a Consultant and Benefit from Our Platform',
            ],
            'subtitle' => [
                'ar' => 'وسّع قاعدة عملائك وزد دخلك من خلال تقديم خدماتك الاستشارية عبر منصتنا الاحترافية',
                'en' => 'Expand your client base and increase your income by providing consulting services through our professional platform',
            ],
            'order' => 6,
            'is_active' => true,
            'settings' => ['badge' => 'التطبيق'],
        ]);

        // 7. CTA Section
        $ctaSection = LandingSection::create([
            'landing_page_id' => $landingPage->id,
            'type' => 'cta',
            'title' => [
                'ar' => 'ابدأ رحلتك نحو النجاح اليوم',
                'en' => 'Start Your Journey to Success Today',
            ],
            'subtitle' => [
                 'ar' => 'انضم إلى آلاف رواد الأعمال الذين اتخذوا قراراتهم بثقة',
                'en' => 'Join thousands of entrepreneurs who made their decisions confidently',
            ],
            'order' => 7,
            'is_active' => true,
        ]);

        LandingSectionItem::create([
            'landing_section_id' => $ctaSection->id,
            'title' => ['ar' => 'ابدأ الآن', 'en' => 'Start Now'],
            'link' => '/register',
            'order' => 1,
            'is_active' => true,
            'data' => ['variant' => 'primary'],
        ]);

        LandingSectionItem::create([
            'landing_section_id' => $ctaSection->id,
            'title' => ['ar' => 'تواصل معنا', 'en' => 'Contact Us'],
            'link' => '/contact',
            'order' => 2,
            'is_active' => true,
            'data' => ['variant' => 'secondary'],
        ]);
    }
}
