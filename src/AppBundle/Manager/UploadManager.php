<?php
namespace AppBundle\Manager;

use AppBundle\Entity\Photo;
use AppBundle\Repository\PhotoRepository;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Storage\StorageObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UploadManager implements UploadManagerInterface
{
    /** @var  PhotoRepository */
    private $photoRepository;
    /** @var  StorageClient */
    private $gcStorageClient;
    /** @var  string */
    private $defaultBucketName;

    public function __construct(
        PhotoRepository $photoRepository,
        StorageClient $gcStorageClient,
        string $defaultBucketName
    ){
        $this->photoRepository = $photoRepository;
        $this->gcStorageClient = $gcStorageClient;
        $this->defaultBucketName = $defaultBucketName;
    }

    /**
     * Upload photo in DB and GCS
     *
     * @param Photo $photo
     * @return bool
     */
    public function upload(Photo $photo)
    {
        $success = false;
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $photo->getFile();
        // Generate a unique name for the file before saving it
        $fileName = md5(uniqid()).'.'.$uploadedFile->guessExtension();
        if (!empty($uploadedFile)) {
            $options = [
                'name' => $fileName
            ];
            $options = array_merge($options, ['predefinedAcl' => 'bucketOwnerFullControl', 'resumable' => true]);
            $content = file_get_contents($uploadedFile->getPathname());

            /** @var Bucket $bucket */
            $bucket = $this->gcStorageClient->bucket($this->defaultBucketName);
            /** @var StorageObject $uploadedObject */
            $uploadedObject = $bucket->upload($content, $options);

            if ($uploadedObject->exists()) {
                $photo->setName($photo->getName().'.'.$uploadedFile->guessExtension());
                $photo->setFile($fileName);

                $this->photoRepository->save($photo);
                $success = true;
            }
        }

        return $success;
    }

    /**
     * Download photo from GCS
     *
     * @param Photo $photo
     * @return Response
     * @throws NotFoundHttpException
     */
    public function download(Photo $photo)
    {
        $fileName = $photo->getFile();
        /** @var Bucket $bucket */
        $bucket = $this->gcStorageClient->bucket($this->defaultBucketName);
        /** @var StorageObject $object */
        $object = $bucket->object($fileName);
        // check if file exists
        if (!$object->exists()) {
            throw new NotFoundHttpException('File not found');
        }

        $alias = $photo->getName();

        $response = new Response($object->downloadAsStream());

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $alias,
            iconv('UTF-8', 'ASCII//TRANSLIT', $alias)
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * Delete photo from DB and GCS
     *
     * @param Photo $photo
     * @return void
     * @throws NotFoundHttpException
     */
    public function delete(Photo $photo)
    {
        $fileName = $photo->getFile();
        /** @var Bucket $bucket */
        $bucket = $this->gcStorageClient->bucket($this->defaultBucketName);
        /** @var StorageObject $object */
        $object = $bucket->object($fileName);
        // check if file exists
        if (!$object->exists()) {
            throw new NotFoundHttpException('File not found');
        }

        $object->delete();
        if (!$object->exists()) {
            $this->photoRepository->delete($photo);
        }
    }

    /**
     * Return list of buckets in GCS
     */
    public function getBuckets()
    {
        $buckets = $this->gcStorageClient->buckets();
        foreach ($buckets as $bucket) {
            echo $bucket->name() . PHP_EOL;
        }
    }
}