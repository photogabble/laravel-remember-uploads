<?php

namespace Photogabble\LaravelRememberUploads;

class RememberedFile extends \Symfony\Component\HttpFoundation\File\UploadedFile
{

    /**
     * Returns whether the file was uploaded successfully.
     *
     * @return bool True if the file has been uploaded with HTTP and no error occurred
     */
    public function isValid()
    {
        $isOk = UPLOAD_ERR_OK === $this->error;

        return $isOk; // @todo check that pathname is within the expected storage directory
        //return $this->test ? $isOk : $isOk && is_uploaded_file($this->getPathname());
    }

}
