<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Event;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class FinancialBalanceWorkbook
{
    private const GREEN = '243834';

    private const GOLD = 'B79045';

    private const LIGHT_GREEN = 'E8F0ED';

    private const CURRENCY_FORMAT = '$#,##0.00;[Red]($#,##0.00)';

    public function __construct(private FinancialBalanceCalculator $calculator) {}

    public function event(Event $event): Spreadsheet
    {
        $spreadsheet = $this->spreadsheet('Balance de evento');
        $summary = $spreadsheet->getActiveSheet();
        $summary->setTitle('Resumen');
        $movements = $spreadsheet->createSheet()->setTitle('Movimientos');
        $balance = $this->calculator->forEvent($event);

        $movementRange = $this->writeMovements($movements, $balance['transactions'], false);
        $this->writeEventSummary($summary, $event, $movementRange, 'Movimientos');
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    public function client(Client $client): Spreadsheet
    {
        $spreadsheet = $this->spreadsheet('Balance de cliente');
        $summary = $spreadsheet->getActiveSheet();
        $summary->setTitle('Resumen cliente');
        $events = $client->events->sortBy('event_date')->values();

        foreach ($events as $event) {
            $sheet = $spreadsheet->createSheet()->setTitle('Evento '.$event->id);
            $balance = $this->calculator->forEvent($event);
            $movementRange = $this->writeEventMovementsBlock($sheet, $balance['transactions']);
            $this->writeEventSummary($sheet, $event, $movementRange, $sheet->getTitle());
            $sheet->freezePane('A20');
        }

        $this->writeClientSummary($summary, $client, $events);

        $allMovements = $client->transactions
            ->sortBy(fn (Transaction $transaction) => $transaction->transaction_date->format('Y-m-d').str_pad((string) $transaction->id, 12, '0', STR_PAD_LEFT))
            ->values();
        $movementSheet = $spreadsheet->createSheet()->setTitle('Movimientos');
        $this->writeMovements($movementSheet, $this->calculator->withRunningBalance($allMovements), true);
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    public function save(Spreadsheet $spreadsheet): string
    {
        $path = null;

        try {
            $path = tempnam(sys_get_temp_dir(), 'h5-balance-');

            if ($path === false) {
                throw new \RuntimeException('No fue posible crear el archivo temporal del balance.');
            }

            (new Xlsx($spreadsheet))->save($path);

            return $path;
        } catch (\Throwable $exception) {
            if (is_string($path) && is_file($path)) {
                @unlink($path);
            }

            throw $exception;
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private function spreadsheet(string $title): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Hacienda Cinco La Victoria')
            ->setTitle($title)
            ->setCompany('Hacienda Cinco La Victoria');
        $spreadsheet->getDefaultStyle()->getFont()->setName('Aptos')->setSize(10);

        return $spreadsheet;
    }

    private function writeEventSummary(
        Worksheet $sheet,
        Event $event,
        array $movementRange,
        string $movementSheet,
    ): void {
        $this->title($sheet, 'BALANCE FINANCIERO DEL EVENTO', 'A1:D1');
        $sheet->setCellValue('A2', 'Hacienda Cinco La Victoria');
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A2:D2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->fromArray([
            ['Cliente', null],
            ['Evento', null],
            ['Tipo de evento', null],
            ['Fecha del evento', Date::PHPToExcel($event->event_date)],
        ], null, 'A4');
        $this->setText($sheet, 'B4', $event->client->full_name);
        $this->setText($sheet, 'B5', $event->title);
        $this->setText($sheet, 'B6', $event->event_type);
        $sheet->getStyle('A4:A7')->getFont()->setBold(true)->getColor()->setARGB(self::GREEN);
        $sheet->getStyle('B7')->getNumberFormat()->setFormatCode('dd/mm/yyyy');

        $sheet->fromArray([
            ['Concepto', 'Monto MXN'],
            ['Costo total', (float) $event->total_amount],
            ['Ingresos pagados', null],
            ['Ingresos pendientes', null],
            ['Gastos pagados', null],
            ['Saldo pendiente', null],
            ['Balance', null],
        ], null, 'A10');

        $typeColumn = $movementRange['type'];
        $statusColumn = $movementRange['status'];
        $amountColumn = $movementRange['amount'];
        $firstRow = $movementRange['first_row'];
        $lastRow = $movementRange['last_row'];
        $source = "'{$movementSheet}'";

        $sheet->setCellValue('B12', "=SUMIFS({$source}!\${$amountColumn}\${$firstRow}:\${$amountColumn}\${$lastRow},{$source}!\${$typeColumn}\${$firstRow}:\${$typeColumn}\${$lastRow},\"Ingreso\",{$source}!\${$statusColumn}\${$firstRow}:\${$statusColumn}\${$lastRow},\"Pagado\")");
        $sheet->setCellValue('B13', "=SUMIFS({$source}!\${$amountColumn}\${$firstRow}:\${$amountColumn}\${$lastRow},{$source}!\${$typeColumn}\${$firstRow}:\${$typeColumn}\${$lastRow},\"Ingreso\",{$source}!\${$statusColumn}\${$firstRow}:\${$statusColumn}\${$lastRow},\"Pendiente\")");
        $sheet->setCellValue('B14', "=SUMIFS({$source}!\${$amountColumn}\${$firstRow}:\${$amountColumn}\${$lastRow},{$source}!\${$typeColumn}\${$firstRow}:\${$typeColumn}\${$lastRow},\"Gasto\",{$source}!\${$statusColumn}\${$firstRow}:\${$statusColumn}\${$lastRow},\"Pagado\")");
        $sheet->setCellValue('B15', '=B11-B12');
        $sheet->setCellValue('B16', '=B12-B14');

        $this->tableHeader($sheet, 'A10:B10');
        $sheet->getStyle('A11:B16')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setARGB('D1D5DB');
        $sheet->getStyle('B11:B16')->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
        $sheet->getStyle('A15:B16')->getFont()->setBold(true);
        $sheet->getStyle('A15:B16')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::LIGHT_GREEN);
        $sheet->freezePane('A10');
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(34);
        $sheet->getColumnDimension('C')->setWidth(4);
        $sheet->getColumnDimension('D')->setWidth(4);
        $sheet->setShowGridlines(false);
    }

    private function writeClientSummary(Worksheet $sheet, Client $client, Collection $events): void
    {
        $this->title($sheet, 'BALANCE FINANCIERO DEL CLIENTE', 'A1:I1');
        $this->setText($sheet, 'A2', $client->full_name);
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2:I2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->fromArray([
            ['Evento', 'Tipo', 'Fecha', 'Costo total', 'Ingresos pagados', 'Ingresos pendientes', 'Gastos', 'Saldo pendiente', 'Balance'],
        ], null, 'A5');
        $this->tableHeader($sheet, 'A5:I5');

        $row = 6;

        foreach ($events as $event) {
            $eventSheet = "'Evento {$event->id}'";
            $this->setText($sheet, 'A'.$row, $event->title);
            $this->setText($sheet, 'B'.$row, $event->event_type);
            $sheet->setCellValue('C'.$row, Date::PHPToExcel($event->event_date));
            $sheet->setCellValue('D'.$row, "={$eventSheet}!B11");
            $sheet->setCellValue('E'.$row, "={$eventSheet}!B12");
            $sheet->setCellValue('F'.$row, "={$eventSheet}!B13");
            $sheet->setCellValue('G'.$row, "={$eventSheet}!B14");
            $sheet->setCellValue('H'.$row, "={$eventSheet}!B15");
            $sheet->setCellValue('I'.$row, "={$eventSheet}!B16");
            $row++;
        }

        $totalRow = $row;
        $sheet->setCellValue('A'.$totalRow, 'TOTALES');
        $sheet->mergeCells("A{$totalRow}:C{$totalRow}");

        foreach (range('D', 'I') as $column) {
            $sheet->setCellValue($column.$totalRow, $events->isEmpty() ? '=0' : "=SUM({$column}6:{$column}".($totalRow - 1).')');
        }

        $sheet->getStyle("A{$totalRow}:I{$totalRow}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
        $sheet->getStyle("A{$totalRow}:I{$totalRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::GREEN);
        $sheet->getStyle('C6:C'.max(6, $totalRow - 1))->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $sheet->getStyle('D6:I'.$totalRow)->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
        $sheet->setAutoFilter('A5:I'.max(5, $totalRow - 1));
        $sheet->freezePane('A6');
        $this->setColumnWidths($sheet, [28, 20, 14, 18, 18, 20, 16, 18, 18]);
        $sheet->setShowGridlines(false);
    }

    private function writeEventMovementsBlock(Worksheet $sheet, Collection $transactions): array
    {
        return $this->writeMovements($sheet, $transactions, false, 19, false);
    }

    private function writeMovements(
        Worksheet $sheet,
        Collection $transactions,
        bool $includeEvent,
        int $headerRow = 5,
        bool $withTitle = true,
    ): array {
        $headers = ['Fecha', 'Referencia'];

        if ($includeEvent) {
            $headers[] = 'Evento';
        }

        $headers = [...$headers, 'Tipo', 'Concepto / categoría', 'Método', 'Estatus', 'Monto', 'Saldo acumulado'];
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));

        if ($withTitle) {
            $this->title($sheet, 'DETALLE DE MOVIMIENTOS', 'A1:'.$lastColumn.'1');
            $sheet->setCellValue('A2', 'Hacienda Cinco La Victoria');
            $sheet->mergeCells('A2:'.$lastColumn.'2');
            $sheet->getStyle('A2:'.$lastColumn.'2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->fromArray([$headers], null, 'A'.$headerRow);
        $this->tableHeader($sheet, 'A'.$headerRow.':'.$lastColumn.$headerRow);
        $firstRow = $headerRow + 1;
        $row = $firstRow;
        $typeIndex = $includeEvent ? 4 : 3;
        $statusIndex = $includeEvent ? 7 : 6;
        $amountIndex = $includeEvent ? 8 : 7;
        $balanceIndex = $includeEvent ? 9 : 8;

        foreach ($transactions as $item) {
            /** @var Transaction $transaction */
            $transaction = $item['transaction'];
            $column = 1;
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++).$row, Date::PHPToExcel($transaction->transaction_date));
            $this->setText($sheet, Coordinate::stringFromColumnIndex($column++).$row, $transaction->reference ?: '-');

            if ($includeEvent) {
                $this->setText($sheet, Coordinate::stringFromColumnIndex($column++).$row, $transaction->event?->title ?? 'Sin evento');
            }

            $this->setText($sheet, Coordinate::stringFromColumnIndex($column++).$row, $transaction->type_label);
            $this->setText($sheet, Coordinate::stringFromColumnIndex($column++).$row, $transaction->category ?: 'Sin categoría');
            $this->setText($sheet, Coordinate::stringFromColumnIndex($column++).$row, $this->methodLabel($transaction->method));
            $this->setText($sheet, Coordinate::stringFromColumnIndex($column++).$row, $this->statusLabel($transaction->status));
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($column).$row, (float) $transaction->amount);
            $typeColumn = Coordinate::stringFromColumnIndex($typeIndex);
            $statusColumn = Coordinate::stringFromColumnIndex($statusIndex);
            $amountColumn = Coordinate::stringFromColumnIndex($amountIndex);
            $balanceColumn = Coordinate::stringFromColumnIndex($balanceIndex);
            $delta = "IF({$statusColumn}{$row}=\"Pagado\",IF({$typeColumn}{$row}=\"Ingreso\",{$amountColumn}{$row},-{$amountColumn}{$row}),0)";
            $sheet->setCellValue($balanceColumn.$row, $row === $firstRow ? '='.$delta : "={$balanceColumn}".($row - 1).'+'.$delta);
            $row++;
        }

        $lastRow = max($firstRow, $row - 1);
        $typeColumn = Coordinate::stringFromColumnIndex($typeIndex);
        $statusColumn = Coordinate::stringFromColumnIndex($statusIndex);
        $amountColumn = Coordinate::stringFromColumnIndex($amountIndex);
        $balanceColumn = Coordinate::stringFromColumnIndex($balanceIndex);

        if ($transactions->isEmpty()) {
            $sheet->setCellValue('A'.$firstRow, 'Sin movimientos');
        }

        $sheet->getStyle('A'.$firstRow.':A'.$lastRow)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $sheet->getStyle($amountColumn.$firstRow.':'.$balanceColumn.$lastRow)->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
        $sheet->getStyle('A'.$firstRow.':'.$lastColumn.$lastRow)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setARGB('E5E7EB');
        $sheet->setAutoFilter('A'.$headerRow.':'.$lastColumn.$lastRow);
        $sheet->freezePane('A'.$firstRow);
        $this->setColumnWidths(
            $sheet,
            $includeEvent
                ? [14, 22, 28, 12, 30, 18, 14, 16, 18]
                : [14, 22, 12, 30, 18, 14, 16, 18],
        );
        $sheet->setShowGridlines(false);

        return [
            'first_row' => $firstRow,
            'last_row' => $lastRow,
            'type' => $typeColumn,
            'status' => $statusColumn,
            'amount' => $amountColumn,
        ];
    }

    private function title(Worksheet $sheet, string $title, string $range): void
    {
        $sheet->mergeCells($range);
        $sheet->setCellValue(explode(':', $range)[0], $title);
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::GREEN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);
    }

    private function tableHeader(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::GOLD]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => self::GREEN]]],
        ]);
    }

    private function setColumnWidths(Worksheet $sheet, array $widths): void
    {
        foreach ($widths as $offset => $width) {
            $index = $offset + 1;
            $column = Coordinate::stringFromColumnIndex($index);
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private function setText(Worksheet $sheet, string $cell, ?string $value): void
    {
        $sheet->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);
    }

    private function methodLabel(?string $method): string
    {
        return match ($method) {
            'transfer' => 'Transferencia',
            'cash' => 'Efectivo',
            'card' => 'Tarjeta',
            'other' => 'Otro',
            default => $method ?: '-',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'paid' => 'Pagado',
            'pending' => 'Pendiente',
            'cancelled' => 'Cancelado',
            default => $status,
        };
    }
}
