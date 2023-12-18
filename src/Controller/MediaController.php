<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Knp\Component\Pager\PaginatorInterface;
use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\Event\MediaFileSaveEvent;
use Siganushka\MediaBundle\Form\MediaType;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MediaController extends AbstractFOSRestController
{
    protected MediaRepository $mediaRepository;

    public function __construct(MediaRepository $mediaRepository)
    {
        $this->mediaRepository = $mediaRepository;
    }

    /**
     * @Route("/media", methods={"GET"})
     */
    public function getCollection(Request $request, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $this->mediaRepository->createQueryBuilder('m');

        $page = $request->query->getInt('page', 1);
        $size = $request->query->getInt('size', 10);

        $pagination = $paginator->paginate($queryBuilder, $page, $size);

        return $this->viewResponse($pagination);
    }

    /**
     * @Route("/media", methods={"POST"})
     */
    public function postCollection(Request $request, EventDispatcherInterface $eventDispatcher, EntityManagerInterface $entityManager)
    {
        $formData = array_replace_recursive(
            $request->request->all(),
            $request->files->all(),
        );

        $form = $this->createForm(MediaType::class);
        $form->submit($formData);

        if (!$form->isValid()) {
            return $this->viewResponse($form);
        }

        /** @var ChannelInterface */
        $channel = $form['channel']->getData();
        /** @var UploadedFile */
        $file = $form['file']->getData();

        $path = $file->getPathname();
        if ($path && false === $hash = hash_file('MD5', $path)) {
            throw new BadRequestHttpException('Unable to hash file.');
        }

        $media = $this->mediaRepository->findOneBy(['hash' => $hash]);
        if ($media) {
            return $this->viewResponse($media);
        }

        $event = new MediaFileSaveEvent($channel, $file, $hash);
        $eventDispatcher->dispatch($event);

        if (null === $media = $event->getMedia()) {
            throw new BadRequestHttpException('Unable to save file.');
        }

        $entityManager->persist($media);
        $entityManager->flush();

        return $this->viewResponse($media);
    }

    /**
     * @Route("/media/{id<\d+>}", methods={"GET"})
     */
    public function getItem(int $id): Response
    {
        $entity = $this->mediaRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException(sprintf('Resource #%d not found.', $id));
        }

        return $this->viewResponse($entity);
    }

    /**
     * @Route("/media/{id<\d+>}", methods={"DELETE"})
     */
    public function deleteItem(EntityManagerInterface $entityManager, int $id): Response
    {
        $entity = $this->mediaRepository->find($id);
        if (!$entity) {
            throw $this->createNotFoundException(sprintf('Resource #%d not found.', $id));
        }

        $entityManager->remove($entity);
        $entityManager->flush();

        // 204 no content response
        return $this->viewResponse(null, Response::HTTP_NO_CONTENT);
    }

    protected function viewResponse($data = null, int $statusCode = null, array $headers = []): Response
    {
        $attributes = [
            'id', 'hash', 'channel', 'name', 'size', 'width', 'height', 'image', 'reference',
            'updatedAt', 'createdAt',
        ];

        $context = new Context();
        $context->setAttribute('attributes', $attributes);

        $view = $this->view($data, $statusCode, $headers);
        $view->setContext($context);

        return $this->handleView($view);
    }
}
