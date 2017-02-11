<?php
namespace AppBundle\Controller;

use AppBundle\Entity\Folder;
use AppBundle\Entity\Photo;
use AppBundle\Form\FolderCreateType;
use AppBundle\Form\PhotoUploadType;
use AppBundle\Manager\UploadManager;
use AppBundle\Manager\UploadManagerInterface;
use AppBundle\Repository\FolderRepository;
use AppBundle\Repository\PhotoRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route(service="app.controller.index")
 */
class IndexController
{
    /** @var UploadManager */
    private $uploadManager;
    /** @var FolderRepository */
    private $folderRepository;
    /** @var PhotoRepository */
    private $photoRepository;
    /** @var EngineInterface  */
    private $templating;
    /** @var FormFactory */
    private $formFactory;

    function __construct(
        UploadManagerInterface $uploadManager,
        FolderRepository $folderRepository,
        PhotoRepository $photoRepository,
        EngineInterface $templating,
        FormFactory $formFactory
    ){
        $this->uploadManager    = $uploadManager;
        $this->folderRepository = $folderRepository;
        $this->photoRepository  = $photoRepository;
        $this->templating       = $templating;
        $this->formFactory      = $formFactory;

    }

    /**
     * @Route("/", defaults={"id" = null}, name="homepage")
     * @Route("/{id}", requirements={"id": "\d+"}, name="folder")
     *
     * @param $id
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function indexAction($id, Request $request)
    {
        $currentFolder = null;
        /** @var Folder[] $folderList */
        $folderList = $this->folderRepository->findBy(['parent' => $id]);
        /** @var Photo[] $photoList */
        $photoList  = $this->photoRepository->findBy(['folder' => $id]);
        /** @var Photo $photo */
        $photo = new Photo();
        /** @var Folder $newFolder */
        $newFolder = new Folder();

        if (!empty($id)) {
            /** @var Folder $currentFolder */
            $currentFolder = $this->folderRepository->find($id);
            $breadcrumbs = $this->makeBreadcrumbs($currentFolder);
            $photo->setFolder($currentFolder);
            $newFolder->setParent($currentFolder);
        }

        /** @var FormInterface $uploadPhotoForm */
        $uploadPhotoForm = $this->formFactory->create(PhotoUploadType::class, $photo);
        $uploadPhotoForm->handleRequest($request);

        /** @var FormInterface $createFolderForm */
        $createFolderForm = $this->formFactory->create(FolderCreateType::class, $newFolder);
        $createFolderForm->handleRequest($request);

        if ($uploadPhotoForm->isSubmitted() && $uploadPhotoForm->isValid()) {
            $this->uploadManager->upload($photo);
            return $this->redirectToReferer($request);
        }

        if ($createFolderForm->isSubmitted() && $createFolderForm->isValid()) {
            $this->folderRepository->save($newFolder);
            return $this->redirectToReferer($request);
        }

        $response = $this->templating->renderResponse('default/index.html.twig', [
            'folderList' => $folderList,
            'photoList' => $photoList,
            'uploadPhotoForm' => $uploadPhotoForm->createView(),
            'createFolderForm' => $createFolderForm->createView(),
            'breadcrumbs' => $this->makeBreadcrumbs($currentFolder),
        ]);

        return $response;
    }

    /**
     * Download photo
     * @Route("/download/{id}", requirements={"id": "\d+"}, name="photo.download")
     *
     * @param $id
     * @return Response
     */
    public function downloadPhotoAction($id)
    {
        /** @var Photo $photo */
        $photo = $this->photoRepository->find($id);
        $response =  $this->uploadManager->download($photo);
        return $response;
    }

    /**
     * Delete photo
     * @Route("/delete/{id}", requirements={"id": "\d+"}, name="photo.delete")
     *
     * @param $id
     * @param Request $request
     * @return RedirectResponse
     */
    public function deletePhotoAction($id, Request $request)
    {
        /** @var Photo $photo */
        $photo = $this->photoRepository->find($id);
        $this->uploadManager->delete($photo);
        return $this->redirectToReferer($request);
    }

    /**
     * Redirect to last viewed page
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function redirectToReferer(Request $request) {
        return new RedirectResponse(
            $request
                ->headers
                ->get('referer')
        );
    }

    /**
     * Return breadcrumbs for current folder
     *
     * @param Folder|null $folder
     * @return array
     */
    private function makeBreadcrumbs(Folder $folder = null)
    {
        $breadcrumbs = [];

        while (!empty($folder)) {
            $item['name'] = $folder->getName();
            $item['link'] = '/'.$folder->getId();
            array_unshift($breadcrumbs,$item);
            $folder = $folder->getParent();
        }

        $homeItem = ['name' => 'Home', 'link' => '/'];
        array_unshift($breadcrumbs,$homeItem);

        return $breadcrumbs;
    }
}
