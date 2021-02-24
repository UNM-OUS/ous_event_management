<?php
namespace Digraph\Modules\ous_event_management\Jostens;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Digraph\Helpers\AbstractHelper;

class JostensHelper extends AbstractHelper
{
    public function queryInstitution(string $query): array
    {
        return $this->querySheet(__DIR__ . '/institutions.xlsx', $query, ['name', 'city']);
    }

    public function locateInstitution(string $query): ?array
    {
        $query = preg_split('/, */',strtoupper($query));
        return $this->locateRow(
            __DIR__ . '/institutions.xlsx',
            [
                'name' => $query[0],
                'city' => $query[1],
                'state' => $query[2]
            ]
        );
    }

    public function queryDegree(string $query): array
    {
        return $this->querySheet(__DIR__ . '/degrees.xlsx', $query, ['degree']);
    }

    public function locateDegree(string $query): ?array
    {
        $query = strtoupper($query);
        return $this->locateRow(
            __DIR__ . '/degrees.xlsx',
            [
                'degree' => $query,
            ]
        );
    }

    protected function locateRow($file, $q): ?array
    {
        $reader = ReaderEntityFactory::createReaderFromFile($file);
        $reader->open($file);
        $headers = null;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCells();
                if (!$headers) {
                    $headers = array_flip(array_map(function ($cell) {
                        return strtolower(trim($cell->getValue()));
                    }, $cells));
                } else {
                    // row data keyed by headers in first row
                    $rowData = array_map(function ($i) use ($cells) {return @$cells[$i]->getValue();}, $headers);
                    $count = count($q);
                    if (count(array_intersect_assoc($q,$rowData)) == $count) {
                        return $rowData;
                    }
                }
            }
        }
        return null;
    }

    protected function querySheet($file, $query, $cols): array
    {
        $results = [];
        $reader = ReaderEntityFactory::createReaderFromFile($file);
        $reader->open($file);
        $headers = null;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCells();
                if (!$headers) {
                    $headers = array_flip(array_map(function ($cell) {
                        return strtolower(trim($cell->getValue()));
                    }, $cells));
                } else {
                    // row data keyed by headers in first row
                    $rowData = array_map(function ($i) use ($cells) {return @$cells[$i]->getValue();}, $headers);
                    $score = 0;
                    foreach ($cols as $c) {
                        $pos = stripos($rowData[$c], $query);
                        if ($pos !== false) {
                            $score += $pos === 0 ? 2 : 1;
                        }
                    }
                    if ($score) {
                        $rowData['sort_score'] = $score;
                        $results[] = $rowData;
                    }
                }
            }
        }
        usort($results, function ($a, $b) {
            return $b['sort_score'] - $a['sort_score'];
        });
        return $results;
    }
}
