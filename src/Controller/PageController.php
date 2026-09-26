<?php

namespace App\Controller;

use App\Entity\Page;
use App\Entity\Product;
use App\Repository\FaqItemRepository;
use App\Repository\BookingRepository;
use App\Repository\PageRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly FaqItemRepository $faqItems,
        private readonly BookingRepository $bookings,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[Route('/', name: 'home')]
    public function home(PageRepository $pages): Response
    {
        $page = $pages->findOneBy(['slug' => 'home', 'isPublished' => true]) ?? $this->buildFallbackHome();

        return $this->render('page/show.html.twig', [
            'page' => $page,
            'breadcrumbs' => [],
        ]);
    }

    #[Route('/{slug}', name: 'page_show', requirements: ['slug' => '.+'])]
    public function show(string $slug, PageRepository $pages, Request $request): Response
    {
        if ($slug === 'collections/all' && ($request->query->has('pickup') || $request->query->has('return'))) {
            $this->denyAccessUnlessGranted('ROLE_USER');
        }

        $page = $pages->findOneBy(['slug' => $slug, 'isPublished' => true]) ?? $this->buildFallbackPage($slug);

        if ($page === null) {
            throw $this->createNotFoundException('Page not found.');
        }

        return $this->render('page/show.html.twig', [
            'page' => $page,
            'breadcrumbs' => $this->buildBreadcrumbs($page),
        ]);
    }

    /**
     * @return list<array{label: string, url: ?string}>
     */
    private function buildBreadcrumbs(Page $page): array
    {
        $breadcrumbs = [
            ['label' => 'Home', 'url' => '/'],
        ];

        if (str_starts_with($page->getSlug(), 'products/')) {
            $breadcrumbs[] = ['label' => 'Our Rental Bikes', 'url' => '/collections/all'];
        } elseif (str_starts_with($page->getSlug(), 'collections/') && $page->getSlug() !== 'collections/all') {
            $breadcrumbs[] = ['label' => 'Our Rental Bikes', 'url' => '/collections/all'];
        }

        $breadcrumbs[] = ['label' => $page->getTitle(), 'url' => null];

        return $breadcrumbs;
    }

    private function buildFallbackHome(): Page
    {
        $page = (new Page())
            ->setSlug('home')
            ->setTitle('SunCamel')
            ->setIsPublished(true)
            ->setPublishedAt(new \DateTimeImmutable());

        $page->setMeta([
            'title' => 'SunCamel',
            'description' => 'Rent a bike. Explore Raglan.',
            'og_image' => '/images/suncamel/shared/og-image.png',
        ]);

        $page->setBlocks([
            [
                'type' => 'hero',
                'data' => [
                    'title_html' => 'Rent a bike.<br>Explore Raglan.',
                    'subtitle_html' => 'Experience Raglan at your own pace<br>on SunCamel premium e-bikes.',
                    'primary' => ['label' => 'View our rental bikes', 'url' => '/collections/all'],
                    'secondary' => ['label' => 'Get in touch', 'url' => '/pages/contact-us'],
                    'image' => '/images/suncamel/home/hero.jpg',
                ],
            ],
            [
                'type' => 'text',
                'data' => [
                    'title' => 'Cruise Raglan with a SunCamel E-Bike',
                    'html' => 'We\'ve got a range of quality e-bikes to rent, perfect for checking out the surf, sunsets, and beaches.<br>Have a look at our bikes below and <b><u><a href="/collections/all">book online</a></u></b> or text us on <b><a href="tel:0275645263">027 564 5263</a></b> - it\'s that simple.',
                    'color_set' => '1',
                ],
            ],
            [
                'type' => 'collections',
                'data' => [
                    'items' => [
                        [
                            'title' => 'Our Rental Bikes',
                            'url' => '/collections/all',
                            'image' => '/images/suncamel/home/collection-all.jpg',
                        ],
                        [
                            'title' => 'Adventure Bikes',
                            'url' => '/collections/adventure-bikes',
                            'image' => '/images/suncamel/home/collection-adventure.jpg',
                        ],
                        [
                            'title' => 'Cruiser Bikes',
                            'url' => '/collections/cruiser-e-bikes',
                            'image' => '/images/suncamel/home/collection-cruiser.png',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'text',
                'data' => [
                    'title' => 'Have a question?',
                    'html' => 'Check out our <b><a href="/pages/faq">FAQ\'s</a></b> or give us a call on <a href="tel:0275645263"><b>027 564 5263</b></a>.',
                    'color_set' => '1',
                ],
            ],
            [
                'type' => 'testimonials',
                'data' => [
                    'title' => 'What people <br>say about us',
                    'color_set' => '3',
                    'items' => [
                        [
                            'quote' => 'So much fun!! Me and my partner took SunCamel e-bikes for a ride out to Manu Bay, enjoyed a picnic, watched the sunset and surfers. Highly recommend, staff were super friendly and helpful - thanks for the great evening!',
                            'author' => 'Kenny Cochrane',
                            'date' => '9.12.2025',
                        ],
                        [
                            'quote' => 'We were visiting for the weekend from Auckland, and had a 2x bikes delivered to our airbnb. It was super easy to book online, and the staff made everything really hassel free. We had lots of fun on the bikes. Highly recommend. Thank you!',
                            'author' => 'Ashley de Lotz',
                            'date' => '15.12.2025',
                        ],
                        [
                            'quote' => 'The team were fantastic, offering insightful advice on which bike would best suit my needs and planned routes. Highly recommend. Thank you Suncamel.',
                            'author' => 'Adrian Vajas',
                            'date' => '1.12.2025',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'accordion',
                'data' => [
                    'title' => "FAQ's",
                    'description_html' => 'A few of the questions we get asked a lot.<br><b><a href="/pages/faq">Click here to see all our FAQ\'s.</a></b>',
                    'color_set' => '5',
                    'items' => $this->getHomeFaqItems(),
                ],
            ],
            [
                'type' => 'text',
                'data' => [
                    'html' => '<p><u><a href="/pages/faq">CLICK&nbsp;HERE&nbsp;TO&nbsp;VIEW&nbsp;ALL&nbsp;OUR&nbsp;FAQ\'S</a></u></p>',
                    'color_set' => '5',
                ],
            ],
            [
                'type' => 'cta',
                'data' => [
                    'title' => 'Ready to explore Raglan?',
                    'primary' => ['label' => 'Book your E-bike >', 'url' => '/collections/all'],
                    'image' => '/images/suncamel/shared/cta-bike.jpg',
                    'color_set' => '4',
                ],
            ],
            [
                'type' => 'image-gallery',
                'data' => [
                    'title' => 'Experience <br>eco-friendly adventures with SunCamel e-bikes.',
                    'color_set' => '5',
                    'images' => [
                        '/images/suncamel/home/gallery-1.png',
                        '/images/suncamel/home/gallery-2.jpg',
                        '/images/suncamel/home/gallery-3.jpg',
                        '/images/suncamel/home/gallery-4.jpg',
                    ],
                ],
            ],
        ]);

        return $page;
    }

    private function buildFallbackPage(string $slug): ?Page
    {
        if ($slug === 'collections/all') {
            return $this->buildFallbackCollection();
        }

        if ($slug === 'collections/adventure-bikes') {
            return $this->buildFallbackCollection(
                'collections/adventure-bikes',
                'Adventure Bikes',
                'High-powered e-bikes for longer routes and mixed terrain.',
                ['sportracer', 'adventurer-e-bike'],
            );
        }

        if ($slug === 'collections/cruiser-e-bikes') {
            return $this->buildFallbackCollection(
                'collections/cruiser-e-bikes',
                'Cruiser E-Bikes',
                'Comfortable e-bikes for relaxed coastal rides.',
                ['cruiser-e-bike'],
            );
        }

        if (str_starts_with($slug, 'products/')) {
            $productSlug = substr($slug, strlen('products/'));
            return $this->buildFallbackProduct($productSlug);
        }

        if ($slug === 'pages/faq') {
            return $this->buildFallbackFaqPage();
        }

        if ($slug === 'pages/contact-us') {
            return $this->buildFallbackContactPage();
        }

        if ($slug === 'pages/suncamel-terms-and-conditions') {
            return $this->buildFallbackTermsPage();
        }

        return null;
    }

    private function buildFallbackTermsPage(): Page
    {
        $page = (new Page())
            ->setSlug('pages/suncamel-terms-and-conditions')
            ->setTitle('Terms & Conditions')
            ->setIsPublished(true)
            ->setPublishedAt(new \DateTimeImmutable());

        $page->setMeta([
            'title' => 'Terms & Conditions | SunCamel',
            'description' => 'Read the terms and conditions that apply to SunCamel e-bike rentals, bookings, cancellations and rider responsibilities.',
            'og_image' => '/images/suncamel/shared/og-image.png',
        ]);

        $page->setBlocks([
            [
                'type' => 'title',
                'data' => [
                    'title' => 'Terms & Conditions',
                    'eyebrow' => 'SunCamel rentals',
                    'image' => '/images/suncamel/products/collection-hero.png',
                ],
            ],
            [
                'type' => 'text',
                'data' => [
                    'title' => 'Rental terms',
                    'html' => <<<'HTML'
<p>By booking or using a SunCamel e-bike, you agree to the terms below.</p>
<h3>1. Rider eligibility</h3>
<p>Riders must be at least 18 years old and confident riding a regular bicycle. A valid driver licence is required when booking an e-bike over 300W.</p>
<h3>2. Booking and payment</h3>
<p>Full payment is required to confirm a booking. A NZ$350 security deposit is required for each bike and is returned after the bike has been returned and inspected, subject to these terms.</p>
<h3>3. Changes and cancellations</h3>
<p>You may change or cancel your booking up to 10 hours before the scheduled pickup time. Refunds are provided in accordance with this cancellation period. Please contact us as soon as possible if your plans change.</p>
<h3>4. Pickup, delivery and return</h3>
<p>The bike must be collected or received at the agreed time and returned at the scheduled time and location. A late fee of NZ$50 per hour applies after the first hour past the scheduled return time.</p>
<h3>5. Safe and lawful use</h3>
<p>You must follow New Zealand road rules, wear the supplied helmet, use the bike responsibly and follow all safety instructions provided by SunCamel. You must not ride while impaired or allow another person to use the bike without our approval.</p>
<h3>6. Beach riding</h3>
<p>Beach access is permitted only via Kitesurf Beach and around low tide. Bikes must be ridden on firm sand only, walked through soft sand and never taken into salt water. Entry via Ngarunui Beach is not permitted.</p>
<h3>7. Care, loss and damage</h3>
<p>You are responsible for the bike and supplied equipment during the rental. Secure the bike with the supplied lock whenever it is unattended and notify us immediately of any accident, breakdown, theft, loss or damage. Costs resulting from loss, theft, misuse or damage beyond normal wear may be deducted from the security deposit, without limiting any further amount reasonably due.</p>
<h3>8. Breakdowns and emergencies</h3>
<p>Stop using the bike if it becomes unsafe. Call us promptly on <a href="tel:+64275645263">+64 27 564 5263</a> so we can assist. In an emergency, contact the appropriate emergency service first.</p>
<h3>9. Contact</h3>
<p>Questions about these terms can be sent to <a href="mailto:guilhem.camel@gmail.com">guilhem.camel@gmail.com</a> or discussed by phone on <a href="tel:+64275645263">+64 27 564 5263</a>.</p>
HTML,
                    'color_set' => '1',
                ],
            ],
        ]);

        return $page;
    }

    private function buildFallbackFaqPage(): Page
    {
        $page = (new Page())
            ->setSlug('pages/faq')
            ->setTitle('FAQ')
            ->setIsPublished(true)
            ->setPublishedAt(new \DateTimeImmutable());

        $page->setMeta([
            'title' => 'FAQs - all the info you need | SunCamel',
            'description' => 'Find answers to all your questions about our e-bikes - from rentals and rules to routes and safety tips. Everything you need to know in one place.',
            'og_image' => '/images/suncamel/shared/og-image.png',
        ]);

        $managedFaqBlocks = $this->getManagedFaqBlocks();
        if ($managedFaqBlocks !== []) {
            $page->setBlocks(array_merge([
                [
                    'type' => 'title',
                    'data' => [
                        'title' => 'FAQs - all the info you need',
                        'eyebrow' => 'FAQ',
                        'image' => '/images/suncamel/faq/hero.png',
                    ],
                ],
                [
                    'type' => 'text',
                    'data' => [
                        'html' => '<p>Browse below to find answers to all our commonly asked questions about our e-bikes - from rentals and safety rules to routes and tips.<br>Everything you need to know is here in one place.</p><p>Still have questions? Give us a call at <a href="tel:0275645263">027 564 5263</a>.</p>',
                    ],
                ],
            ], $managedFaqBlocks, [
                [
                    'type' => 'cta',
                    'data' => [
                        'title' => 'Ready to book your ride?',
                        'primary' => ['label' => 'View rental bikes', 'url' => '/collections/all'],
                        'image' => '/images/suncamel/shared/cta-bike.jpg',
                    ],
                ],
            ]));

            return $page;
        }

        $page->setBlocks([
            [
                'type' => 'title',
                'data' => [
                    'title' => 'FAQs - all the info you need',
                    'eyebrow' => 'FAQ',
                    'image' => '/images/suncamel/faq/hero.png',
                ],
            ],
            [
                'type' => 'text',
                'data' => [
                    'html' => '<p>Browse below to find answers to all our commonly asked questions about our e-bikes - from rentals and safety rules to routes and tips.<br>Everything you need to know is here in one place.</p><p>Still have questions? Give us a call at <a href="tel:0275645263">027 564 5263</a>.</p>',
                ],
            ],
            [
                'type' => 'accordion',
                'data' => [
                    'title' => 'General Questions',
                    'items' => [
                        [
                            'question' => 'What types of e-bikes do you have?',
                            'answer' => 'Our e-bike range includes the Cruiser, the Sportracer, and the Adventurer, giving you options suited to different riding styles and power needs.',
                        ],
                        [
                            'question' => 'Do I need prior experience to ride an e-bike?',
                            'answer' => 'No, our e-bikes are easy to use and suitable for beginners.',
                        ],
                        [
                            'question' => 'How old do I need to be to rent an e-bike?',
                            'answer' => 'You must be at least 18 years old.',
                        ],
                        [
                            'question' => 'Do I need to know how to ride a regular bicycle first?',
                            'answer' => 'Yes. For safety reasons, all riders must be comfortable riding a regular bicycle.',
                        ],
                        [
                            'question' => 'What is included with the rental?',
                            'answer' => 'Your rental comes with a helmet, a lock, and a quick rundown on how to operate the e-bike.',
                        ],
                        [
                            'question' => 'Who do I contact in case of a problem or emergency?',
                            'answer_html' => '<p>Please call <strong><a href="tel:0275645263">027 564 5263</a></strong>.</p>',
                        ],
                        [
                            'question' => 'What are the road rules?',
                            'answer_html' => '<p>The <strong>Cruiser &amp; Adventurer</strong> follow the legislation corresponding to e-bikes and are treated just like a regular bike, so normal bike rules apply.</p>',
                        ],
                        [
                            'question' => 'What do you recommend exploring in Raglan?',
                            'answer' => 'We suggest taking the scenic route from Raglan to Manu Bay, with a stop at Ngarunui Beach along the way. If you are feeling adventurous, you can continue all the way to Te Toto Gorge on the Adventurer e-bike.',
                        ],
                        [
                            'question' => 'Can I take my bike on the beach?',
                            'answer_html' => '<p>Yes, you can take your e-bike on the beach but only if you enter and exit at <a href="https://maps.app.goo.gl/yHSTcReSSadFaYDf8" target="_blank" rel="noopener noreferrer">Kitesurf Beach</a>.</p><p>You <strong>cannot</strong> enter via Ngarunui Beach entrance.</p><p>We recommend you access the beach on either side of the low tide.</p><p>Please do not ride the bike in salt water.</p><p>Ride the bike in hard sand only.</p><p>Walk alongside the bike in soft sand.</p>',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'accordion',
                'data' => [
                    'title' => 'Booking and payment',
                    'items' => [
                        [
                            'question' => 'What are the Terms and Conditions?',
                            'answer_html' => '<p><strong><a href="/pages/suncamel-terms-and-conditions">Click here</a></strong> to view our full terms and conditions.</p>',
                        ],
                        [
                            'question' => 'How do I make a booking?',
                            'answer_html' => '<p>Simply <a href="/collections/all">click here</a> to make a booking online, or call us on <a href="tel:0275645263">027 564 5263</a>. You will be emailed instructions for pickup or delivery.</p><p>You will need a valid driver&apos;s licence to book an e-bike.</p>',
                        ],
                        [
                            'question' => 'Do you accept walk-ins or do I need to book in advance?',
                            'answer_html' => '<p>We recommend booking in advance to secure availability, but walk-ins are welcome. Please <a href="/pages/contact-us">give us a call</a> to make sure we are home.</p><p><strong>Opening hours:</strong><br>10am-4pm 7 days a week.</p><p><strong>Find us:</strong><br>58 Wainui Road, Raglan.</p>',
                        ],
                        [
                            'question' => 'What payment methods do you accept?',
                            'answer_html' => '<p>We can securely take debit and credit cards when booking online, or cash if you are picking up directly from us.</p><p><strong>Please note:</strong> Full payment is required to complete your booking, and a $350 security deposit is required for each bike.</p>',
                        ],
                        [
                            'question' => 'Is a deposit required?',
                            'answer' => 'Yes, a $350 security deposit is needed for all bookings. This will be returned to you on completion of your booking.',
                        ],
                        [
                            'question' => 'Can I change or cancel my booking?',
                            'answer' => 'No worries - you can make changes or cancel up to 10 hours before your booking.',
                        ],
                        [
                            'question' => 'What is your refund policy?',
                            'answer' => 'Refunds are available according to our cancellation guidelines.',
                        ],
                        [
                            'question' => 'Is there a surcharge for late drop-off?',
                            'answer' => 'Yes, there is a $50 fee for each hour after the first hour past your scheduled return time.',
                        ],
                        [
                            'question' => 'How long will it take to get my deposit back?',
                            'answer' => 'Your bond will be returned after the e-bike has been picked up from you or dropped back at our storage location.',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'accordion',
                'data' => [
                    'title' => 'Pick up and return',
                    'items' => [
                        [
                            'question' => 'Where do I pick up the e-bike?',
                            'answer' => 'We can deliver your e-bike to your address, or you can meet us at our local location or one of the designated spots listed on our website.',
                        ],
                        [
                            'question' => 'What do I need to bring when I pick up the bike?',
                            'answer' => 'For e-bikes over 300W, just bring your valid driver\'s licence.',
                        ],
                        [
                            'question' => 'What are your opening hours?',
                            'answer' => '10am-4pm 7 days a week.',
                        ],
                        [
                            'question' => 'Do you offer delivery or pickup at holiday accommodation / locations?',
                            'answer' => 'Yes. We can deliver at 8am and pick up at 8pm, and we are happy to arrange times with you as needed, all without extra charge.',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'cta',
                'data' => [
                    'title' => 'Ready to book your ride?',
                    'primary' => ['label' => 'View rental bikes', 'url' => '/collections/all'],
                    'image' => '/images/suncamel/shared/cta-bike.jpg',
                ],
            ],
        ]);

        return $page;
    }

    private function getManagedFaqBlocks(): array
    {
        $items = $this->faqItems->findActiveOrdered();

        if ($items === []) {
            return [];
        }

        $groups = [];
        foreach ($items as $item) {
            $groups[$item->getCategory()][] = [
                'question' => $item->getQuestion(),
                'answer_html' => $item->getAnswerHtml(),
            ];
        }

        $blocks = [];
        foreach ($groups as $category => $questions) {
            $blocks[] = [
                'type' => 'accordion',
                'data' => [
                    'title' => $category,
                    'items' => $questions,
                ],
            ];
        }

        return $blocks;
    }

    private function getHomeFaqItems(): array
    {
        $items = $this->faqItems->findActiveOrdered();

        if ($items !== []) {
            shuffle($items);

            return array_map(static fn ($item) => [
                'question' => $item->getQuestion(),
                'answer_html' => $item->getAnswerHtml(),
            ], array_slice($items, 0, 5));
        }

        return [
            [
                'question' => 'What do you recommend exploring in Raglan?',
                'answer' => 'We suggest taking the scenic route from Raglan to Manu Bay, with a stop at Ngarunui Beach along the way. If you\'re feeling adventurous, you can continue all the way to Te Toto Gorge on the Adventurer e-bike.',
            ],
            [
                'question' => 'Do I need prior experience to ride an e-bike?',
                'answer' => 'No, our e-bikes are easy to use and suitable for beginners.',
            ],
            [
                'question' => 'How old do I need to be to rent an e-bike?',
                'answer' => 'You must be at least 18 years old.',
            ],
            [
                'question' => 'What is included with the rental?',
                'answer' => 'Your rental comes with a helmet, a lock, and a quick rundown on how to operate the e-bike.',
            ],
            [
                'question' => 'What are the rules?',
                'answer' => 'The Adventurer and Sportracer need to follow the same road rules as cars because they\'re over 300W. The Cruiser is treated just like a regular bike, so normal bike rules apply.',
            ],
        ];
    }

    private function buildFallbackContactPage(): Page
    {
        $page = (new Page())
            ->setSlug('pages/contact-us')
            ->setTitle('Contact Us')
            ->setIsPublished(true)
            ->setPublishedAt(new \DateTimeImmutable());

        $page->setMeta([
            'title' => 'Contact us and visit us | SunCamel',
            'description' => 'Find our location and get all the details you need to contact us easily. We are here to help with any questions or bookings.',
            'og_image' => '/images/suncamel/shared/og-image.png',
        ]);

        $page->setBlocks([
            [
                'type' => 'title',
                'data' => [
                    'title' => 'Contact Us',
                    'eyebrow' => 'Contact',
                    'image' => '/images/suncamel/contact/hero.jpg',
                ],
            ],
            [
                'type' => 'columns',
                'data' => [
                    'items' => [
                        [
                            'title' => 'Phone',
                            'html' => '<p>Need something sorted quickly? Give us a call - perfect for urgent enquiries.</p>',
                            'action' => [
                                'label' => '+64 27 564 5263',
                                'url' => 'tel:+64275645263',
                            ],
                        ],
                        [
                            'title' => 'Find Us',
                            'html' => '<p>We are open 10am- 4 pm, 7 days a week.</p><p>Drop in and see us at:<br>58 Wainui Road, Raglan, New Zealand</p>',
                            'action' => [
                                'label' => 'Click here for Google Maps link',
                                'url' => 'https://maps.app.goo.gl/HG7XQXgEFCTv4VNd6',
                            ],
                        ],
                        [
                            'title' => 'Email',
                            'html' => '<p>Have questions? Flick us an email and we will be back in touch within 48 hours.</p>',
                            'action' => [
                                'label' => 'guilhem.camel@gmail.com',
                                'url' => 'mailto:guilhem.camel@gmail.com',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        return $page;
    }

    /**
     * @param list<string>|null $productSlugs
     */
    private function buildFallbackCollection(
        string $slug = 'collections/all',
        string $title = 'Our Rental Bikes',
        string $description = 'Explore our full range of e-bikes, from easy-going Cruisers to high-powered Adventure models.',
        ?array $productSlugs = null,
    ): Page
    {
        $catalog = $this->getProductCatalog();
        $products = $productSlugs === null
            ? array_values($catalog)
            : array_values(array_intersect_key($catalog, array_flip($productSlugs)));
        $products = array_values(array_filter(
            $products,
            static fn (array $product) => ($product['availability'] ?? 0) > 0,
        ));

        $page = (new Page())
            ->setSlug($slug)
            ->setTitle($title)
            ->setIsPublished(true)
            ->setPublishedAt(new \DateTimeImmutable());

        $page->setMeta([
            'title' => $title . ' | SunCamel',
            'description' => $description,
            'og_image' => '/images/suncamel/shared/og-image.png',
        ]);

        $page->setBlocks([
            [
                'type' => 'title',
                'data' => [
                    'title' => $title,
                    'subtitle' => 'Choose your ride and book online in minutes.',
                    'image' => '/images/suncamel/products/collection-hero.png',
                ],
            ],
            [
                'type' => 'product-list',
                'data' => [
                    'items' => $products,
                ],
            ],
            [
                'type' => 'text',
                'data' => [
                    'title' => 'Need help choosing?',
                    'html' => 'Tell us where you want to ride and we will match you with the perfect bike.',
                ],
            ],
            [
                'type' => 'cta',
                'data' => [
                    'title' => 'Ready to roll?',
                    'primary' => ['label' => 'Book your E-bike', 'url' => '/collections/all'],
                    'image' => '/images/suncamel/shared/cta-bike.jpg',
                ],
            ],
        ]);

        return $page;
    }

    private function buildFallbackProduct(string $slug): ?Page
    {
        $catalog = $this->getProductCatalog();
        $product = $catalog[$slug] ?? null;

        if ($product === null) {
            return null;
        }

        $page = (new Page())
            ->setSlug('products/' . $slug)
            ->setTitle($product['title'])
            ->setIsPublished(true)
            ->setPublishedAt(new \DateTimeImmutable());

        $page->setMeta([
            'title' => $product['title'] . ' | SunCamel',
            'description' => $product['summary'],
            'og_image' => $product['image'],
        ]);

        $page->setBlocks([
            [
                'type' => 'title',
                'data' => [
                    'title' => $product['title'],
                    'subtitle' => $product['tagline'],
                    'image' => $product['hero_image'],
                ],
            ],
            [
                'type' => 'product-detail',
                'data' => $product,
            ],
            [
                'type' => 'accordion',
                'data' => [
                    'title' => 'Rental FAQ',
                    'items' => [
                        [
                            'question' => 'What is included with the rental?',
                            'answer' => 'Helmet, lock, and a quick setup walkthrough are included with every booking.',
                        ],
                        [
                            'question' => 'Can you deliver to my accommodation?',
                            'answer' => 'Yes, local Airbnb delivery is available. Choose delivery during booking.',
                        ],
                        [
                            'question' => 'Do I need experience to ride an e-bike?',
                            'answer' => 'No. We will show you how everything works and recommend a route.',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'collections',
                'data' => [
                    'items' => array_map(static fn (array $item) => [
                        'title' => $item['title'],
                        'url' => $item['url'],
                        'image' => $item['image'],
                    ], $catalog),
                ],
            ],
        ]);

        return $page;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getProductCatalog(): array
    {
        $managedProducts = $this->products->findActiveOrdered();

        if ($managedProducts !== []) {
            $catalog = [];
            foreach ($managedProducts as $product) {
                $catalog[$product->getSlug()] = $this->normalizeProduct($product);
            }

            return $catalog;
        }

        return [
            'cruiser-e-bike' => [
                'slug' => 'cruiser-e-bike',
                'title' => 'Cruiser E-Bike',
                'tagline' => 'Easy-going comfort for beachfront rides and relaxed cruising.',
                'summary' => 'A smooth, upright ride built for comfort. Ideal for beach runs, cafes, and sunset loops.',
                'price' => '$79.00',
                'duration' => '4 hours',
                'availability' => 1,
                'url' => '/products/cruiser-e-bike',
                'image' => '/images/suncamel/products/cruiser-cover.png',
                'hero_image' => '/images/suncamel/products/cruiser-hero.jpg',
                'gallery' => [
                    '/images/suncamel/products/cruiser-gallery-1.png',
                    '/images/suncamel/products/cruiser-gallery-2.jpg',
                ],
                'highlights' => [
                    'Step-through frame for easy on/off',
                    'Comfort saddle and upright posture',
                    'Ideal for flat and coastal routes',
                ],
                'specs' => [
                    'Range up to 60 km',
                    'Assisted top speed 32 km/h',
                    'Hydraulic disc brakes',
                    '7-speed drivetrain',
                ],
                'cta' => [
                    'label' => 'Book this bike',
                    'url' => '/collections/all',
                ],
            ],
            'sportracer' => [
                'slug' => 'sportracer',
                'title' => 'Sportracer',
                'tagline' => 'Lightweight speed with extra torque for longer loops.',
                'summary' => 'A sport-focused e-bike designed for longer rides and a more dynamic feel.',
                'price' => '$79.00',
                'duration' => '4 hours',
                'availability' => 1,
                'url' => '/products/sportracer',
                'image' => '/images/suncamel/products/sportracer-cover.jpeg',
                'hero_image' => '/images/suncamel/products/sportracer-hero.png',
                'gallery' => [
                    '/images/suncamel/products/sportracer-gallery-1.png',
                    '/images/suncamel/products/sportracer-gallery-2.jpg',
                ],
                'highlights' => [
                    'Responsive geometry for longer rides',
                    'Extra torque for rolling hills',
                    'Balanced for comfort and speed',
                ],
                'specs' => [
                    'Range up to 80 km',
                    'Assisted top speed 32 km/h',
                    'Hydraulic disc brakes',
                    'Integrated lights',
                ],
                'cta' => [
                    'label' => 'Book this bike',
                    'url' => '/collections/all',
                ],
            ],
            'adventurer-e-bike' => [
                'slug' => 'adventurer-e-bike',
                'title' => 'Adventurer E-bike',
                'tagline' => 'Go further off the beaten track with extra power and grip.',
                'summary' => 'Built for adventure with wider tires and extra stability on mixed terrain.',
                'price' => '$79.00',
                'duration' => '4 hours',
                'availability' => 1,
                'url' => '/products/adventurer-e-bike',
                'image' => '/images/suncamel/products/adventurer-cover.png',
                'hero_image' => '/images/suncamel/products/adventurer-hero.jpg',
                'gallery' => [
                    '/images/suncamel/products/adventurer-gallery-1.png',
                    '/images/suncamel/products/adventurer-gallery-2.jpg',
                ],
                'highlights' => [
                    'Wider tires for gravel and trails',
                    'Strong motor for climbs',
                    'Stable and confidence-inspiring',
                ],
                'specs' => [
                    'Range up to 70 km',
                    'Assisted top speed 32 km/h',
                    'Hydraulic disc brakes',
                    'Front suspension',
                ],
                'cta' => [
                    'label' => 'Book this bike',
                    'url' => '/collections/all',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeProduct(Product $product): array
    {
        $gallery = [];
        foreach ($product->getImages() as $image) {
            if ($image->getImage() !== null && $image->getImage() !== '') {
                $gallery[] = $this->productImagePath($image->getImage());
            }
        }

        $availability = $product->getStockQuantity();
        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null && $request->query->has('pickup') && $request->query->has('return')) {
            try {
                $timezone = new \DateTimeZone('Pacific/Auckland');
                $pickup = new \DateTimeImmutable((string) $request->query->get('pickup') . ' 08:00:00', $timezone);
                $return = new \DateTimeImmutable((string) $request->query->get('return') . ' 12:00:00', $timezone);
                $availability = max(0, $availability - $this->bookings->reservedQuantity($product, $pickup, $return));
            } catch (\Exception) {
                $availability = 0;
            }
        }

        return [
            'slug' => $product->getSlug(),
            'title' => $product->getTitle(),
            'tagline' => $product->getTagline(),
            'summary' => $product->getSummary(),
            'price' => $product->getPrice(),
            'duration' => $product->getDuration(),
            'availability' => $availability,
            'url' => '/products/' . $product->getSlug(),
            'image' => $this->productImagePath($product->getCoverImage()),
            'hero_image' => $this->productImagePath($product->getHeroImage() ?: $product->getCoverImage()),
            'gallery' => $gallery,
            'highlights' => $product->getHighlights(),
            'specs' => $product->getSpecs(),
            'cta' => [
                'label' => 'Book this bike',
                'url' => '/collections/all',
            ],
        ];
    }

    private function productImagePath(?string $image): string
    {
        if ($image === null || $image === '') {
            return '/images/suncamel/products/collection-hero.png';
        }

        if (str_starts_with($image, '/')) {
            return $image;
        }

        return '/images/suncamel/products/' . $image;
    }

}
