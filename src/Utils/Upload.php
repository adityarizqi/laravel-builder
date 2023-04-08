<?php

namespace App\Utils;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

trait Upload
{
    /**
     * Upload Public File
     *
     * @param object $file File to be upload
     * @param string $folder Target folder where files are stored
     * @return string File directory is after upload
     */
    public function uploadFile($file, $folder)
    {
        $fileName = time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path($folder), $fileName);
        return $folder . '/' . $fileName;
    }

    /**
     * Upload Private File
     *
     * @param object $file File to be upload
     * @param string $folder Target folder where files are stored
     * @return string File directory is after upload
     */
    public function uploadPrivateFile($file, $folder)
    {
        $file_name = md5(time() . rand()) . '.' . $file->getClientOriginalExtension();
        $file->storeAs('uploads/' . $folder . '/', $file_name);
        return 'uploads/' . $folder . '/' . $file_name;
    }

    /**
     * Destroy Public File
     *
     * @param string $path File directory to be destroy
     * @return boolean
     */
    public function destroyFile($path)
    {
        return File::delete(public_path($path));
    }

    /**
     * Destroy Private File
     *
     * @param string $path File directory to be destroy
     * @return boolean
     */
    public function destroyPrivateImage($path)
    {
        return Storage::delete($path);
    }
}
