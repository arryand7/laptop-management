<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BorrowReportExport;
use App\Http\Controllers\Controller;
use App\Models\BorrowTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\Response;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->resolveFilters($request);
        $transactions = $this->applyFilters($filters)
            ->with(['student', 'laptop', 'staff'])
            ->orderByDesc('borrowed_at')
            ->get();

        debug_event('Admin:Reports', 'Menampilkan laporan', $filters);

        return view('admin.reports.index', compact('filters', 'transactions'));
    }

    public function exportExcel(Request $request)
    {
        $filters = $this->resolveFilters($request);

        debug_event('Admin:Reports', 'Export Excel dimulai', $filters);

        return Excel::download(new BorrowReportExport($filters), 'laporan-peminjaman.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $filters = $this->resolveFilters($request);
        $transactions = $this->applyFilters($filters)
            ->with(['student', 'laptop', 'staff'])
            ->orderByDesc('borrowed_at')
            ->get();

        $html = view('admin.reports.pdf', compact('filters', 'transactions'))->render();

        $pdf = new Mpdf(['format' => 'A4-L']);
        $pdf->WriteHTML($html);

        debug_event('Admin:Reports', 'Export PDF berhasil', $filters);

        return new Response($pdf->Output('laporan-peminjaman.pdf', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-peminjaman.pdf"',
        ]);
    }

    protected function resolveFilters(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:all,borrowed,returned,late'],
        ]);

        $start = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : Carbon::now()->subMonth()->startOfDay();

        $end = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : Carbon::now()->endOfDay();

        if ($end->lessThan($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [
            'start_date' => $start,
            'end_date' => $end,
            'status' => $validated['status'] ?? 'all',
        ];
    }

    protected function applyFilters(array $filters)
    {
        return BorrowTransaction::query()
            ->whereBetween('borrowed_at', [$filters['start_date'], $filters['end_date']])
            ->when($filters['status'] === 'borrowed', fn ($query) => $query->where('status', 'borrowed'))
            ->when($filters['status'] === 'returned', fn ($query) => $query->where('status', 'returned')->where('was_late', false))
            ->when($filters['status'] === 'late', fn ($query) => $query->where('status', 'returned')->where('was_late', true));
    }
}
