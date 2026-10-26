<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\BlogPost;
use App\Models\Contact;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Render Med Solutions Landing Page.
     */
    public function index(): View
    {
        $services = Cache::remember('home.services', 3600, fn () =>
            Service::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
        );

        $testimonials = Cache::remember('home.testimonials', 3600, fn () =>
            Testimonial::query()
                ->where('is_published', true)
                ->latest()
                ->take(6)
                ->get()
        );

        $latestTips = Cache::remember('home.tips', 3600, fn () =>
            BlogPost::query()
                ->where('is_published', true)
                ->latest('published_at')
                ->take(3)
                ->get()
        );

        $preTravelSteps = [
            [
                'step' => '01',
                'title' => 'Passport & Medical Records Audit',
                'desc' => 'Ensure both patient and attendant passports have at least 6 months of validity. Compile all prescriptions, diagnostic scans, and pathology reports in PDF format.',
            ],
            [
                'step' => '02',
                'title' => 'Specialist Selection & Treatment Plan',
                'desc' => 'Receive an authoritative evaluation from top Indian specialists covering recommended procedures, expected hospital stay, and transparent cost estimates before travelling.',
            ],
            [
                'step' => '03',
                'title' => 'Visa Invitation Letter (VIL) & MED Visa',
                'desc' => 'Always apply for an Indian Medical Visa (MED) rather than a Tourist Visa for surgeries or inpatient care. We obtain official hospital VILs and prepare your IVAC file.',
            ],
            [
                'step' => '04',
                'title' => 'Forex Endorsement & Flight/Train Tickets',
                'desc' => 'Endorse foreign currency (USD / Dual-Currency Card) on your passport and book confirmed air or train tickets aligned with your confirmed doctor appointment.',
            ],
            [
                'step' => '05',
                'title' => 'Pickup, Interpreter & Hospital-Near Stay',
                'desc' => 'Upon arrival in India, our care team handles airport/station pickup, Bengali-speaking interpreter support, self-cooking accommodation near the hospital, and FRRO registration.',
            ],
        ];

        return view('home', compact('services', 'testimonials', 'latestTips', 'preTravelSteps'));
    }

    /**
     * Store public Contact Us inquiry.
     */
    public function storeContact(StoreContactRequest $request): RedirectResponse
    {
        Contact::create($request->validated());

        return back()
            ->withFragment('contact')
            ->with('success', 'Thank you! Your inquiry has been received. A Med Solutions coordinator will contact you shortly.');
    }
}
