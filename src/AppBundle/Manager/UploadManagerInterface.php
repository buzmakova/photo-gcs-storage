<?php
namespace AppBundle\Manager;

use AppBundle\Entity\Photo;
use Symfony\Component\HttpFoundation\Response;

interface UploadManagerInterface
{
    /**
     * Upload photo
     *
     * @param Photo $photo
     * @return bool
     */
    public function upload(Photo $photo);

    /**
     * Download photo
     *
     * @param Photo $photo
     * @return Response
     */
    public function download(Photo $photo);

    /**
     * Delete photo
     *
     * @param Photo $photo
     * @return void
     */
    public function delete(Photo $photo);
}