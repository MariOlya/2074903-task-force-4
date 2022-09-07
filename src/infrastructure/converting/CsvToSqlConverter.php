<?php declare(strict_types=1);

namespace omarinina\infrastructure\converting;

use SplFileObject;
use omarinina\infrastructure\exception\FileExistException;
use omarinina\infrastructure\exception\FileOpenException;
use omarinina\infrastructure\exception\HeaderColumnsException;

class CsvToSqlConverter
{
    /** @var string */
    private string $csvFile;

    /** @var string */
    private string $sqlFile;

    /** @var string */
    private string $usedTable;

    /** @var array */
    private array $columns = [];

    /**
     * @param string $csvFile
     * @param string $sqlFile
     * @param string $usedTable
     * @param array $columns
     */
    public function __construct(
        string $csvFile,
        string $sqlFile,
        string $usedTable,
        array $columns
    )
    {
        $this->csvFile = $csvFile;
        $this->sqlFile = $sqlFile;
        $this->usedTable = $usedTable;
        $this->columns = $columns;
    }

    /**
     * @return void
     * @throws HeaderColumnsException count of columns in read file is not
     * the same as in DB table
     */
    public function runParseCsvToSql() {
        $writtenFile = $this->openSqlWrittenFile();
        $headerColumns = null;
        $readFile = $this->openCsvReadFile();
        $readFile->rewind();
        foreach ($this->readLines($readFile) as $i => $line) {
            if ($i === 0) {
                $headerColumns = $line;
                if (count($headerColumns) !== count($this->columns)) {
                    throw new HeaderColumnsException;
                }
                continue;
            }
            if ($this->isValidLine($line)) {
                $writtenFile->fwrite(
                'INSERT INTO ' . $this->usedTable
                    . ' (' . implode(', ', $this->columns) . ') VALUES ('
                    . implode(', ', $line) . ');' . PHP_EOL);
            }
        }
    }

    /**
     * @return SplFileObject
     * @throws FileExistException file which we try to open doesn't exist
     * @throws FileOpenException file didin't open
     */
    private function openCsvReadFile(): SplFileObject
    {
        if (!file_exists($this->csvFile)) {
            throw new FileExistException;
        }

        $readFile = new SplFileObject($this->csvFile, 'r');

        if (!$readFile) {
            throw new FileOpenException;
        }

        return $readFile;
    }

    /**
     * @return SplFileObject
     * @throws FileExistException file which we try to open doesn't exist
     * @throws FileOpenException file didin't open
     */
    private function openSqlWrittenFile(): SplFileObject
    {
        if (!file_exists($this->sqlFile)) {
            throw new FileExistException;
        }

        $writtenFile = new SplFileObject($this->sqlFile, 'w');

        if (!$writtenFile) {
            throw new FileOpenException;
        }

        return $writtenFile;
    }

    /**
     * @param SplFileObject $readFile
     * @return iterable
     */
    private function readLines(SplFileObject $readFile): iterable
    {
        while (!$readFile->eof()) {
            yield $readFile->fgetcsv();
        }
    }

    /**
     * @param array $line
     * @return boolean
     */
    private function isValidLine(array $line): bool
    {
        if (count($line) === count($this->columns)) {
            foreach ($line as $item) {
                if(!isset($item) && $item === null) {
                    return false;
                };
            }
            return true;
        }
    }
}
