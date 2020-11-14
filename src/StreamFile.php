<?php


namespace DlgProjects;

use Exception;

class StreamFile
{

    /**
     * @var string
     */
    protected $file;
    /**
     * @var string
     */
    protected $fileName;
    /**
     * @var resource
     */
    protected $openFile;
    /**
     * @var int
     */
    protected $bufferSize = 524288;
    /**
     * @var int
     */
    protected $rangeStart = 0;
    /**
     * @var null|int
     */
    protected $rangeEnd = null;
    /**
     * @var int
     */
    protected $fileSize;
    /**
     * @var string
     */
    protected $typeMime = 'video/mp4';

    /**
     * StreamFile constructor.
     * @param string $file File to broadcast
     * @throws Exception
     */
    public function __construct(string $file)
    {
        $this->setFile($file);
        $this->readContentRange();
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $file
     * @return $this
     * @throws Exception
     */
    protected function setFile(string $file): streamFile
    {
        if (!is_file($file)) {
            throw new Exception('File not found');
        }
        $this->setFileName(basename($file));
        $this->setFileSize(intval(filesize($file)));
        $mimeType = mime_content_type($file);
        if ($mimeType !== false) {
            $this->setTypeMime($mimeType);
        }
        $this->file = $file;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param mixed $fileName
     * @return streamFile
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * @return resource|bool
     */
    protected function getOpenFile()
    {
        return $this->openFile;
    }

    /**
     * @return int
     */
    public function getBufferSize(): int
    {
        return $this->bufferSize;
    }

    /**
     * @param int $bufferSize
     * @return streamFile
     */
    public function setBufferSize(int $bufferSize): streamFile
    {
        $this->bufferSize = $bufferSize;
        return $this;
    }

    /**
     * @return int
     */
    public function getRangeStart(): int
    {
        return $this->rangeStart;
    }

    /**
     * @param int $rangeStart
     * @return streamFile
     */
    protected function setRangeStart(int $rangeStart): streamFile
    {
        $this->rangeStart = $rangeStart;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRangeEnd()
    {
        if ($this->rangeEnd === null) {
            return $this->getFileSize() - 1;
        }
        return $this->rangeEnd;
    }

    /**
     * @param int $rangeEnd
     * @return streamFile
     */
    protected function setRangeEnd(int $rangeEnd): streamFile
    {
        if ($rangeEnd === 0) {
            $rangeEnd = $this->getFileSize() - 1;
        }
        $this->rangeEnd = $rangeEnd;
        return $this;
    }

    /**
     * @return string
     */
    public function getTypeMime(): string
    {
        return $this->typeMime;
    }

    /**
     * @param string $typeMime
     * @return streamFile
     */
    public function setTypeMime(string $typeMime): streamFile
    {
        $this->typeMime = $typeMime;
        return $this;
    }

    /**
     * start the stream process
     * @throws Exception
     */
    public function startStream()
    {
        if ($this->getFileSize() === 0) {
            throw new Exception('File size not found !');
        }
        ob_get_clean();
        $this->createHeaders();
        $this->readData($this->getRangeStart(), $this->getRangeEnd());

    }

    /**
     * open the file for reading
     * @throws Exception
     */
    protected function openFile()
    {
        if (!file_exists($this->getFile())) {
            throw new Exception('File not found.');
        }
        $fp = fopen($this->file, 'rb');
        if (!$fp) {
            throw new Exception('File open failed.');
        }
        $this->openFile = $fp;
    }

    /**
     * close the file if it is open
     */
    protected function closeFile()
    {
        if ($this->getOpenFile()) {
            fclose($this->getOpenFile());
        }
    }

    /**
     * read the header and save the range, return http 416 if the range isn't satisfiable
     */
    protected function readContentRange()
    {
        if (isset($_SERVER['HTTP_RANGE'])) {
            preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $range);
            $this->setRangeStart(intval($range[1]));
            $this->setRangeEnd(intval($range[2]));

            if ($this->getRangeEnd() < $this->getRangeStart() || $this->getRangeEnd() >= $this->getFileSize() || $this->getRangeStart() >= $this->getFileSize()) {
                ob_get_clean();

                header('HTTP/1.1 416 Range Not Satisfiable');
                header('Content-Range: bytes ' . $this->getRangeStart() . '-' . $this->getRangeEnd() . '/' . $this->getFileSize());
                header("Accept-Ranges: 0-" . ($this->getFileSize() - 1));
                exit;
            }
        }
    }

    /**
     * create headers
     */
    protected function createHeaders()
    {
        if (isset($_SERVER['HTTP_RANGE'])) {
            $streamLength = ($this->getRangeEnd() - $this->getRangeStart()) + 1;

            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes ' . $this->getRangeStart() . '-' . $this->getRangeEnd() . '/' . $this->getFileSize());
        } else {
            $streamLength = $this->getFileSize();
        }

        header('Content-Type: ' . $this->getTypeMime());

        header("Cache-Control: max-age=2592000, public");
        header("Expires: " . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
        header("Last-Modified: " . gmdate('D, d M Y H:i:s', @filemtime($this->getFile())) . ' GMT');
        header("Accept-Ranges: 0-" . ($this->getFileSize() - 1));

        header('Content-Length: ' . $streamLength);
        header('Content-Disposition: inline; filename="' . $this->getFileName() . '"');

    }

    /**
     * reads and display (echo) data from start point to end point
     * @param int $start Starting byte for reading
     * @param int $end end byte of reading
     * @throws Exception
     */
    protected function readData(int $start, int $end)
    {
        if (empty($this->getOpenFile())) {
            $this->openFile();
        }

        fseek($this->getOpenFile(), $start);

        $buffer = $this->getBufferSize();
        $i = $start;

        $old_execution_time = ini_get('max_execution_time');
        ini_set('max_execution_time', 0);

        while (!feof($this->getOpenFile()) && $i <= $end) {
            $buffer = (($i + $buffer) > $end) ? ($buffer = $end - $i + 1) : $this->getBufferSize();
            $data = fread($this->getOpenFile(), $buffer);
            echo $data;
            flush();
            ob_flush();
        }

        ini_set('max_execution_time', $old_execution_time);
        $this->closeFile();
    }

    /**
     * return file size in byte
     * @return int
     */
    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    /**
     * set file size in byte
     * @param int $fileSize
     * @return streamFile
     */
    public function setFileSize(int $fileSize): streamFile
    {
        $this->fileSize = $fileSize;
        return $this;
    }

}