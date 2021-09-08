<?php

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Digraph\Forms\Form;
use Formward\Fields\FileMulti;

$package->cache_noStore();
/** @var Digraph\Modules\ous_event_management\UserList */
$list = $package->noun();

echo "<h2>Signups using this list</h2>";
echo "<p>The following signup forms are linked to this list:</p>";

echo "<ul>";
foreach ($cms->helper('graph')->parents($list['dso.id'], 'event-signupwindow-userlist') as $window) {
    echo "<li>" . $window->eventGroup()->link() . ": " . $window->link() . "</li>";
}
echo "</ul>";

echo "<h2>Replace data for this list</h2>";
echo "<p>If there are multiple files you must upload them all at once, because uploading data here replaces any existing data in this list.</p>";

$form = new Form('');
$form['files'] = new FileMulti('Spreadsheet files');

if ($form->handle()) {
    ini_set('max_execution_time', 300);
    $list->clearList();
    $count = 0;
    foreach ($form['files']->value() as $file) {
        switch (pathinfo($file['name'], PATHINFO_EXTENSION)) {
            case 'xlsx':
                $reader = ReaderEntityFactory::createXLSXReader();
                break;
            case 'csv':
                $reader = ReaderEntityFactory::createCSVReader();
                break;
            default:
                throw new \Exception("Only xlsx and csv files are supported");
        }
        $reader->open($file['file']);
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
                    $rowData = array_map(function ($i) use ($cells) {
                        return @$cells[$i]->getValue();
                    }, $headers);
                    // verify that this row is valid
                    if (!$list->filterRowData($rowData)) {
                        continue;
                    }
                    // preprocess row data
                    $rowData = $list->preProcessRowData($rowData);
                    $list->addRow($rowData);
                    $count++;
                }
            }
        }
        $reader->close();
    }
    $cms->helper('notifications')->flashConfirmation('List now has ' . $count . ' users');
    $package->redirect($package->url());
} else {
    echo $form;
}
