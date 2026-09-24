<?php

namespace App\Http\Controllers;

use App\Models\KapzProfile;
use App\Models\OutsideActivityDeclaration;
use App\Models\OutsideActivityDeclarationItem;
use App\Models\ReportingPeriod;
use App\Services\PdfGeneratorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatementController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isExpertOrAdmin = $user->isAdmin() || in_array($user->role, ['expert', 'manager']);
        
        $periods = ReportingPeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get();
        $selectedPeriodId = $request->input('period_id', $periods->first()?->id ?? 1);
        $period = ReportingPeriod::findOrFail($selectedPeriodId);

        $allKapzList = KapzProfile::visibleTo($user)->with('user')->orderBy('full_name')->get();

        if ($isExpertOrAdmin) {
            $selectedKapzId = $request->input('kapz_id', $allKapzList->first()?->id);
            $kapz = $allKapzList->firstWhere('id', (int) $selectedKapzId) ?? $allKapzList->first();
            $selectedKapzId = $kapz?->id;
        } else {
            $kapz = $user->kapzProfile;
            $selectedKapzId = $kapz?->id;
        }

        if (!$kapz) {
            return redirect()->route('dashboard')->with('error', 'KAPZ profil nebol nájdený.');
        }

        // Find or create the Outside Activity Declaration for this KAPZ and period
        $declaration = OutsideActivityDeclaration::firstOrCreate(
            [
                'kapz_id' => $kapz->id,
                'reporting_period_id' => $period->id,
            ],
            [
                'status' => 'DRAFT',
                'signed_at' => now(),
            ]
        );

        // Populate items if empty
        if ($declaration->items()->count() === 0) {
            $this->initializeDeclarationItems($declaration, $kapz, $period);
        }

        $items = $declaration->items()->orderBy('order_num')->get();

        // Contract types available in dropdown (Presne podľa oficiálnej matice)
        $contractTypes = [
            'Pracovná zmluva',
            'Pracovná zmluva na skrátený pracovný úväzok',
            'Dohoda o pracovnej činnosti',
            'Dohoda o vykonaní práce',
            'Dohoda o brigádnickej práci študenta',
            'Podnikanie ako SZČO',
            'Konateľ v s.r.o., štatutárny zástupca v s.r.o.',
            'Mandátna zmluva',
            'Iný pracovný úväzok u zamestnávateľa ZR',
            'Iné',
        ];

        return view('statements.index', compact(
            'declaration',
            'items',
            'period',
            'periods',
            'kapz',
            'allKapzList',
            'isExpertOrAdmin',
            'contractTypes'
        ));
    }

    private function initializeDeclarationItems(OutsideActivityDeclaration $declaration, KapzProfile $kapz, ReportingPeriod $period)
    {
        $lastDayOfMonth = Carbon::create($period->year, $period->month, 1)->endOfMonth()->format('Y-m-d');
        $order = 1;

        // 1. First row: KAPZ Coordinator
        OutsideActivityDeclarationItem::create([
            'declaration_id' => $declaration->id,
            'order_num' => $order++,
            'person_type' => 'KAPZ',
            'person_id' => $kapz->id,
            'personal_number' => $kapz->personal_number,
            'full_name' => $kapz->full_name,
            'has_gainful_activity' => 'nie',
            'is_funded_by_esif' => 'nie',
            'contract_type' => null,
            'signature_date' => $lastDayOfMonth,
            'signature_status' => null,
        ]);

        // 2. Subsequent rows: Assigned APZ Assistants for the month
        $assignedApzs = $kapz->assignedApzsForDate($lastDayOfMonth);
        foreach ($assignedApzs as $apz) {
            OutsideActivityDeclarationItem::create([
                'declaration_id' => $declaration->id,
                'order_num' => $order++,
                'person_type' => 'APZ',
                'person_id' => $apz->id,
                'personal_number' => $apz->personal_number,
                'full_name' => $apz->full_name,
                'has_gainful_activity' => 'nie',
                'is_funded_by_esif' => 'nie',
                'contract_type' => null,
                'signature_date' => $lastDayOfMonth,
                'signature_status' => null,
            ]);
        }
    }

    public function save(Request $request, OutsideActivityDeclaration $declaration)
    {
        $this->authorizeKapzAccess($declaration->kapz_id);
        $itemsData = $request->input('items', []);

        foreach ($itemsData as $itemId => $data) {
            $item = OutsideActivityDeclarationItem::where('declaration_id', $declaration->id)->find($itemId);
            if ($item) {
                $hasGainful = ($data['has_gainful_activity'] ?? 'nie') === 'áno' ? 'áno' : 'nie';
                $isEsif = ($data['is_funded_by_esif'] ?? 'nie') === 'áno' ? 'áno' : 'nie';
                $contractType = $hasGainful === 'áno' ? ($data['contract_type'] ?? null) : null;
                $signatureDate = !empty($data['signature_date']) ? $data['signature_date'] : null;

                $item->update([
                    'has_gainful_activity' => $hasGainful,
                    'is_funded_by_esif' => $isEsif,
                    'contract_type' => $contractType,
                    'signature_date' => $signatureDate,
                ]);
            }
        }

        $declaration->update([
            'status' => 'SIGNED',
            'signed_at' => now(),
        ]);

        return back()->with('success', 'Prehlásenie o činnosti mimo pracovného pomeru bolo úspešne uložené.');
    }

    public function quickFillAllNo(Request $request, OutsideActivityDeclaration $declaration)
    {
        $this->authorizeKapzAccess($declaration->kapz_id);
        $lastDayOfMonth = Carbon::create($declaration->reportingPeriod->year, $declaration->reportingPeriod->month, 1)->endOfMonth()->format('Y-m-d');

        $declaration->items()->update([
            'has_gainful_activity' => 'nie',
            'is_funded_by_esif' => 'nie',
            'contract_type' => null,
            'signature_date' => $lastDayOfMonth,
            'signature_status' => null,
        ]);

        $declaration->update([
            'status' => 'SIGNED',
            'signed_at' => now(),
        ]);

        return back()->with('success', 'Všetky riadky prehlásenia boli nastavené na: Nie / Nie / Posledný deň v mesiaci.');
    }

    public function downloadPdf(OutsideActivityDeclaration $declaration, PdfGeneratorService $pdfGenerator)
    {
        $this->authorizeKapzAccess($declaration->kapz_id);
        $declaration->load(['kapz', 'reportingPeriod', 'items']);
        $pdf = $pdfGenerator->generateOutsideActivityDeclarationPdf($declaration);
        
        $filename = sprintf('PREHLASENIE_MIMO_PP_%s_%02d_%04d.pdf', 
            $declaration->kapz->personal_number, 
            $declaration->reportingPeriod->month, 
            $declaration->reportingPeriod->year
        );

        return $pdf->download($filename);
    }
}
