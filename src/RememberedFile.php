<?php

namespace Photogabble\LaravelRememberUploads;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class RememberedFile
{
    /**
     * Current pathname for the remembered file.
     *
     * @var string
     */
    public $tmpPathname;

    /**
     * Original name of uploaded file.
     *
     * @var string
     */
    public $originalName;

    /**
     * Remembered file mime type.
     *
     * @var string
     */
    public $mimeType;

    /**
     * Remembered file size in bytes.
     *
     * @var int
     */
    public $size;

    /**
     * RememberedFile constructor.
     *
     * @param string $tmpPathname
     * @param UploadedFile $file
     */
    public function __construct($tmpPathname, UploadedFile $file)
    {
        $this->tmpPathname = $tmpPathname;
        $this->originalName = $file->getClientOriginalName();
        $this->mimeType = $file->getMimeType();
        $this->size = $file->getSize();
    }

    /**
     * @return UploadedFile
     */
    public function toUploadedFile()
    {
        return new UploadedFile($this->tmpPathname, $this->originalName, $this->mimeType, $this->size);
    }
}