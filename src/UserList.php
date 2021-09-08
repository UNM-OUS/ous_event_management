<?php

namespace Digraph\Modules\ous_event_management;

use Digraph\DSO\Noun;
use Envms\FluentPDO\Queries\Select;
use Envms\FluentPDO\Query;

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
        'academic period',
        'commencement honors flag',
        'award category',
        'confidentiality indicator',
        'outcomes_count',
    ];

    public function hook_create()
    {
        parent::hook_create();
        $this->createTable();
    }

    public function hook_update()
    {
        parent::hook_update();
        $this->createTable();
    }

    public function createTable()
    {
        $pdo = $this->cms()->pdo('userlists');
        $pdo->exec('CREATE TABLE IF NOT EXISTS "user" ("id" INTEGER, "list_id" TEXT NOT NULL, "netid" TEXT, "email" TEXT, "data" TEXT NOT NULL, PRIMARY KEY("id"))');
        $pdo->exec('CREATE INDEX IF NOT EXISTS "user_email" ON "user" ("email")');
        $pdo->exec('CREATE INDEX IF NOT EXISTS "user_netid" ON "user" ("netid")');
        $pdo->exec('CREATE INDEX IF NOT EXISTS "user_list_id" ON "user" ("list_id")');
    }

    public function query(): Query
    {
        $query = new Query($this->cms()->pdo('userlists'));
        return $query;
    }

    public function select(): Select
    {
        return $this->query()
            ->from('user')
            ->where('list_id = ?', [$this['dso.id']]);
    }

    public function addRow(array $row)
    {
        $this->query()
            ->insertInto(
                'user',
                [
                    'list_id' => $this['dso.id'],
                    'netid' => @$row['netid'],
                    'email' => @$row['email'],
                    'data' => json_encode($row)
                ]
            )->execute();
    }

    public function clearList()
    {
        $this->query()
            ->delete('user')
            ->where('list_id = ?', [$this['dso.id']])
            ->execute();
    }

    /**
     * Search for a the first result matching a given email or NetID
     *
     * @param string $query
     * @return array
     */
    public function findFirst(string $query): ?array
    {
        $first = $this->select()
            ->where('netid = :q OR email = :q', ['q' => $query])
            ->fetch();
        if ($first) {
            return json_decode($first['data'], true);
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
        return array_map(
            function ($e) {
                return json_decode($e['data'], true);
            },
            $this->select()
                ->where('netid = :q OR email = :q', ['q' => $query])
                ->fetchAll()
        );
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
        $query = $this->select();
        while ($row = $query->fetch()) {
            $row = json_decode($row['data'], true);
            if ($fn($row)) {
                $results[$this->hashRowData($row)] = $row;
                if ($limit && count($results) == $limit) {
                    break;
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

    function formMap(string $action): array
    {
        $map = parent::formMap($action);
        $map['digraph_title'] = false;
        $map['digraph_body'] = false;
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
