<?php

namespace App\Http\Controllers;

use App\Models\ReportingPeriod;
use App\Models\KapzProfile;
use App\Models\ApzProfile;
use App\Models\ArrivalDepartureBook;
use App\Models\ArrivalDepartureBookItem;
use App\Services\ArrivalDepartureBookService;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookReportController extends Controller
{
    protected ArrivalDepartureBookService $bookService;
    protected PdfGeneratorService $pdfGenerator;

    public function __construct(ArrivalDepartureBookService $bookService, PdfGeneratorService $pdfGenerator)
    {
        $this->bookService = $bookService;
        $this->pdfGenerator = $pdfGenerator;
    }

    public function index(Request $request)
    {
        $periodId = $request->input('period_id', 1);
        $period = ReportingPeriod::findOrFail($periodId);
        $periods = ReportingPeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get();

        $kapz = $this->resolveKapz($request);

        $refDate = sprintf('%04d-%02d-15', $period->year, $period->month);
        $apzList = $kapz->assignedApzsForDate($refDate);

        // Selected person (default: KAPZ, or APZ by ID)
        $personType = $request->input('person_type', 'KAPZ');
        $personId = (int)$request->input('person_id', $kapz->id);

        if ($personType === 'APZ' && !$apzList->contains('id', $personId)) {
            if ($apzList->isNotEmpty()) {
                $personId = $apzList->first()->id;
            } else {
                // Bez pridelených APZ zobraz knihu samotného KAPZ – nikdy nie cudzieho APZ.
                $personType = 'KAPZ';
                $personId = $kapz->id;
            }
        }
        if ($personType !== 'APZ') {
            $personType = 'KAPZ';
            $personId = $kapz->id;
        }

        // Get or automatically sync book
        $book = $this->bookService->getOrCreateBook($kapz, $period, $personType, $personId);
        $items = $book->items;

        return view('knihy.index', compact(
            'kapz',
            'period',
            'periods',
            'apzList',
            'personType',
            'personId',
            'book',
            'items'
        ));
    }

    public function save(Request $request, ArrivalDepartureBook $book)
    {
        $this->authorizeKapzAccess($book->kapz_id);
        $itemsData = $request->input('items', []);

        foreach ($itemsData as $itemId => $data) {
            $item = ArrivalDepartureBookItem::where('book_id', $book->id)->find($itemId);
            if ($item) {
                $item->update([
                    'arrival_hour' => $data['arrival_hour'] ?? null,
                    'arrival_minute' => $data['arrival_minute'] ?? null,
                    'departure_hour' => $data['departure_hour'] ?? null,
                    'departure_minute' => $data['departure_minute'] ?? null,
                    'break_departure_hour' => $data['break_departure_hour'] ?? null,
                    'break_departure_minute' => $data['break_departure_minute'] ?? null,
                    'break_arrival_hour' => $data['break_arrival_hour'] ?? null,
                    'break_arrival_minute' => $data['break_arrival_minute'] ?? null,
                    'break_reason' => $data['break_reason'] ?? null,
                    'visited_location' => $data['visited_location'] ?? null,
                    'approved_by' => $data['approved_by'] ?? null,
                    'note' => $data['note'] ?? null,
                ]);
            }
        }

        return back()->with('success', "Kniha príchodov a odchodov pre {$book->full_name} bola úspešne uložená.");
    }

    public function sync(Request $request, ArrivalDepartureBook $book)
    {
        $this->authorizeKapzAccess($book->kapz_id);
        $this->bookService->syncBookItems($book, $book->kapz, $book->reportingPeriod, $book->person_type, $book->person_id);

        return back()->with('success', "Kniha príchodov a odchodov pre {$book->full_name} bola nanovo zosynchronizovaná z dochádzky.");
    }

    public function downloadPdf(Request $request, ArrivalDepartureBook $book)
    {
        $this->authorizeKapzAccess($book->kapz_id);
        $pdf = $this->pdfGenerator->generateKnihaPrichodovOdchodovPdf($book);
        $filename = 'KNIHA_' . str_replace(' ', '_', $book->full_name) . '_' . sprintf('%02d', $book->reportingPeriod->month) . '_' . $book->reportingPeriod->year . '.pdf';

        return $pdf->download($filename);
    }

    public function downloadAllPdf(Request $request)
    {
        $period = ReportingPeriod::findOrFail($request->input('period_id', 1));
        $kapz = $this->resolveKapz($request);

        $books = $this->bookService->getAllTeamBooks($kapz, $period);
        $pdf = $this->pdfGenerator->generateAllKnihyPdf($books);
        $filename = 'KNIHY_CELÝ_TÍM_' . $kapz->personal_number . '_' . sprintf('%02d', $period->month) . '_' . $period->year . '.pdf';

        return $pdf->download($filename);
    }
}
