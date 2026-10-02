<?php

namespace App\Controller;

use App\Entity\Page;
use App\Entity\Product;
use App\Repository\FaqItemRepository;
use App\Repository\BookingRepository;
use App\Repository\PageRepository;
use App\Repository\ProductRepository;
use App\Service\RentalPeriodFactory;
use App\Service\RentalPricing;
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
        private readonly RentalPeriodFactory $rentalPeriods,
        private readonly RentalPricing $rentalPricing,
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

        // The legal terms are maintained in code so an older CMS record cannot
        // silently override the current wording or its dedicated presentation.
        $page = $slug === 'pages/suncamel-terms-and-conditions'
            ? $this->buildFallbackTermsPage()
            : ($pages->findOneBy(['slug' => $slug, 'isPublished' => true]) ?? $this->buildFallbackPage($slug));

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
                'type' => 'legal',
                'data' => [
                    'title' => 'Rental terms',
                    'html' => <<<'HTML'
<p>By booking or using a SunCamel e-bike, you agree to the terms below.</p>
<h3>1. Business details</h3>
<p>SunCamel Limited trades as SunCamel in Raglan, New Zealand. You can contact us at <a href="mailto:guilhem.camel@gmail.com">guilhem.camel@gmail.com</a> or on <a href="tel:+64275645263">+64 27 564 5263</a>.</p>
<h3>2. Eligibility and rider requirements</h3>
<ul>
<li>The minimum age to hire an e-bike is 18 years.</li>
<li>Every rider must present a valid driver licence at the time of hire.</li>
<li>A helmet is provided and must be worn at all times while riding.</li>
<li>Only one rider is permitted per bike. Passengers, child seats and trailers are not permitted.</li>
<li>SunCamel may refuse hire to anyone it reasonably considers unfit to ride safely.</li>
</ul>
<h3>3. E-bike types and legal use</h3>
<p>All e-bikes supplied by SunCamel are represented as compliant with applicable New Zealand requirements for road-legal e-bikes and use in public spaces. The rider is responsible for understanding and following all applicable New Zealand laws and regulations, riding only where legally permitted, and paying any fine or penalty or meeting any other legal consequence resulting from misuse.</p>
<h3>4. Pickup, return and late charges</h3>
<p>The bike must be collected or received at the agreed time and returned at the scheduled time and location. If you expect to be late, contact us as soon as possible on <a href="tel:+64275645263">027 564 5263</a>. Otherwise, the following late charges apply:</p>
<ul>
<li>Hourly: NZ$45 per hour</li>
<li>Half day: NZ$80</li>
<li>Full day: NZ$120</li>
<li>Two days: NZ$200</li>
<li>One week: NZ$500</li>
</ul>
<h3>5. Use of e-bikes</h3>
<p>Permitted riding areas include sealed roads, gravel roads, cycling tracks and beach access from Kitesurf Beach only. The bikes are not suitable for mountain bike parks. Beach riding should take place around low tide, on firm sand only. Bikes must be walked through soft sand, must never enter salt water, and must not access the beach via Ngarunui Beach.</p>
<p>Unless SunCamel agrees otherwise in writing, reckless or dangerous riding, riding under the influence of alcohol or drugs, riding in unsafe weather or hazardous conditions, racing, stunts and commercial use are strictly prohibited.</p>
<h3>6. Payments and security deposit</h3>
<p>Full payment is required to confirm a booking. We accept online payments and cash. A NZ$350 security deposit per bike is taken at the time of booking. It will be refunded when the bike and all accessories are returned and inspected in the same condition as when hired, except for fair wear and tear.</p>
<h3>7. Damage, loss and theft</h3>
<p>The customer is responsible for the e-bike and all accessories throughout the hire period. The bike must be secured with the supplied lock whenever unattended. The customer is liable for damage to the bike, theft or loss of the bike, and lost keys, chargers or accessories, up to a maximum liability of NZ$5,000. Repair or replacement costs may be deducted from the security deposit, and any additional amount remains payable by the customer.</p>
<p>Notify SunCamel immediately of any accident, breakdown, theft, loss or damage. Stop using the bike if it becomes unsafe and call <a href="tel:+64275645263">+64 27 564 5263</a> for assistance. In an emergency, contact the appropriate emergency service first.</p>
<h3>8. Safety and briefing</h3>
<p>SunCamel will provide a safety briefing before the hire begins. Customers must follow all instructions given by SunCamel staff and acknowledge that riding an e-bike involves inherent risks.</p>
<h3>9. Cancellations, refunds and no-shows</h3>
<ul>
<li>Cancellations made at least 24 hours before pickup: 100% refund.</li>
<li>Cancellations made between 4 and 24 hours before pickup: 50% refund.</li>
<li>Cancellations made less than 4 hours before pickup: no refund.</li>
<li>No-shows: no refund.</li>
<li>Weather-related cancellations: 100% refund only where the weather is dangerous.</li>
</ul>
<p>SunCamel may cancel a hire because of unsafe conditions or circumstances beyond its reasonable control. If SunCamel cancels, any applicable refund will be communicated to the customer.</p>
<h3>10. Acknowledgement of risk and liability</h3>
<p>By hiring an e-bike, the customer accepts responsibility for their safety, acknowledges the risks associated with e-bike use and agrees to ride at their own risk. To the fullest extent permitted by New Zealand law, SunCamel Limited is not liable for injury, loss, damage or expense arising from use of the e-bike, except to the extent caused by SunCamel's negligence.</p>
<p>Nothing in these terms excludes, restricts or modifies any right or remedy that cannot lawfully be excluded under New Zealand law.</p>
<h3>11. Photography and marketing</h3>
<p>SunCamel may use photos or videos taken during the hire period for marketing and promotional purposes unless the customer asks us not to do so in writing.</p>
<h3>12. Governing law</h3>
<p>These Terms &amp; Conditions are governed by the laws of New Zealand. Any dispute is subject to the exclusive jurisdiction of the New Zealand courts.</p>
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
                'half_day_price' => '$79.00',
                'daily_price' => '$79.00',
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
                'half_day_price' => '$79.00',
                'daily_price' => '$79.00',
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
                'half_day_price' => '$79.00',
                'daily_price' => '$79.00',
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
        $selectedPeriod = null;
        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null && $request->query->has('pickup') && $request->query->has('return')) {
            try {
                $period = $this->rentalPeriods->fromInput(
                    (string) $request->query->get('pickup'),
                    (string) $request->query->get('return'),
                    (string) $request->query->get('duration', 'full_day'),
                    (int) $request->query->get('pickup_time', 8),
                    (int) $request->query->get('return_time', 16),
                );
                $selectedPeriod = $period;
                $availability = max(0, $availability - $this->bookings->reservedQuantity($product, $period->pickup, $period->return));
            } catch (\Exception) {
                $availability = 0;
            }
        }

        $displayPrice = $selectedPeriod === null
            ? ['total_amount' => $product->getHalfDayAmount(), 'label' => 'half day']
            : $this->rentalPricing->calculate($product, $selectedPeriod);

        return [
            'slug' => $product->getSlug(),
            'title' => $product->getTitle(),
            'tagline' => $product->getTagline(),
            'summary' => $product->getSummary(),
            'price' => 'NZ$' . number_format($displayPrice['total_amount'] / 100, 2),
            'half_day_price' => 'NZ$' . number_format($product->getHalfDayAmount() / 100, 2),
            'daily_price' => 'NZ$' . number_format($product->getFullDayAmount() / 100, 2),
            'duration' => $displayPrice['label'],
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
