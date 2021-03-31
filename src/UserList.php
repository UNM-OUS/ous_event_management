<?php
namespace Digraph\Modules\ous_event_management;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Digraph\DSO\Noun;

/**
 * This Noun type is used to upload a list of students from MyReports.
 * It is primarily intended to be used as a child of a SignupWindow, in
 * which case it will be used to control who is allowed to sign up.
 *
 * Eventually it will also likely be used to generate online programs,
 * but that may not happen until at least 2021.
 */
class UserList extends Noun
{
    // note that FILESTORE is false, so files can't be re-downloaded
    // once they are uploaded
    const SLUG_ENABLED = false;
    const FILESTORE_PATH = 'users';
    const FILESTORE_FILE_CLASS = FileStoreFile::class;

    const CLEANUP = [
        'email address',
        'phone number',
        'student first name',
        'student last name',
        'graduation status',
        'academic period',
        'commencement honors flag',
        'award category',
        'confidentiality indicator',
        'outcomes_count',
    ];

    /**
     * Search for a the first result matching a given email or NetID
     *
     * @param string $query
     * @return array
     */
    public function findFirst(string $query): ?array
    {
        $query = strtolower($query);
        $r = $this->filter(function ($row) use ($query) {
            return strtolower(@$row['netid']) == $query || strtolower(@$row['email']) == $query;
        }, 1);
        if ($r) {
            return array_shift($r);
        } else {
            return null;
        }
    }

    /**
     * Search for all results matching a given email or NetID
     *
     * @param string $query
     * @return array
     */
    function findAll(string $query): array
    {
        $query = strtolower($query);
        return $this->filter(function ($row) use ($query) {
            return strtolower(@$row['netid']) == $query || strtolower(@$row['email']) == $query;
        });
    }

    function map(callable $fn, int $limit = 0): array
    {
        return $this->filter(function ($rowData) use ($fn) {
            return $fn($rowData);
        }, $limit);
    }

    function filter(callable $fn, int $limit = 0): array
    {
        $results = [];
        foreach ($this->userFiles() as $file) {
            $reader = ReaderEntityFactory::createReaderFromFile($file->name());
            $reader->open($file->path());
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
                        // verify that this row is valid
                        if (!$this->filterRowData($rowData)) {
                            continue;
                        }
                        // preprocess row data
                        $rowData = $this->preProcessRowData($rowData);
                        // process with $fn and add to results if $fn returns true
                        if ($fn($rowData)) {
                            $results[$this->hashRowData($rowData)] = $rowData;
                            // check limit, return results if we're done
                            if ($limit && count($results) >= $limit) {
                                return array_values($results);
                            }
                        }
                    }
                }
            }
        }
        return array_values($results);
    }

    function hashRowData(array $row): string
    {
        return md5(serialize($row));
    }

    function preProcessRowData(array $row): array
    {
        if (@$row['full name'] && preg_match('/^(.+?), ([^ ]+)( .\.)?/', @$row['full name'], $matches)) {
            $row['first name'] = $matches[2];
            $row['last name'] = $matches[1];
        } elseif (@$row['full name']) {
            $name = explode(' ', $row['full name']);
            $row['first name'] = array_shift($name);
            $row['last name'] = array_pop($name);
        } else {
            $row['first name'] = @$row['first name'] ?? @$row['student first name'];
            $row['last name'] = @$row['last name'] ?? @$row['student last name'];
        }
        $row['name'] = @$row['name'] ?? @$row['first name'] . ' ' . @$row['last name'];
        $row['honors'] = @$row['honors'] ?? @$row['commencement honors flag'];
        $row['email'] = @$row['email'] ?? @$row['email address'];
        $row['phone'] = @$row['phone'] ?? @$row['phone number'];
        $row['category'] = $this->degreeCategory($row);
        if (@$row['graduation status'] == 'Hold Pending') {
            $row['graduation status'] = "Pending";
        }
        $row['semester'] = @$row['academic period'];
        $this->cleanRowData($row);
        return $row;
    }

    function cleanRowData(array &$row)
    {
        foreach (static::CLEANUP as $k) {
            unset($row[$k]);
        }
    }

    function degreeCategory(array $row): string
    {
        if ($cat = @$row['award category']) {
            unset($row['award category']);
            if ($cat == 'Baccalaureate Degree') {
                return "Bachelor";
            }
            if ($cat == 'Associate Degree') {
                return "Associate";
            }
            if ($cat == 'Masters Degree') {
                return 'Graduate';
            }
            if ($cat == 'Doctoral Degree') {
                return 'Graduate';
            }
            if ($cat == 'Post-Masters Degree') {
                return 'Graduate';
            }
            if ($cat == 'First-Professional Degree') {
                return 'Graduate';
            }
            if ($cat == 'Post Second. Cert/Dipl >1 < 2') {
                return 'Post-secondary Certificate';
            }
            if ($cat == 'Post Second. Cert/Dipl <1 yr.') {
                return 'Post-secondary Certificate';
            }
        }
        return "Unknown award category";
    }

    function filterRowData(array $row): bool
    {
        // privacy flags
        if (@$row['confidentiality indicator'] == 'Y') {
            return false;
        }
        // filter by degree status
        if ($this['userlist.minstatus'] != 'none') {
            // sought is most permissive
            if ($this['userlist.minstatus'] == 'sought') {
                return true;
            }
            // pending
            if ($this['userlist.minstatus'] == 'pending') {
                return @$row['graduation status'] && in_array($row['graduation status'], ['Pending', 'Hold Pending', 'Awarded']);
            }
            // pending
            if ($this['userlist.minstatus'] == 'awarded') {
                return @$row['graduation status'] && in_array($row['graduation status'], ['Awarded']);
            }
        }
        // return true by default
        return true;
    }

    function userFiles()
    {
        return $this->cms()->helper('filestore')->list($this, $this::FILESTORE_PATH);
    }

    function formMap(string $action): array
    {
        $map = parent::formMap($action);
        $map['digraph_title'] = false;
        $map['digraph_body'] = false;
        $map['userlist_files'] = [
            'weight' => 250,
            'label' => 'Spreadsheets',
            'class' => 'Digraph\\Forms\\Fields\\FileStoreFieldMulti',
            'required' => true,
            'extraConstructArgs' => [static::FILESTORE_PATH],
        ];
        $map['userlist_minstatus'] = [
            'weight' => 200,
            'label' => 'Minimum degree status',
            'field' => 'userlist.minstatus',
            'class' => 'select',
            'required' => true,
            'default' => 'pending',
            'call' => [
                'options' => [[
                    'none' => 'None',
                    'sought' => 'Sought',
                    'pending' => 'Pending',
                    'awarded' => 'Awarded',
                ]],
            ],
        ];
        return $map;
    }

    function parentEdgeType(Noun $parent): ?string
    {
        if ($parent instanceof SignupWindow) {
            return 'event-signupwindow-userlist';
        }
        return null;
    }
}
