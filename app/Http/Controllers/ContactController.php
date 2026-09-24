<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('scope', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%")
                  ->orWhere('section', 'like', "%{$search}%")
                  ->orWhere('region_expert', 'like', "%{$search}%");
            });
        }

        if ($request->filled('section')) {
            $query->where('section', $request->section);
        }

        if ($request->filled('scope')) {
            $query->where('scope', $request->scope);
        }

        $allContacts = $query->orderBy('id', 'asc')->get();

        // Group contacts by section in specific order matching official document
        $groupedContacts = $allContacts->groupBy('section');

        // Statistics
        $totalCount = Contact::count();
        $kapzCount = Contact::where('section', 'like', '%Koordinátori%')->count();
        $expertCount = Contact::where('section', 'like', '%Experti%')->count();
        $managementCount = Contact::where('section', 'like', '%manažér%')->orWhere('section', 'like', '%Riaditeľka%')->count();
        $headquartersCount = Contact::whereNotIn('section', ['Koordinátori asistentov podpory zdravia', 'Experti pre terén'])->count();

        $sections = Contact::select('section')->distinct()->whereNotNull('section')->pluck('section');
        $scopes = Contact::select('scope')->distinct()->whereNotNull('scope')->orderBy('scope', 'asc')->pluck('scope');

        return view('contacts.index', compact(
            'groupedContacts',
            'allContacts',
            'totalCount',
            'kapzCount',
            'expertCount',
            'managementCount',
            'headquartersCount',
            'sections',
            'scopes'
        ));
    }

    public function downloadPdf(Request $request, PdfGeneratorService $pdfGenerator)
    {
        $query = Contact::query();
        if ($request->filled('section')) {
            $query->where('section', $request->section);
        }
        if ($request->filled('scope')) {
            $query->where('scope', $request->scope);
        }
        $contacts = $query->orderBy('id', 'asc')->get();
        $grouped = $contacts->groupBy('section');

        $pdf = $pdfGenerator->generateContactsPdf($grouped);
        return $pdf->download('ZOZNAM_KONTAKTOV_' . date('Y_m_d') . '.pdf');
    }

    public function exportCsv(Request $request)
    {
        $contacts = Contact::orderBy('id', 'asc')->get();

        $response = new StreamedResponse(function () use ($contacts) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($handle, ['Sekcia / Oddelenie', 'P. č.', 'Priezvisko a meno', 'E-mail', 'Tel. číslo', 'Pôsobnosť / lokalita', 'Pracovná pozícia', 'Príslušnosť k Expertovi pre terén'], ';');

            foreach ($contacts as $c) {
                fputcsv($handle, [
                    $c->section,
                    $c->order_num,
                    $c->name,
                    $c->email,
                    $c->phone,
                    $c->scope,
                    $c->position,
                    $c->region_expert,
                ], ';');
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="ZOZNAM_KONTAKTOV_' . date('Y_m_d') . '.csv"');

        return $response;
    }
}
