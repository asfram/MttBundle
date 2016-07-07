<?php

namespace CanalTP\MttBundle;

use CanalTP\MttBundle\CsvModelInterface;
use League\Csv\Reader;
use League\Csv\Writer;

class CsvGenerator
{
    public static function generateCSV(CsvModelInterface $csvModel)
    {
        $csv = Writer::createFromFileObject(new \SplTempFileObject());
        //$csv->setOutputBOM(Reader::BOM_UTF8);
        $csv->setDelimiter(';');
        $csv->insertOne($csvModel->getHeaders());
        $csv->insertAll($csvModel->getRows());

        return (string) $csv;
    }
}
