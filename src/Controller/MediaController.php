<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use Knp\Component\Pager\PaginatorInterface;
use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Event\MediaFileSaveEvent;
use Siganushka\MediaBundle\Form\Type\MediaType;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MediaController extends AbstractFOSRestController
{
    protected MediaRepository $mediaRepository;

    public function __construct(MediaRepository $mediaRepository)
    {
        $this->mediaRepository = $mediaRepository;
    }

    public function getCollection(Request $request, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $this->mediaRepository->createQueryBuilderWithSorted();

        $page = $request->query->getInt('page', 1);
        $size = $request->query->getInt('size', 10);

        $pagination = $paginator->paginate($queryBuilder, $page, $size);

        return $this->viewResponse($pagination);
    }

    public function postCollection(Request $request, EventDispatcherInterface $eventDispatcher, EntityManagerInterface $entityManager)
    {
        $formData = array_replace_recursive($request->request->all(), $request->files->all());

        $form = $this->createForm(MediaType::class);
        $form->submit($formData);

        if (!$form->isValid()) {
            return $this->viewResponse($form);
        }

        /** @var ChannelInterface */
        $channel = $form['channel']->getData();
        /** @var UploadedFile */
        $file = $form['file']->getData();

        $path = $file->getRealPath();
        if ($path && false === $hash = hash_file('MD5', $path)) {
            throw new HttpException(400, 'Unable to hash file.');
        }

        $media = $this->mediaRepository->findOneBy(['hash' => $hash]);
        if ($media instanceof Media) {
            return $this->viewResponse($media);
        }

        $event = new MediaFileSaveEvent($channel, $file, $hash);
        $eventDispatcher->dispatch($event);

        $media = $event->getMedia();
        if (!$media instanceof Media) {
            throw new HttpException(500, 'Unable to save file.');
        }

        $entityManager->persist($media);
        $entityManager->flush();

        return $this->viewResponse($media);
    }

    public function getItem(int $id): Response
    {
        $entity = $this->mediaRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException(sprintf('Resource with value "%d" not found.', $id));
        }

        return $this->viewResponse($entity);
    }

    public function deleteItem(EntityManagerInterface $entityManager, int $id): Response
    {
        $entity = $this->mediaRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException(sprintf('Resource with value "%d" not found.', $id));
        }

        $entityManager->remove($entity);
        $entityManager->flush();

        // 204 no content response
        return $this->viewResponse(null, Response::HTTP_NO_CONTENT);
    }

    protected function viewResponse($data = null, int $statusCode = null, array $headers = []): Response
    {
        $context = new Context();
        $context->setGroups([
            'trait_resource', 'trait_sortable', 'trait_enable', 'trait_timestampable',
            'media',
        ]);

        $view = View::create($data, $statusCode, $headers);
        $view->setContext($context);

        return $this->getViewHandler()->handle($view);
    }
}
